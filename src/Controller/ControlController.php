<?php

declare(strict_types=1);

namespace App\Controller;

use App\Database\Connection;
use App\Service\DateTimeService;
use App\Service\FileUploadService;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\PhpRenderer;

final class ControlController extends AbstractController
{
    /** @var callable */
    private $shutdownRunner;

    public function __construct(
        Connection $db,
        PhpRenderer $view,
        string $basePath,
        private readonly FileUploadService $fileUploadService,
        private readonly DateTimeService $dateTimeService,
        ?callable $shutdownRunner = null
    ) {
        parent::__construct($db, $view, $basePath);
        $this->shutdownRunner = $shutdownRunner ?? static function (): void {
            system('shutdown -s -t 1');
        };
    }

    public function showIdentity(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $identities = $this->db->pdo()->query('SELECT * FROM identity')->fetchAll();

        return $this->render($response, 'control/identity.php', [
            'identities' => $identities,
        ]);
    }

    public function updateIdentity(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $pdo = $this->db->pdo();
        $row = $pdo->query('SELECT logo FROM identity LIMIT 1')->fetch();
        $oldLogo = $row !== false ? (string) ($row['logo'] ?? '') : '';

        $body = $this->body($request);
        $tittle = (string) ($body['tittle'] ?? '');
        $name = (string) ($body['name'] ?? '');
        $marquee = (string) ($body['marquee'] ?? '');
        $logo = $oldLogo;

        $files = $this->files($request);
        if (isset($files['logo']) && $files['logo']->getError() === UPLOAD_ERR_OK) {
            $logo = $this->fileUploadService->moveUploadedImage($files['logo'], 'Profil_');
            if ($oldLogo !== '' && $logo !== $oldLogo) {
                $this->fileUploadService->deletePicture($oldLogo);
            }
        }

        $stmt = $pdo->prepare('UPDATE identity SET logo = ?, tittle = ?, name = ?, marquee = ?');
        $stmt->execute([$logo, $tittle, $name, $marquee]);

        return $this->redirect($response, '/');
    }

    public function showBackground(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $row = $this->db->pdo()->query('SELECT background FROM identity WHERE id = 1 LIMIT 1')->fetch();
        $currentBackground = $row !== false ? (string) ($row['background'] ?? '') : '';

        return $this->render($response, 'control/background.php', [
            'currentBackground' => $currentBackground,
        ]);
    }

    public function updateBackground(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = $this->body($request);
        $bg = (string) ($body['background'] ?? '');

        $stmt = $this->db->pdo()->prepare('UPDATE identity SET background = ? WHERE id = 1');
        $stmt->execute([$bg]);

        return $this->redirect($response, '/');
    }

    public function showAudio(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->render($response, 'control/audio.php');
    }

    public function updateAudio(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $files = $this->files($request);
        if (!isset($files['audio_file']) || $files['audio_file']->getError() !== UPLOAD_ERR_OK) {
            return $this->redirect($response, '/control/audio');
        }

        $pdo = $this->db->pdo();
        $targetId = 1;

        $selectStmt = $pdo->prepare('SELECT audio FROM identity WHERE id = ? LIMIT 1');
        $selectStmt->execute([$targetId]);
        $row = $selectStmt->fetch();
        $oldAudio = $row !== false ? ($row['audio'] ?? null) : null;

        if ($oldAudio !== null && $oldAudio !== '') {
            $this->fileUploadService->deleteAudio((string) $oldAudio);
        }

        $filename = $this->fileUploadService->moveUploadedAudio($files['audio_file']);
        $prefix = $this->basePath !== '' ? $this->basePath : '';
        $dbPath = $prefix . '/assets/audio/' . $filename;

        $updateStmt = $pdo->prepare('UPDATE identity SET audio = ? WHERE id = ?');
        $updateStmt->execute([$dbPath, $targetId]);

        return $this->redirect($response, '/');
    }

    public function showShalatTime(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $stmt = $this->db->pdo()->prepare('SELECT * FROM shalattime WHERE id = ? LIMIT 1');
        $stmt->execute([1]);
        $row = $stmt->fetch();

        $data = $row !== false ? $row : [
            'subuh' => '',
            'dzuhur' => '',
            'ashar' => '',
            'maghrib' => '',
            'isya' => '',
            'jumat' => '',
        ];

        return $this->render($response, 'control/shalattime.php', [
            'data' => $data,
        ]);
    }

    public function updateShalatTime(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = $this->body($request);
        $data = $body['shalat'] ?? null;

        if (!is_array($data)) {
            return $this->redirect($response, '/');
        }

        $stmt = $this->db->pdo()->prepare(
            'UPDATE shalattime SET subuh = ?, dzuhur = ?, ashar = ?, maghrib = ?, isya = ?, jumat = ? WHERE id = ?'
        );
        $ok = $stmt->execute([
            (string) ($data['subuh'] ?? ''),
            (string) ($data['dzuhur'] ?? ''),
            (string) ($data['ashar'] ?? ''),
            (string) ($data['maghrib'] ?? ''),
            (string) ($data['isya'] ?? ''),
            (string) ($data['jumat'] ?? ''),
            1,
        ]);

        if ($ok) {
            return $this->alertAndRedirect($response, 'Jadwal shalat berhasil diperbarui!', '/');
        }

        return $this->html($response, "<script>alert('Gagal memperbarui jadwal.'); window.history.back();</script>");
    }

    public function showKhotbahDay(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $query = $request->getQueryParams();
        $id = isset($query['id']) ? (int) $query['id'] : 1;

        $pdo = $this->db->pdo();
        $stmt = $pdo->prepare('SELECT * FROM khotbah_view WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $khotbahView = $stmt->fetch();

        if ($khotbahView === false) {
            return $this->text($response, "Data dengan ID {$id} tidak ditemukan!", 404);
        }

        $speakers = $pdo->query("SELECT DISTINCT * FROM data_list WHERE identity != ''")->fetchAll();
        $fridays = $this->dateTimeService->upcomingFridays(8);

        return $this->render($response, 'control/khotbah_day.php', [
            'p' => $khotbahView,
            'speakers' => $speakers,
            'totalRows' => count($speakers),
            'fridays' => $fridays,
        ]);
    }

    public function updateKhotbahDay(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = $this->body($request);
        $id = (int) ($body['id'] ?? 1);
        $name = (string) ($body['speaker'] ?? '');
        $title = (string) ($body['title'] ?? '');

        $pdo = $this->db->pdo();
        $selectStmt = $pdo->prepare('SELECT avatar FROM khotbah_view WHERE id = ? LIMIT 1');
        $selectStmt->execute([$id]);
        $row = $selectStmt->fetch();
        $oldAvatar = $row !== false ? (string) ($row['avatar'] ?? '') : '';
        $avatar = $oldAvatar;

        $files = $this->files($request);
        if (isset($files['avatar']) && $files['avatar']->getError() === UPLOAD_ERR_OK) {
            try {
                $newAvatar = $this->fileUploadService->moveUploadedImage(
                    $files['avatar'],
                    'Avatar_',
                    ['png', 'jpg', 'jpeg']
                );
                if ($oldAvatar !== '' && $newAvatar !== $oldAvatar) {
                    $this->fileUploadService->deletePicture($oldAvatar);
                }
                $avatar = $newAvatar;
            } catch (InvalidArgumentException) {
                $avatar = $oldAvatar;
            }
        }

        $updateStmt = $pdo->prepare('UPDATE khotbah_view SET name = ?, title = ?, avatar = ? WHERE id = ?');
        $updateStmt->execute([$name, $title, $avatar, $id]);

        return $this->alertAndRedirect($response, 'Data berhasil diupdate', '/');
    }

    public function shutdown(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        ($this->shutdownRunner)();

        return $this->text($response, 'Shutdown initiated.', 200);
    }
}
