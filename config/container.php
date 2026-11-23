<?php

declare(strict_types=1);

use App\Controller\ActivityController;
use App\Controller\AuthController;
use App\Controller\BannerController;
use App\Controller\ControlController;
use App\Controller\DashboardController;
use App\Controller\DisplayController;
use App\Controller\ErrorController;
use App\Controller\KhotbahListController;
use App\Controller\LicenseController;
use App\Controller\MoneyController;
use App\Controller\RawatibController;
use App\Controller\StaffController;
use App\Controller\YoutubeController;
use App\Database\Connection;
use App\Middleware\AuthMiddleware;
use App\Middleware\LicenseMiddleware;
use App\Middleware\SessionMiddleware;
use App\Service\DateTimeService;
use App\Service\FileUploadService;
use App\Service\LicenseService;
use App\Service\YoutubeEmbedService;
use App\Support\BasePathDetector;
use App\Support\HtmlErrorRenderer;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Slim\Views\PhpRenderer;

return static function (array $settings, array $overrides = []): ContainerInterface {
    $basePath = array_key_exists('basePath', $overrides)
        ? (string) $overrides['basePath']
        : BasePathDetector::detect((string) ($settings['app']['base_path'] ?? ''));

    $definitions = [
        'settings' => $settings,
        'basePath' => $basePath,

        Connection::class => static function (ContainerInterface $c) use ($overrides): Connection {
            if (isset($overrides[Connection::class])) {
                return $overrides[Connection::class];
            }
            if (isset($overrides[PDO::class]) && $overrides[PDO::class] instanceof PDO) {
                return new Connection($c->get('settings')['db'], $overrides[PDO::class]);
            }
            return new Connection($c->get('settings')['db']);
        },

        PhpRenderer::class => static function (ContainerInterface $c): PhpRenderer {
            $paths = $c->get('settings')['paths'];
            $bp = (string) $c->get('basePath');

            return new PhpRenderer($paths['templates'], [
                'basePath' => $bp,
                'e' => static fn (mixed $value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8'),
            ]);
        },

        LicenseService::class => static function (ContainerInterface $c) use ($overrides): LicenseService {
            if (isset($overrides[LicenseService::class])) {
                return $overrides[LicenseService::class];
            }
            $license = $c->get('settings')['license'];
            $uuidResolver = $overrides['uuidResolver'] ?? null;

            return new LicenseService(
                (string) $license['file_path'],
                (string) $license['secret_salt'],
                is_callable($uuidResolver) ? $uuidResolver : null
            );
        },

        FileUploadService::class => static function (ContainerInterface $c) use ($overrides): FileUploadService {
            if (isset($overrides[FileUploadService::class])) {
                return $overrides[FileUploadService::class];
            }
            $paths = $c->get('settings')['paths'];
            return new FileUploadService(
                (string) $paths['pictures'],
                (string) $paths['audio']
            );
        },

        YoutubeEmbedService::class => static fn (): YoutubeEmbedService => new YoutubeEmbedService(),

        DateTimeService::class => static fn (): DateTimeService => new DateTimeService(),

        HtmlErrorRenderer::class => static function (ContainerInterface $c): HtmlErrorRenderer {
            $debug = (bool) ($c->get('settings')['app']['debug'] ?? false);
            return new HtmlErrorRenderer(
                $c->get(PhpRenderer::class),
                (string) $c->get('basePath'),
                $debug
            );
        },

        SessionMiddleware::class => static fn (): SessionMiddleware => new SessionMiddleware(),

        LicenseMiddleware::class => static fn (ContainerInterface $c): LicenseMiddleware => new LicenseMiddleware(
            $c->get(LicenseService::class),
            (string) $c->get('basePath')
        ),

        AuthMiddleware::class => static fn (ContainerInterface $c): AuthMiddleware => new AuthMiddleware(
            (string) $c->get('basePath')
        ),

        AuthController::class => static fn (ContainerInterface $c): AuthController => new AuthController(
            $c->get(Connection::class),
            $c->get(PhpRenderer::class),
            (string) $c->get('basePath')
        ),

        LicenseController::class => static fn (ContainerInterface $c): LicenseController => new LicenseController(
            $c->get(Connection::class),
            $c->get(PhpRenderer::class),
            (string) $c->get('basePath'),
            $c->get(LicenseService::class)
        ),

        DashboardController::class => static fn (ContainerInterface $c): DashboardController => new DashboardController(
            $c->get(Connection::class),
            $c->get(PhpRenderer::class),
            (string) $c->get('basePath')
        ),

        YoutubeController::class => static fn (ContainerInterface $c): YoutubeController => new YoutubeController(
            $c->get(Connection::class),
            $c->get(PhpRenderer::class),
            (string) $c->get('basePath')
        ),

        MoneyController::class => static fn (ContainerInterface $c): MoneyController => new MoneyController(
            $c->get(Connection::class),
            $c->get(PhpRenderer::class),
            (string) $c->get('basePath')
        ),

        RawatibController::class => static fn (ContainerInterface $c): RawatibController => new RawatibController(
            $c->get(Connection::class),
            $c->get(PhpRenderer::class),
            (string) $c->get('basePath'),
            $c->get(FileUploadService::class)
        ),

        ActivityController::class => static fn (ContainerInterface $c): ActivityController => new ActivityController(
            $c->get(Connection::class),
            $c->get(PhpRenderer::class),
            (string) $c->get('basePath')
        ),

        KhotbahListController::class => static fn (ContainerInterface $c): KhotbahListController => new KhotbahListController(
            $c->get(Connection::class),
            $c->get(PhpRenderer::class),
            (string) $c->get('basePath')
        ),

        StaffController::class => static fn (ContainerInterface $c): StaffController => new StaffController(
            $c->get(Connection::class),
            $c->get(PhpRenderer::class),
            (string) $c->get('basePath')
        ),

        BannerController::class => static fn (ContainerInterface $c): BannerController => new BannerController(
            $c->get(Connection::class),
            $c->get(PhpRenderer::class),
            (string) $c->get('basePath'),
            $c->get(FileUploadService::class)
        ),

        ControlController::class => static function (ContainerInterface $c) use ($overrides): ControlController {
            $shutdownRunner = $overrides['shutdownRunner'] ?? null;
            return new ControlController(
                $c->get(Connection::class),
                $c->get(PhpRenderer::class),
                (string) $c->get('basePath'),
                $c->get(FileUploadService::class),
                $c->get(DateTimeService::class),
                is_callable($shutdownRunner) ? $shutdownRunner : null
            );
        },

        DisplayController::class => static fn (ContainerInterface $c): DisplayController => new DisplayController(
            $c->get(Connection::class),
            $c->get(PhpRenderer::class),
            (string) $c->get('basePath'),
            $c->get(FileUploadService::class),
            $c->get(YoutubeEmbedService::class),
            $c->get(DateTimeService::class)
        ),

        ErrorController::class => static fn (ContainerInterface $c): ErrorController => new ErrorController(
            $c->get(Connection::class),
            $c->get(PhpRenderer::class),
            (string) $c->get('basePath')
        ),
    ];

    $builder = new ContainerBuilder();
    $builder->addDefinitions($definitions);

    return $builder->build();
};
