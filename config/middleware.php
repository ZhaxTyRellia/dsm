<?php

declare(strict_types=1);

use App\Middleware\SessionMiddleware;
use App\Support\HtmlErrorRenderer;
use Slim\App;
use Slim\Handlers\ErrorHandler;

return static function (App $app): void {
    $container = $app->getContainer();
    $settings = $container->get('settings');
    $debug = (bool) ($settings['app']['debug'] ?? false);

    $app->addBodyParsingMiddleware();
    $app->addRoutingMiddleware();
    $app->add($container->get(SessionMiddleware::class));

    $errorMiddleware = $app->addErrorMiddleware($debug, true, true);
    $errorHandler = $errorMiddleware->getDefaultErrorHandler();
    if ($errorHandler instanceof ErrorHandler) {
        $htmlErrorRenderer = $container->get(HtmlErrorRenderer::class);
        $errorHandler->registerErrorRenderer('text/html', $htmlErrorRenderer);
        $errorHandler->setDefaultErrorRenderer('text/html', $htmlErrorRenderer);
    }
};
