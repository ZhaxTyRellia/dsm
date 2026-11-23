<?php

declare(strict_types=1);

use Slim\App;
use Slim\Factory\AppFactory;

return static function (?array $overrides = null): App {
    $overrides = $overrides ?? [];
    $rootDir = dirname(__DIR__);

    $settingsFactory = require $rootDir . '/config/settings.php';
    $settings = $settingsFactory($rootDir);

    if (isset($overrides['settings']) && is_array($overrides['settings'])) {
        $settings = array_replace_recursive($settings, $overrides['settings']);
    }

    $containerFactory = require $rootDir . '/config/container.php';
    $container = $containerFactory($settings, $overrides);

    AppFactory::setContainer($container);
    $app = AppFactory::create();

    $basePath = (string) $container->get('basePath');
    if ($basePath !== '') {
        $app->setBasePath($basePath);
    }

    $registerMiddleware = require $rootDir . '/config/middleware.php';
    $registerMiddleware($app);

    $registerRoutes = require $rootDir . '/config/routes.php';
    $registerRoutes($app);

    return $app;
};
