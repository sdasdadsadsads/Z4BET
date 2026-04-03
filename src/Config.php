<?php

declare(strict_types=1);

namespace App;

final class Config
{
    /**
     * @var array<string, string>
     */
    private array $values = [];

    public function __construct(?string $envPath = null)
    {
        if ($envPath !== null && is_file($envPath)) {
            $this->loadDotEnv($envPath);
        }
    }

    public function getString(string $key, ?string $default = null): ?string
    {
        $value = getenv($key);
        if ($value === false) {
            return $this->values[$key] ?? $default;
        }

        return (string) $value;
    }

    public function getBool(string $key, bool $default = false): bool
    {
        $value = $this->getString($key);
        if ($value === null) {
            return $default;
        }

        return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
    }

    public function getInt(string $key, int $default = 0): int
    {
        $value = $this->getString($key);
        if ($value === null || !is_numeric($value)) {
            return $default;
        }

        return (int) $value;
    }

    private function loadDotEnv(string $envPath): void
    {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            [$name, $value] = array_pad(explode('=', $trimmed, 2), 2, '');
            $name = trim($name);
            $value = trim($value);
            $this->values[$name] = $value;
            putenv($name . '=' . $value);
        }
    }
}
