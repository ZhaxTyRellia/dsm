<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Kernel;
use App\Service\DateTimeService;
use App\Service\FileUploadService;
use App\Service\YoutubeEmbedService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\PhpRenderer;

final class DisplayController extends AbstractController
{
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

    public function portrait(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $pdo = $this->pdo();

        $identity = $pdo->query('SELECT * FROM identity WHERE id = 1 LIMIT 1')->fetch();
        if ($identity === false) {
            $identity = [
                'logo' => 'default.png',
                'tittle' => 'Title',
                'name' => 'Name',
                'background' => 'bg1.png',
                'audio' => 'assets/audio/audio.mp3',
                'marquee' => '',
            ];
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

        return $this->render($response, 'display/portrait.php', [
            'identity' => $identity,
            'profil' => $identity,
            'bgImage' => (string) ($identity['background'] ?? 'bg1.png'),
            'audioPath' => ltrim((string) ($identity['audio'] ?? 'assets/audio/audio.mp3'), '/'),
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

        if ($banner !== false && $limit > 0) {
            for ($i = 1; $i <= $limit; $i++) {
                $imageName = trim((string) ($banner['pict' . $i] ?? ''));
                if ($imageName !== '' && $this->fileUploadService->pictureExists($imageName)) {
                    $images[] = $imageName;
                }
            }
        }

        return $this->json($response, [
            'active_slides' => $limit,
            'images' => $images,
        ]);
    }

    public function currentTime(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->html($response, $this->dateTimeService->formatCurrentDateHtml());
    }
}
