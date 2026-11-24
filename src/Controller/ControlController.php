<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Kernel;
use App\Service\DateTimeService;
use App\Service\FileUploadService;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Slim\Views\PhpRenderer;
use Throwable;

final class ControlController extends AbstractController
{
    /** @var callable */
    private $shutdownRunner;

    public function __construct(
        Kernel $kernel,
        PhpRenderer $view,
        string $basePath,
        private readonly FileUploadService $fileUploadService,
        private readonly DateTimeService $dateTimeService,
        ?callable $shutdownRunner = null
    ) {
        parent::__construct($kernel, $view, $basePath);
        $this->shutdownRunner = $shutdownRunner ?? static function (): void {
            system('shutdown -s -t 1');
        };
    }

    public function showIdentity(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $identities = $this->pdo()->query('SELECT * FROM identity')->fetchAll();

        return $this->renderAdmin(
            $request,
            $response,
            'control/identity.php',
            ['identities' => $identities],
            'Masjid Profile',
            'control-identity'
        );
    }

    public function updateIdentity(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->assertActivated($request);
        $pdo = $this->pdo();
        $row = $pdo->query('SELECT logo FROM identity LIMIT 1')->fetch();
        $oldLogo = $row !== false ? (string) ($row['logo'] ?? '') : '';

        $body = $this->body($request);
        $tittle = trim((string) ($body['tittle'] ?? ''));
        $name = (string) ($body['name'] ?? '');
        $marquee = (string) ($body['marquee'] ?? '');
        $logo = $oldLogo;

        if ($tittle === '') {
            $this->flash('error', 'Masjid name cannot be empty.');

            return $this->redirect($response, '/');
        }

        $files = $this->files($request);
        if (isset($files['logo']) && $files['logo']->getError() !== UPLOAD_ERR_NO_FILE) {
            try {
                $logo = $this->fileUploadService->moveUploadedImage(
                    $files['logo'],
                    FileUploadService::CATEGORY_IDENTITY,
                    'Profil_'
                );
            } catch (InvalidArgumentException|RuntimeException $e) {
                $this->flash('error', $e->getMessage());

                return $this->redirect($response, '/');
            }
        }

        try {
            $stmt = $pdo->prepare('UPDATE identity SET logo = ?, tittle = ?, name = ?, marquee = ?');
            $stmt->execute([$logo, $tittle, $name, $marquee]);

            if ($oldLogo !== '' && $logo !== $oldLogo) {
                $this->fileUploadService->deleteImage(FileUploadService::CATEGORY_IDENTITY, $oldLogo);
            }

            $this->flash('success', 'Masjid profile updated successfully.');
        } catch (Throwable) {
            if ($logo !== $oldLogo) {
                $this->fileUploadService->deleteImage(FileUploadService::CATEGORY_IDENTITY, $logo);
            }
            $this->flash('error', 'Unable to save masjid profile.');
        }

        return $this->redirect($response, '/');
    }

    public function showBackground(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $row = $this->pdo()->query('SELECT background FROM identity WHERE id = 1 LIMIT 1')->fetch();
        $currentBackground = $row !== false ? (string) ($row['background'] ?? '') : '';

        return $this->renderAdmin(
            $request,
            $response,
            'control/background.php',
            ['currentBackground' => $currentBackground],
            'Display Background',
            'control-background'
        );
    }

    public function updateBackground(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->assertActivated($request);
        $body = $this->body($request);
        $bg = trim((string) ($body['background'] ?? ''));
        $allowedBackgrounds = ['bg1.png', 'bg2.jpg', 'bg3.jpeg', 'bg4.jpg', 'bg5.jpg', 'bg6.jpg'];

        if (!in_array($bg, $allowedBackgrounds, true)) {
            $this->flash('error', 'Please choose a valid display background.');

            return $this->redirect($response, '/');
        }

        try {
            $stmt = $this->pdo()->prepare('UPDATE identity SET background = ? WHERE id = 1');
            $stmt->execute([$bg]);

            $this->flash('success', 'Display background updated successfully.');
        } catch (Throwable) {
            $this->flash('error', 'Unable to save display background.');
        }

        return $this->redirect($response, '/');
    }

    public function showAudio(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->renderAdmin(
            $request,
            $response,
            'control/audio.php',
            [],
            'Background Music',
            'control-audio'
        );
    }

    public function updateAudio(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->assertActivated($request);
        $files = $this->files($request);
        if (!isset($files['audio_file']) || $files['audio_file']->getError() !== UPLOAD_ERR_OK) {
            $this->flash('error', 'Please choose an audio file first.');

            return $this->redirect($response, '/');
        }

        $pdo = $this->pdo();
        $targetId = 1;

        $selectStmt = $pdo->prepare('SELECT audio FROM identity WHERE id = ? LIMIT 1');
        $selectStmt->execute([$targetId]);
        $row = $selectStmt->fetch();
        $oldAudio = $row !== false ? ($row['audio'] ?? null) : null;

        try {
            $filename = $this->fileUploadService->moveUploadedAudio($files['audio_file']);
        } catch (RuntimeException $e) {
            $this->flash('error', $e->getMessage());

            return $this->redirect($response, '/');
        }

        $relativeDbPath = 'uploads/audio/' . $filename;

        try {
            $updateStmt = $pdo->prepare('UPDATE identity SET audio = ? WHERE id = ?');
            $updateStmt->execute([$relativeDbPath, $targetId]);

            if ($oldAudio !== null && $oldAudio !== '') {
                $this->fileUploadService->deleteAudio((string) $oldAudio);
            }

            $this->flash('success', 'Background audio updated successfully.');
        } catch (Throwable) {
            $this->fileUploadService->deleteAudio($relativeDbPath);
            $this->flash('error', 'Unable to save background audio.');
        }

        return $this->redirect($response, '/');
    }

    public function showShalatTime(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM shalattime WHERE id = ? LIMIT 1');
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

        return $this->renderAdmin(
            $request,
            $response,
            'control/shalattime.php',
            ['data' => $data],
            'Jadwal Waktu Shalat',
            'control-shalat'
        );
    }

    public function updateShalatTime(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->assertActivated($request);
        $body = $this->body($request);
        $data = $body['shalat'] ?? null;

        if (!is_array($data)) {
            $this->flash('error', 'Please fill in all prayer times.');

            return $this->redirect($response, '/');
        }

        try {
            $stmt = $this->pdo()->prepare(
                'UPDATE shalattime SET subuh = ?, dzuhur = ?, ashar = ?, maghrib = ?, isya = ?, jumat = ? WHERE id = ?'
            );
            $stmt->execute([
                (string) ($data['subuh'] ?? ''),
                (string) ($data['dzuhur'] ?? ''),
                (string) ($data['ashar'] ?? ''),
                (string) ($data['maghrib'] ?? ''),
                (string) ($data['isya'] ?? ''),
                (string) ($data['jumat'] ?? ''),
                1,
            ]);

            $this->flash('success', 'Prayer times updated successfully.');
        } catch (Throwable) {
            $this->flash('error', 'Unable to save prayer times.');
        }

        return $this->redirect($response, '/');
    }

    public function showKhotbahDay(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->assertActivated($request);
        $query = $request->getQueryParams();
        $id = isset($query['id']) ? (int) $query['id'] : 1;

        $pdo = $this->pdo();
        $stmt = $pdo->prepare('SELECT * FROM khotbah_view WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $khotbahView = $stmt->fetch();

        if ($khotbahView === false) {
            $this->flash('error', 'The requested Khotbah banner entry was not found.');

            return $this->redirect($response, '/');
        }

        $speakers = $pdo->query("SELECT DISTINCT * FROM data_list WHERE identity != ''")->fetchAll();
        $fridays = $this->dateTimeService->upcomingFridays(8);

        return $this->renderAdmin(
            $request,
            $response,
            'control/khotbah_day.php',
            [
                'p' => $khotbahView,
                'speakers' => $speakers,
                'totalRows' => count($speakers),
                'fridays' => $fridays,
            ],
            'Banner Khotbah Jum\'at',
            'control-khotbah'
        );
    }

    public function updateKhotbahDay(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->assertActivated($request);
        $body = $this->body($request);
        $id = (int) ($body['id'] ?? 1);
        $name = trim((string) ($body['speaker'] ?? ''));
        $title = trim((string) ($body['title'] ?? ''));

        $pdo = $this->pdo();
        $selectStmt = $pdo->prepare('SELECT avatar FROM khotbah_view WHERE id = ? LIMIT 1');
        $selectStmt->execute([$id]);
        $row = $selectStmt->fetch();
        $oldAvatar = $row !== false ? (string) ($row['avatar'] ?? '') : '';
        $avatar = $oldAvatar;

        $files = $this->files($request);
        if (isset($files['avatar']) && $files['avatar']->getError() !== UPLOAD_ERR_NO_FILE) {
            try {
                $avatar = $this->fileUploadService->moveUploadedImage(
                    $files['avatar'],
                    FileUploadService::CATEGORY_KHOTBAH,
                    'Avatar_',
                    ['png', 'jpg', 'jpeg']
                );
            } catch (InvalidArgumentException|RuntimeException $e) {
                $this->flash('error', $e->getMessage());

                return $this->redirect($response, '/');
            }
        }

        try {
            $updateStmt = $pdo->prepare('UPDATE khotbah_view SET name = ?, title = ?, avatar = ? WHERE id = ?');
            $updateStmt->execute([$name, $title, $avatar, $id]);

            if ($oldAvatar !== '' && $avatar !== $oldAvatar) {
                $this->fileUploadService->deleteImage(FileUploadService::CATEGORY_KHOTBAH, $oldAvatar);
            }

            $this->flash('success', 'Banner Khotbah Jum\'at updated successfully.');
        } catch (Throwable) {
            if ($avatar !== $oldAvatar) {
                $this->fileUploadService->deleteImage(FileUploadService::CATEGORY_KHOTBAH, $avatar);
            }
            $this->flash('error', 'Unable to update Banner Khotbah Jum\'at.');
        }

        return $this->redirect($response, '/');
    }

    public function factoryReset(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->assertActivated($request);
        try {
            $this->kernel->factoryReset($this->fileUploadService, false);
            $this->flash('success', 'All settings have been restored to their defaults.');
        } catch (Throwable) {
            $this->flash('error', 'Unable to restore default settings.');
        }

        return $this->redirect($response, '/');
    }

    public function shutdown(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->assertActivated($request);
        ($this->shutdownRunner)();

        return $this->text($response, 'Shutting down computer.', 200);
    }
}
