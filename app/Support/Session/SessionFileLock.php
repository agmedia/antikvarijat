<?php

namespace App\Support\Session;

/**
 * Owns one advisory session-bucket lock and always releases it.
 */
final class SessionFileLock
{
    /** @var resource|null */
    private $handle;

    /**
     * @param  resource  $handle
     */
    public function __construct($handle)
    {
        $this->handle = $handle;
    }

    public function release(): void
    {
        if (! is_resource($this->handle)) {
            return;
        }

        @flock($this->handle, LOCK_UN);
        @fclose($this->handle);
        $this->handle = null;
    }

    public function __destruct()
    {
        $this->release();
    }
}
