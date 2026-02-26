<?php

declare(strict_types=1);

namespace Santander\SDK;

use Santander\SDK\Client\SantanderApiClient;

class WorkspaceManagement
{
    public const WORKSPACES_ENDPOINT = '/management_payments_partners/v1/workspaces';
    public const WORKSPACE_TYPE_PAYMENTS = 'PAYMENTS';
    public const WORKSPACE_TYPE_DIGITAL_CORBAN = 'DIGITAL_CORBAN';
    public const WORKSPACE_TYPES = [
        self::WORKSPACE_TYPE_PAYMENTS,
        self::WORKSPACE_TYPE_DIGITAL_CORBAN,
    ];
    public const WORKSPACE_PRODUCT_FLAGS = [
        'pixPaymentsActive',
        'bankSlipPaymentsActive',
        'bankSlipAvailableActive',
        'barCodePaymentsActive',
        'taxesByFieldPaymentsActive',
        'vehicleTaxesPaymentsActive',
        'bankSlipAvailableWebhookActive',
        'bankTransferPaymentsActive',
        'smartTransfersActive',
    ];
    public const DIGITAL_CORBAN_DISABLED_FLAGS = [
        'pixPaymentsActive',
        'bankTransferPaymentsActive',
        'smartTransfersActive',
    ];

    private SantanderApiClient $client;

    public function __construct(SantanderApiClient $client)
    {
        $this->client = $client;
    }

    public function listWorkspaces(array $params = []): array
    {
        return $this->client->get(self::WORKSPACES_ENDPOINT, $params === [] ? null : $params);
    }

    public function getWorkspace(string $workspaceId): array
    {
        if ($workspaceId === '') {
            throw new \InvalidArgumentException('workspace_id not provided');
        }

        return $this->client->get(self::WORKSPACES_ENDPOINT . '/' . $workspaceId);
    }

    public function createWorkspace(array $data): array
    {
        return $this->client->post(self::WORKSPACES_ENDPOINT, $data);
    }

    public function createWorkspaceValidated(array $data): array
    {
        return $this->client->post(self::WORKSPACES_ENDPOINT, $this->prepareWorkspaceCreationPayload($data));
    }

    public function updateWorkspace(string $workspaceId, array $data): array
    {
        if ($workspaceId === '') {
            throw new \InvalidArgumentException('workspace_id not provided');
        }

        return $this->client->patch(self::WORKSPACES_ENDPOINT . '/' . $workspaceId, $data);
    }

    public function deleteWorkspace(string $workspaceId): array
    {
        if ($workspaceId === '') {
            throw new \InvalidArgumentException('workspace_id not provided');
        }

        return $this->client->delete(self::WORKSPACES_ENDPOINT . '/' . $workspaceId);
    }

    private function prepareWorkspaceCreationPayload(array $data): array
    {
        $payload = $data;

        $payload['type'] = $this->normalizeWorkspaceType($data['type'] ?? null);
        $payload['mainDebitAccount'] = $this->normalizeDebitAccount($data['mainDebitAccount'] ?? null, 'mainDebitAccount');

        if (array_key_exists('description', $data) && $data['description'] !== null) {
            $payload['description'] = $this->validateDescription($data['description']);
        } else {
            unset($payload['description']);
        }

        if (array_key_exists('tags', $data) && $data['tags'] !== null) {
            $payload['tags'] = $this->validateTags($data['tags']);
        } else {
            unset($payload['tags']);
        }

        $additionalDebitAccounts = $data['additionalDebitAccounts'] ?? $data['addicionalDebitAccounts'] ?? null;
        if ($additionalDebitAccounts !== null) {
            $payload['additionalDebitAccounts'] = $this->normalizeAdditionalDebitAccounts($additionalDebitAccounts);
        } else {
            unset($payload['additionalDebitAccounts']);
        }
        unset($payload['addicionalDebitAccounts']);

        $payload['webhookURL'] = $this->normalizeWebhookUrl($data);

        foreach (self::WORKSPACE_PRODUCT_FLAGS as $flag) {
            $payload[$flag] = (bool) ($data[$flag] ?? false);
        }

        if ($payload['bankSlipAvailableActive'] && ! $payload['bankSlipPaymentsActive']) {
            throw new \InvalidArgumentException('bankSlipPaymentsActive must be true when bankSlipAvailableActive is true');
        }

        if ($payload['bankSlipAvailableWebhookActive'] && $payload['webhookURL'] === null) {
            throw new \InvalidArgumentException('webhookURL must be informed when bankSlipAvailableWebhookActive is true');
        }

        if ($payload['type'] === self::WORKSPACE_TYPE_DIGITAL_CORBAN) {
            foreach (self::DIGITAL_CORBAN_DISABLED_FLAGS as $flag) {
                $payload[$flag] = false;
            }
        }

        return $payload;
    }

    private function normalizeWorkspaceType(mixed $type): string
    {
        if (! is_string($type) || trim($type) === '') {
            throw new \InvalidArgumentException('type not provided');
        }

        $normalizedType = strtoupper(trim($type));
        if (! in_array($normalizedType, self::WORKSPACE_TYPES, true)) {
            throw new \InvalidArgumentException('Invalid workspace type. Accepted values: PAYMENTS, DIGITAL_CORBAN');
        }

        return $normalizedType;
    }

    private function normalizeDebitAccount(mixed $account, string $fieldName): array
    {
        if (! is_array($account)) {
            throw new \InvalidArgumentException($fieldName . ' must be an object with branch and number');
        }

        $branch = $this->normalizeAccountDigits($account['branch'] ?? null, $fieldName . '.branch', 4, false);
        $number = $this->normalizeAccountDigits($account['number'] ?? null, $fieldName . '.number', 12, true);

        return [
            'branch' => $branch,
            'number' => $number,
        ];
    }

    private function normalizeAdditionalDebitAccounts(mixed $accounts): array
    {
        if (! is_array($accounts)) {
            throw new \InvalidArgumentException('additionalDebitAccounts must be an array');
        }

        $normalizedAccounts = [];
        foreach ($accounts as $index => $account) {
            $normalizedAccounts[] = $this->normalizeDebitAccount($account, 'additionalDebitAccounts.' . $index);
        }

        return $normalizedAccounts;
    }

    private function normalizeAccountDigits(mixed $value, string $fieldName, int $maxLength, bool $padSingleDigit): string
    {
        if (! is_string($value) && ! is_int($value)) {
            throw new \InvalidArgumentException($fieldName . ' must be numeric');
        }

        $digits = trim((string) $value);
        if ($digits === '' || ! ctype_digit($digits)) {
            throw new \InvalidArgumentException($fieldName . ' must contain only digits');
        }

        if (strlen($digits) > $maxLength) {
            throw new \InvalidArgumentException($fieldName . ' must have at most ' . $maxLength . ' digits');
        }

        if ($padSingleDigit && strlen($digits) === 1) {
            return str_pad($digits, $maxLength, '0', STR_PAD_LEFT);
        }

        return $digits;
    }

    private function validateDescription(mixed $description): string
    {
        if (! is_string($description)) {
            throw new \InvalidArgumentException('description must be a string');
        }

        $normalizedDescription = trim($description);
        if (strlen($normalizedDescription) > 30) {
            throw new \InvalidArgumentException('description must have at most 30 characters');
        }

        return $normalizedDescription;
    }

    private function validateTags(mixed $tags): array
    {
        if (! is_array($tags)) {
            throw new \InvalidArgumentException('tags must be an array');
        }

        if (count($tags) > 5) {
            throw new \InvalidArgumentException('tags must have at most 5 items');
        }

        $normalizedTags = [];
        foreach ($tags as $index => $tag) {
            if (! is_string($tag)) {
                throw new \InvalidArgumentException('tags.' . $index . ' must be a string');
            }

            $normalizedTag = trim($tag);
            if (strlen($normalizedTag) > 20) {
                throw new \InvalidArgumentException('tags.' . $index . ' must have at most 20 characters');
            }

            $normalizedTags[] = $normalizedTag;
        }

        return $normalizedTags;
    }

    private function normalizeWebhookUrl(array $data): ?string
    {
        if (! array_key_exists('webhookURL', $data) || $data['webhookURL'] === null || $data['webhookURL'] === '') {
            return null;
        }

        if (! is_string($data['webhookURL'])) {
            throw new \InvalidArgumentException('webhookURL must be a string');
        }

        $webhookUrl = trim($data['webhookURL']);
        if (! str_starts_with($webhookUrl, 'https://')) {
            throw new \InvalidArgumentException('webhookURL must start with https://');
        }

        if (strlen($webhookUrl) > 350) {
            throw new \InvalidArgumentException('webhookURL must have at most 350 characters');
        }

        return $webhookUrl;
    }
}
