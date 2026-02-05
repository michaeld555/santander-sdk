<?php

declare(strict_types=1);

namespace Santander\SDK;

use Santander\SDK\Client\SantanderApiClient;

class SantanderSdk
{
    private SantanderApiClient $client;
    private Pix $pix;
    private PaymentReceipts $receipts;

    public function __construct(SantanderApiClient $client, Pix $pix, PaymentReceipts $receipts)
    {
        $this->client = $client;
        $this->pix = $pix;
        $this->receipts = $receipts;
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
}