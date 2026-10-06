<?php

namespace App\Session;

use App\Support\Session\SessionFileLockPool;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Session\FileSessionHandler;

class LockedFileSessionHandler extends FileSessionHandler
{
    /** @var \App\Support\Session\SessionFileLockPool */
    private $sessionLocks;

    public function __construct(
        Filesystem $files,
        string $path,
        int $minutes,
        SessionFileLockPool $sessionLocks
    ) {
        parent::__construct($files, $path, $minutes);
        $this->sessionLocks = $sessionLocks;
    }

    /**
     * Read while maintenance is prevented from removing this session inode.
     *
     * @return string|false
     */
    #[\ReturnTypeWillChange]
    public function read($sessionId)
    {
        $lock = $this->sessionLocks->acquireShared((string) $sessionId);

        try {
            return parent::read($sessionId);
        } finally {
            $lock->release();
        }
    }

    /**
     * Acquire the stable bucket before file_put_contents opens the inode.
     *
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function write($sessionId, $data)
    {
        $lock = $this->sessionLocks->acquireExclusive((string) $sessionId);

        try {
            return parent::write($sessionId, $data);
        } finally {
            $lock->release();
        }
    }

    /**
     * @return bool
     */
    #[\ReturnTypeWillChange]
    public function destroy($sessionId)
    {
        $lock = $this->sessionLocks->acquireExclusive((string) $sessionId);

        try {
            return parent::destroy($sessionId);
        } finally {
            $lock->release();
        }
    }
}
