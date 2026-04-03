<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class KnpClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function postJson(string $path, array $payload): array
    {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/');

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode >= 400) {
            throw new RuntimeException('KNP request failed. HTTP code: ' . $httpCode);
        }

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        return $decoded;
    }

    /**
     * @return array<string, mixed>
     */
    public function getJson(string $path): array
    {
        $url = rtrim($this->baseUrl, '/') . '/' . ltrim($path, '/');

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPGET => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
            ],
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode >= 400) {
            throw new RuntimeException('KNP GET request failed. HTTP code: ' . $httpCode);
        }

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        return $decoded;
    }
}
