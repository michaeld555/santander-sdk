<?php

declare(strict_types=1);

namespace Santander\SDK\Exceptions;

class SantanderRequestError extends SantanderError
{
    private int $statusCode;
    private ?array $content;

    public function __construct(string $message, int $statusCode, ?array $content = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->statusCode = $statusCode;
        $this->content = $content;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getContent(): ?array
    {
        return $this->content;
    }

    public function __toString(): string
    {
        $content = $this->content;
        $contentText = $content === null ? 'No response details' : json_encode($content, JSON_UNESCAPED_SLASHES);
        return 'Request failed: ' . parent::__toString() . ' - ' . $this->statusCode . ' ' . $contentText;
    }
}