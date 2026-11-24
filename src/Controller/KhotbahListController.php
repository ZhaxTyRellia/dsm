<?php

declare(strict_types=1);

namespace App\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final class KhotbahListController extends AbstractController
{
    public function insert(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->assertActivated($request);
        $body = $this->body($request);
        $moon = trim((string) ($body['moon'] ?? ''));
        $date = trim((string) ($body['date'] ?? ''));
        $identity = trim((string) ($body['identity'] ?? ''));

        if ($moon === '' || $date === '' || $identity === '') {
            $this->flash('error', 'Please complete all Friday sermon schedule fields.');

            return $this->redirect($response, '/');
        }

        try {
            $stmt = $this->pdo()->prepare(
                'INSERT INTO data_list (moon, `date`, identity) VALUES (?, ?, ?)'
            );
            $stmt->execute([$moon, $date, $identity]);

            $this->flash('success', 'Friday sermon schedule added successfully.');
        } catch (Throwable) {
            $this->flash('error', 'Unable to add Friday sermon schedule.');
        }

        return $this->redirect($response, '/');
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->assertActivated($request);
        $body = $this->body($request);
        $id = (int) ($body['id'] ?? 0);
        $moon = trim((string) ($body['moon'] ?? ''));
        $date = trim((string) ($body['date'] ?? ''));
        $identity = trim((string) ($body['identity'] ?? ''));

        if ($id <= 0 || $moon === '' || $date === '' || $identity === '') {
            $this->flash('error', 'Please complete all Friday sermon schedule fields.');

            return $this->redirect($response, '/');
        }

        try {
            $pdo = $this->pdo();
            $check = $pdo->prepare('SELECT COUNT(*) FROM data_list WHERE id = ?');
            $check->execute([$id]);
            if ((int) $check->fetchColumn() === 0) {
                $this->flash('error', 'Friday sermon schedule not found.');

                return $this->redirect($response, '/');
            }

            $stmt = $pdo->prepare(
                'UPDATE data_list SET moon = ?, `date` = ?, identity = ? WHERE id = ?'
            );
            $stmt->execute([$moon, $date, $identity, $id]);

            $this->flash('success', 'Friday sermon schedule updated successfully.');
        } catch (Throwable) {
            $this->flash('error', 'Unable to update Friday sermon schedule.');
        }

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

        if ($id <= 0) {
            $this->flash('error', 'Friday sermon schedule not found.');

            return $this->redirect($response, '/');
        }

        try {
            $stmt = $this->pdo()->prepare('DELETE FROM data_list WHERE id = ?');
            $stmt->execute([$id]);
            if ($stmt->rowCount() > 0) {
                $this->flash('success', 'Friday sermon schedule removed.');
            } else {
                $this->flash('error', 'Friday sermon schedule not found.');
            }
        } catch (Throwable) {
            $this->flash('error', 'Unable to remove Friday sermon schedule.');
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
