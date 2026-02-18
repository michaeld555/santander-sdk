<?php

declare(strict_types=1);

namespace Santander\SDK;

use Santander\SDK\Client\SantanderApiClient;

class SantanderSdk
{
    private SantanderApiClient $client;
    private Pix $pix;
    private PaymentReceipts $receipts;
    private WorkspaceManagement $workspaces;
    private BankAccounts $accounts;
    private BankSlipPayments $bankSlips;
    private BarcodePayments $barcodes;
    private VehicleTaxesPayments $vehicleTaxes;
    private TaxesByFieldsPayments $taxesByFields;
    private PixAutomatic $pixAutomatic;

    public function __construct(
        SantanderApiClient $client,
        Pix $pix,
        PaymentReceipts $receipts,
        ?WorkspaceManagement $workspaces = null,
        ?BankAccounts $accounts = null,
        ?BankSlipPayments $bankSlips = null,
        ?BarcodePayments $barcodes = null,
        ?VehicleTaxesPayments $vehicleTaxes = null,
        ?TaxesByFieldsPayments $taxesByFields = null,
        ?PixAutomatic $pixAutomatic = null
    ) {
        $this->client = $client;
        $this->pix = $pix;
        $this->receipts = $receipts;
        $this->workspaces = $workspaces ?? new WorkspaceManagement($client);
        $this->accounts = $accounts ?? new BankAccounts($client);
        $this->bankSlips = $bankSlips ?? new BankSlipPayments($client);
        $this->barcodes = $barcodes ?? new BarcodePayments($client);
        $this->vehicleTaxes = $vehicleTaxes ?? new VehicleTaxesPayments($client);
        $this->taxesByFields = $taxesByFields ?? new TaxesByFieldsPayments($client);
        $this->pixAutomatic = $pixAutomatic ?? new PixAutomatic($client);
    }

    public function client(): SantanderApiClient
    {
        return $this->client;
    }

    public function pix(): Pix
    {
        return $this->pix;
    }

    public function receipts(): PaymentReceipts
    {
        return $this->receipts;
    }

    public function workspaces(): WorkspaceManagement
    {
        return $this->workspaces;
    }

    public function accounts(): BankAccounts
    {
        return $this->accounts;
    }

    public function bankSlips(): BankSlipPayments
    {
        return $this->bankSlips;
    }

    public function barcodes(): BarcodePayments
    {
        return $this->barcodes;
    }

    public function vehicleTaxes(): VehicleTaxesPayments
    {
        return $this->vehicleTaxes;
    }

    public function taxesByFields(): TaxesByFieldsPayments
    {
        return $this->taxesByFields;
    }

    public function pixAutomatic(): PixAutomatic
    {
        return $this->pixAutomatic;
    }
}
