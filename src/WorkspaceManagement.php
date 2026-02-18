<?php

declare(strict_types=1);

namespace Santander\SDK;

use Santander\SDK\Client\SantanderApiClient;

class WorkspaceManagement
{
    public const WORKSPACES_ENDPOINT = '/management_payments_partners/v1/workspaces';

    private SantanderApiClient $client;

    public function __construct(SantanderApiClient $client)
    {
        $this->client = $client;
    }

    public function listWorkspaces(array $params = []): array
    {
        return $this->client->get(self::WORKSPACES_ENDPOINT, $params === [] ? null : $params);
    }

    public function getWorkspace(string $workspaceId): array
    {
        if ($workspaceId === '') {
            throw new \InvalidArgumentException('workspace_id not provided');
        }

        return $this->client->get(self::WORKSPACES_ENDPOINT . '/' . $workspaceId);
    }

    public function createWorkspace(array $data): array
    {
        return $this->client->post(self::WORKSPACES_ENDPOINT, $data);
    }

    public function updateWorkspace(string $workspaceId, array $data): array
    {
        if ($workspaceId === '') {
            throw new \InvalidArgumentException('workspace_id not provided');
        }

        return $this->client->patch(self::WORKSPACES_ENDPOINT . '/' . $workspaceId, $data);
    }

    public function deleteWorkspace(string $workspaceId): array
    {
        if ($workspaceId === '') {
            throw new \InvalidArgumentException('workspace_id not provided');
        }

        return $this->client->delete(self::WORKSPACES_ENDPOINT . '/' . $workspaceId);
    }
}
