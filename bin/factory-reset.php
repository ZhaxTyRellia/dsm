<?php

declare(strict_types=1);

use App\Core\Kernel;
use App\Service\FileUploadService;

require dirname(__DIR__) . '/vendor/autoload.php';

$rootDir = dirname(__DIR__);
$settingsFactory = require $rootDir . '/config/settings.php';
$settings = $settingsFactory($rootDir);

$includeLicense = in_array('--include-license', $argv ?? [], true);

$kernel = new Kernel(
    (string) $settings['db']['path'],
    '',
    (string) $settings['paths']['uploads'],
    (bool) $settings['app']['debug']
);

$uploadService = new FileUploadService((string) $settings['paths']['uploads']);
$kernel->factoryReset($uploadService, $includeLicense);

echo "Default settings restored successfully.\n";
echo "Data file  : " . $settings['db']['path'] . "\n";
echo "Uploads    : " . $settings['paths']['uploads'] . "\n";
echo "Sign-in    : admin / admin123\n";
echo "Activation : " . ($includeLicense ? "cleared (activation required)" : "kept") . "\n";
