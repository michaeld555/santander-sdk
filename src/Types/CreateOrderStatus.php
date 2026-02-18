<?php

declare(strict_types=1);

namespace Santander\SDK\Types;

final class CreateOrderStatus
{
    public const READY_TO_PAY = 'READY_TO_PAY';
    public const PENDING_VALIDATION = 'PENDING_VALIDATION';
    public const REJECTED = 'REJECTED';
}