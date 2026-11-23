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
use App\Core\Kernel;
use App\Middleware\AuthMiddleware;
use App\Service\DateTimeService;
use App\Service\FileUploadService;
use App\Service\YoutubeEmbedService;
use App\Support\BasePathDetector;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Slim\Views\PhpRenderer;

return static function (array $settings, array $overrides = []): ContainerInterface {
    $basePath = array_key_exists('basePath', $overrides)
        ? (string) $overrides['basePath']
        : BasePathDetector::detect((string) ($settings['app']['base_path'] ?? ''));

    $baseUrl = ($basePath !== '' ? $basePath : '') . '/';

    $definitions = [
        'settings' => $settings,
        'basePath' => $basePath,
        'baseUrl' => $baseUrl,

        Kernel::class => static function (ContainerInterface $c) use ($overrides): Kernel {
            if (isset($overrides[Kernel::class]) && $overrides[Kernel::class] instanceof Kernel) {
                return $overrides[Kernel::class];
            }

            $cfg = $c->get('settings');
            $pdo = isset($overrides[PDO::class]) && $overrides[PDO::class] instanceof PDO
                ? $overrides[PDO::class]
                : null;
            $publicKeyPem = isset($overrides['publicKeyPem']) && is_string($overrides['publicKeyPem'])
                ? $overrides['publicKeyPem']
                : null;
            $uuidResolver = isset($overrides['uuidResolver']) && is_callable($overrides['uuidResolver'])
                ? $overrides['uuidResolver']
                : null;

            return new Kernel(
                (string) ($cfg['db']['path'] ?? ''),
                (string) $c->get('basePath'),
                (string) ($cfg['paths']['pictures'] ?? ''),
                (bool) ($cfg['app']['debug'] ?? false),
                $pdo,
                $publicKeyPem,
                $uuidResolver
            );
        },

        PhpRenderer::class => static function (ContainerInterface $c): PhpRenderer {
            $paths = $c->get('settings')['paths'];

            return new PhpRenderer($paths['templates'], [
                'basePath' => (string) $c->get('basePath'),
                'baseUrl' => (string) $c->get('baseUrl'),
                'e' => static fn (mixed $value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8'),
            ]);
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

        AuthMiddleware::class => static fn (ContainerInterface $c): AuthMiddleware => new AuthMiddleware(
            (string) $c->get('basePath')
        ),

        AuthController::class => static fn (ContainerInterface $c): AuthController => new AuthController(
            $c->get(Kernel::class),
            $c->get(PhpRenderer::class),
            (string) $c->get('basePath')
        ),

        DashboardController::class => static fn (ContainerInterface $c): DashboardController => new DashboardController(
            $c->get(Kernel::class),
            $c->get(PhpRenderer::class),
            (string) $c->get('basePath')
        ),

        YoutubeController::class => static fn (ContainerInterface $c): YoutubeController => new YoutubeController(
            $c->get(Kernel::class),
            $c->get(PhpRenderer::class),
            (string) $c->get('basePath')
        ),

        MoneyController::class => static fn (ContainerInterface $c): MoneyController => new MoneyController(
            $c->get(Kernel::class),
            $c->get(PhpRenderer::class),
            (string) $c->get('basePath')
        ),

        RawatibController::class => static fn (ContainerInterface $c): RawatibController => new RawatibController(
            $c->get(Kernel::class),
            $c->get(PhpRenderer::class),
            (string) $c->get('basePath'),
            $c->get(FileUploadService::class)
        ),

        ActivityController::class => static fn (ContainerInterface $c): ActivityController => new ActivityController(
            $c->get(Kernel::class),
            $c->get(PhpRenderer::class),
            (string) $c->get('basePath')
        ),

        KhotbahListController::class => static fn (ContainerInterface $c): KhotbahListController => new KhotbahListController(
            $c->get(Kernel::class),
            $c->get(PhpRenderer::class),
            (string) $c->get('basePath')
        ),

        StaffController::class => static fn (ContainerInterface $c): StaffController => new StaffController(
            $c->get(Kernel::class),
            $c->get(PhpRenderer::class),
            (string) $c->get('basePath')
        ),

        BannerController::class => static fn (ContainerInterface $c): BannerController => new BannerController(
            $c->get(Kernel::class),
            $c->get(PhpRenderer::class),
            (string) $c->get('basePath'),
            $c->get(FileUploadService::class)
        ),

        ControlController::class => static function (ContainerInterface $c) use ($overrides): ControlController {
            $shutdownRunner = $overrides['shutdownRunner'] ?? null;

            return new ControlController(
                $c->get(Kernel::class),
                $c->get(PhpRenderer::class),
                (string) $c->get('basePath'),
                $c->get(FileUploadService::class),
                $c->get(DateTimeService::class),
                is_callable($shutdownRunner) ? $shutdownRunner : null
            );
        },

        DisplayController::class => static fn (ContainerInterface $c): DisplayController => new DisplayController(
            $c->get(Kernel::class),
            $c->get(PhpRenderer::class),
            (string) $c->get('basePath'),
            $c->get(FileUploadService::class),
            $c->get(YoutubeEmbedService::class),
            $c->get(DateTimeService::class)
        ),
    ];

    $builder = new ContainerBuilder();
    $builder->addDefinitions($definitions);

    return $builder->build();
};
