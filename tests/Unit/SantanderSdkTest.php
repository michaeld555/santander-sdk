<?php

declare(strict_types=1);

namespace Santander\SDK\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Santander\SDK\BankAccounts;
use Santander\SDK\BankSlipPayments;
use Santander\SDK\BarcodePayments;
use Santander\SDK\Client\SantanderApiClient;
use Santander\SDK\PaymentReceipts;
use Santander\SDK\Pix;
use Santander\SDK\PixAutomatic;
use Santander\SDK\SantanderSdk;
use Santander\SDK\TaxesByFieldsPayments;
use Santander\SDK\VehicleTaxesPayments;
use Santander\SDK\WorkspaceManagement;

class SantanderSdkTest extends TestCase
{
    public function testNewModulesAreAvailableOnSdk(): void
    {
        $client = $this->getMockBuilder(SantanderApiClient::class)
            ->disableOriginalConstructor()
            ->getMock();

        $sdk = new SantanderSdk(
            $client,
            new Pix($client),
            new PaymentReceipts($client)
        );

        $this->assertInstanceOf(WorkspaceManagement::class, $sdk->workspaces());
        $this->assertInstanceOf(BankAccounts::class, $sdk->accounts());
        $this->assertInstanceOf(BankSlipPayments::class, $sdk->bankSlips());
        $this->assertInstanceOf(BarcodePayments::class, $sdk->barcodes());
        $this->assertInstanceOf(VehicleTaxesPayments::class, $sdk->vehicleTaxes());
        $this->assertInstanceOf(TaxesByFieldsPayments::class, $sdk->taxesByFields());
        $this->assertInstanceOf(PixAutomatic::class, $sdk->pixAutomatic());
    }
}
