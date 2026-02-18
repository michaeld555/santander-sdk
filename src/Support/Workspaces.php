<?php

declare(strict_types=1);

namespace Santander\SDK\Support;

use Santander\SDK\Client\SantanderApiClient;

final class Workspaces
{
    public const WORKSPACES_ENDPOINT = '/management_payments_partners/v1/workspaces';

    public static function getWorkspaces(SantanderApiClient $client): ?array
    {
        $response = $client->get(self::WORKSPACES_ENDPOINT);
        return $response['_content'] ?? null;
    }

    public static function getFirstWorkspaceIdOfType(SantanderApiClient $client, string $workspaceType): ?string
    {
        $workspaces = self::getWorkspaces($client);
        if (! $workspaces || count($workspaces) === 0) {
            return null;
        }

        foreach ($workspaces as $workspace) {
            if (($workspace['type'] ?? null) === $workspaceType && ($workspace['status'] ?? null) === 'ACTIVE') {
                return (string) $workspace['id'];
            }
        }

        return null;
    }
}