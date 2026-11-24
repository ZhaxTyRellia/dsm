<?php

declare(strict_types=1);

return static function (string $rootDir): array {
    $timezone = 'Asia/Jakarta';
    date_default_timezone_set($timezone);

    return [
        'app' => [
            'env' => 'production',
            'debug' => false,
            'base_path' => '',
            'timezone' => $timezone,
        ],
        'db' => [
            'path' => $rootDir . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'dsm.sqlite',
        ],
        'paths' => [
            'root' => $rootDir,
            'public' => $rootDir . '/public',
            'templates' => $rootDir . '/templates',
            'uploads' => $rootDir . '/public/uploads',
        ],
    ];
};
