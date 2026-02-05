<?php

declare(strict_types=1);

namespace Santander\SDK\Exceptions;

class SantanderClientError extends SantanderError
{
    public function __toString(): string
    {
        return 'Santander client error: ' . parent::__toString();
    }
}