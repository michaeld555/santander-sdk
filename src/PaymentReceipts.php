<?php

declare(strict_types=1);

namespace Santander\SDK;

use Generator;
use Santander\SDK\Client\SantanderApiClient;
use Santander\SDK\Exceptions\SantanderRequestError;
use Santander\SDK\Types\ReceiptStatus;

class PaymentReceipts
{
    public const RECEIPTS_ENDPOINT = '/consult_payment_receipts/v1/payment_receipts';
    public const ALREADY_REQUESTED_RECEIPT = '006';

    private SantanderApiClient $client;

    public function __construct(SantanderApiClient $client)
    {
        $this->client = $client;
    }

    public function paymentList(array $params): array
    {
        $payments = [];
        foreach ($this->paymentListIterByPages($params) as $response) {
            $payments = array_merge($payments, $response['paymentsReceipts'] ?? []);
        }
        return $payments;
    }

    public function paymentListIterByPages(array $params): Generator
    {
        $response = $this->paymentListRequest($params);
        yield $response;

        while (isset($response['links']['_next']['href'])) {
            $nextLink = $response['links']['_next']['href'];
            $nextOffset = $this->extractOffsetFromUrl($nextLink);
            if ($nextOffset === null) {
                break;
            }

            $params['_offset'] = $nextOffset;
            $response = $this->paymentListRequest($params);
            yield $response;
        }
    }

    public function createReceipt(string $paymentId, bool $handleAlreadyCreated = true): array
    {
        if ($paymentId === '') {
            throw new \InvalidArgumentException('payment_id is required to create a receipt request.');
        }

        $endpoint = self::RECEIPTS_ENDPOINT . '/' . $paymentId . '/file_requests';
        try {
            $response = $this->client->post($endpoint, null);
            return $this->receiptResult($response, $paymentId);
        } catch (SantanderRequestError $e) {
            if ($e->getStatusCode() === 400 && $handleAlreadyCreated) {
                $content = $e->getContent() ?? [];
                $errors = $content['errors'] ?? [];
                foreach ($errors as $error) {
                    if (($error['code'] ?? null) === self::ALREADY_REQUESTED_RECEIPT) {
                        return $this->handleAlreadyCreated($paymentId);
                    }
                }
            }
            throw $e;
        }
    }

    public function getReceipt(string $paymentId, string $receiptRequestId): array
    {
        if ($paymentId === '' || $receiptRequestId === '') {
            throw new \InvalidArgumentException('payment_id and receipt_request are required');
        }

        $endpoint = self::RECEIPTS_ENDPOINT . '/' . $paymentId . '/file_requests/' . $receiptRequestId;
        $response = $this->client->get($endpoint);
        return $this->receiptResult($response, $paymentId);
    }

    public function receiptCreationHistory(string $paymentId): array
    {
        $endpoint = self::RECEIPTS_ENDPOINT . '/' . $paymentId . '/file_requests';
        return $this->client->get($endpoint);
    }

    private function paymentListRequest(array $params): array
    {
        if (! isset($params['_limit'])) {
            $params['_limit'] = '1000';
        }

        return $this->client->get(self::RECEIPTS_ENDPOINT, $params);
    }

    private function handleAlreadyCreated(string $paymentId): array
    {
        $this->client->getConfig()->logger?->info('Receipt already requested. Trying to get the receipt request ID.');

        $receiptHistory = $this->receiptCreationHistory($paymentId);
        $requests = $receiptHistory['paymentReceiptsFileRequests'] ?? [];
        if (! $requests) {
            $this->client->getConfig()->logger?->error('No previous receipts in history');
            throw new \RuntimeException('No previous receipts in history');
        }

        $lastFromHistory = $requests[array_key_last($requests)];
        $requestId = $lastFromHistory['request']['requestId'] ?? null;
        if (! $requestId) {
            throw new \RuntimeException('Receipt request id not found in history');
        }

        $result = $this->getReceipt($paymentId, $requestId);
        $status = $result['status'] ?? null;
        if ($status !== ReceiptStatus::EXPUNGED && $status !== ReceiptStatus::ERROR) {
            return $result;
        }

        $this->client->getConfig()->logger?->info('The last receipt is in an error state, creating another one.');
        usleep(500000);
        $endpoint = self::RECEIPTS_ENDPOINT . '/' . $paymentId . '/file_requests';
        $response = $this->client->post($endpoint, null);
        return $this->receiptResult($response, $paymentId);
    }

    private function receiptResult(array $response, string $paymentId): array
    {
        return [
            'payment_id' => $paymentId,
            'receipt_request_id' => $response['request']['requestId'] ?? null,
            'status' => $response['file']['statusInfo']['statusCode'] ?? null,
            'location' => $response['file']['fileRepository']['location'] ?? null,
            'data' => $response,
        ];
    }

    private function extractOffsetFromUrl(string $url): ?string
    {
        $query = parse_url($url, PHP_URL_QUERY);
        if (! $query) {
            return null;
        }
        parse_str($query, $params);
        return $params['_offset'] ?? null;
    }
}