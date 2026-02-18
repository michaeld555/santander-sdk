<?php

declare(strict_types=1);

namespace Santander\SDK;

use Santander\SDK\Client\SantanderApiClient;

class PixAutomatic
{
    public const API_V1_ENDPOINT = '/api/v1';

    private SantanderApiClient $client;

    public function __construct(SantanderApiClient $client)
    {
        $this->client = $client;
    }

    public function createLocation(array $data): array
    {
        return $this->client->post($this->endpoint('/locrec'), $data);
    }

    public function listLocations(array $params = []): array
    {
        return $this->client->get($this->endpoint('/locrec'), $params === [] ? null : $params);
    }

    public function getLocation(string $locationId): array
    {
        return $this->client->get($this->endpoint('/locrec/' . $this->requireIdentifier($locationId, 'location_id')));
    }

    public function unbindLocation(string $locationId, string $recurrenceId): array
    {
        return $this->client->delete(
            $this->endpoint(
                '/locrec/' . $this->requireIdentifier($locationId, 'location_id') . '/'
                . $this->requireIdentifier($recurrenceId, 'recurrence_id')
            )
        );
    }

    public function createRecurrence(array $data): array
    {
        return $this->client->post($this->endpoint('/rec'), $data);
    }

    public function listRecurrences(array $params = []): array
    {
        return $this->client->get($this->endpoint('/rec'), $params === [] ? null : $params);
    }

    public function getRecurrence(string $recurrenceId): array
    {
        return $this->client->get($this->endpoint('/rec/' . $this->requireIdentifier($recurrenceId, 'recurrence_id')));
    }

    public function updateRecurrence(string $recurrenceId, array $data): array
    {
        return $this->client->patch(
            $this->endpoint('/rec/' . $this->requireIdentifier($recurrenceId, 'recurrence_id')),
            $data
        );
    }

    public function cancelRecurrence(string $recurrenceId, array $data = []): array
    {
        return $this->updateRecurrence($recurrenceId, $data);
    }

    public function createConfirmationRequest(array $data): array
    {
        return $this->client->post($this->endpoint('/solicrec'), $data);
    }

    public function getConfirmationRequest(string $confirmationRequestId): array
    {
        return $this->client->get(
            $this->endpoint('/solicrec/' . $this->requireIdentifier($confirmationRequestId, 'confirmation_request_id'))
        );
    }

    public function reviewConfirmationRequest(string $confirmationRequestId, array $data): array
    {
        return $this->client->patch(
            $this->endpoint('/solicrec/' . $this->requireIdentifier($confirmationRequestId, 'confirmation_request_id')),
            $data
        );
    }

    public function createRecurringCharge(string $txid, array $data): array
    {
        return $this->client->put($this->endpoint('/cobr/' . $this->requireIdentifier($txid, 'txid')), $data);
    }

    public function reviewRecurringCharge(string $txid, array $data): array
    {
        return $this->client->patch($this->endpoint('/cobr/' . $this->requireIdentifier($txid, 'txid')), $data);
    }

    public function getRecurringCharge(string $txid): array
    {
        return $this->client->get($this->endpoint('/cobr/' . $this->requireIdentifier($txid, 'txid')));
    }

    public function listRecurringCharges(array $params = []): array
    {
        return $this->client->get($this->endpoint('/cobr'), $params === [] ? null : $params);
    }

    public function requestRecurringChargeRetry(string $txid, string $retryDate, array $data = []): array
    {
        return $this->client->post(
            $this->endpoint(
                '/cobr/' . $this->requireIdentifier($txid, 'txid') . '/retentativa/'
                . $this->requireIdentifier($retryDate, 'retry_date')
            ),
            $data === [] ? null : $data
        );
    }

    public function configureRecurrenceWebhook(array $data): array
    {
        return $this->client->put($this->endpoint('/webhookrec'), $data);
    }

    public function getRecurrenceWebhook(array $params = []): array
    {
        return $this->client->get($this->endpoint('/webhookrec'), $params === [] ? null : $params);
    }

    public function deleteRecurrenceWebhook(): array
    {
        return $this->client->delete($this->endpoint('/webhookrec'));
    }

    public function configureRecurringChargeWebhook(array $data): array
    {
        return $this->client->put($this->endpoint('/webhookcobr'), $data);
    }

    public function getRecurringChargeWebhook(array $params = []): array
    {
        return $this->client->get($this->endpoint('/webhookcobr'), $params === [] ? null : $params);
    }

    public function deleteRecurringChargeWebhook(): array
    {
        return $this->client->delete($this->endpoint('/webhookcobr'));
    }

    private function endpoint(string $path): string
    {
        return self::API_V1_ENDPOINT . $path;
    }

    private function requireIdentifier(string $value, string $name): string
    {
        if ($value === '') {
            throw new \InvalidArgumentException($name . ' not provided');
        }

        return $value;
    }
}
