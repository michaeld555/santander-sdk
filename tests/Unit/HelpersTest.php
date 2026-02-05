<?php

declare(strict_types=1);

namespace Santander\SDK\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Santander\SDK\Support\Helpers;

class HelpersTest extends TestCase
{
    public function testTruncateValue(): void
    {
        $this->assertSame('10.99', Helpers::truncateValue('10.999'));
        $this->assertSame('50.00', Helpers::truncateValue(50));
    }

    public function testGetPixKeyType(): void
    {
        $this->assertSame('CPF', Helpers::getPixKeyType('111.444.777-35'));
        $this->assertSame('CNPJ', Helpers::getPixKeyType('12.345.678/0001-95'));
        $this->assertSame('EMAIL', Helpers::getPixKeyType('email@example.com'));
        $this->assertSame('EVP', Helpers::getPixKeyType('a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6'));
        $this->assertSame('CELULAR', Helpers::getPixKeyType('+5511912345678'));
    }

    public function testDocumentType(): void
    {
        $this->assertSame('CPF', Helpers::documentType('11144477735'));
        $this->assertSame('CNPJ', Helpers::documentType('12345678000195'));
    }
}