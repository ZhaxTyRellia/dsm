<?php

declare(strict_types=1);

namespace App\Service;

final class YoutubeEmbedService
{
    /**
     * @return array{video_id: string, channel_id: string, embed_src: string}
     */
    public function parse(string $url): array
    {
        $trimmed = trim($url);
        $vidId = '';
        $channelId = '';
        $embedSrc = '';

        if ($trimmed !== '') {
            $parsed = parse_url($trimmed);
            $host = strtolower($parsed['host'] ?? '');
            $path = trim($parsed['path'] ?? '', '/');
            $query = [];

            if (!empty($parsed['query'])) {
                parse_str($parsed['query'], $query);
            }

            if (!empty($query['v'])) {
                $vidId = (string) $query['v'];
            } elseif (!empty($query['video_id'])) {
                $vidId = (string) $query['video_id'];
            } elseif (!empty($query['channel'])) {
                $channelId = (string) $query['channel'];
            } elseif (preg_match('#^live/([^/?]+)#', $path, $m)) {
                $vidId = $m[1];
            } elseif (preg_match('#^embed/([^/?]+)#', $path, $m)) {
                $vidId = $m[1];
            } elseif (strpos($host, 'youtu.be') !== false || ($path !== '' && preg_match('#^[^/]+$#', $path))) {
                $vidId = basename($path);
            } elseif (preg_match('#^@?([^/?]+)$#', $path, $m)) {
                $vidId = $m[1];
            }

            if ($channelId !== '') {
                $embedSrc = 'https://www.youtube.com/embed/live_stream?channel=' . urlencode($channelId);
            } elseif ($vidId !== '') {
                $embedSrc = 'https://www.youtube.com/embed/' . $vidId;
            }
        }

        return [
            'video_id' => $vidId,
            'channel_id' => $channelId,
            'embed_src' => $embedSrc,
        ];
    }
}
