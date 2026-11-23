<?php

declare(strict_types=1);

namespace App\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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
        $tier = (string) ($body['tier'] ?? '');
        $identity = (string) ($body['identity'] ?? '');
        $idNo = self::TIER_MAP[$tier] ?? 99;

        $stmt = $this->pdo()->prepare(
            'INSERT INTO staff (tier, id_no, identity) VALUES (?, ?, ?)'
        );
        $stmt->execute([$tier, $idNo, $identity]);

        $this->flash('success', 'Data pengurus berhasil ditambahkan.');

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
            $stmt = $this->pdo()->prepare('DELETE FROM staff WHERE id = ?');
            $stmt->execute([$id]);
            $this->flash('success', 'Data pengurus berhasil dihapus.');
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
