<?php

namespace Tests\Feature;

use App\Console\Commands\PruneFileSessions;
use App\Session\LockedFileSessionHandler;
use App\Support\Session\SessionFileLockPool;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class PruneFileSessionsTest extends TestCase
{
    private $directory;
    private $sessions;
    private $lockPath;
    private $bucketLockDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir() . '/biblos-session-prune-' . bin2hex(random_bytes(8));
        $this->sessions = $this->directory . '/sessions';
        mkdir($this->directory, 0700);
        mkdir($this->sessions, 0700);
        $this->lockPath = storage_path('framework/session-prune-' . sha1(realpath($this->sessions)) . '.lock');
        $this->bucketLockDirectory = (new SessionFileLockPool(
            $this->sessions,
            storage_path('framework')
        ))->directory();
        config([
            'session.driver' => 'file',
            'session.files' => $this->sessions,
            'session.lifetime' => 120,
            'logging.channels.db-capacity' => [
                'driver' => 'single',
                'path' => $this->directory . '/cleanup.log',
                'level' => 'warning',
            ],
        ]);
        app('session')->forgetDrivers();
        app()->forgetInstance('session.store');
    }

    protected function tearDown(): void
    {
        if ($this->sessions && is_dir($this->sessions)) {
            foreach (new \FilesystemIterator($this->sessions) as $entry) {
                if ($entry->isDir() && ! $entry->isLink()) {
                    rmdir($entry->getPathname());
                } else {
                    unlink($entry->getPathname());
                }
            }
            rmdir($this->sessions);
        }
        if ($this->directory && is_dir($this->directory)) {
            foreach (new \FilesystemIterator($this->directory) as $entry) {
                unlink($entry->getPathname());
            }
            rmdir($this->directory);
        }
        if ($this->lockPath && is_file($this->lockPath)) {
            unlink($this->lockPath);
        }
        if ($this->bucketLockDirectory && is_dir($this->bucketLockDirectory)) {
            (new Filesystem())->deleteDirectory($this->bucketLockDirectory);
        }

        parent::tearDown();
    }

    public function test_cleanup_removes_only_expired_session_files_and_logs_aggregate_counts(): void
    {
        $expired = $this->sessionFile(1, time() - 8000);
        $active = $this->sessionFile(2, time() - 30);
        file_put_contents($this->sessions . '/.gitignore', '*');
        mkdir($this->sessions . '/' . str_repeat('d', 40));

        $summary = $this->prune();

        $this->assertFileDoesNotExist($expired);
        $this->assertFileExists($active);
        $this->assertSame(1, $summary['removed']);
        $this->assertSame(1, $summary['active']);
        $this->assertSame(2, $summary['ignored']);
        $this->assertTrue($summary['complete']);
        $log = file_get_contents($this->directory . '/cleanup.log');
        $this->assertStringContainsString('file_session_cleanup', $log);
        $this->assertStringNotContainsString(basename($expired), $log);
        $this->assertStringNotContainsString('secret-session-content', $log);
        $this->assertSame('warning', config('logging.channels.db-capacity.level'));
    }

    public function test_registered_file_driver_preserves_cart_data_and_session_lifetime(): void
    {
        $sessionId = str_repeat('c', 40);
        $store = app('session.store');

        $this->assertInstanceOf(LockedFileSessionHandler::class, $store->getHandler());
        $store->setId($sessionId);
        $store->start();
        $store->put('cart.items', [['product_id' => 123, 'quantity' => 2]]);
        $store->save();

        app('session')->forgetDrivers();
        app()->forgetInstance('session.store');
        $restored = app('session.store');
        $restored->setId($sessionId);
        $restored->start();

        $this->assertSame([['product_id' => 123, 'quantity' => 2]], $restored->get('cart.items'));
        $restored->save();

        touch($this->sessions . '/' . $sessionId, time() - 8000);
        app('session')->forgetDrivers();
        app()->forgetInstance('session.store');
        $expired = app('session.store');
        $expired->setId($sessionId);
        $expired->start();

        $this->assertNull($expired->get('cart.items'));
    }

    public function test_lock_pool_has_a_fixed_small_number_of_durable_bucket_files(): void
    {
        $pool = new SessionFileLockPool($this->sessions, storage_path('framework'));
        $pool->prepare();
        $initialFiles = (new Filesystem())->files($pool->directory(), true);

        foreach (range(1, 600) as $index) {
            $lock = $pool->acquireExclusive(hash('sha256', (string) $index));
            $lock->release();
        }

        $this->assertCount(SessionFileLockPool::DEFAULT_BUCKETS + 2, $initialFiles);
        $this->assertCount(count($initialFiles), (new Filesystem())->files($pool->directory(), true));
    }

    public function test_handler_refuses_to_write_when_the_lock_pool_is_unavailable(): void
    {
        $unusableRoot = $this->directory . '/not-a-directory';
        file_put_contents($unusableRoot, 'file');
        $handler = new LockedFileSessionHandler(
            new Filesystem(),
            $this->sessions,
            120,
            new SessionFileLockPool($this->sessions, $unusableRoot)
        );
        $sessionId = str_repeat('f', 40);

        $this->expectException(\RuntimeException::class);
        try {
            $handler->write($sessionId, 'must-not-be-silently-lost');
        } finally {
            $this->assertFileDoesNotExist($this->sessions . '/' . $sessionId);
        }
    }

    public function test_grace_period_preserves_recently_expired_sessions(): void
    {
        $path = $this->sessionFile(1, time() - 7500);

        $summary = $this->prune();

        $this->assertFileExists($path);
        $this->assertSame(1, $summary['active']);
        $this->assertSame(0, $summary['removed']);
    }

    public function test_cleanup_rechecks_a_session_refreshed_after_the_initial_stat(): void
    {
        $path = $this->sessionFile(1, time() - 8000);
        $this->app->bind(PruneFileSessions::class, RefreshSessionBeforePrune::class);

        $summary = $this->prune();

        $this->assertFileExists($path);
        $this->assertSame(1, $summary['changed']);
        $this->assertSame(0, $summary['removed']);
    }

    public function test_locked_expired_session_is_skipped_without_waiting(): void
    {
        $path = $this->sessionFile(1, time() - 8000);
        $handle = fopen($path, 'r');
        flock($handle, LOCK_EX);
        try {
            $summary = $this->prune();
            $this->assertFileExists($path);
            $this->assertSame(1, $summary['busy']);
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }

    public function test_cleanup_and_handler_share_the_same_nonblocking_bucket_lock(): void
    {
        $path = $this->sessionFile(1, time() - 8000);
        $locks = new SessionFileLockPool($this->sessions, storage_path('framework'));
        $writerLock = $locks->acquireExclusive(basename($path));

        try {
            $summary = $this->prune();
        } finally {
            $writerLock->release();
        }

        $this->assertFileExists($path);
        $this->assertSame(1, $summary['busy']);
        $this->assertSame(0, $summary['removed']);
    }

    public function test_active_session_does_not_contend_on_its_busy_bucket(): void
    {
        $path = $this->sessionFile(1, time() - 30);
        $locks = new SessionFileLockPool($this->sessions, storage_path('framework'));
        $writerLock = $locks->acquireExclusive(basename($path));

        try {
            $summary = $this->prune();
        } finally {
            $writerLock->release();
        }

        $this->assertFileExists($path);
        $this->assertSame(1, $summary['active']);
        $this->assertSame(0, $summary['busy']);
    }

    public function test_multiprocess_writer_waits_before_open_and_recreates_a_pruned_session(): void
    {
        $path = $this->sessionFile(1, time() - 8000);
        $sessionId = basename($path);
        $started = $this->directory . '/writer-started';
        $booted = $this->directory . '/writer-booted';
        $locks = new SessionFileLockPool($this->sessions, storage_path('framework'));
        $cleanupLock = $locks->acquireExclusive($sessionId);
        $code = <<<'PHP'
error_reporting(E_ERROR);
file_put_contents($argv[6], 'booted');
require $argv[1].'/vendor/autoload.php';
$pool = new App\Support\Session\SessionFileLockPool($argv[2], $argv[3]);
$handler = new App\Session\LockedFileSessionHandler(
    new Illuminate\Filesystem\Filesystem(), $argv[2], 120, $pool
);
file_put_contents($argv[5], 'started');
$written = $handler->write($argv[4], 'new-cart-state');
fwrite(STDOUT, json_encode(['written' => $written, 'exists' => file_exists($argv[2].'/'.$argv[4])]));
PHP;
        $process = new Process([
            PHP_BINARY,
            '-r',
            $code,
            base_path(),
            $this->sessions,
            storage_path('framework'),
            $sessionId,
            $started,
            $booted,
        ]);
        $process->setTimeout(5);
        $process->start();

        $deadline = microtime(true) + 2;
        while (! is_file($started) && microtime(true) < $deadline) {
            usleep(10000);
        }

        try {
            $this->assertFileExists(
                $started,
                'Writer failed before locking (booted=' . (is_file($booted) ? 'yes' : 'no') . '): '
                    . $process->getErrorOutput() . $process->getOutput()
            );
            usleep(50000);
            $this->assertTrue($process->isRunning(), 'The writer did not wait for the cleanup bucket.');
            $this->assertTrue(unlink($path));
        } finally {
            $cleanupLock->release();
        }

        $process->wait();
        $this->assertTrue($process->isSuccessful(), $process->getErrorOutput());
        $this->assertSame(['written' => true, 'exists' => true], json_decode($process->getOutput(), true));
        $this->assertSame('new-cart-state', file_get_contents($path));
    }

    public function test_symlink_is_never_followed_or_removed(): void
    {
        $target = $this->directory . '/external';
        file_put_contents($target, 'secret-session-content');
        touch($target, time() - 8000);
        $link = $this->sessions . '/' . str_repeat('s', 40);
        symlink($target, $link);

        $summary = $this->prune();

        $this->assertTrue(is_link($link));
        $this->assertFileExists($target);
        $this->assertSame(1, $summary['ignored']);
        $this->assertSame(0, $summary['removed']);
    }

    public function test_manual_file_bound_stops_the_pass_without_touching_remaining_sessions(): void
    {
        for ($index = 1; $index <= 3; $index++) {
            $this->sessionFile($index, time() - 8000);
        }

        $summary = $this->prune(['--max-files' => 1]);

        $this->assertSame(1, $summary['entries']);
        $this->assertSame(1, $summary['removed']);
        $this->assertFalse($summary['complete']);
        $this->assertCount(2, iterator_to_array(new \FilesystemIterator($this->sessions)));
    }

    public function test_manual_time_bound_stops_a_rate_limited_pass(): void
    {
        for ($index = 1; $index <= 3; $index++) {
            $this->sessionFile($index, time() - 8000);
        }

        $summary = $this->prune([
            '--max-seconds' => 1,
            '--batch-size' => 1,
            '--pause-milliseconds' => 600,
        ]);

        $this->assertGreaterThan(0, $summary['entries']);
        $this->assertLessThan(3, $summary['entries']);
        $this->assertFalse($summary['complete']);
    }

    public function test_dry_run_reports_expiry_without_removing_files(): void
    {
        $path = $this->sessionFile(1, time() - 8000);

        $summary = $this->prune(['--dry-run' => true]);

        $this->assertFileExists($path);
        $this->assertSame(1, $summary['expired']);
        $this->assertSame(0, $summary['removed']);
    }

    public function test_process_lock_prevents_a_second_manual_pass(): void
    {
        $path = $this->sessionFile(1, time() - 8000);
        $lock = fopen($this->lockPath, 'c');
        flock($lock, LOCK_EX);
        try {
            $this->assertSame(0, Artisan::call('session:prune-files', ['--nice' => 0]));
            $this->assertStringContainsString('another pass is running', Artisan::output());
            $this->assertFileExists($path);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    public function test_non_file_driver_skips_cleanup(): void
    {
        $path = $this->sessionFile(1, time() - 8000);
        config(['session.driver' => 'redis']);

        $this->assertSame(0, Artisan::call('session:prune-files', ['--nice' => 0]));
        $this->assertFileExists($path);
        $this->assertStringContainsString('different session driver', Artisan::output());
    }

    public function test_cleanup_rejects_a_broad_storage_directory(): void
    {
        config(['session.files' => storage_path('framework')]);

        $this->assertSame(1, Artisan::call('session:prune-files', ['--nice' => 0]));
        $this->assertStringContainsString('dedicated session directory', Artisan::output());
    }

    public function test_scheduled_cleanup_is_background_single_pass_with_overlap_protection(): void
    {
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($event) => strpos((string) $event->command, 'session:prune-files') !== false);

        $this->assertNotNull($event);
        $this->assertSame('*/15 * * * *', $event->expression);
        $this->assertTrue($event->runInBackground);
        $this->assertTrue($event->withoutOverlapping);
        $this->assertSame(120, $event->expiresAt);
        $this->assertStringNotContainsString('--max-seconds', $event->command);
        $this->assertStringNotContainsString('--max-files', $event->command);
    }

    /** @dataProvider sessionLotteryCases */
    public function test_session_config_disables_web_gc_only_for_production_file_sessions(string $environment, string $driver, array $expected): void
    {
        $process = new Process([
            PHP_BINARY,
            '-d',
            'display_errors=0',
            '-r',
            'require "vendor/autoload.php"; require "bootstrap/app.php"; $config = require "config/session.php"; echo json_encode([$config["driver"], $config["lottery"]]);',
        ], base_path(), [
            'APP_ENV' => $environment,
            'SESSION_DRIVER' => $driver,
        ]);
        $process->mustRun();

        $this->assertSame([$driver, $expected], json_decode($process->getOutput(), true));
    }

    public static function sessionLotteryCases(): array
    {
        return [
            'production file' => ['production', 'file', [0, 100]],
            'production native' => ['production', 'native', [0, 100]],
            'production redis' => ['production', 'redis', [2, 100]],
            'production database' => ['production', 'database', [2, 100]],
            'local file' => ['local', 'file', [2, 100]],
        ];
    }

    private function sessionFile(int $index, int $modifiedAt): string
    {
        $path = $this->sessions . '/' . str_pad((string) $index, 40, 'a');
        file_put_contents($path, 'secret-session-content');
        touch($path, $modifiedAt);

        return $path;
    }

    private function prune(array $options = []): array
    {
        $this->assertSame(0, Artisan::call('session:prune-files', array_merge([
            '--pause-milliseconds' => 0,
            '--nice' => 0,
        ], $options)));

        return json_decode(trim(Artisan::output()), true);
    }
}

class RefreshSessionBeforePrune extends PruneFileSessions
{
    protected function openSessionFile(string $path)
    {
        touch($path, time());

        return parent::openSessionFile($path);
    }
}
