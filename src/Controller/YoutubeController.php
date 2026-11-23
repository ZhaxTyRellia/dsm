<?php

declare(strict_types=1);

namespace App\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class YoutubeController extends AbstractController
{
    public function update(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->assertActivated($request);
        $body = $this->body($request);

        if (array_key_exists('youtube_url', $body)) {
            $url = trim((string) $body['youtube_url']);
            $pdo = $this->pdo();

            $exists = (int) $pdo->query('SELECT COUNT(*) FROM youtube WHERE id = 1')->fetchColumn();
            if ($exists > 0) {
                $stmt = $pdo->prepare('UPDATE youtube SET url = ? WHERE id = 1');
                $stmt->execute([$url]);
            } else {
                $stmt = $pdo->prepare('INSERT INTO youtube (id, url) VALUES (1, ?)');
                $stmt->execute([$url]);
            }

            $this->flash('success', 'YouTube video link updated.');
        }

        return $this->redirect($response, '/');
    }
}
