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
            $candidate = $trimmed;
            if (!preg_match('#^[a-z][a-z0-9+\-.]*://#i', $candidate)
                && preg_match('#^(?:(?:www\.|m\.)?youtube(?:-nocookie)?\.com|youtu\.be)/#i', $candidate)
            ) {
                $candidate = 'https://' . $candidate;
            }

            $parsed = parse_url($candidate);
            $host = strtolower($parsed['host'] ?? '');
            $path = trim($parsed['path'] ?? '', '/');
            $query = [];

            if (!empty($parsed['query'])) {
                parse_str($parsed['query'], $query);
            }

            if ($host === '') {
                if (preg_match('#^[A-Za-z0-9_-]{11}$#', $trimmed)) {
                    $vidId = $trimmed;
                }
            } elseif (
                $host === 'youtube.com'
                || str_ends_with($host, '.youtube.com')
                || $host === 'youtu.be'
                || $host === 'youtube-nocookie.com'
                || str_ends_with($host, '.youtube-nocookie.com')
            ) {
                if (!empty($query['v'])) {
                    $vidId = (string) $query['v'];
                } elseif (!empty($query['video_id'])) {
                    $vidId = (string) $query['video_id'];
                } elseif (!empty($query['channel'])) {
                    $channelId = (string) $query['channel'];
                } elseif (preg_match('#^live/([^/?]+)#', $path, $m)) {
                    $vidId = $m[1];
                } elseif (preg_match('#^shorts/([^/?]+)#', $path, $m)) {
                    $vidId = $m[1];
                } elseif (preg_match('#^embed/([^/?]+)#', $path, $m)) {
                    $vidId = $m[1];
                } elseif (preg_match('#^channel/([^/?]+)#', $path, $m)) {
                    $channelId = $m[1];
                } elseif ($host === 'youtu.be' && $path !== '' && preg_match('#^([^/]+)$#', $path, $m)) {
                    $vidId = $m[1];
                }
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
