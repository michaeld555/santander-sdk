<?php

declare(strict_types=1);

namespace Santander\SDK\Tests\Unit;

use Illuminate\Http\Client\Factory;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Santander\SDK\Auth\SantanderAuth;
use Santander\SDK\Client\SantanderApiClient;
use Santander\SDK\Client\SantanderClientConfiguration;
use Santander\SDK\Exceptions\SantanderRequestError;

class SantanderApiClientTest extends TestCase
{
    public function testThrowsRealErrorMessageFromApiPayload(): void
    {
        $responseBody = json_encode([
            'errors' => [
                [
                    'code' => '006',
                    'message' => 'Receipt already requested',
                ],
            ],
        ]);

        $client = $this->buildClientWithFakeResponse(
            $responseBody === false ? '' : $responseBody,
            400,
            ['Content-Type' => 'application/json']
        );

        try {
            $client->get('/failing-endpoint', ['_limit' => '1']);
            $this->fail('Expected SantanderRequestError to be thrown.');
        } catch (SantanderRequestError $e) {
            $this->assertSame(400, $e->getStatusCode());
            $this->assertSame('Receipt already requested (code: 006)', $e->getMessage());
            $this->assertSame([
                'errors' => [
                    [
                        'code' => '006',
                        'message' => 'Receipt already requested',
                    ],
                ],
            ], $e->getContent());
        }
    }

    public function testFallsBackToResponseBodyWhenPayloadIsNotJson(): void
    {
        $client = $this->buildClientWithFakeResponse(
            "  upstream timeout \n please retry  ",
            503,
            ['Content-Type' => 'text/plain']
        );

        try {
            $client->post('/failing-endpoint');
            $this->fail('Expected SantanderRequestError to be thrown.');
        } catch (SantanderRequestError $e) {
            $this->assertSame(503, $e->getStatusCode());
            $this->assertSame('upstream timeout please retry', $e->getMessage());
            $this->assertNull($e->getContent());
        }
    }

    private function buildClientWithFakeResponse(string $body, int $status, array $headers): SantanderApiClient
    {
        $http = new Factory();
        $http->fake([
            'https://example.test/*' => $http->response($body, $status, $headers),
        ]);

        $config = new SantanderClientConfiguration(
            clientId: 'app-key',
            clientSecret: 'secret',
            cert: null,
            baseUrl: 'https://example.test',
            workspaceId: 'workspace-1',
            logRequestResponseLevel: 'ERROR',
            logger: new NullLogger(),
            timeout: 30
        );

        $auth = $this->createMock(SantanderAuth::class);
        $auth->expects($this->once())
            ->method('authHeaders')
            ->willReturn([
                'Authorization' => 'Bearer token',
                'X-Application-Key' => 'app-key',
            ]);

        return new SantanderApiClient($config, $auth, $http);
    }
}
