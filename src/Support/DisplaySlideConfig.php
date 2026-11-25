<?php

declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

final class DisplaySlideConfig
{
    public const DEFAULT_DURATION = 60;
    public const MIN_DURATION = 5;
    public const MAX_DURATION = 3600;

    /** @var array<int, array{key: string, index: int, label: string, description: string}> */
    private const DEFINITIONS = [
        [
            'key' => 'home',
            'index' => 0,
            'label' => 'Home (Video & Laporan Kas)',
            'description' => 'Main screen with mosque header, prayer times, video, and cash report',
        ],
        [
            'key' => 'banner',
            'index' => 1,
            'label' => 'Banner Pengumuman',
            'description' => 'Full-screen announcement image slides',
        ],
        [
            'key' => 'rutinitas',
            'index' => 2,
            'label' => 'Jadwal Rutinitas Bulanan',
            'description' => 'Monthly study programs and schedules',
        ],
        [
            'key' => 'khotbah',
            'index' => 3,
            'label' => "Daftar Khotbah Jum'at",
            'description' => 'Yearly Friday sermon speaker table',
        ],
        [
            'key' => 'pengurus',
            'index' => 4,
            'label' => 'Susunan Pengurus',
            'description' => 'Masjid committee structure',
        ],
        [
            'key' => 'rawatib',
            'index' => 5,
            'label' => 'Imam Rawatib',
            'description' => 'Daily and substitute imam schedule',
        ],
    ];

    /**
     * @return array<int, array{key: string, index: int, label: string, description: string}>
     */
    public static function definitions(): array
    {
        return self::DEFINITIONS;
    }

    /**
     * @return array<string, array{enabled: bool, duration: int}>
     */
    public static function defaults(): array
    {
        $defaults = [];
        foreach (self::DEFINITIONS as $def) {
            $defaults[$def['key']] = [
                'enabled' => true,
                'duration' => self::DEFAULT_DURATION,
            ];
        }

        return $defaults;
    }

    public static function defaultJson(): string
    {
        return (string) json_encode(self::defaults(), JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return array<string, array{enabled: bool, duration: int}>
     */
    public static function parse(?string $json): array
    {
        $config = self::defaults();
        $clean = trim((string) ($json ?? ''));
        if ($clean === '') {
            return $config;
        }

        $decoded = json_decode($clean, true);
        if (!is_array($decoded)) {
            return $config;
        }

        foreach (self::DEFINITIONS as $def) {
            $key = $def['key'];
            if (!isset($decoded[$key]) || !is_array($decoded[$key])) {
                continue;
            }
            $item = $decoded[$key];
            $enabled = array_key_exists('enabled', $item) ? (bool) $item['enabled'] : true;
            $duration = isset($item['duration']) && is_numeric($item['duration'])
                ? (int) $item['duration']
                : self::DEFAULT_DURATION;
            $duration = max(self::MIN_DURATION, min(self::MAX_DURATION, $duration));

            $config[$key] = [
                'enabled' => $enabled,
                'duration' => $duration,
            ];
        }

        $hasAnyEnabled = false;
        foreach ($config as $item) {
            if ($item['enabled']) {
                $hasAnyEnabled = true;
                break;
            }
        }
        if (!$hasAnyEnabled) {
            $config['home']['enabled'] = true;
        }

        return $config;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, array{enabled: bool, duration: int}>
     */
    public static function fromFormInput(array $input): array
    {
        $result = [];
        $enabledCount = 0;

        foreach (self::DEFINITIONS as $def) {
            $key = $def['key'];
            $entry = isset($input[$key]) && is_array($input[$key]) ? $input[$key] : [];
            $enabled = isset($entry['enabled']) && (string) $entry['enabled'] === '1';

            $rawDuration = trim((string) ($entry['duration'] ?? (string) self::DEFAULT_DURATION));
            if ($rawDuration === '' || !is_numeric($rawDuration)) {
                throw new InvalidArgumentException(
                    "Duration for {$def['label']} must be between " . self::MIN_DURATION . ' and ' . self::MAX_DURATION . ' seconds.'
                );
            }

            $duration = (int) $rawDuration;
            if ($duration < self::MIN_DURATION || $duration > self::MAX_DURATION) {
                throw new InvalidArgumentException(
                    "Duration for {$def['label']} must be between " . self::MIN_DURATION . ' and ' . self::MAX_DURATION . ' seconds.'
                );
            }

            if ($enabled) {
                $enabledCount++;
            }

            $result[$key] = [
                'enabled' => $enabled,
                'duration' => $duration,
            ];
        }

        if ($enabledCount === 0) {
            throw new InvalidArgumentException('At least one slide must stay enabled.');
        }

        return $result;
    }
}
