<?php

declare(strict_types=1);

namespace Santander\SDK;

use Santander\SDK\Support\WorkspacePaymentEndpoint;

class BankSlipPayments extends WorkspacePaymentEndpoint
{
    public const AVAILABLE_BANK_SLIPS_ENDPOINT = self::WORKSPACE_BASE_ENDPOINT . '/available_bank_slips';

    protected function resourceName(): string
    {
        return 'bank_slip_payments';
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

    public function listAvailableBankSlips(array $params = []): array
    {
        return $this->client->get(self::AVAILABLE_BANK_SLIPS_ENDPOINT, $params === [] ? null : $params);
    }
}
