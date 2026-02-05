<?php

declare(strict_types=1);

namespace Santander\SDK;

use Santander\SDK\Client\SantanderApiClient;
use Santander\SDK\Exceptions\SantanderClientError;
use Santander\SDK\Support\Helpers;

class Pix
{
    public const PIX_ENDPOINT = '/management_payments_partners/v1/workspaces/:workspaceid/pix_payments';

    private SantanderApiClient $client;

    public function __construct(SantanderApiClient $client)
    {
        $this->client = $client;
    }

    public function transferPix(
        string|array $pixKey,
        int|float|string $value,
        string $description,
        array $tags = [],
        string $id = ''
    ): array {
        $transferFlow = new TransferFlow($this->client, self::PIX_ENDPOINT);

        try {
            $numericValue = (float) $value;
            if ($numericValue <= 0) {
                throw new \InvalidArgumentException('Invalid value for PIX transfer: ' . $value);
            }

            $createPixData = $this->generateCreatePixData($pixKey, $value, $description, $tags, $id);
            $createPixResponse = $transferFlow->createPayment($createPixData);

            if (! isset($createPixResponse['id'])) {
                throw new SantanderClientError('Payment ID was not returned on creation');
            }
            if (! array_key_exists('status', $createPixResponse)) {
                throw new SantanderClientError('Payment status was not returned on creation');
            }

            $transferFlow->ensureReadyToPay($createPixResponse);
            $paymentData = [
                'status' => 'AUTHORIZED',
                'paymentValue' => Helpers::truncateValue($value),
            ];
            $confirmResponse = $transferFlow->confirmPayment($paymentData, (string) $createPixResponse['id']);

            return [
                'success' => true,
                'request_id' => $transferFlow->requestId,
                'data' => $confirmResponse,
                'error' => '',
            ];
        } catch (\Throwable $e) {
            $errorMessage = $e->getMessage();
            $this->client->getConfig()->logger?->error($errorMessage);

            return [
                'success' => false,
                'request_id' => $transferFlow->requestId,
                'data' => null,
                'error' => $errorMessage,
            ];
        }
    }

    public function getTransfer(string $pixPaymentId): array
    {
        if ($pixPaymentId === '') {
            throw new \InvalidArgumentException('pix_payment_id not provided');
        }

        return $this->client->get(self::PIX_ENDPOINT . '/' . $pixPaymentId);
    }

    private function generateCreatePixData(
        string|array $pixKey,
        int|float|string $value,
        string $description,
        array $tags = [],
        string $id = ''
    ): array {
        $data = [
            'tags' => $tags,
            'paymentValue' => Helpers::truncateValue($value),
            'remittanceInformation' => $description,
        ];

        if ($id !== '') {
            $data['id'] = $id;
        }

        if (is_string($pixKey)) {
            $pixType = Helpers::getPixKeyType($pixKey);
            $data['dictCode'] = $pixKey;
            $data['dictCodeType'] = $pixType;
            return $data;
        }

        if (is_array($pixKey)) {
            $beneficiary = $pixKey;
            $bankCode = $beneficiary['bankCode'] ?? null;
            $ispb = $beneficiary['ispb'] ?? null;

            if ($bankCode === null && $ispb === null) {
                throw new \InvalidArgumentException("Either 'bankCode' or 'ispb' must be provided");
            }
            if ($bankCode !== null && $ispb !== null) {
                unset($beneficiary['ispb']);
            }

            $data['beneficiary'] = $beneficiary;
            return $data;
        }

        throw new \InvalidArgumentException('PIX key or Beneficiary not provided');
    }
}