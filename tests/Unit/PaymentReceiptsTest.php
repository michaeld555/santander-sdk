<?php

declare(strict_types=1);

namespace Santander\SDK\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Santander\SDK\Client\SantanderApiClient;
use Santander\SDK\Client\SantanderClientConfiguration;
use Santander\SDK\Exceptions\SantanderRequestError;
use Santander\SDK\PaymentReceipts;
use Santander\SDK\Types\ReceiptStatus;

class PaymentReceiptsTest extends TestCase
{
    public function testGetReceiptReturnsNormalizedStructure(): void
    {
        $paymentId = 'PAY-123';
        $requestId = 'REQ-789';

        $client = $this->createMock(SantanderApiClient::class);
        $client->method('getConfig')->willReturn($this->configWithLogger());
        $client->expects($this->once())
            ->method('get')
            ->with(PaymentReceipts::RECEIPTS_ENDPOINT . '/' . $paymentId . '/file_requests/' . $requestId)
            ->willReturn([
                'request' => ['requestId' => $requestId],
                'file' => [
                    'statusInfo' => ['statusCode' => ReceiptStatus::AVAILABLE],
                    'fileRepository' => ['location' => 'https://example.test/receipt.pdf'],
                ],
            ]);

        $receipts = new PaymentReceipts($client);
        $result = $receipts->getReceipt($paymentId, $requestId);

        $this->assertSame([
            'payment_id' => $paymentId,
            'receipt_request_id' => $requestId,
            'status' => ReceiptStatus::AVAILABLE,
            'location' => 'https://example.test/receipt.pdf',
            'data' => [
                'request' => ['requestId' => $requestId],
                'file' => [
                    'statusInfo' => ['statusCode' => ReceiptStatus::AVAILABLE],
                    'fileRepository' => ['location' => 'https://example.test/receipt.pdf'],
                ],
            ],
        ], $result);
    }

    public function testCreateReceiptHandlesAlreadyRequestedUsingHistoryAndGetReceipt(): void
    {
        $paymentId = 'PAY-123';
        $historyEndpoint = PaymentReceipts::RECEIPTS_ENDPOINT . '/' . $paymentId . '/file_requests';
        $receiptRequestId = 'REQ-456';
        $getReceiptEndpoint = $historyEndpoint . '/' . $receiptRequestId;

        $client = $this->createMock(SantanderApiClient::class);
        $client->method('getConfig')->willReturn($this->configWithLogger());

        $client->expects($this->once())
            ->method('post')
            ->with($historyEndpoint, null)
            ->willThrowException(new SantanderRequestError(
                'already requested',
                400,
                [
                    'errors' => [
                        [
                            'errorCode' => 6,
                            'message' => 'Receipt already requested',
                        ],
                    ],
                ]
            ));

        $client->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(function (string $endpoint) use ($historyEndpoint, $getReceiptEndpoint, $receiptRequestId): array {
                if ($endpoint === $historyEndpoint) {
                    return [
                        'paymentReceiptsFileRequests' => [
                            [
                                'request' => ['requestId' => $receiptRequestId],
                            ],
                        ],
                    ];
                }

                if ($endpoint === $getReceiptEndpoint) {
                    return [
                        'request' => ['requestId' => $receiptRequestId],
                        'file' => [
                            'statusInfo' => ['statusCode' => ReceiptStatus::AVAILABLE],
                            'fileRepository' => ['location' => 'https://example.test/r.pdf'],
                        ],
                    ];
                }

                $this->fail('Unexpected endpoint called: ' . $endpoint);
            });

        $receipts = new PaymentReceipts($client);
        $result = $receipts->createReceipt($paymentId);

        $this->assertSame($paymentId, $result['payment_id']);
        $this->assertSame($receiptRequestId, $result['receipt_request_id']);
        $this->assertSame(ReceiptStatus::AVAILABLE, $result['status']);
        $this->assertSame('https://example.test/r.pdf', $result['location']);
    }

    public function testReceiptCreationHistoryRequiresPaymentId(): void
    {
        $client = $this->createMock(SantanderApiClient::class);
        $client->expects($this->never())->method('get');

        $receipts = new PaymentReceipts($client);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('payment_id is required');
        $receipts->receiptCreationHistory('');
    }

    private function configWithLogger(): SantanderClientConfiguration
    {
        return new SantanderClientConfiguration(
            clientId: 'id',
            clientSecret: 'secret',
            cert: null,
            baseUrl: 'https://example.test',
            workspaceId: 'workspace-1',
            logRequestResponseLevel: 'ERROR',
            logger: new NullLogger(),
            timeout: 30
        );
    }
}
