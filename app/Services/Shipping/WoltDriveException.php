<?php

namespace App\Services\Shipping;

use RuntimeException;
use Throwable;

class WoltDriveException extends RuntimeException
{
    /** @var string */
    private $woltErrorCode;

    /** @var int|null */
    private $httpStatus;

    public function __construct(
        string $message,
        string $woltErrorCode = 'WOLT_DRIVE_ERROR',
        ?int $httpStatus = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);

        $this->woltErrorCode = $woltErrorCode;
        $this->httpStatus = $httpStatus;
    }

    public function errorCode(): string
    {
        return $this->woltErrorCode;
    }

    public function httpStatus(): ?int
    {
        return $this->httpStatus;
    }
}
