<?php

declare(strict_types=1);

use App\Support\BasePathDetector;

require dirname(__DIR__) . '/vendor/autoload.php';

$basePath = BasePathDetector::detect(null, $_SERVER);
if (($_SERVER['REDIRECT_STATUS'] ?? '') === '403') {
    $_SERVER['REQUEST_URI'] = ($basePath !== '' ? $basePath : '') . '/403';
    $_SERVER['REQUEST_METHOD'] = 'GET';
}

$createApp = require dirname(__DIR__) . '/config/bootstrap.php';
$app = $createApp();
$app->run();
