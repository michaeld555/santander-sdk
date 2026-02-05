<?php

declare(strict_types=1);

namespace Santander\SDK\Types;

final class ReceiptStatus
{
    public const REQUESTED = 'REQUESTED';
    public const AVAILABLE = 'AVAILABLE';
    public const EXPUNGED = 'EXPUNGED';
    public const ERROR = 'ERROR';
}