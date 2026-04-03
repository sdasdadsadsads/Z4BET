<?php

declare(strict_types=1);

namespace App\Admin;

final class AdminSettings
{
    /**
     * @param array<string, mixed> $settings
     */
    public function __construct(private array $settings)
    {
    }

    public static function fromEnv(bool $autoVideo, bool $fallbackToImageClip, string $videoModel): self
    {
        return new self([
            'auto_video' => $autoVideo,
            'fallback_to_image_clip' => $fallbackToImageClip,
            'knp_video_model' => $videoModel,
        ]);
    }

    public function isAutoVideoEnabled(): bool
    {
        return (bool) ($this->settings['auto_video'] ?? false);
    }

    public function shouldFallbackToImageClip(): bool
    {
        return (bool) ($this->settings['fallback_to_image_clip'] ?? false);
    }

    public function knpVideoModel(): string
    {
        return (string) ($this->settings['knp_video_model'] ?? '');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->settings;
    }
}
