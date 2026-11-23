<?php

declare(strict_types=1);

namespace App\Support;

final class BasePathDetector
{
    public static function detect(?string $configuredBasePath = null, ?array $server = null): string
    {
        if ($configuredBasePath !== null && trim($configuredBasePath) !== '') {
            $normalized = rtrim(trim($configuredBasePath), '/');
            return $normalized === '' ? '' : '/' . ltrim($normalized, '/');
        }

        $server = $server ?? $_SERVER;
        $scriptName = str_replace('\\', '/', (string) ($server['SCRIPT_NAME'] ?? ''));
        $requestUri = (string) ($server['REQUEST_URI'] ?? '/');
        $requestPath = (string) (parse_url($requestUri, PHP_URL_PATH) ?: '/');

        if ($scriptName === '') {
            return '';
        }

        $scriptDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        if ($scriptDir === '/' || $scriptDir === '.') {
            $scriptDir = '';
        }

        if ($scriptDir !== '' && ($requestPath === $scriptDir || str_starts_with($requestPath, $scriptDir . '/'))) {
            return $scriptDir;
        }

        if (str_ends_with($scriptDir, '/public')) {
            $parentDir = substr($scriptDir, 0, -strlen('/public'));
            if ($parentDir === '') {
                return '';
            }
            if ($requestPath === $parentDir || str_starts_with($requestPath, $parentDir . '/')) {
                return $parentDir;
            }
        }

        return '';
    }
}
