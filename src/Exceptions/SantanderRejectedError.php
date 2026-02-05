<?php

declare(strict_types=1);

namespace Santander\SDK\Exceptions;

class SantanderRejectedError extends SantanderError
{
    public function __toString(): string
    {
        return 'Payment rejection: ' . parent::__toString();
    }
}