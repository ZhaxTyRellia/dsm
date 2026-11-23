<?php

declare(strict_types=1);

namespace App\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class KhotbahListController extends AbstractController
{
    public function insert(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->assertActivated($request);
        $body = $this->body($request);
        $moon = (string) ($body['moon'] ?? '');
        $date = (string) ($body['date'] ?? '');
        $identity = (string) ($body['identity'] ?? '');

        $stmt = $this->pdo()->prepare(
            'INSERT INTO data_list (moon, `date`, identity) VALUES (?, ?, ?)'
        );
        $stmt->execute([$moon, $date, $identity]);

        $this->flash('success', 'Jadwal khotbah Jum\'at berhasil ditambahkan.');

        return $this->redirect($response, '/');
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->assertActivated($request);
        $body = $this->body($request);
        $id = (int) ($body['id'] ?? 0);
        $moon = (string) ($body['moon'] ?? '');
        $date = (string) ($body['date'] ?? '');
        $identity = (string) ($body['identity'] ?? '');

        $stmt = $this->pdo()->prepare(
            'UPDATE data_list SET moon = ?, `date` = ?, identity = ? WHERE id = ?'
        );
        $stmt->execute([$moon, $date, $identity, $id]);

        $this->flash('success', 'Jadwal khotbah Jum\'at berhasil diperbarui.');

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
            $stmt = $this->pdo()->prepare('DELETE FROM data_list WHERE id = ?');
            $stmt->execute([$id]);
            $this->flash('success', 'Jadwal khotbah Jum\'at berhasil dihapus.');
        }

        return $this->redirect($response, '/');
    }

    public function legacyAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = $this->body($request);
        $query = $request->getQueryParams();
        $postAction = (string) ($body['action'] ?? '');

        if ($postAction === 'insert') {
            return $this->insert($request, $response);
        }

        if ($postAction === 'update') {
            return $this->update($request, $response);
        }

        if (($query['action'] ?? '') === 'delete') {
            return $this->delete($request, $response);
        }

        return $this->redirect($response, '/');
    }
}
