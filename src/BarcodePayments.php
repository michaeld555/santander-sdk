<?php

declare(strict_types=1);

namespace Santander\SDK;

use Santander\SDK\Support\WorkspacePaymentEndpoint;

class BarcodePayments extends WorkspacePaymentEndpoint
{
    protected function resourceName(): string
    {
        return 'barcode_payments';
    }

    public function createPayment(array $data): array
    {
        return $this->createPaymentRequest($data);
    }

    public function confirmPayment(string $paymentId, array $data): array
    {
        return $this->confirmPaymentRequest($paymentId, $data);
    }

    public function getPayment(string $paymentId): array
    {
        return $this->getPaymentRequest($paymentId);
    }

    public function listPayments(array $params = []): array
    {
        return $this->listPaymentsRequest($params);
    }
}
