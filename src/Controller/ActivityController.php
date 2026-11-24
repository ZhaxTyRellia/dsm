<?php

declare(strict_types=1);

namespace App\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final class ActivityController extends AbstractController
{
    private const TOPICS = [
        1 => 'KAJIAN HADITS "BULUGHUL MAROM"',
        2 => 'KAJIAN UMUM "TASAWUF"',
        3 => "KHOTMIL QUR'AN",
        4 => 'KAJIAN HADITS "AHKAM"',
        5 => 'KAJIAN FIQIH "SAFINATUN NAJAH"',
        6 => 'BACA SOROGAN KITAB KUNING',
        7 => 'SHOLAT HAJAT, TASBIH & ISTIGHOSAH',
        8 => "TARTIL QUR'AN",
        9 => "SHOLAWAT DIBA'",
        10 => 'KAJIAN UMUM "IBADAH"',
        11 => 'KAJIAN HADITS "AHKAM"',
        12 => 'KAJIAN IBADAH "IRSYADUL IBAD"',
        13 => 'KAJIAN FIQIH KONTEMPORER',
        14 => "KAJIAN AL QUR'AN",
        15 => 'KEGIATAN BANSOS',
        16 => "PENGAJIAN MAJLIS TA'LIM AN-NISA",
        17 => 'KHITANAN MASSAL',
    ];

    public function insert(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->assertActivated($request);
        $body = $this->body($request);
        $idDesc = (int) ($body['id_desc'] ?? 0);
        $desc = trim((string) ($body['desc'] ?? ''));
        $time = trim((string) ($body['time'] ?? ''));
        $speaker = trim((string) ($body['speaker'] ?? ''));

        if ($desc === '' && isset(self::TOPICS[$idDesc])) {
            $desc = self::TOPICS[$idDesc];
        }

        if ($desc === '' || $time === '' || $speaker === '') {
            $this->flash('error', 'Please complete all activity fields.');

            return $this->redirect($response, '/');
        }

        try {
            $stmt = $this->pdo()->prepare(
                'INSERT INTO monthly_planner (id_desc, `desc`, `time`, speaker) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$idDesc, $desc, $time, $speaker]);

            $this->flash('success', 'Monthly activity added successfully.');
        } catch (Throwable) {
            $this->flash('error', 'Unable to add monthly activity.');
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
            $this->flash('error', 'Monthly activity not found.');

            return $this->redirect($response, '/');
        }

        try {
            $stmt = $this->pdo()->prepare('DELETE FROM monthly_planner WHERE id = ?');
            $stmt->execute([$id]);
            if ($stmt->rowCount() > 0) {
                $this->flash('success', 'Monthly activity removed.');
            } else {
                $this->flash('error', 'Monthly activity not found.');
            }
        } catch (Throwable) {
            $this->flash('error', 'Unable to remove monthly activity.');
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
