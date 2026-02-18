<?php

declare(strict_types=1);

namespace Santander\SDK\Types;

final class OrderStatus
{
    public const READY_TO_PAY = CreateOrderStatus::READY_TO_PAY;
    public const PENDING_VALIDATION = CreateOrderStatus::PENDING_VALIDATION;
    public const PAYED = ConfirmOrderStatus::PAYED;
    public const PENDING_CONFIRMATION = ConfirmOrderStatus::PENDING_CONFIRMATION;
    public const REJECTED = ConfirmOrderStatus::REJECTED;
}