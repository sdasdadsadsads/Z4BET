<?php

declare(strict_types=1);

namespace App\Services;

final class ClipPlanner
{
    public function __construct(private readonly int $secondsPerScene = 8)
    {
    }

    public function sceneCountFromDurationSeconds(int $durationSeconds): int
    {
        if ($durationSeconds <= 0) {
            return 0;
        }

        return (int) ceil($durationSeconds / $this->secondsPerScene);
    }
}
