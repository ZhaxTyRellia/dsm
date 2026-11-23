<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Kernel;
use App\Service\FileUploadService;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\PhpRenderer;

final class RawatibController extends AbstractController
{
    public function __construct(
        Kernel $kernel,
        PhpRenderer $view,
        string $basePath,
        private readonly FileUploadService $fileUploadService
    ) {
        parent::__construct($kernel, $view, $basePath);
    }

    public function insert(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->assertActivated($request);
        $body = $this->body($request);
        $dataShalat = (string) ($body['data_shalat'] ?? '');
        $hari = (string) ($body['hari'] ?? '');
        $name = (string) ($body['name'] ?? '');

        if ($this->rawatibExists($dataShalat, $hari, $name)) {
            return $this->text(
                $response,
                'Data imam untuk kategori salat, hari, dan nama tersebut sudah ada.',
                409
            );
        }

        $files = $this->files($request);
        $profileFilename = null;

        if (isset($files['profile']) && $files['profile']->getError() === UPLOAD_ERR_OK) {
            $profileFilename = $this->fileUploadService->moveUploadedImage(
                $files['profile'],
                FileUploadService::CATEGORY_RAWATIB,
                'avatar_',
                null,
                true
            );
        }

        $stmt = $this->pdo()->prepare(
            'INSERT INTO data_rawatib (data_shalat, hari, name, profile) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$dataShalat, $hari, $name, $profileFilename]);

        $this->flash('success', 'Data imam rawatib berhasil ditambahkan.');

        return $this->redirect($response, '/');
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->assertActivated($request);
        $body = $this->body($request);
        $id = (int) ($body['id'] ?? 0);
        $dataShalat = (string) ($body['data_shalat'] ?? '');
        $hari = (string) ($body['hari'] ?? '');
        $name = (string) ($body['name'] ?? '');

        if ($this->rawatibExists($dataShalat, $hari, $name, $id)) {
            return $this->text(
                $response,
                'Data imam untuk kategori salat, hari, dan nama tersebut sudah ada.',
                409
            );
        }

        $pdo = $this->pdo();
        $stmtSelect = $pdo->prepare('SELECT profile FROM data_rawatib WHERE id = ? LIMIT 1');
        $stmtSelect->execute([$id]);
        $currentData = $stmtSelect->fetch();

        if ($currentData === false) {
            return $this->text($response, 'Data imam tidak ditemukan.', 404);
        }

        $oldProfile = $currentData['profile'] !== null ? (string) $currentData['profile'] : null;
        $profileFilename = $oldProfile;

        $files = $this->files($request);
        if (isset($files['profile']) && $files['profile']->getError() === UPLOAD_ERR_OK) {
            try {
                $profileFilename = $this->fileUploadService->moveUploadedImage(
                    $files['profile'],
                    FileUploadService::CATEGORY_RAWATIB,
                    'avatar_',
                    ['jpg', 'jpeg', 'png'],
                    true
                );
            } catch (InvalidArgumentException $e) {
                return $this->text($response, $e->getMessage(), 422);
            }
        }

        $stmtUpdate = $pdo->prepare(
            'UPDATE data_rawatib SET data_shalat = ?, hari = ?, name = ?, profile = ? WHERE id = ?'
        );
        $stmtUpdate->execute([$dataShalat, $hari, $name, $profileFilename, $id]);

        if ($profileFilename !== $oldProfile && $oldProfile !== null && $oldProfile !== '') {
            $this->fileUploadService->deleteImage(FileUploadService::CATEGORY_RAWATIB, $oldProfile);
        }

        $this->flash('success', 'Data imam rawatib berhasil diperbarui.');

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
            $pdo = $this->pdo();
            $stmtSelect = $pdo->prepare('SELECT profile FROM data_rawatib WHERE id = ? LIMIT 1');
            $stmtSelect->execute([$id]);
            $row = $stmtSelect->fetch();

            if ($row !== false && !empty($row['profile'])) {
                $this->fileUploadService->deleteImage(FileUploadService::CATEGORY_RAWATIB, (string) $row['profile']);
            }

            $stmtDelete = $pdo->prepare('DELETE FROM data_rawatib WHERE id = ?');
            $stmtDelete->execute([$id]);
            $this->flash('success', 'Data imam rawatib berhasil dihapus.');
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

    private function rawatibExists(string $dataShalat, string $hari, string $name, int $excludedId = 0): bool
    {
        $stmt = $this->pdo()->prepare(
            'SELECT id FROM data_rawatib WHERE data_shalat = ? AND hari = ? AND name = ? AND id != ? LIMIT 1'
        );
        $stmt->execute([$dataShalat, $hari, $name, $excludedId]);

        return $stmt->fetch() !== false;
    }
}
