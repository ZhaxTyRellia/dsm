<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Kernel;
use App\Service\DateTimeService;
use App\Service\FileUploadService;
use App\Service\YoutubeEmbedService;
use App\Support\DisplaySlideConfig;
use App\Support\DisplayThemeCatalog;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\PhpRenderer;

final class DisplayController extends AbstractController
{
    private const ALLOWED_MODES = ['auto', 'portrait', 'landscape'];

    private const MONTHS = [
        'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember',
    ];

    public function __construct(
        Kernel $kernel,
        PhpRenderer $view,
        string $basePath,
        private readonly FileUploadService $fileUploadService,
        private readonly YoutubeEmbedService $youtubeEmbedService,
        private readonly DateTimeService $dateTimeService
    ) {
        parent::__construct($kernel, $view, $basePath);
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $pdo = $this->pdo();

        $identity = $pdo->query('SELECT * FROM identity WHERE id = 1 LIMIT 1')->fetch();
        if ($identity === false) {
            $identity = [
                'logo' => 'default.png',
                'tittle' => 'Title',
                'name' => 'Name',
                'background' => DisplayThemeCatalog::DEFAULT_FILE,
                'display_mode' => 'auto',
                'audio' => 'uploads/audio/audio.mp3',
                'audio_enabled' => 0,
                'marquee' => '',
                'slide_settings' => '',
            ];
        }

        $query = $request->getQueryParams();

        $bgImage = DisplayThemeCatalog::normalize($identity['background'] ?? null);
        if (isset($query['bg']) && DisplayThemeCatalog::isValidFile((string) $query['bg'])) {
            $bgImage = (string) $query['bg'];
        }
        $theme = DisplayThemeCatalog::findByFile($bgImage);

        $displayMode = strtolower(trim((string) ($identity['display_mode'] ?? 'auto')));
        if (!in_array($displayMode, self::ALLOWED_MODES, true)) {
            $displayMode = 'auto';
        }
        if (isset($query['mode'])) {
            $requestedMode = strtolower(trim((string) $query['mode']));
            if (in_array($requestedMode, self::ALLOWED_MODES, true)) {
                $displayMode = $requestedMode;
            }
        }

        $slideConfig = DisplaySlideConfig::parse($identity['slide_settings'] ?? null);

        $isPreview = isset($query['preview']) && (string) $query['preview'] === '1';

        $initialScene = null;
        if (isset($query['scene']) && is_numeric($query['scene'])) {
            $initialScene = max(0, min(5, (int) $query['scene']));
        }

        $shalatTimes = $pdo->query('SELECT * FROM shalattime WHERE id = 1 LIMIT 1')->fetch() ?: [];
        $khotbahView = $pdo->query('SELECT * FROM khotbah_view WHERE id = 1 LIMIT 1')->fetch() ?: [
            'title' => '',
            'avatar' => 'default.png',
            'name' => '',
        ];

        $youtubeRow = $pdo->query('SELECT url FROM youtube LIMIT 1')->fetch();
        $videoData = $this->youtubeEmbedService->parse((string) ($youtubeRow['url'] ?? ''));

        $monthlyRows = $pdo->query('SELECT * FROM monthly_planner ORDER BY id_desc ASC')->fetchAll();
        $monthlyCounts = [];
        foreach ($monthlyRows as $row) {
            $key = (string) ($row['id_desc'] ?? '');
            $monthlyCounts[$key] = ($monthlyCounts[$key] ?? 0) + 1;
        }

        $khotbahRows = $pdo->query('SELECT * FROM data_list ORDER BY id ASC')->fetchAll();
        $khotbahGrouped = [];
        foreach ($khotbahRows as $row) {
            $khotbahGrouped[(string) ($row['moon'] ?? '')][] = $row;
        }

        $staffRows = $pdo->query('SELECT * FROM staff ORDER BY id_no ASC, id ASC')->fetchAll();
        $staffCounts = [];
        foreach ($staffRows as $row) {
            $tier = (string) ($row['tier'] ?? '');
            $staffCounts[$tier] = ($staffCounts[$tier] ?? 0) + 1;
        }

        $rawatibRows = $pdo->query(
            "SELECT * FROM data_rawatib ORDER BY CASE data_shalat "
            . "WHEN 'ISYA' THEN 1 "
            . "WHEN 'SUBUH' THEN 2 "
            . "WHEN 'DZUHUR' THEN 3 "
            . "WHEN 'ASHAR' THEN 4 "
            . "WHEN 'MAGHRIB' THEN 5 "
            . "WHEN 'BADAL' THEN 6 "
            . "ELSE 7 END, id ASC"
        )->fetchAll();

        $rawatibGrouped = [];
        foreach ($rawatibRows as $row) {
            $kategori = (string) ($row['data_shalat'] ?? '');
            $rawatibGrouped[$kategori][] = [
                'hari' => !empty($row['hari']) ? (string) $row['hari'] : '-',
                'nama' => (string) ($row['name'] ?? ''),
                'profil' => !empty($row['profile']) ? (string) $row['profile'] : 'default.png',
            ];
        }

        $bannerRow = $pdo->query('SELECT * FROM banner WHERE id = 1 LIMIT 1')->fetch();
        $limit = $bannerRow !== false ? (int) ($bannerRow['active_slides'] ?? 0) : 0;

        return $this->render($response, 'display/index.php', [
            'identity' => $identity,
            'profil' => $identity,
            'bgImage' => $bgImage,
            'theme' => $theme,
            'allowedThemes' => DisplayThemeCatalog::all(),
            'displayMode' => $displayMode,
            'slideConfig' => $slideConfig,
            'reloadToken' => (int) ($identity['display_reload_token'] ?? 0),
            'initialTestToken' => (int) (($this->parseTestTrigger($identity['display_test_trigger'] ?? null)['token']) ?? 0),
            'isPreview' => $isPreview,
            'initialScene' => $initialScene,
            'audioPath' => ltrim((string) ($identity['audio'] ?? 'uploads/audio/audio.mp3'), '/'),
            'audioEnabled' => (int) ($identity['audio_enabled'] ?? 0) === 1,
            'shalatTimes' => $shalatTimes,
            'view' => $khotbahView,
            'embedSrc' => $videoData['embed_src'],
            'currentDateHtml' => $this->dateTimeService->formatCurrentDateHtml(),
            'monthlyRows' => $monthlyRows,
            'monthlyCounts' => $monthlyCounts,
            'months' => self::MONTHS,
            'khotbahGrouped' => $khotbahGrouped,
            'staffRows' => $staffRows,
            'staffCounts' => $staffCounts,
            'rawatibGrouped' => $rawatibGrouped,
            'limit' => $limit,
        ]);
    }

    public function portrait(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->index($request, $response);
    }

    public function moneyRows(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $row = $this->pdo()->query('SELECT * FROM data_money WHERE id = 1 LIMIT 1')->fetch();

        return $this->render($response, 'display/partials/money_rows.php', [
            'row' => $row !== false ? $row : null,
        ]);
    }

    public function shalatTimeJson(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $this->pdo()->query('SELECT * FROM shalattime WHERE id = 1 LIMIT 1')->fetch();

        return $this->json($response, $data !== false ? $data : null);
    }

    public function khotbahJson(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $pdo = $this->pdo();
        $view = $pdo->query('SELECT * FROM khotbah_view WHERE id = 1 LIMIT 1')->fetch() ?: [];
        $profil = $pdo->query('SELECT * FROM identity WHERE id = 1 LIMIT 1')->fetch() ?: [];

        $avatar = trim((string) ($view['avatar'] ?? ''));
        $avatarName = $avatar !== '' ? $avatar : 'default.png';

        return $this->json($response, [
            'title' => (string) ($view['title'] ?? ''),
            'avatar' => $avatarName,
            'avatar_url' => $this->fileUploadService->publicImageUrl(FileUploadService::CATEGORY_KHOTBAH, $avatarName),
            'name' => (string) ($view['name'] ?? ''),
            'tittle' => (string) ($profil['tittle'] ?? ''),
            'profil_name' => (string) ($profil['name'] ?? ''),
        ]);
    }

    public function scenePlusJson(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $banner = $this->pdo()->query('SELECT * FROM banner WHERE id = 1 LIMIT 1')->fetch();
        $limit = $banner !== false ? (int) ($banner['active_slides'] ?? 0) : 0;
        $images = [];
        $imageUrls = [];

        if ($banner !== false && $limit > 0) {
            for ($i = 1; $i <= $limit; $i++) {
                $imageName = trim((string) ($banner['pict' . $i] ?? ''));
                if (
                    $imageName !== ''
                    && $this->fileUploadService->imageExists(FileUploadService::CATEGORY_BANNER, $imageName)
                ) {
                    $images[] = $imageName;
                    $imageUrls[] = $this->fileUploadService->publicImageUrl(FileUploadService::CATEGORY_BANNER, $imageName);
                }
            }
        }

        return $this->json($response, [
            'active_slides' => $limit,
            'images' => $images,
            'image_urls' => $imageUrls,
        ]);
    }

    public function currentTime(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->html($response, $this->dateTimeService->formatCurrentDateHtml());
    }

    public function audioJson(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $row = $this->pdo()->query('SELECT audio, audio_enabled FROM identity WHERE id = 1 LIMIT 1')->fetch();

        return $this->json($response, [
            'enabled' => (int) ($row !== false ? ($row['audio_enabled'] ?? 0) : 0) === 1,
            'src' => ltrim((string) ($row !== false ? ($row['audio'] ?? 'uploads/audio/audio.mp3') : 'uploads/audio/audio.mp3'), '/'),
        ]);
    }

    public function displayConfigJson(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $row = $this->pdo()->query(
            'SELECT background, display_mode, slide_settings, display_reload_token, display_test_trigger FROM identity WHERE id = 1 LIMIT 1'
        )->fetch();
        $bgImage = DisplayThemeCatalog::normalize($row !== false ? ($row['background'] ?? null) : null);
        $theme = DisplayThemeCatalog::findByFile($bgImage);
        $mode = strtolower(trim((string) ($row !== false ? ($row['display_mode'] ?? 'auto') : 'auto')));
        if (!in_array($mode, self::ALLOWED_MODES, true)) {
            $mode = 'auto';
        }

        return $this->json($response, [
            'mode' => $mode,
            'background' => $bgImage,
            'theme' => $theme['id'],
            'tone' => $theme['tone'],
            'slides' => DisplaySlideConfig::parse($row !== false ? ($row['slide_settings'] ?? null) : null),
            'reload_token' => (int) ($row !== false ? ($row['display_reload_token'] ?? 0) : 0),
            'test_trigger' => $this->parseTestTrigger($row !== false ? ($row['display_test_trigger'] ?? null) : null),
        ]);
    }

    /**
     * @return array{token: int, type: string, label: string, duration: int}|null
     */
    private function parseTestTrigger(mixed $raw): ?array
    {
        if (!is_string($raw) || trim($raw) === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return null;
        }

        $token = (int) ($decoded['token'] ?? 0);
        if ($token <= 0) {
            return null;
        }

        $type = strtolower(trim((string) ($decoded['type'] ?? '')));
        if (!in_array($type, ['prayer', 'jumat'], true)) {
            return null;
        }

        $duration = max(3, min(60, (int) ($decoded['duration'] ?? 10)));
        $label = trim((string) ($decoded['label'] ?? ($type === 'jumat' ? "JUM'AT" : 'DZUHUR')));
        if ($label === '') {
            $label = $type === 'jumat' ? "JUM'AT" : 'DZUHUR';
        }

        return [
            'token' => $token,
            'type' => $type,
            'label' => $label,
            'duration' => $duration,
        ];
    }
}
