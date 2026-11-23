<?php

declare(strict_types=1);

use Dotenv\Dotenv;

return static function (string $rootDir): array {
    if (is_file($rootDir . '/.env')) {
        Dotenv::createImmutable($rootDir)->safeLoad();
    }

    $env = static function (string $key, string $default = ''): string {
        if (array_key_exists($key, $_ENV)) {
            return (string) $_ENV[$key];
        }
        if (array_key_exists($key, $_SERVER)) {
            return (string) $_SERVER[$key];
        }
        $val = getenv($key);
        return $val !== false ? (string) $val : $default;
    };

    $timezone = $env('APP_TIMEZONE', 'Asia/Jakarta');
    if ($timezone !== '') {
        date_default_timezone_set($timezone);
    }

    $debugRaw = strtolower($env('APP_DEBUG', 'false'));
    $debug = in_array($debugRaw, ['1', 'true', 'yes', 'on'], true);

    $dbPathRaw = trim($env('DB_PATH', 'storage/database/dsm.sqlite'));
    if ($dbPathRaw === ':memory:' || str_starts_with($dbPathRaw, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $dbPathRaw)) {
        $dbPath = $dbPathRaw;
    } else {
        $dbPath = $rootDir . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dbPathRaw);
    }

    return [
        'app' => [
            'env' => $env('APP_ENV', 'production'),
            'debug' => $debug,
            'base_path' => $env('APP_BASE_PATH', ''),
            'timezone' => $timezone,
        ],
        'db' => [
            'path' => $dbPath,
        ],
        'paths' => [
            'root' => $rootDir,
            'public' => $rootDir . '/public',
            'templates' => $rootDir . '/templates',
            'pictures' => $rootDir . '/public/assets/img/pictures',
            'audio' => $rootDir . '/public/assets/audio',
        ],
    ];
};
