<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Kernel;
use App\Service\FileUploadService;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Slim\Views\PhpRenderer;
use Throwable;

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
        $dataShalat = trim((string) ($body['data_shalat'] ?? ''));
        $hari = trim((string) ($body['hari'] ?? ''));
        $name = trim((string) ($body['name'] ?? ''));

        if ($dataShalat === '' || $hari === '' || $name === '') {
            $this->flash('error', 'Please complete all required imam fields.');

            return $this->redirect($response, '/');
        }

        if ($this->rawatibExists($dataShalat, $hari, $name)) {
            $this->flash('error', 'An Imam Rawatib entry with this prayer, day, and name already exists.');

            return $this->redirect($response, '/');
        }

        $files = $this->files($request);
        $profileFilename = null;

        if (isset($files['profile']) && $files['profile']->getError() !== UPLOAD_ERR_NO_FILE) {
            try {
                $profileFilename = $this->fileUploadService->moveUploadedImage(
                    $files['profile'],
                    FileUploadService::CATEGORY_RAWATIB,
                    'avatar_',
                    ['jpg', 'jpeg', 'png'],
                    true
                );
            } catch (InvalidArgumentException|RuntimeException $e) {
                $this->flash('error', $e->getMessage());

                return $this->redirect($response, '/');
            }
        }

        try {
            $stmt = $this->pdo()->prepare(
                'INSERT INTO data_rawatib (data_shalat, hari, name, profile) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$dataShalat, $hari, $name, $profileFilename]);
            $this->flash('success', 'Imam Rawatib added successfully.');
        } catch (Throwable) {
            if ($profileFilename !== null) {
                $this->fileUploadService->deleteImage(FileUploadService::CATEGORY_RAWATIB, $profileFilename);
            }
            $this->flash('error', 'Unable to save Imam Rawatib.');
        }

        return $this->redirect($response, '/');
    }

    public function update(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->assertActivated($request);
        $body = $this->body($request);
        $id = (int) ($body['id'] ?? 0);
        $dataShalat = trim((string) ($body['data_shalat'] ?? ''));
        $hari = trim((string) ($body['hari'] ?? ''));
        $name = trim((string) ($body['name'] ?? ''));

        if ($id <= 0 || $dataShalat === '' || $hari === '' || $name === '') {
            $this->flash('error', 'Please complete all required imam fields.');

            return $this->redirect($response, '/');
        }

        if ($this->rawatibExists($dataShalat, $hari, $name, $id)) {
            $this->flash('error', 'An Imam Rawatib entry with this prayer, day, and name already exists.');

            return $this->redirect($response, '/');
        }

        $pdo = $this->pdo();
        $stmtSelect = $pdo->prepare('SELECT profile FROM data_rawatib WHERE id = ? LIMIT 1');
        $stmtSelect->execute([$id]);
        $currentData = $stmtSelect->fetch();

        if ($currentData === false) {
            $this->flash('error', 'Imam record not found.');

            return $this->redirect($response, '/');
        }

        $oldProfile = $currentData['profile'] !== null ? (string) $currentData['profile'] : null;
        $profileFilename = $oldProfile;

        $files = $this->files($request);
        if (isset($files['profile']) && $files['profile']->getError() !== UPLOAD_ERR_NO_FILE) {
            try {
                $profileFilename = $this->fileUploadService->moveUploadedImage(
                    $files['profile'],
                    FileUploadService::CATEGORY_RAWATIB,
                    'avatar_',
                    ['jpg', 'jpeg', 'png'],
                    true
                );
            } catch (InvalidArgumentException|RuntimeException $e) {
                $this->flash('error', $e->getMessage());

                return $this->redirect($response, '/');
            }
        }

        try {
            $stmtUpdate = $pdo->prepare(
                'UPDATE data_rawatib SET data_shalat = ?, hari = ?, name = ?, profile = ? WHERE id = ?'
            );
            $stmtUpdate->execute([$dataShalat, $hari, $name, $profileFilename, $id]);

            if ($profileFilename !== $oldProfile && $oldProfile !== null && $oldProfile !== '') {
                $this->fileUploadService->deleteImage(FileUploadService::CATEGORY_RAWATIB, $oldProfile);
            }

            $this->flash('success', 'Imam Rawatib updated successfully.');
        } catch (Throwable) {
            if ($profileFilename !== $oldProfile && $profileFilename !== null) {
                $this->fileUploadService->deleteImage(FileUploadService::CATEGORY_RAWATIB, $profileFilename);
            }
            $this->flash('error', 'Unable to update Imam Rawatib.');
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
            $this->flash('error', 'Imam record not found.');

            return $this->redirect($response, '/');
        }

        try {
            $pdo = $this->pdo();
            $stmtSelect = $pdo->prepare('SELECT profile FROM data_rawatib WHERE id = ? LIMIT 1');
            $stmtSelect->execute([$id]);
            $row = $stmtSelect->fetch();

            if ($row === false) {
                $this->flash('error', 'Imam record not found.');

                return $this->redirect($response, '/');
            }

            if (!empty($row['profile'])) {
                $this->fileUploadService->deleteImage(FileUploadService::CATEGORY_RAWATIB, (string) $row['profile']);
            }

            $stmtDelete = $pdo->prepare('DELETE FROM data_rawatib WHERE id = ?');
            $stmtDelete->execute([$id]);
            $this->flash('success', 'Imam Rawatib removed.');
        } catch (Throwable) {
            $this->flash('error', 'Unable to remove Imam Rawatib.');
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
