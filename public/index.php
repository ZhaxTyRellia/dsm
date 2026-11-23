<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$createApp = require dirname(__DIR__) . '/config/bootstrap.php';
$app = $createApp();
$app->run();
