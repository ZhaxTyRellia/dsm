<?php

declare(strict_types=1);

namespace App\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ActivityController extends AbstractController
{
    public function insert(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->assertActivated($request);
        $body = $this->body($request);
        $idDesc = (int) ($body['id_desc'] ?? 0);
        $desc = (string) ($body['desc'] ?? '');
        $time = (string) ($body['time'] ?? '');
        $speaker = (string) ($body['speaker'] ?? '');

        $stmt = $this->pdo()->prepare(
            'INSERT INTO monthly_planner (id_desc, `desc`, `time`, speaker) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$idDesc, $desc, $time, $speaker]);

        $this->flash('success', 'Aktivitas rutinitas bulanan berhasil ditambahkan.');

        return $this->redirect($response, '/');
    }

    /**
     * @param array<string, string> $args
     */
    public function delete(ServerRequestInterface $request, ResponseInterface $response, array $args = []): ResponseInterface
    {
        $this->assertActivated($request);
        $query = $request->getQueryParams();
        $id = (int) ($args['id'] ?? $query['id'] ?? 0);

        if ($id > 0) {
            $stmt = $this->pdo()->prepare('DELETE FROM monthly_planner WHERE id = ?');
            $stmt->execute([$id]);
            $this->flash('success', 'Aktivitas rutinitas bulanan berhasil dihapus.');
        }

        return $this->redirect($response, '/');
    }

    public function legacyAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = $this->body($request);
        $query = $request->getQueryParams();

        if (($body['action'] ?? '') === 'insert') {
            return $this->insert($request, $response);
        }

        if (($query['action'] ?? '') === 'delete') {
            return $this->delete($request, $response);
        }

        return $this->redirect($response, '/');
    }
}
