<?php

declare(strict_types=1);

namespace App\Support;

final class DisplayThemeCatalog
{
    public const DEFAULT_FILE = 'emerald-gold.svg';

    /** @var array<int, array{file: string, id: string, name: string, subtitle: string, tone: string}> */
    private const THEMES = [
        [
            'file' => 'emerald-gold.svg',
            'id' => 'emerald-gold',
            'name' => 'Emerald Gold',
            'subtitle' => 'Masjid Emerald & Gold',
            'tone' => 'dark',
        ],
        [
            'file' => 'ivory-andalusia.svg',
            'id' => 'ivory-andalusia',
            'name' => 'Ivory Andalusia',
            'subtitle' => 'Warm Pearl & Gold',
            'tone' => 'light',
        ],
        [
            'file' => 'midnight-nabawi.svg',
            'id' => 'midnight-nabawi',
            'name' => 'Midnight Nabawi',
            'subtitle' => 'Royal Sapphire & Gold',
            'tone' => 'dark',
        ],
        [
            'file' => 'sandstone-haramain.svg',
            'id' => 'sandstone-haramain',
            'name' => 'Sandstone Haramain',
            'subtitle' => 'Desert Marble & Bronze',
            'tone' => 'light',
        ],
        [
            'file' => 'sage-sanctuary.svg',
            'id' => 'sage-sanctuary',
            'name' => 'Sage Sanctuary',
            'subtitle' => 'Botanical Mint & Forest',
            'tone' => 'light',
        ],
        [
            'file' => 'obsidian-kiswah.svg',
            'id' => 'obsidian-kiswah',
            'name' => 'Obsidian Kiswah',
            'subtitle' => 'Rich Charcoal & Gold',
            'tone' => 'dark',
        ],
    ];

    /** @var array<string, string> */
    private const LEGACY_MAP = [
        'bg1.png' => 'emerald-gold.svg',
        'bg2.jpg' => 'ivory-andalusia.svg',
        'bg3.jpeg' => 'midnight-nabawi.svg',
        'bg4.jpg' => 'sandstone-haramain.svg',
        'bg5.jpg' => 'sage-sanctuary.svg',
        'bg6.jpg' => 'obsidian-kiswah.svg',
    ];

    /**
     * @return array<int, array{file: string, id: string, name: string, subtitle: string, tone: string}>
     */
    public static function all(): array
    {
        return self::THEMES;
    }

    /**
     * @return string[]
     */
    public static function allowedFiles(): array
    {
        return array_column(self::THEMES, 'file');
    }

    public static function isValidFile(string $file): bool
    {
        return in_array(trim($file), self::allowedFiles(), true);
    }

    public static function normalize(?string $value): string
    {
        $clean = trim((string) ($value ?? ''));
        if ($clean === '') {
            return self::DEFAULT_FILE;
        }

        if (in_array($clean, self::allowedFiles(), true)) {
            return $clean;
        }

        $lower = strtolower($clean);
        if (isset(self::LEGACY_MAP[$lower])) {
            return self::LEGACY_MAP[$lower];
        }

        return self::DEFAULT_FILE;
    }

    /**
     * @return array{file: string, id: string, name: string, subtitle: string, tone: string}
     */
    public static function findByFile(?string $file): array
    {
        $normalized = self::normalize($file);
        foreach (self::THEMES as $theme) {
            if ($theme['file'] === $normalized) {
                return $theme;
            }
        }

        return self::THEMES[0];
    }
}
