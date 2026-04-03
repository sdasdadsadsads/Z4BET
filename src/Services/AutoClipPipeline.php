<?php

declare(strict_types=1);

namespace App\Services;

use App\Admin\AdminSettings;
use RuntimeException;

final class AutoClipPipeline
{
    public function __construct(
        private readonly KnpClient $knpClient,
        private readonly VideoService $videoService,
        private readonly ClipPlanner $clipPlanner,
        private readonly AdminSettings $settings,
        private readonly string $chatModel,
        private readonly string $imageModel,
        private readonly string $ttsModel,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function run(string $topic, int $voiceDurationSeconds): array
    {
        $sceneCount = $this->clipPlanner->sceneCountFromDurationSeconds($voiceDurationSeconds);
        $script = $this->generateScript($topic, $sceneCount);
        $images = $this->generateImages($script['scene_prompts'] ?? []);
        $voice = $this->generateSpeech((string) ($script['voiceover_text'] ?? ''));

        if ($this->settings->isAutoVideoEnabled()) {
            $videoResult = $this->buildVideo($topic, $script, $images, $voice);
            if ($videoResult['ok'] === true) {
                return ['mode' => 'video', 'result' => $videoResult];
            }

            if (!$this->settings->shouldFallbackToImageClip()) {
                throw new RuntimeException((string) ($videoResult['error'] ?? 'Video generation failed.'));
            }
        }

        return [
            'mode' => 'image_clip',
            'result' => [
                'ok' => true,
                'images' => $images,
                'voice' => $voice,
                'subtitle_text' => $script['voiceover_text'] ?? '',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function generateScript(string $topic, int $sceneCount): array
    {
        return $this->knpClient->postJson('/v1/chat/completions', [
            'model' => $this->chatModel,
            'messages' => [
                ['role' => 'system', 'content' => 'Create a short-form clip script in Thai with title, scene_prompts and voiceover_text.'],
                ['role' => 'user', 'content' => sprintf('หัวข้อ: %s, จำนวนฉาก: %d', $topic, $sceneCount)],
            ],
            'response_format' => ['type' => 'json_object'],
        ]);
    }

    /**
     * @param array<int, string> $scenePrompts
     * @return array<int, array<string, mixed>>
     */
    private function generateImages(array $scenePrompts): array
    {
        $result = [];
        foreach ($scenePrompts as $index => $prompt) {
            $result[] = $this->knpClient->postJson('/v1/images/generations', [
                'model' => $this->imageModel,
                'prompt' => $prompt,
                'scene' => $index + 1,
            ]);
        }

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function generateSpeech(string $text): array
    {
        return $this->knpClient->postJson('/v1/audio/speech', [
            'model' => $this->ttsModel,
            'input' => $text,
        ]);
    }

    /**
     * @param array<string, mixed> $script
     * @param array<int, array<string, mixed>> $images
     * @param array<string, mixed> $voice
     * @return array{ok: bool, url: ?string, error: ?string, raw: array<string, mixed>}
     */
    private function buildVideo(string $topic, array $script, array $images, array $voice): array
    {
        $taskId = $this->videoService->createTask([
            'topic' => $topic,
            'title' => $script['title'] ?? $topic,
            'scenes' => $images,
            'voice' => $voice,
        ]);

        return $this->videoService->waitUntilFinished($taskId);
    }
}
