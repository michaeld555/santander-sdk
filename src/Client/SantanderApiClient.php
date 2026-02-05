<?php

declare(strict_types=1);

namespace Santander\SDK\Client;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Santander\SDK\Auth\SantanderAuth;
use Santander\SDK\Exceptions\SantanderClientError;
use Santander\SDK\Exceptions\SantanderRequestError;
use Santander\SDK\Support\Helpers;
use Santander\SDK\Support\Workspaces;

class SantanderApiClient
{

    private SantanderClientConfiguration $config;
    private SantanderAuth $auth;
    private Factory $http;
    private LoggerInterface $logger;

    public function __construct(SantanderClientConfiguration $config, SantanderAuth $auth, Factory $http)
    {
        $this->config = $config;
        $this->auth = $auth;
        $this->http = $http;
        $this->logger = $config->logger ?? Log::getLogger();
        if ($this->config->logger === null) {
            $this->config->logger = $this->logger;
        }

        $this->setDefaultWorkspaceId();
    }

    public function get(string $endpoint, ?array $params = null): array
    {
        return $this->request('GET', $endpoint, null, $params);
    }

    public function post(string $endpoint, ?array $data = null): array
    {
        return $this->request('POST', $endpoint, $data);
    }

    public function put(string $endpoint, array $data): array
    {
        return $this->request('PUT', $endpoint, $data);
    }

    public function delete(string $endpoint): array
    {
        return $this->request('DELETE', $endpoint);
    }

    public function patch(string $endpoint, array $data): array
    {
        return $this->request('PATCH', $endpoint, $data);
    }

    public function getConfig(): SantanderClientConfiguration
    {
        return $this->config;
    }

    private function setDefaultWorkspaceId(): void
    {
        if ($this->config->workspaceId !== '') {
            return;
        }

        $workspaceId = Workspaces::getFirstWorkspaceIdOfType($this, 'PAYMENTS');
        if (! $workspaceId) {
            throw new SantanderClientError('Conta sem configuracao de workspace na configuracao e na conta.');
        }

        $this->logger->info('Workspace obtido e configurado com sucesso: ' . $workspaceId);
        $this->config->setWorkspaceId($workspaceId);
    }

    private function prepareUrl(string $endpoint): string
    {
        if (stripos($endpoint, ':workspaceid') !== false) {
            if ($this->config->workspaceId === '') {
                throw new SantanderClientError('ID da workspace nao configurado');
            }
            $endpoint = str_ireplace(':workspaceid', $this->config->workspaceId, $endpoint);
        }

        return $endpoint;
    }

    private function request(string $method, string $endpoint, ?array $data = null, ?array $params = null): array
    {
        $url = $this->prepareUrl($endpoint);
        $response = null;

        try {
            $options = [];
            if ($data !== null) {
                $options['json'] = $data;
            }
            if ($params !== null) {
                $options['query'] = $params;
            }

            $response = $this->baseRequest()->send($method, $url, $options);

            if (! $response->successful()) {
                $this->logErrorIfNeeded($method, $url, $params, $data, $response, null);
                throw new SantanderRequestError('Not successful code', $response->status(), Helpers::tryParseResponseToJson($response));
            }

            $this->logRequestSuccessIfNeeded($method, $url, $params, $data, $response);

            $json = $response->json();
            return is_array($json) ? $json : [];
        } catch (ConnectionException $e) {
            $this->logErrorIfNeeded($method, $url, $params, $data, $response, $e);
            throw new SantanderRequestError('Error in request: ' . $e->getMessage(), 0, null, $e);
        } catch (SantanderRequestError $e) {
            throw $e;
        } catch (\Throwable $e) {
            $this->logErrorIfNeeded($method, $url, $params, $data, $response, $e);
            throw new SantanderRequestError('Error in request: ' . $e->getMessage(), 0, null, $e);
        }
    }

    private function baseRequest(): PendingRequest
    {
        $request = $this->http->baseUrl($this->config->baseUrl)
            ->timeout($this->config->timeout)
            ->withHeaders($this->auth->authHeaders());

        if ($this->config->cert) {
            $request = $request->withOptions(['cert' => $this->config->cert]);
        }

        return $request;
    }

    private function logErrorIfNeeded(
        string $method,
        string $url,
        ?array $params,
        ?array $data,
        ?Response $response,
        ?\Throwable $error
    ): void {
        if (! $this->shouldLogError()) {
            return;
        }

        $extra = $this->getRequestSummary($method, $url, $response, $data, $params, $error);
        $this->logger->error('API request failed', $extra);
    }

    private function logRequestSuccessIfNeeded(
        string $method,
        string $url,
        ?array $params,
        ?array $data,
        Response $response
    ): void {
        if (! $this->shouldLogAll()) {
            return;
        }

        $extra = $this->getRequestSummary($method, $url, $response, $data, $params, null);
        $this->logger->info('API request successful', $extra);
    }

    private function getRequestSummary(
        string $method,
        string $url,
        ?Response $response,
        ?array $requestData,
        ?array $requestParams,
        ?\Throwable $error
    ): array {
        return [
            'method' => $method,
            'url' => $url,
            'request_body' => $requestData,
            'request_params' => $requestParams,
            'status_code' => $response ? $response->status() : null,
            'response_body' => $response ? Helpers::tryParseResponseToJson($response) : null,
            'status' => $error ? 'error' : 'success',
            'error' => $error ? [
                'message' => $error->getMessage(),
                'type' => $error::class,
            ] : null,
        ];
    }

    private function shouldLogAll(): bool
    {
        return strtoupper($this->config->logRequestResponseLevel) === 'ALL';
    }

    private function shouldLogError(): bool
    {
        return in_array(strtoupper($this->config->logRequestResponseLevel), ['ALL', 'ERROR'], true);
    }
    
}
