<?php

declare(strict_types=1);

namespace Santander\SDK\Auth;

use Illuminate\Http\Client\Factory;
use Illuminate\Support\Carbon;
use Santander\SDK\Client\SantanderClientConfiguration;
use Santander\SDK\Exceptions\SantanderRequestError;

class SantanderAuth
{
    
    public const TOKEN_ENDPOINT = '/auth/oauth/v2/token';
    public const TIMEOUT_SECS = 60;
    public const BEFORE_EXPIRE_TOKEN_SECONDS = 60;

    private Factory $http;
    private string $baseUrl;
    private string $clientId;
    private string $clientSecret;
    private string|array|null $cert;
    private int $timeout;

    private ?string $token = null;
    private ?Carbon $expiresAt = null;

    public function __construct(
        Factory $http,
        string $baseUrl,
        string $clientId,
        string $clientSecret,
        string|array|null $cert,
        int $timeout = self::TIMEOUT_SECS
    ) {
        $this->http = $http;
        $this->baseUrl = $baseUrl;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->cert = $cert;
        $this->timeout = $timeout;
    }

    public static function fromConfig(Factory $http, SantanderClientConfiguration $config): self
    {
        return new self(
            $http,
            $config->baseUrl,
            $config->clientId,
            $config->clientSecret,
            $config->cert,
            $config->timeout
        );
    }

    public function getToken(): string
    {
        if ($this->isExpired()) {
            $this->renew();
        }

        if ($this->token === null) {
            throw new SantanderRequestError('Token was not obtained', 0, null);
        }

        return $this->token;
    }

    public function authHeaders(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->getToken(),
            'X-Application-Key' => $this->clientId,
        ];
    }

    public function renew(): void
    {
        $request = $this->http
            ->baseUrl($this->baseUrl)
            ->asForm()
            ->timeout($this->timeout)
            ->withOptions($this->requestOptions());

        $response = $request->post(self::TOKEN_ENDPOINT, [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type' => 'client_credentials',
        ]);

        if (! $response->successful()) {
            $errorData = $response->json() ?? [];
            $message = $errorData['error_description'] ?? $response->body();
            throw new SantanderRequestError($message ?: 'Authentication error', $response->status(), $errorData);
        }

        $data = $response->json();
        if (! is_array($data) || ! isset($data['access_token'], $data['expires_in'])) {
            throw new SantanderRequestError('Invalid token response', $response->status(), is_array($data) ? $data : null);
        }

        $this->token = (string) $data['access_token'];
        $this->expiresAt = Carbon::now()->addSeconds((int) $data['expires_in']);
    }

    public function isExpired(): bool
    {
        if (! $this->expiresAt) {
            return true;
        }

        return Carbon::now()->greaterThan($this->expiresAt->copy()->subSeconds(self::BEFORE_EXPIRE_TOKEN_SECONDS));
    }

    private function requestOptions(): array
    {
        $options = [];
        if ($this->cert) {
            $options['cert'] = $this->cert;
        }
        return $options;
    }

}