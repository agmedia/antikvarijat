<?php

namespace App\Support\Session;

use RuntimeException;

/**
 * A fixed-size set of locks shared by file-session writers and maintenance.
 *
 * Locking a stable hash bucket before opening a session inode closes the race
 * where a writer could otherwise wait on an inode that maintenance unlinks.
 */
final class SessionFileLockPool
{
    public const DEFAULT_BUCKETS = 256;

    /** @var string */
    private $directory;

    /** @var int */
    private $buckets;

    /** @var bool */
    private $prepared = false;

    public function __construct(string $sessionDirectory, ?string $lockRoot = null, int $buckets = self::DEFAULT_BUCKETS)
    {
        if ($buckets < 1 || $buckets > 4096) {
            throw new RuntimeException('The file-session lock bucket count is invalid.');
        }

        $sessionDirectory = realpath($sessionDirectory) ?: $this->normalizePath($sessionDirectory);
        $lockRoot = $lockRoot ?: storage_path('framework');
        $this->directory = rtrim($lockRoot, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . 'session-write-locks-' . sha1($sessionDirectory);
        $this->buckets = $buckets;
    }

    public function prepare(): void
    {
        if ($this->prepared) {
            return;
        }

        if (! is_dir($this->directory)
            && (file_exists($this->directory)
                || (! @mkdir($this->directory, 0775, true) && ! is_dir($this->directory)))) {
            throw new RuntimeException('Unable to create the file-session lock directory.');
        }

        $readyPath = $this->directory . DIRECTORY_SEPARATOR . '.ready';
        if (is_file($readyPath)) {
            $this->prepared = true;

            return;
        }

        // The first initializer creates every bucket before publishing .ready.
        // Pre-creation is important when the scheduler and PHP worker run as
        // different users: both only need read access to lock existing files.
        $initializationPath = $this->directory . DIRECTORY_SEPARATOR . '.initialize.lock';
        $initializationHandle = $this->openInitializationLock($initializationPath);
        if (! @flock($initializationHandle, LOCK_EX)) {
            @fclose($initializationHandle);
            throw new RuntimeException('Unable to initialize file-session lock buckets.');
        }

        try {
            if (! is_file($readyPath)) {
                for ($bucket = 0; $bucket < $this->buckets; $bucket++) {
                    $path = $this->bucketPath($bucket);
                    $handle = @fopen($path, 'c');
                    if ($handle === false && is_file($path)) {
                        $handle = @fopen($path, 'r');
                    }
                    if ($handle === false) {
                        throw new RuntimeException('Unable to create file-session lock buckets.');
                    }
                    @fclose($handle);
                }

                $ready = @fopen($readyPath, 'x');
                if ($ready === false && ! is_file($readyPath)) {
                    throw new RuntimeException('Unable to publish file-session lock buckets.');
                }
                if (is_resource($ready)) {
                    @fclose($ready);
                }
            }

            $this->prepared = true;
        } finally {
            @flock($initializationHandle, LOCK_UN);
            @fclose($initializationHandle);
        }
    }

    public function acquireShared(string $sessionId): SessionFileLock
    {
        return $this->acquire($sessionId, LOCK_SH, false);
    }

    public function acquireExclusive(string $sessionId): SessionFileLock
    {
        return $this->acquire($sessionId, LOCK_EX, false);
    }

    public function tryAcquireExclusive(string $sessionId): ?SessionFileLock
    {
        return $this->acquire($sessionId, LOCK_EX, true);
    }

    public function directory(): string
    {
        return $this->directory;
    }

    private function acquire(string $sessionId, int $operation, bool $nonBlocking): ?SessionFileLock
    {
        $this->prepare();

        $bucket = $this->bucket($sessionId);
        $path = $this->bucketPath($bucket);
        // Existing bucket files only need to be readable for flock on Unix.
        // The fallback lets the web and scheduler users share locks even when
        // their umasks differ, while first use still creates the bounded file.
        $handle = @fopen($path, 'c');
        if ($handle === false && is_file($path)) {
            $handle = @fopen($path, 'r');
        }
        if ($handle === false) {
            throw new RuntimeException('Unable to open a file-session lock bucket.');
        }

        $wouldBlock = 0;
        $lockOperation = $operation | ($nonBlocking ? LOCK_NB : 0);
        if (! @flock($handle, $lockOperation, $wouldBlock)) {
            @fclose($handle);

            if ($nonBlocking && $wouldBlock === 1) {
                return null;
            }

            throw new RuntimeException('Unable to acquire a file-session lock bucket.');
        }

        return new SessionFileLock($handle);
    }

    private function bucket(string $sessionId): int
    {
        $hash = unpack('Nbucket', substr(hash('sha256', $sessionId, true), 0, 4));

        return (int) ($hash['bucket'] % $this->buckets);
    }

    /**
     * @return resource
     */
    private function openInitializationLock(string $path)
    {
        // Another user can observe the directory in the tiny interval between
        // mkdir and creation of this file. Briefly wait for the initializer
        // rather than incorrectly treating that normal race as a disk failure.
        for ($attempt = 0; $attempt < 50; $attempt++) {
            $handle = @fopen($path, 'c');
            if ($handle === false && is_file($path)) {
                $handle = @fopen($path, 'r');
            }
            if ($handle !== false) {
                return $handle;
            }

            usleep(10000);
        }

        throw new RuntimeException('Unable to open the file-session lock initializer.');
    }

    private function bucketPath(int $bucket): string
    {
        return $this->directory . DIRECTORY_SEPARATOR . sprintf('%03d.lock', $bucket);
    }

    private function normalizePath(string $path): string
    {
        if ($path === '') {
            return '';
        }

        if ($path[0] !== DIRECTORY_SEPARATOR) {
            $path = getcwd() . DIRECTORY_SEPARATOR . $path;
        }

        return rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
    }
}
