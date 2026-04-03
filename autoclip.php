<?php

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use App\Admin\AdminSettings;
use App\Config;
use App\Services\AutoClipPipeline;
use App\Services\ClipPlanner;
use App\Services\KnpClient;
use App\Services\VideoService;

$config = new Config(__DIR__ . '/.env');
$knp = new KnpClient(
    (string) $config->getString('KNP_BASE_URL', ''),
    (string) $config->getString('KNP_API_KEY', ''),
);

$admin = AdminSettings::fromEnv(
    $config->getBool('AUTO_VIDEO', true),
    $config->getBool('FALLBACK_TO_IMAGE_CLIP', true),
    (string) $config->getString('KNP_VIDEO_MODEL', 'knp-video-1'),
);

$videoService = new VideoService(
    $knp,
    $admin->knpVideoModel(),
    (string) $config->getString('KNP_VIDEO_CREATE_PATH', '/v1/video/create'),
    (string) $config->getString('KNP_VIDEO_STATUS_PATH', '/v1/video/status/{task_id}'),
    (string) $config->getString('KNP_VIDEO_STATUS_FIELD', 'status'),
    (string) $config->getString('KNP_VIDEO_URL_FIELD', 'output.url'),
    (string) $config->getString('KNP_VIDEO_ERROR_FIELD', 'error.message'),
    (string) $config->getString('KNP_VIDEO_DONE_VALUE', 'completed'),
    (string) $config->getString('KNP_VIDEO_FAILED_VALUE', 'failed'),
    $config->getInt('KNP_VIDEO_POLL_INTERVAL_SECONDS', 5),
    $config->getInt('KNP_VIDEO_MAX_POLL_ATTEMPTS', 24),
);

$pipeline = new AutoClipPipeline(
    $knp,
    $videoService,
    new ClipPlanner($config->getInt('SECONDS_PER_SCENE', 8)),
    $admin,
    (string) $config->getString('KNP_CHAT_MODEL', 'knp-chat-1'),
    (string) $config->getString('KNP_IMAGE_MODEL', 'knp-image-1'),
    (string) $config->getString('KNP_TTS_MODEL', 'knp-tts-1'),
);

$topic = $argv[1] ?? 'คำคมกำลังใจเช้านี้';
$duration = isset($argv[2]) ? (int) $argv[2] : 180;

$result = $pipeline->run($topic, $duration);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
