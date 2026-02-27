<?php

declare(strict_types=1);

namespace Santander\SDK\Client;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
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
        if ($config->logger) {
            $this->logger = $config->logger;
        } else {
            $this->logger = $this->resolveDefaultLogger();
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
            $this->logger->warning('Nenhuma workspace PAYMENTS ativa encontrada. Crie uma workspace ou configure SANTANDER_WORKSPACE_ID.');
            return;
        }

        $this->logger->info('Workspace obtido e configurado com sucesso: ' . $workspaceId);
        $this->config->setWorkspaceId($workspaceId);
    }

    private function prepareUrl(string $endpoint): string
    {
        if (stripos($endpoint, ':workspaceid') !== false || stripos($endpoint, ':workspace_id') !== false) {
            if ($this->config->workspaceId === '') {
                throw new SantanderClientError('ID da workspace nao configurado');
            }
            $endpoint = str_ireplace(
                [':workspaceid', ':workspace_id'],
                $this->config->workspaceId,
                $endpoint
            );
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
                $content = Helpers::tryParseResponseToJson($response);
                $message = $this->buildRequestErrorMessage($response, $content);
                throw new SantanderRequestError($message, $response->status(), $content);
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

    private function buildRequestErrorMessage(Response $response, ?array $content): string
    {
        $messages = [];
        if ($content !== null) {
            $rootMessage = $this->firstMessageFromArray($content);
            if ($rootMessage !== null) {
                $messages[] = $rootMessage;
            }

            $errors = $content['errors'] ?? null;
            if (is_array($errors)) {
                foreach ($errors as $error) {
                    if (is_string($error)) {
                        $error = trim($error);
                        if ($error !== '') {
                            $messages[] = $error;
                        }
                        continue;
                    }

                    if (! is_array($error)) {
                        continue;
                    }

                    $errorMessage = $this->firstMessageFromArray($error);
                    $errorCode = $error['code'] ?? $error['errorCode'] ?? null;
                    if ($errorMessage !== null && is_scalar($errorCode) && trim((string) $errorCode) !== '') {
                        $messages[] = $errorMessage . ' (code: ' . trim((string) $errorCode) . ')';
                        continue;
                    }

                    if ($errorMessage !== null) {
                        $messages[] = $errorMessage;
                    }
                }
            }
        }

        $messages = array_values(array_unique(array_filter($messages, fn ($message) => $message !== '')));
        if ($messages !== []) {
            return implode(' | ', $messages);
        }

        $body = trim($response->body());
        if ($body !== '') {
            $singleLine = preg_replace('/\s+/', ' ', $body);
            return trim($singleLine ?? $body);
        }

        return 'HTTP request failed with status ' . $response->status();
    }

    private function firstMessageFromArray(array $content): ?string
    {
        $messageKeys = ['message', 'error_description', 'error', 'detail', 'description', 'title'];
        foreach ($messageKeys as $key) {
            if (! isset($content[$key])) {
                continue;
            }

            if (! is_string($content[$key])) {
                continue;
            }

            $message = trim($content[$key]);
            if ($message !== '') {
                return $message;
            }
        }

        return null;
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

    private function resolveDefaultLogger(): LoggerInterface
    {
        try {
            $root = Log::getFacadeRoot();
            if ($root) {
                return Log::getLogger();
            }
        } catch (\Throwable $e) {
            // Ignorar quando estiver fora do Laravel ou sem facade root.
        }

        return new NullLogger();
    }

}
