<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\YoutubeEmbedService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final class YoutubeController extends AbstractController
{
    public function update(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->assertActivated($request);
        $body = $this->body($request);

        if (!array_key_exists('youtube_url', $body)) {
            $this->flash('error', 'Please enter a valid YouTube link.');

            return $this->redirect($response, '/');
        }

        $url = trim((string) $body['youtube_url']);
        if ($url !== '') {
            $parsed = (new YoutubeEmbedService())->parse($url);
            if ($parsed['embed_src'] === '') {
                $this->flash('error', 'Please enter a valid YouTube link.');

                return $this->redirect($response, '/');
            }
        }

        try {
            $pdo = $this->pdo();
            $exists = (int) $pdo->query('SELECT COUNT(*) FROM youtube WHERE id = 1')->fetchColumn();
            if ($exists > 0) {
                $stmt = $pdo->prepare('UPDATE youtube SET url = ? WHERE id = 1');
                $stmt->execute([$url]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO youtube (id, url) VALUES (1, ?)');
                $stmt->execute([$url]);
            }

            $this->flash('success', $url === '' ? 'YouTube video link cleared.' : 'YouTube video link updated.');
        } catch (Throwable) {
            $this->flash('error', 'Unable to save the YouTube video link.');
        }

        return $this->redirect($response, '/');
    }
}
