<?php

declare(strict_types=1);

namespace Santander\SDK\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Santander\SDK\Client\SantanderApiClient;
use Santander\SDK\WorkspaceManagement;

class WorkspaceManagementTest extends TestCase
{
    public function testCreateWorkspaceValidatedNormalizesPayloadAndAppliesDefaults(): void
    {
        $client = $this->createMock(SantanderApiClient::class);
        $workspaceManagement = new WorkspaceManagement($client);

        $input = [
            'type' => 'payments',
            'mainDebitAccount' => [
                'branch' => 1,
                'number' => 7,
            ],
            'description' => ' Workspace API ',
            'tags' => ['client:123'],
            'addicionalDebitAccounts' => [
                [
                    'branch' => '12',
                    'number' => '9',
                ],
            ],
            'bankSlipPaymentsActive' => true,
            'bankSlipAvailableActive' => true,
        ];

        $expectedPayload = [
            'type' => 'PAYMENTS',
            'mainDebitAccount' => [
                'branch' => '1',
                'number' => '000000000007',
            ],
            'description' => 'Workspace API',
            'tags' => ['client:123'],
            'additionalDebitAccounts' => [
                [
                    'branch' => '12',
                    'number' => '000000000009',
                ],
            ],
            'webhookURL' => null,
            'pixPaymentsActive' => false,
            'bankSlipPaymentsActive' => true,
            'bankSlipAvailableActive' => true,
            'barCodePaymentsActive' => false,
            'taxesByFieldPaymentsActive' => false,
            'vehicleTaxesPaymentsActive' => false,
            'bankSlipAvailableWebhookActive' => false,
            'bankTransferPaymentsActive' => false,
            'smartTransfersActive' => false,
        ];

        $expectedResponse = ['id' => 'workspace-1'];

        $client->expects($this->once())
            ->method('post')
            ->with(WorkspaceManagement::WORKSPACES_ENDPOINT, $expectedPayload)
            ->willReturn($expectedResponse);

        $response = $workspaceManagement->createWorkspaceValidated($input);

        $this->assertSame($expectedResponse, $response);
    }

    public function testCreateWorkspaceValidatedForcesTransferFlagsToFalseForDigitalCorban(): void
    {
        $client = $this->createMock(SantanderApiClient::class);
        $workspaceManagement = new WorkspaceManagement($client);

        $client->expects($this->once())
            ->method('post')
            ->with(
                WorkspaceManagement::WORKSPACES_ENDPOINT,
                $this->callback(function (array $payload): bool {
                    $this->assertSame('DIGITAL_CORBAN', $payload['type']);
                    $this->assertFalse($payload['pixPaymentsActive']);
                    $this->assertFalse($payload['bankTransferPaymentsActive']);
                    $this->assertFalse($payload['smartTransfersActive']);
                    return true;
                })
            )
            ->willReturn(['id' => 'workspace-2']);

        $workspaceManagement->createWorkspaceValidated([
            'type' => 'DIGITAL_CORBAN',
            'mainDebitAccount' => [
                'branch' => '0001',
                'number' => '130367795',
            ],
            'pixPaymentsActive' => true,
            'bankTransferPaymentsActive' => true,
            'smartTransfersActive' => true,
        ]);
    }

    public function testCreateWorkspaceValidatedThrowsWhenDdaIsEnabledWithoutBankSlipPayments(): void
    {
        $client = $this->createMock(SantanderApiClient::class);
        $workspaceManagement = new WorkspaceManagement($client);

        $client->expects($this->never())->method('post');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('bankSlipPaymentsActive must be true when bankSlipAvailableActive is true');

        $workspaceManagement->createWorkspaceValidated([
            'type' => 'PAYMENTS',
            'mainDebitAccount' => [
                'branch' => '1',
                'number' => '2',
            ],
            'bankSlipAvailableActive' => true,
            'bankSlipPaymentsActive' => false,
        ]);
    }

    public function testCreateWorkspaceValidatedThrowsWhenWebhookNotificationIsEnabledWithoutWebhookUrl(): void
    {
        $client = $this->createMock(SantanderApiClient::class);
        $workspaceManagement = new WorkspaceManagement($client);

        $client->expects($this->never())->method('post');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('webhookURL must be informed when bankSlipAvailableWebhookActive is true');

        $workspaceManagement->createWorkspaceValidated([
            'type' => 'PAYMENTS',
            'mainDebitAccount' => [
                'branch' => '1',
                'number' => '2',
            ],
            'bankSlipPaymentsActive' => true,
            'bankSlipAvailableWebhookActive' => true,
        ]);
    }

    public function testCreateWorkspaceValidatedThrowsWhenDescriptionExceedsLimit(): void
    {
        $client = $this->createMock(SantanderApiClient::class);
        $workspaceManagement = new WorkspaceManagement($client);

        $client->expects($this->never())->method('post');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('description must have at most 30 characters');

        $workspaceManagement->createWorkspaceValidated([
            'type' => 'PAYMENTS',
            'mainDebitAccount' => [
                'branch' => '1',
                'number' => '2',
            ],
            'description' => str_repeat('a', 31),
        ]);
    }
}
