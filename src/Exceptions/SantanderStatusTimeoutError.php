<?php

declare(strict_types=1);

namespace Santander\SDK\Exceptions;

class SantanderStatusTimeoutError extends SantanderError
{
    private string $step;

    public function __construct(string $message, string $step, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->step = $step;
    }

    public function getStep(): string
    {
        return $this->step;
    }

    public function __toString(): string
    {
        return 'Status update timeout after several attempts: ' . parent::__toString();
    }
}