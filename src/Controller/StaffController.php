<?php

declare(strict_types=1);

namespace App\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final class StaffController extends AbstractController
{
    public const TIER_MAP = [
        'PENASEHAT' => 1,
        'KETUA' => 2,
        'WAKIL KETUA' => 3,
        'SEKRETARIS' => 4,
        'BENDAHARA' => 5,
        'HUMAS, SOSIAL & PHBI' => 6,
        'PERIBADATAN/DAKWAH' => 7,
        'PENDIDIKAN' => 8,
        'REMAJA MASJID' => 9,
        'PEMBANGUNAN & PEMELIHARAAN KEBERSIHAN' => 10,
        'KELISTRIKAN DAN TEKNIS' => 11,
        'KEAMANAN' => 12,
        'PERGUDANGAN' => 13,
    ];

    public function insert(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->assertActivated($request);
        $body = $this->body($request);
        $tier = trim((string) ($body['tier'] ?? ''));
        $identity = trim((string) ($body['identity'] ?? ''));

        if ($tier === '' || $identity === '') {
            $this->flash('error', 'Please choose a position and enter the full name.');

            return $this->redirect($response, '/');
        }

        $idNo = self::TIER_MAP[$tier] ?? 99;

        try {
            $stmt = $this->pdo()->prepare(
                'INSERT INTO staff (tier, id_no, identity) VALUES (?, ?, ?)'
            );
            $stmt->execute([$tier, $idNo, $identity]);

            $this->flash('success', 'Committee member added successfully.');
        } catch (Throwable) {
            $this->flash('error', 'Unable to add committee member.');
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
            $this->flash('error', 'Committee member not found.');

            return $this->redirect($response, '/');
        }

        try {
            $stmt = $this->pdo()->prepare('DELETE FROM staff WHERE id = ?');
            $stmt->execute([$id]);
            if ($stmt->rowCount() > 0) {
                $this->flash('success', 'Committee member removed.');
            } else {
                $this->flash('error', 'Committee member not found.');
            }
        } catch (Throwable) {
            $this->flash('error', 'Unable to remove committee member.');
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
