<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class VideoService
{
    public function __construct(
        private readonly KnpClient $knpClient,
        private readonly string $model,
        private readonly string $createPath,
        private readonly string $statusPath,
        private readonly string $statusField,
        private readonly string $urlField,
        private readonly string $errorField,
        private readonly string $doneValue,
        private readonly string $failedValue,
        private readonly int $pollIntervalSeconds = 5,
        private readonly int $maxAttempts = 24,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function createTask(array $payload): string
    {
        $payload['model'] = $this->model;
        $response = $this->knpClient->postJson($this->createPath, $payload);

        $taskId = $response['task_id'] ?? null;
        if (!is_string($taskId) || $taskId === '') {
            throw new RuntimeException('KNP video task_id not found in create response.');
        }

        return $taskId;
    }

    /**
     * @return array{ok: bool, url: ?string, error: ?string, raw: array<string, mixed>}
     */
    public function waitUntilFinished(string $taskId): array
    {
        for ($attempt = 1; $attempt <= $this->maxAttempts; $attempt++) {
            $statusResponse = $this->status($taskId);
            $status = (string) ($this->readPath($statusResponse, $this->statusField) ?? '');

            if ($status === $this->doneValue) {
                $url = $this->readPath($statusResponse, $this->urlField);

                return [
                    'ok' => is_string($url) && $url !== '',
                    'url' => is_string($url) ? $url : null,
                    'error' => null,
                    'raw' => $statusResponse,
                ];
            }

            if ($status === $this->failedValue) {
                $error = $this->readPath($statusResponse, $this->errorField);

                return [
                    'ok' => false,
                    'url' => null,
                    'error' => is_string($error) ? $error : 'Video generation failed.',
                    'raw' => $statusResponse,
                ];
            }

            sleep($this->pollIntervalSeconds);
        }

        return [
            'ok' => false,
            'url' => null,
            'error' => 'Video generation timed out while polling task status.',
            'raw' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function status(string $taskId): array
    {
        $path = str_replace('{task_id}', $taskId, $this->statusPath);

        return $this->knpClient->getJson($path);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function readPath(array $payload, string $path): mixed
    {
        $parts = explode('.', $path);
        $current = $payload;

        foreach ($parts as $part) {
            if (!is_array($current) || !array_key_exists($part, $current)) {
                return null;
            }
            $current = $current[$part];
        }

        return $current;
    }
}
