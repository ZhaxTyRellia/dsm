<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Kernel;
use App\Service\FileUploadService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\PhpRenderer;

final class BannerController extends AbstractController
{
    private const SLIDE_COLUMNS = [
        'pict1',
        'pict2',
        'pict3',
        'pict4',
        'pict5',
        'pict6',
        'pict7',
        'pict8',
        'pict9',
    ];

    public function __construct(
        Kernel $kernel,
        PhpRenderer $view,
        string $basePath,
        private readonly FileUploadService $fileUploadService
    ) {
        parent::__construct($kernel, $view, $basePath);
    }

    public function updateSlides(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->assertActivated($request);
        $body = $this->body($request);
        $val = (int) ($body['active_slides'] ?? 0);

        if ($val >= 1 && $val <= 9) {
            $pdo = $this->pdo();
            $current = $pdo->query('SELECT * FROM banner WHERE id = 1 LIMIT 1')->fetch();
            $oldLimit = (int) ($current['active_slides'] ?? 0);

            if ($current !== false && $val < $oldLimit) {
                for ($i = $val + 1; $i <= 9; $i++) {
                    $col = 'pict' . $i;
                    $oldFile = (string) ($current[$col] ?? '');
                    if ($oldFile !== '' && strtolower($oldFile) !== 'default.png') {
                        $this->fileUploadService->deletePicture($oldFile);
                    }

                    $resetStmt = $pdo->prepare("UPDATE banner SET {$col} = 'default.png' WHERE id = 1");
                    $resetStmt->execute();
                }
            }

            $stmt = $pdo->prepare('UPDATE banner SET active_slides = ? WHERE id = 1');
            $stmt->execute([$val]);
            $this->flash('success', "Jumlah slide banner aktif diatur ke {$val} slide.");
        }

        return $this->redirect($response, '/');
    }

    public function upload(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->assertActivated($request);
        $files = $this->files($request);
        $pdo = $this->pdo();

        foreach (self::SLIDE_COLUMNS as $target) {
            if (!isset($files[$target]) || $files[$target]->getError() !== UPLOAD_ERR_OK) {
                continue;
            }

            $selectStmt = $pdo->query("SELECT {$target} FROM banner WHERE id = 1 LIMIT 1");
            $row = $selectStmt->fetch();
            $oldFile = $row !== false ? (string) ($row[$target] ?? '') : '';

            $newName = $this->fileUploadService->moveUploadedImage($files[$target], 'image_');

            $updateStmt = $pdo->prepare("UPDATE banner SET {$target} = ? WHERE id = 1");
            $updateStmt->execute([$newName]);

            if ($oldFile !== '' && strtolower($oldFile) !== 'default.png') {
                $this->fileUploadService->deletePicture($oldFile);
            }

            $this->flash('success', 'Gambar banner berhasil diunggah.');

            return $this->redirect($response, '/');
        }

        return $this->redirect($response, '/');
    }

    public function legacyAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = $this->body($request);
        if (($body['action'] ?? '') === 'update_slides') {
            return $this->updateSlides($request, $response);
        }

        return $this->upload($request, $response);
    }
}
