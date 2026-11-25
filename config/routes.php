<?php

declare(strict_types=1);

use App\Controller\ActivityController;
use App\Controller\AuthController;
use App\Controller\BannerController;
use App\Controller\ControlController;
use App\Controller\DashboardController;
use App\Controller\DisplayController;
use App\Controller\KhotbahListController;
use App\Controller\MoneyController;
use App\Controller\RawatibController;
use App\Controller\StaffController;
use App\Controller\YoutubeController;
use App\Middleware\AuthMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

return static function (App $app): void {
    $container = $app->getContainer();
    $authMiddleware = $container->get(AuthMiddleware::class);

    // Public Signage Display & Live Polling APIs (accessible without license activation)
    $app->get('/display', [DisplayController::class, 'index']);
    $app->get('/display/', [DisplayController::class, 'index']);
    $app->get('/display/index.php', [DisplayController::class, 'index']);
    $app->get('/potrait', [DisplayController::class, 'index']);
    $app->get('/portrait', [DisplayController::class, 'index']);
    $app->get('/potrait.php', [DisplayController::class, 'index']);

    $app->get('/api/money', [DisplayController::class, 'moneyRows']);
    $app->get('/money_update', [DisplayController::class, 'moneyRows']);
    $app->get('/money_update.php', [DisplayController::class, 'moneyRows']);

    $app->get('/api/shalat-time', [DisplayController::class, 'shalatTimeJson']);
    $app->get('/potrait_update', [DisplayController::class, 'shalatTimeJson']);
    $app->get('/potrait_update.php', [DisplayController::class, 'shalatTimeJson']);

    $app->get('/api/khotbah', [DisplayController::class, 'khotbahJson']);
    $app->get('/special_update', [DisplayController::class, 'khotbahJson']);
    $app->get('/special_update.php', [DisplayController::class, 'khotbahJson']);

    $app->get('/api/scene-plus', [DisplayController::class, 'scenePlusJson']);
    $app->get('/scene_plus_update', [DisplayController::class, 'scenePlusJson']);
    $app->get('/scene_plus_update.php', [DisplayController::class, 'scenePlusJson']);

    $app->get('/api/time', [DisplayController::class, 'currentTime']);
    $app->get('/api/audio', [DisplayController::class, 'audioJson']);
    $app->get('/api/display-config', [DisplayController::class, 'displayConfigJson']);
    $app->get('/app/control/get_time', [DisplayController::class, 'currentTime']);
    $app->get('/app/control/get_time.php', [DisplayController::class, 'currentTime']);

    // Auth routes (locked by global Kernel license gate when unactivated)
    $app->get('/login', [AuthController::class, 'showLogin']);
    $app->post('/login', [AuthController::class, 'login']);
    $app->map(['GET', 'POST'], '/logout', [AuthController::class, 'logout']);

    $app->get('/app', [AuthController::class, 'showLogin']);
    $app->get('/app/', [AuthController::class, 'showLogin']);
    $app->get('/app/index.php', [AuthController::class, 'showLogin']);
    $app->post('/app', [AuthController::class, 'login']);
    $app->post('/app/', [AuthController::class, 'login']);
    $app->post('/app/index.php', [AuthController::class, 'login']);
    $app->map(['GET', 'POST'], '/app/action/logout', [AuthController::class, 'logout']);
    $app->map(['GET', 'POST'], '/app/action/logout.php', [AuthController::class, 'logout']);

    // Dashboard (protected by Kernel license gate + AuthMiddleware)
    $app->get('/', [DashboardController::class, 'index'])->add($authMiddleware);
    $app->post('/', static function ($request, $response) use ($container) {
        /** @var \App\Core\Kernel $kernel */
        $kernel = $container->get(\App\Core\Kernel::class);
        return $response->withHeader('Location', $kernel->baseUrl())->withStatus(302);
    });
    $app->get('/index.php', [DashboardController::class, 'index'])->add($authMiddleware);

    // Protected Admin & Control Routes (protected by Kernel license gate + AuthMiddleware)
    $app->group('', function (RouteCollectorProxy $group): void {
        // YouTube
        $group->post('/youtube', [YoutubeController::class, 'update']);
        $group->post('/app/action/youtube.php', [YoutubeController::class, 'update']);

        // Money Report
        $group->post('/money', [MoneyController::class, 'update']);
        $group->map(['GET', 'POST'], '/money/{id}/delete', [MoneyController::class, 'delete']);
        $group->map(['GET', 'POST'], '/app/action/data_money.php', [MoneyController::class, 'legacyAction']);

        // Imam Rawatib
        $group->post('/rawatib', [RawatibController::class, 'insert']);
        $group->post('/rawatib/update', [RawatibController::class, 'update']);
        $group->map(['GET', 'POST'], '/rawatib/{id}/delete', [RawatibController::class, 'delete']);
        $group->map(['GET', 'POST'], '/app/action/data_rawatib.php', [RawatibController::class, 'legacyAction']);

        // Monthly Activity
        $group->post('/activity', [ActivityController::class, 'insert']);
        $group->map(['GET', 'POST'], '/activity/{id}/delete', [ActivityController::class, 'delete']);
        $group->map(['GET', 'POST'], '/app/action/data_activity.php', [ActivityController::class, 'legacyAction']);

        // Khotbah List
        $group->post('/khotbah-list', [KhotbahListController::class, 'insert']);
        $group->post('/khotbah-list/update', [KhotbahListController::class, 'update']);
        $group->map(['GET', 'POST'], '/khotbah-list/{id}/delete', [KhotbahListController::class, 'delete']);
        $group->map(['GET', 'POST'], '/app/action/data_khotbah.php', [KhotbahListController::class, 'legacyAction']);

        // Staff
        $group->post('/staff', [StaffController::class, 'insert']);
        $group->map(['GET', 'POST'], '/staff/{id}/delete', [StaffController::class, 'delete']);
        $group->map(['GET', 'POST'], '/app/action/data_staff.php', [StaffController::class, 'legacyAction']);

        // Banner
        $group->post('/banner/slides', [BannerController::class, 'updateSlides']);
        $group->post('/banner/upload', [BannerController::class, 'upload']);
        $group->post('/app/action/banner.php', [BannerController::class, 'legacyAction']);

        // Control Panels
        $group->get('/control/identity', [ControlController::class, 'showIdentity']);
        $group->post('/control/identity', [ControlController::class, 'updateIdentity']);
        $group->get('/app/control/identity', [ControlController::class, 'showIdentity']);
        $group->get('/app/control/identity.php', [ControlController::class, 'showIdentity']);
        $group->post('/app/control/identity', [ControlController::class, 'updateIdentity']);
        $group->post('/app/control/identity.php', [ControlController::class, 'updateIdentity']);

        $group->get('/control/background', [ControlController::class, 'showBackground']);
        $group->post('/control/background', [ControlController::class, 'updateBackground']);
        $group->post('/control/slides', [ControlController::class, 'updateSlides']);
        $group->post('/control/reload-display', [ControlController::class, 'reloadDisplay']);
        $group->post('/control/test-modal', [ControlController::class, 'triggerModalTest']);
        $group->get('/app/control/background', [ControlController::class, 'showBackground']);
        $group->get('/app/control/Background', [ControlController::class, 'showBackground']);
        $group->get('/app/control/background.php', [ControlController::class, 'showBackground']);
        $group->post('/app/action/background.php', [ControlController::class, 'updateBackground']);

        $group->get('/control/audio', [ControlController::class, 'showAudio']);
        $group->post('/control/audio', [ControlController::class, 'updateAudio']);
        $group->post('/control/audio/toggle', [ControlController::class, 'toggleAudio']);
        $group->get('/app/control/audio', [ControlController::class, 'showAudio']);
        $group->get('/app/control/audio.php', [ControlController::class, 'showAudio']);
        $group->post('/app/action/audio.php', [ControlController::class, 'updateAudio']);

        $group->get('/control/shalat-time', [ControlController::class, 'showShalatTime']);
        $group->post('/control/shalat-time', [ControlController::class, 'updateShalatTime']);
        $group->get('/app/control/shalattime', [ControlController::class, 'showShalatTime']);
        $group->get('/app/control/shalattime.php', [ControlController::class, 'showShalatTime']);
        $group->post('/app/action/shalat_time.php', [ControlController::class, 'updateShalatTime']);

        $group->get('/control/khotbah-day', [ControlController::class, 'showKhotbahDay']);
        $group->post('/control/khotbah-day', [ControlController::class, 'updateKhotbahDay']);
        $group->get('/app/control/khotbah_day', [ControlController::class, 'showKhotbahDay']);
        $group->get('/app/control/khotbah_day.php', [ControlController::class, 'showKhotbahDay']);
        $group->post('/app/action/khotbah.php', [ControlController::class, 'updateKhotbahDay']);

        $group->post('/control/factory-reset', [ControlController::class, 'factoryReset']);
        $group->get('/control/information', [ControlController::class, 'showInformation']);
        $group->post('/control/password', [ControlController::class, 'updatePassword']);
        $group->post('/control/unbind-key', [ControlController::class, 'unbindActivationKey']);

        $group->map(['GET', 'POST'], '/control/shutdown', [ControlController::class, 'shutdown']);
        $group->map(['GET', 'POST'], '/app/action/shutdown', [ControlController::class, 'shutdown']);
        $group->map(['GET', 'POST'], '/app/action/shutdown.php', [ControlController::class, 'shutdown']);
    })->add($authMiddleware);
};
