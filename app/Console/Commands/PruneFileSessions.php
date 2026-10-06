<?php

namespace App\Console\Commands;

use App\Support\Session\SessionFileLockPool;
use FilesystemIterator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class PruneFileSessions extends Command
{
    /** @var \App\Support\Session\SessionFileLockPool */
    private $sessionLocks;

    protected $signature = 'session:prune-files
                            {--batch-size=100 : Entries inspected between pauses}
                            {--pause-milliseconds=100 : Pause between batches to limit disk pressure}
                            {--grace-minutes=10 : Extra inactivity beyond the configured session lifetime}
                            {--max-files=0 : Stop after this many directory entries; zero completes one pass}
                            {--max-seconds=0 : Stop after this many seconds; zero completes one pass}
                            {--nice=10 : Lower CPU priority by this amount; zero leaves it unchanged}
                            {--dry-run : Inspect expired files without removing them}';

    protected $description = 'Prune expired file sessions outside HTTP requests with one rate-limited streaming pass';

    public function handle(): int
    {
        if (! in_array(config('session.driver'), ['file', 'native'], true)) {
            $this->info('File-session cleanup skipped: a different session driver is configured.');

            return 0;
        }

        $directory = realpath((string) config('session.files'));
        if ($directory === false || ! is_dir($directory) || $this->isBroadDirectory($directory)) {
            $this->error('File-session cleanup requires an existing dedicated session directory.');

            return 1;
        }

        try {
            $this->sessionLocks = new SessionFileLockPool($directory, storage_path('framework'));
            $this->sessionLocks->prepare();
        } catch (Throwable $exception) {
            $this->error('Unable to initialize safe file-session cleanup locks.');

            return 1;
        }

        // Keep the process lock outside the directory being scanned. flock also
        // prevents overlap when the command is started manually or a scheduler
        // mutex expires during a particularly large pass.
        $lock = @fopen(storage_path('framework/session-prune-' . sha1($directory) . '.lock'), 'c');
        if ($lock === false) {
            $this->error('Unable to acquire the file-session cleanup process lock.');

            return 1;
        }

        if (! flock($lock, LOCK_EX | LOCK_NB)) {
            fclose($lock);
            $this->info('File-session cleanup skipped: another pass is running.');

            return 0;
        }

        try {
            return $this->prune($directory);
        } catch (Throwable $exception) {
            $this->error('File-session cleanup failed (' . get_class($exception) . ').');

            return 1;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    protected function prune(string $directory): int
    {
        $started = microtime(true);
        $batchSize = max(1, min(10000, (int) $this->option('batch-size')));
        $pauseMilliseconds = max(0, min(5000, (int) $this->option('pause-milliseconds')));
        $maxFiles = max(0, (int) $this->option('max-files'));
        $maxSeconds = max(0, (int) $this->option('max-seconds'));
        $nice = max(0, min(19, (int) $this->option('nice')));
        $lifetimeSeconds = max(1, (int) config('session.lifetime', 120)) * 60;
        $graceSeconds = max(0, min(1440, (int) $this->option('grace-minutes'))) * 60;
        // A fixed cutoff is conservative during a long pass: newly expired
        // files wait for the next pass rather than becoming eligible mid-scan.
        $cutoff = time() - $lifetimeSeconds - $graceSeconds;
        $dryRun = (bool) $this->option('dry-run');
        $summary = [
            'event' => 'file_session_cleanup',
            'entries' => 0,
            'expired' => 0,
            'removed' => 0,
            'active' => 0,
            'busy' => 0,
            'changed' => 0,
            'ignored' => 0,
            'errors' => 0,
            'complete' => true,
            'dry_run' => $dryRun,
            'lifetime_seconds' => $lifetimeSeconds,
            'grace_seconds' => $graceSeconds,
        ];
        $nextProgressAt = $started + 60;

        if ($nice > 0 && function_exists('proc_nice')) {
            @proc_nice($nice);
        }

        // CURRENT_AS_PATHNAME avoids eager file-info/stat calls; no recursive
        // Finder scan or sorted array of the complete directory is created.
        $entries = new FilesystemIterator(
            $directory,
            FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_PATHNAME
        );

        foreach ($entries as $path) {
            if (($maxFiles > 0 && $summary['entries'] >= $maxFiles)
                || ($maxSeconds > 0 && microtime(true) - $started >= $maxSeconds)) {
                $summary['complete'] = false;
                break;
            }

            $summary['entries']++;

            if (preg_match('/\A[a-zA-Z0-9]{40}\z/D', basename($path)) !== 1) {
                $summary['ignored']++;
            } else {
                $result = $this->inspectSession($path, $cutoff, $dryRun);
                if ($result === 'removed' || $result === 'expired') {
                    $summary['expired']++;
                }
                if ($result !== 'expired') {
                    $summary[$result]++;
                }
            }

            if ($pauseMilliseconds > 0 && $summary['entries'] % $batchSize === 0) {
                usleep($pauseMilliseconds * 1000);
            }

            if (microtime(true) >= $nextProgressAt) {
                $progress = $summary;
                $progress['event'] = 'file_session_cleanup_progress';
                $progress['complete'] = false;
                $progress['elapsed_seconds'] = round(microtime(true) - $started, 3);
                Log::channel('db-capacity')->warning('file_session_cleanup_progress', $progress);
                $nextProgressAt = microtime(true) + 60;
            }
        }

        $summary['elapsed_seconds'] = round(microtime(true) - $started, 3);
        // Only aggregate counts are logged; names and session contents are
        // authentication material and must never appear in cleanup diagnostics.
        Log::channel('db-capacity')->warning('file_session_cleanup', $summary);
        $this->line(json_encode($summary, JSON_UNESCAPED_SLASHES));

        return $summary['errors'] > 0 ? 1 : 0;
    }

    protected function inspectSession(string $path, int $cutoff, bool $dryRun): string
    {
        // Most files are still active. Avoid touching the shared lock pool for
        // those; the fixed cutoff means an active file cannot become eligible
        // later in this pass. Every check is repeated after locking expired
        // candidates, so a refresh or path replacement remains protected.
        clearstatcache(true, $path);
        $initial = @lstat($path);
        if ($initial === false) {
            return file_exists($path) ? 'errors' : 'changed';
        }
        if (($initial['mode'] & 0170000) !== 0100000) {
            return 'ignored';
        }
        if ($initial['mtime'] >= $cutoff) {
            return 'active';
        }

        // The corresponding handler takes this same stable bucket before it
        // opens the session inode. Once held, no writer can become an invisible
        // waiter on an inode that is about to be unlinked.
        $sessionLock = $this->sessionLocks->tryAcquireExclusive(basename($path));
        if ($sessionLock === null) {
            return 'busy';
        }

        try {
            return $this->inspectLockedSession($path, $cutoff, $dryRun);
        } finally {
            $sessionLock->release();
        }
    }

    private function inspectLockedSession(string $path, int $cutoff, bool $dryRun): string
    {
        clearstatcache(true, $path);
        $initial = @lstat($path);
        if ($initial === false) {
            return file_exists($path) ? 'errors' : 'changed';
        }
        if (($initial['mode'] & 0170000) !== 0100000) {
            return 'ignored';
        }
        if ($initial['mtime'] >= $cutoff) {
            return 'active';
        }

        $handle = $this->openSessionFile($path);
        if ($handle === false) {
            return file_exists($path) ? 'errors' : 'changed';
        }

        try {
            // Laravel's file-session reads and writes use flock as well. Skip
            // busy files rather than delaying a live request behind cleanup.
            if (! flock($handle, LOCK_EX | LOCK_NB)) {
                return 'busy';
            }

            $opened = fstat($handle);
            clearstatcache(true, $path);
            $current = @lstat($path);
            if ($opened === false || $current === false) {
                return 'changed';
            }
            if (($current['mode'] & 0170000) !== 0100000
                || $current['dev'] !== $opened['dev']
                || $current['ino'] !== $opened['ino']
                || $initial['dev'] !== $opened['dev']
                || $initial['ino'] !== $opened['ino']
                || $opened['mtime'] >= $cutoff
                || $current['mtime'] >= $cutoff) {
                return 'changed';
            }

            if ($dryRun) {
                return 'expired';
            }

            return @unlink($path) ? 'removed' : 'errors';
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    protected function openSessionFile(string $path)
    {
        // Opening read-only does not truncate or recreate a session that a
        // concurrent process removed; an exclusive advisory lock still works.
        return @fopen($path, 'r');
    }

    private function isBroadDirectory(string $directory): bool
    {
        return in_array($directory, array_filter([
            DIRECTORY_SEPARATOR,
            realpath(base_path()),
            realpath(storage_path()),
            realpath(storage_path('framework')),
            realpath(public_path()),
        ]), true);
    }
}
