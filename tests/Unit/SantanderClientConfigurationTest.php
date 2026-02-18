<?php

declare(strict_types=1);

namespace Santander\SDK\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Santander\SDK\Client\SantanderClientConfiguration;

class SantanderClientConfigurationTest extends TestCase
{
    public function testFromArrayReadsBankTaxId(): void
    {
        $config = SantanderClientConfiguration::fromArray([
            'client_id' => 'client',
            'client_secret' => 'secret',
            'base_url' => 'https://example.com',
            'bank_tax_id' => '12345678000195',
        ]);

        $this->assertSame('12345678000195', $config->bankTaxId);
    }

    public function testFromArrayUsesDefaultBankTaxIdWhenMissing(): void
    {
        $config = SantanderClientConfiguration::fromArray([]);

        $this->assertSame('90400888000142', $config->bankTaxId);
    }
}
