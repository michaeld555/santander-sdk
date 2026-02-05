<?php

declare(strict_types=1);

namespace Santander\SDK\Client;

use Psr\Log\LoggerInterface;

class SantanderClientConfiguration
{
    
    public string $clientId;
    public string $clientSecret;
    public string|array|null $cert;
    public string $baseUrl;
    public string $workspaceId;
    public string $logRequestResponseLevel;
    public ?LoggerInterface $logger;
    public int $timeout;

    public function __construct(
        string $clientId,
        string $clientSecret,
        string|array|null $cert,
        string $baseUrl,
        string $workspaceId = '',
        string $logRequestResponseLevel = 'ERROR',
        ?LoggerInterface $logger = null,
        int $timeout = 60
    ) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->cert = $cert;
        $this->baseUrl = $baseUrl;
        $this->workspaceId = $workspaceId;
        $this->logRequestResponseLevel = $logRequestResponseLevel;
        $this->logger = $logger;
        $this->timeout = $timeout;
    }

    public static function fromArray(array $config): self
    {
        return new self(
            (string) ($config['client_id'] ?? ''),
            (string) ($config['client_secret'] ?? ''),
            $config['cert'] ?? null,
            (string) ($config['base_url'] ?? ''),
            (string) ($config['workspace_id'] ?? ''),
            (string) ($config['log_request_response_level'] ?? 'ERROR'),
            $config['logger'] ?? null,
            (int) ($config['timeout'] ?? 60)
        );
    }

    public function setWorkspaceId(string $workspaceId): void
    {
        $this->workspaceId = $workspaceId;
    }

    public function __toString(): string
    {
        return 'SantanderClientConfiguration<client_id=' . $this->clientId . ' cert=' . (is_array($this->cert) ? 'array' : (string) $this->cert) . '>';
    }

}