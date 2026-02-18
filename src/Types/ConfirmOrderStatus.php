<?php

declare(strict_types=1);

namespace Santander\SDK\Types;

final class ConfirmOrderStatus
{
    public const PAYED = 'PAYED';
    public const PENDING_CONFIRMATION = 'PENDING_CONFIRMATION';
    public const REJECTED = 'REJECTED';
}