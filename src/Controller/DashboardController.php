<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\YoutubeEmbedService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class DashboardController extends AbstractController
{
    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->assertActivated($request);
        $pdo = $this->pdo();

        $youtubeRow = $pdo->query('SELECT url FROM youtube LIMIT 1')->fetch();
        $currentUrl = (string) ($youtubeRow['url'] ?? '');
        $youtubeParsed = (new YoutubeEmbedService())->parse($currentUrl);

        $latestCashReport = $pdo->query('SELECT * FROM data_money ORDER BY updated_at DESC LIMIT 1')->fetch() ?: [];
        $latestClosingTotal = (float) ($latestCashReport['closing_umat'] ?? 0)
            + (float) ($latestCashReport['closing_masjid'] ?? 0);

        $rawatibList = $pdo->query(
            "SELECT * FROM data_rawatib ORDER BY CASE data_shalat "
            . "WHEN 'ISYA' THEN 1 "
            . "WHEN 'SUBUH' THEN 2 "
            . "WHEN 'DZUHUR' THEN 3 "
            . "WHEN 'ASHAR' THEN 4 "
            . "WHEN 'MAGHRIB' THEN 5 "
            . "WHEN 'BADAL' THEN 6 "
            . "ELSE 7 END, id ASC"
        )->fetchAll();

        $activityList = $pdo->query('SELECT * FROM monthly_planner ORDER BY id DESC')->fetchAll();
        $khotbahList = $pdo->query('SELECT * FROM data_list ORDER BY id DESC')->fetchAll();
        $staffList = $pdo->query('SELECT * FROM staff ORDER BY id DESC')->fetchAll();

        $bannerData = $pdo->query('SELECT * FROM banner WHERE id = 1 LIMIT 1')->fetch();
        if ($bannerData === false) {
            $bannerData = $pdo->query('SELECT * FROM banner LIMIT 1')->fetch() ?: [];
        }
        $limit = max(1, (int) ($bannerData['active_slides'] ?? 1));

        return $this->renderAdmin(
            $request,
            $response,
            'dashboard/index.php',
            [
                'currentUrl' => $currentUrl,
                'currentVideoId' => $youtubeParsed['video_id'],
                'latestCashReport' => $latestCashReport,
                'latestClosingTotal' => $latestClosingTotal,
                'rawatibList' => $rawatibList,
                'activityList' => $activityList,
                'khotbahList' => $khotbahList,
                'staffList' => $staffList,
                'bannerData' => $bannerData,
                'limit' => $limit,
                'kpi' => [
                    'closingTotal' => $latestClosingTotal,
                    'closingDate' => (string) ($latestCashReport['closing_date'] ?? '-'),
                    'rawatibCount' => count($rawatibList),
                    'activityCount' => count($activityList),
                    'khotbahCount' => count($khotbahList),
                    'staffCount' => count($staffList),
                    'activeSlides' => $limit,
                ],
            ],
            'Dashboard Utama',
            'dashboard'
        );
    }
}
