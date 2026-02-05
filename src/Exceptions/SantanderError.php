<?php

declare(strict_types=1);

namespace Santander\SDK\Exceptions;

use Exception;

class SantanderError extends Exception
{
    public function __toString(): string
    {
        return 'Santander - ' . $this->message;
    }
}