<?php

declare(strict_types=1);

namespace Santander\SDK\Support;

use Illuminate\Http\Client\Response;
use InvalidArgumentException;

final class Helpers
{
    public static function truncateValue(int|float|string $value): string
    {
        $number = (float) $value;
        $truncated = floor($number * 100) / 100;
        return number_format($truncated, 2, '.', '');
    }

    public static function getPixKeyType(string $key): string
    {
        $key = trim($key);

        if (self::isValidCpf($key)) {
            return 'CPF';
        }

        if (self::isValidCnpj($key)) {
            return 'CNPJ';
        }

        if (str_contains($key, '@')) {
            return 'EMAIL';
        }

        if (strlen($key) === 32 && preg_match('/^[a-zA-Z0-9]+$/', $key)) {
            return 'EVP';
        }

        $numbers = self::onlyNumbers($key);
        if ($numbers !== null && strlen($numbers) === 13 && str_starts_with($key, '+')) {
            return 'CELULAR';
        }

        throw new InvalidArgumentException('Chave Pix em formato invalido: ' . $key);
    }

    public static function tryParseResponseToJson(?Response $response): ?array
    {
        if (! $response) {
            return null;
        }

        $data = $response->json();
        return is_array($data) ? $data : null;
    }

    public static function onlyNumbers(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return preg_replace('/[^0-9]/', '', $value);
    }

    public static function isValidCpf(string $cpf): bool
    {
        $clean = self::onlyNumbers($cpf);
        if ($clean === null || strlen($clean) !== 11 || ! ctype_digit($clean)) {
            return false;
        }

        if (count(array_unique(str_split($clean))) === 1) {
            return false;
        }

        $digits = array_map('intval', str_split($clean));

        $sum = 0;
        for ($i = 0, $weight = 10; $i < 9; $i++, $weight--) {
            $sum += $digits[$i] * $weight;
        }
        $digit1 = 11 - ($sum % 11);
        if ($digit1 > 9) {
            $digit1 = 0;
        }

        $sum = 0;
        for ($i = 0, $weight = 11; $i < 10; $i++, $weight--) {
            $sum += $digits[$i] * $weight;
        }
        $digit2 = 11 - ($sum % 11);
        if ($digit2 > 9) {
            $digit2 = 0;
        }

        return $digits[9] === $digit1 && $digits[10] === $digit2;
    }

    public static function isValidCnpj(string $cnpj): bool
    {
        $clean = self::onlyNumbers($cnpj);
        if ($clean === null || strlen($clean) !== 14 || ! ctype_digit($clean)) {
            return false;
        }

        if (count(array_unique(str_split($clean))) === 1) {
            return false;
        }

        $digits = array_map('intval', str_split($clean));
        $weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += $digits[$i] * $weights1[$i];
        }
        $remainder = $sum % 11;
        $digit1 = $remainder < 2 ? 0 : 11 - $remainder;

        $sum = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum += $digits[$i] * $weights2[$i];
        }
        $remainder = $sum % 11;
        $digit2 = $remainder < 2 ? 0 : 11 - $remainder;

        return $digits[12] === $digit1 && $digits[13] === $digit2;
    }

    public static function documentType(string $documentNumber): string
    {
        $clean = self::onlyNumbers($documentNumber) ?? $documentNumber;
        $length = strlen($clean);
        if ($length === 11) {
            return 'CPF';
        }
        if ($length === 14) {
            return 'CNPJ';
        }

        throw new InvalidArgumentException('Unknown document type "' . $documentNumber . '"');
    }

    public static function getContentFromUrl(string $url): string
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: Mozilla/5.0\r\n",
                'timeout' => 60,
            ],
        ]);

        $content = file_get_contents($url, false, $context);
        if ($content === false) {
            throw new \RuntimeException('Unable to download content from URL');
        }

        return $content;
    }

    public static function saveBytesToFile(string $content, string $filePath): string
    {
        $result = file_put_contents($filePath, $content);
        if ($result === false) {
            throw new \RuntimeException('Unable to save file to path: ' . $filePath);
        }

        return $filePath;
    }

    public static function downloadFile(string $url, string $filePath): string
    {
        $content = self::getContentFromUrl($url);
        return self::saveBytesToFile($content, $filePath);
    }

    public static function pollingUntilCondition(
        callable $func,
        callable $condition,
        int $timeout = 60,
        int $interval = 1,
        mixed ...$args
    ): mixed {
        $endTime = microtime(true) + $timeout;
        while (microtime(true) <= $endTime) {
            $result = $func(...$args);
            if ($condition($result)) {
                return $result;
            }
            sleep($interval);
        }

        throw new \RuntimeException('Timeout polling until condition is met');
    }
}