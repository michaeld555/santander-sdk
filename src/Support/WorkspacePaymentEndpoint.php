<?php

declare(strict_types=1);

namespace Santander\SDK\Support;

use Santander\SDK\Client\SantanderApiClient;

abstract class WorkspacePaymentEndpoint
{
    public const WORKSPACE_BASE_ENDPOINT = '/management_payments_partners/v1/workspaces/:workspaceid';

    protected SantanderApiClient $client;

    public function __construct(SantanderApiClient $client)
    {
        $this->client = $client;
    }

    abstract protected function resourceName(): string;

    protected function resourceEndpoint(): string
    {
        return self::WORKSPACE_BASE_ENDPOINT . '/' . $this->resourceName();
    }

    protected function createPaymentRequest(array $data): array
    {
        return $this->client->post($this->resourceEndpoint(), $data);
    }

    protected function confirmPaymentRequest(string $paymentId, array $data): array
    {
        return $this->client->patch($this->resourceEndpoint() . '/' . $this->requireIdentifier($paymentId, 'payment_id'), $data);
    }

    protected function getPaymentRequest(string $paymentId): array
    {
        return $this->client->get($this->resourceEndpoint() . '/' . $this->requireIdentifier($paymentId, 'payment_id'));
    }

    protected function listPaymentsRequest(array $params = []): array
    {
        return $this->client->get($this->resourceEndpoint(), $params === [] ? null : $params);
    }

    protected function requireIdentifier(string $value, string $name): string
    {
        if ($value === '') {
            throw new \InvalidArgumentException($name . ' not provided');
        }

        return $value;
    }
}
