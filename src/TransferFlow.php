<?php

declare(strict_types=1);

namespace Santander\SDK;

use Santander\SDK\Client\SantanderApiClient;
use Santander\SDK\Exceptions\SantanderClientError;
use Santander\SDK\Exceptions\SantanderRejectedError;
use Santander\SDK\Exceptions\SantanderRequestError;
use Santander\SDK\Exceptions\SantanderStatusTimeoutError;
use Santander\SDK\Types\ConfirmOrderStatus;
use Santander\SDK\Types\CreateOrderStatus;
use Santander\SDK\Types\OrderStatus;

class TransferFlow
{
    public const MAX_UPDATE_STATUS_AFTER_CONFIRM = 120;
    public const MAX_UPDATE_STATUS_BEFORE_CONFIRM = 10;
    public const UPDATE_STATUS_INTERVAL_TIME = 2;

    private SantanderApiClient $client;
    private string $endpoint;
    private string $currentStep = 'CREATE';
    public ?string $requestId = null;

    public function __construct(SantanderApiClient $client, string $endpoint)
    {
        $this->client = $client;
        $this->endpoint = $endpoint;
    }

    public function createPayment(array $data): array
    {
        $response = $this->client->post($this->endpoint, $data);
        $this->requestId = $response['id'] ?? null;
        $this->checkForRejectedError($response);
        $this->client->getConfig()->logger?->info('Payment created: ' . ($response['id'] ?? ''));
        return $response;
    }

    public function ensureReadyToPay(array $confirmData): void
    {
        $paymentStatus = $confirmData['status'] ?? null;
        if ($paymentStatus !== CreateOrderStatus::READY_TO_PAY) {
            $this->client->getConfig()->logger?->info('PIX is not ready for payment', ['status' => $paymentStatus]);
            $this->paymentStatusPolling(
                paymentId: (string) ($confirmData['id'] ?? ''),
                untilStatus: [CreateOrderStatus::READY_TO_PAY],
                maxUpdateAttempts: self::MAX_UPDATE_STATUS_BEFORE_CONFIRM
            );
        }
    }

    public function confirmPayment(array $confirmData, string $paymentId): array
    {
        try {
            $confirmResponse = $this->requestConfirmPayment($confirmData, $paymentId);
        } catch (SantanderRequestError $e) {
            $this->client->getConfig()->logger?->error($e->getMessage(), ['payment_id' => $paymentId]);
            $confirmResponse = $this->requestPaymentStatus($paymentId);
        }

        if (($confirmResponse['status'] ?? null) !== ConfirmOrderStatus::PAYED) {
            try {
                $confirmResponse = $this->resolveLazyStatusPayed(
                    $paymentId,
                    (string) ($confirmResponse['status'] ?? '')
                );
            } catch (SantanderStatusTimeoutError $e) {
                $this->client->getConfig()->logger?->info('Timeout occurred while updating status: ' . $e->getMessage());
            }
        }

        return $confirmResponse;
    }

    private function requestPaymentStatus(string $paymentId): array
    {
        try {
            return $this->requestPaymentStatusOnce($paymentId);
        } catch (SantanderRequestError $e) {
            $this->client->getConfig()->logger?->error($e->getMessage());
            return $this->requestPaymentStatusOnce($paymentId);
        }
    }

    private function requestPaymentStatusOnce(string $paymentId): array
    {
        if ($paymentId === '') {
            throw new \InvalidArgumentException('payment_id not provided');
        }
        $response = $this->client->get($this->endpoint . '/' . $paymentId);
        $this->checkForRejectedError($response);
        return $response;
    }

    private function requestConfirmPayment(array $confirmData, string $paymentId): array
    {
        $this->currentStep = 'CONFIRM';
        if ($paymentId === '') {
            throw new \InvalidArgumentException('payment_id not provided');
        }
        $response = $this->client->patch($this->endpoint . '/' . $paymentId, $confirmData);
        $this->checkForRejectedError($response);
        return $response;
    }

    private function checkForRejectedError(array $paymentResponse): void
    {
        if (($paymentResponse['status'] ?? null) !== OrderStatus::REJECTED) {
            return;
        }

        $rejectReason = $paymentResponse['rejectReason'] ?? 'Reason not returned by Santander';
        throw new SantanderRejectedError(
            'Payment rejected by the bank at step ' . $this->currentStep . ' - ' . $rejectReason
        );
    }

    private function resolveLazyStatusPayed(string $paymentId, string $currentStatus): array
    {
        if ($currentStatus !== ConfirmOrderStatus::PENDING_CONFIRMATION) {
            throw new SantanderClientError('Unexpected status after confirmation: ' . $currentStatus);
        }

        return $this->paymentStatusPolling(
            paymentId: $paymentId,
            untilStatus: [ConfirmOrderStatus::PAYED],
            maxUpdateAttempts: self::MAX_UPDATE_STATUS_AFTER_CONFIRM
        );
    }

    private function paymentStatusPolling(string $paymentId, array $untilStatus, int $maxUpdateAttempts): array
    {
        $response = null;

        for ($attempt = 1; $attempt <= $maxUpdateAttempts; $attempt++) {
            $response = $this->requestPaymentStatus($paymentId);
            $this->client->getConfig()->logger?->info(
                'Checking status by polling: ' . $paymentId . ' - ' . ($response['status'] ?? '')
            );

            if (in_array($response['status'] ?? null, $untilStatus, true)) {
                break;
            }

            if ($attempt === $maxUpdateAttempts) {
                throw new SantanderStatusTimeoutError('Status update attempt limit reached', $this->currentStep);
            }

            sleep(self::UPDATE_STATUS_INTERVAL_TIME);
        }

        if ($response === null) {
            throw new SantanderClientError('No response received during polling');
        }

        return $response;
    }
}