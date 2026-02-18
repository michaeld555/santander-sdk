<?php

declare(strict_types=1);

namespace Santander\SDK;

use Santander\SDK\Support\WorkspacePaymentEndpoint;

class VehicleTaxesPayments extends WorkspacePaymentEndpoint
{
    public const AVAILABLE_VEHICLE_TAXES_ENDPOINT = self::WORKSPACE_BASE_ENDPOINT . '/available_vehicle_taxes';

    protected function resourceName(): string
    {
        return 'vehicle_taxes_payments';
    }

    public function listAvailableVehicleTaxes(array $params = []): array
    {
        return $this->client->get(self::AVAILABLE_VEHICLE_TAXES_ENDPOINT, $params === [] ? null : $params);
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
