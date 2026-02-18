<?php

declare(strict_types=1);

namespace Santander\SDK;

use Santander\SDK\Client\SantanderApiClient;

class BankAccounts
{
    public const BANK_ACCOUNT_INFORMATION_BASE_ENDPOINT = '/bank_account_information/v1/banks';
    public const DEFAULT_BANK_TAX_ID = '90400888000142';

    private SantanderApiClient $client;

    public function __construct(SantanderApiClient $client)
    {
        $this->client = $client;
    }

    public function listAccounts(array $params = [], ?string $bankTaxId = null): array
    {
        return $this->client->get(
            $this->bankEndpoint($bankTaxId) . '/accounts',
            $params === [] ? null : $params
        );
    }

    public function getBalance(string $accountId, ?string $bankTaxId = null): array
    {
        if ($accountId === '') {
            throw new \InvalidArgumentException('account_id not provided');
        }

        return $this->client->get($this->bankEndpoint($bankTaxId) . '/balances/' . $accountId);
    }

    public function getStatement(string $accountId, array $params, ?string $bankTaxId = null): array
    {
        if ($accountId === '') {
            throw new \InvalidArgumentException('account_id not provided');
        }

        return $this->client->get(
            $this->bankEndpoint($bankTaxId) . '/statements/' . $accountId,
            $params === [] ? null : $params
        );
    }

    private function bankEndpoint(?string $bankTaxId): string
    {
        return self::BANK_ACCOUNT_INFORMATION_BASE_ENDPOINT . '/' . $this->resolveBankTaxId($bankTaxId);
    }

    private function resolveBankTaxId(?string $bankTaxId): string
    {
        $resolved = $bankTaxId;
        if ($resolved === null || $resolved === '') {
            $resolved = $this->client->getConfig()->bankTaxId;
        }
        if ($resolved === '') {
            $resolved = self::DEFAULT_BANK_TAX_ID;
        }

        return $resolved;
    }
}
