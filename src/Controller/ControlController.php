<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Kernel;
use App\Service\DateTimeService;
use App\Service\FileUploadService;
use App\Support\DisplaySlideConfig;
use App\Support\DisplayThemeCatalog;
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
        $row = $this->pdo()->query('SELECT background, display_mode, slide_settings FROM identity WHERE id = 1 LIMIT 1')->fetch();
        $currentBackground = DisplayThemeCatalog::normalize($row !== false ? ($row['background'] ?? null) : null);
        $currentDisplayMode = strtolower(trim((string) ($row !== false ? ($row['display_mode'] ?? 'auto') : 'auto')));
        if (!in_array($currentDisplayMode, ['auto', 'portrait', 'landscape'], true)) {
            $currentDisplayMode = 'auto';
        }

        return $this->renderAdmin(
            $request,
            $response,
            'control/background.php',
            [
                'themes' => DisplayThemeCatalog::all(),
                'currentBackground' => $currentBackground,
                'currentDisplayMode' => $currentDisplayMode,
                'slideConfig' => DisplaySlideConfig::parse($row !== false ? ($row['slide_settings'] ?? null) : null),
                'slideDefinitions' => DisplaySlideConfig::definitions(),
            ],
            'Display Theme',
            'control-background'
        );
    }

    public function updateBackground(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->assertActivated($request);
        $body = $this->body($request);
        $bg = trim((string) ($body['background'] ?? ''));
        $mode = strtolower(trim((string) ($body['display_mode'] ?? 'auto')));
        $allowedModes = ['auto', 'portrait', 'landscape'];

        if (!DisplayThemeCatalog::isValidFile($bg)) {
            $this->flash('error', 'Please choose a valid display theme.');

            return $this->redirect($response, '/');
        }

        if (!in_array($mode, $allowedModes, true)) {
            $this->flash('error', 'Please choose a valid display orientation.');

            return $this->redirect($response, '/');
        }

        try {
            $stmt = $this->pdo()->prepare('UPDATE identity SET background = ?, display_mode = ? WHERE id = 1');
            $stmt->execute([$bg, $mode]);

            $this->flash('success', 'Display theme and orientation updated successfully.');
        } catch (Throwable) {
            $this->flash('error', 'Unable to save display theme.');
        }

        return $this->redirect($response, '/');
    }

    public function updateSlides(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->assertActivated($request);
        $body = $this->body($request);
        $rawSlides = isset($body['slides']) && is_array($body['slides']) ? $body['slides'] : [];

        try {
            $parsed = DisplaySlideConfig::fromFormInput($rawSlides);
            $encoded = (string) json_encode($parsed, JSON_UNESCAPED_UNICODE);

            $stmt = $this->pdo()->prepare('UPDATE identity SET slide_settings = ? WHERE id = 1');
            $stmt->execute([$encoded]);

            $this->flash('success', 'Slide rotation settings updated successfully.');
        } catch (InvalidArgumentException $e) {
            $this->flash('error', $e->getMessage());
        } catch (Throwable) {
            $this->flash('error', 'Unable to save slide rotation settings.');
        }

        return $this->redirect($response, '/');
    }

    public function showAudio(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $row = $this->pdo()->query('SELECT audio, audio_enabled FROM identity WHERE id = 1 LIMIT 1')->fetch();
        $audioEnabled = (int) ($row !== false ? ($row['audio_enabled'] ?? 0) : 0) === 1;
        $currentAudioPath = ltrim((string) ($row !== false ? ($row['audio'] ?? 'uploads/audio/audio.mp3') : 'uploads/audio/audio.mp3'), '/');

        return $this->renderAdmin(
            $request,
            $response,
            'control/audio.php',
            [
                'audioEnabled' => $audioEnabled,
                'currentAudioPath' => $currentAudioPath,
            ],
            'Background Music',
            'control-audio'
        );
    }

    public function toggleAudio(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->assertActivated($request);
        $body = $this->body($request);
        $enabled = ((string) ($body['audio_enabled'] ?? '0')) === '1' ? 1 : 0;

        try {
            $stmt = $this->pdo()->prepare('UPDATE identity SET audio_enabled = ? WHERE id = 1');
            $stmt->execute([$enabled]);

            return $this->json($response, [
                'success' => true,
                'audio_enabled' => $enabled === 1,
                'message' => $enabled === 1 ? 'Background music turned on.' : 'Background music turned off.',
            ]);
        } catch (Throwable) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Unable to update background music status.',
            ], 500);
        }
    }

    public function updateAudio(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->assertActivated($request);
        $body = $this->body($request);
        $files = $this->files($request);
        $hasEnabledParam = array_key_exists('audio_enabled', $body);
        $enabledValue = $hasEnabledParam ? (((string) ($body['audio_enabled'] ?? '0')) === '1' ? 1 : 0) : null;

        $uploadedFile = $files['audio_file'] ?? null;
        $hasUploadedFile = $uploadedFile !== null && $uploadedFile->getError() !== UPLOAD_ERR_NO_FILE;

        if (!$hasUploadedFile) {
            if ($enabledValue !== null) {
                try {
                    $stmt = $this->pdo()->prepare('UPDATE identity SET audio_enabled = ? WHERE id = 1');
                    $stmt->execute([$enabledValue]);
                    $this->flash(
                        'success',
                        $enabledValue === 1 ? 'Background music turned on.' : 'Background music turned off.'
                    );
                } catch (Throwable) {
                    $this->flash('error', 'Unable to save background music settings.');
                }

                return $this->redirect($response, '/');
            }

            $this->flash('error', 'Please choose an audio file first.');

            return $this->redirect($response, '/');
        }

        if ($uploadedFile->getError() !== UPLOAD_ERR_OK) {
            $this->flash('error', 'Unable to upload the audio file.');

            return $this->redirect($response, '/');
        }

        $pdo = $this->pdo();
        $targetId = 1;

        $selectStmt = $pdo->prepare('SELECT audio FROM identity WHERE id = ? LIMIT 1');
        $selectStmt->execute([$targetId]);
        $row = $selectStmt->fetch();
        $oldAudio = $row !== false ? ($row['audio'] ?? null) : null;

        try {
            $filename = $this->fileUploadService->moveUploadedAudio($uploadedFile);
        } catch (RuntimeException $e) {
            $this->flash('error', $e->getMessage());

            return $this->redirect($response, '/');
        }

        $relativeDbPath = 'uploads/audio/' . $filename;

        try {
            if ($enabledValue !== null) {
                $updateStmt = $pdo->prepare('UPDATE identity SET audio = ?, audio_enabled = ? WHERE id = ?');
                $updateStmt->execute([$relativeDbPath, $enabledValue, $targetId]);
            } else {
                $updateStmt = $pdo->prepare('UPDATE identity SET audio = ? WHERE id = ?');
                $updateStmt->execute([$relativeDbPath, $targetId]);
            }

            if ($oldAudio !== null && $oldAudio !== '') {
                $this->fileUploadService->deleteAudio((string) $oldAudio);
            }

            $this->flash('success', 'Background music updated successfully.');
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

        $normalizeTime = static function (string $raw): ?string {
            $clean = trim($raw);
            if (preg_match('/^([01]\d|2[0-3]):([0-5]\d)(?::([0-5]\d))?$/', $clean, $m) !== 1) {
                return null;
            }

            return $m[1] . ':' . $m[2] . ':' . ($m[3] ?? '00');
        };

        $subuh = $normalizeTime((string) ($data['subuh'] ?? ''));
        $dzuhur = $normalizeTime((string) ($data['dzuhur'] ?? ''));
        $ashar = $normalizeTime((string) ($data['ashar'] ?? ''));
        $maghrib = $normalizeTime((string) ($data['maghrib'] ?? ''));
        $isya = $normalizeTime((string) ($data['isya'] ?? ''));
        $jumat = $normalizeTime((string) ($data['jumat'] ?? ''));

        if ($subuh === null || $dzuhur === null || $ashar === null || $maghrib === null || $isya === null || $jumat === null) {
            $this->flash('error', 'Please enter valid 24-hour prayer times.');

            return $this->redirect($response, '/');
        }

        try {
            $pdo = $this->pdo();
            $exists = (int) $pdo->query('SELECT COUNT(*) FROM shalattime WHERE id = 1')->fetchColumn() > 0;
            if ($exists) {
                $stmt = $pdo->prepare(
                    'UPDATE shalattime SET subuh = ?, dzuhur = ?, ashar = ?, maghrib = ?, isya = ?, jumat = ? WHERE id = ?'
                );
                $stmt->execute([$subuh, $dzuhur, $ashar, $maghrib, $isya, $jumat, 1]);
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO shalattime (id, subuh, dzuhur, ashar, maghrib, isya, jumat) VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([1, $subuh, $dzuhur, $ashar, $maghrib, $isya, $jumat]);
            }

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
            $khotbahView = [
                'id' => $id,
                'title' => '',
                'avatar' => 'default.png',
                'name' => '',
            ];
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
            if ($row !== false) {
                $updateStmt = $pdo->prepare('UPDATE khotbah_view SET name = ?, title = ?, avatar = ? WHERE id = ?');
                $updateStmt->execute([$name, $title, $avatar, $id]);
            } else {
                $saveAvatar = $avatar !== '' ? $avatar : 'default.png';
                $insertStmt = $pdo->prepare('INSERT INTO khotbah_view (id, name, title, avatar) VALUES (?, ?, ?, ?)');
                $insertStmt->execute([$id, $name, $title, $saveAvatar]);
            }

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

    public function showInformation(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $activateKey = (string) ($this->pdo()->query('SELECT activate_key FROM identity WHERE id = 1 LIMIT 1')->fetchColumn() ?: '');

        return $this->renderAdmin(
            $request,
            $response,
            'control/information.php',
            [
                'systemInfo' => [
                    'isActivated' => $this->kernel->isActivated(),
                    'deviceId' => $this->kernel->getDeviceId(),
                    'activateKey' => $activateKey,
                    'appName' => 'Masjid Display',
                    'developer' => 'Zhax Ty Rellia',
                    'brand' => 'Gautavaris DigiSoft HUB',
                    'website' => 'https://gautavaris.web.id/',
                ],
            ],
            'Information',
            'control-information'
        );
    }

    public function updatePassword(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->assertActivated($request);
        $body = $this->body($request);

        $currentPassword = (string) ($body['current_password'] ?? '');
        $newPassword = (string) ($body['new_password'] ?? '');
        $confirmPassword = (string) ($body['confirm_password'] ?? '');

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            $this->flash('error', 'Please fill in all password fields.');

            return $this->redirect($response, '/');
        }

        if (strlen($newPassword) < 6) {
            $this->flash('error', 'New password must be at least 6 characters.');

            return $this->redirect($response, '/');
        }

        if ($newPassword !== $confirmPassword) {
            $this->flash('error', 'New password and confirmation do not match.');

            return $this->redirect($response, '/');
        }

        $pdo = $this->pdo();
        $sessionUser = trim((string) ($_SESSION['username'] ?? ''));
        if ($sessionUser !== '') {
            $stmt = $pdo->prepare('SELECT id, username, password FROM user WHERE username = ? LIMIT 1');
            $stmt->execute([$sessionUser]);
            $userRow = $stmt->fetch();
        } else {
            $userRow = $pdo->query('SELECT id, username, password FROM user ORDER BY id ASC LIMIT 1')->fetch();
        }

        if ($userRow === false) {
            $this->flash('error', 'Account record not found.');

            return $this->redirect($response, '/');
        }

        $storedPassword = (string) ($userRow['password'] ?? '');
        if (!password_verify($currentPassword, $storedPassword) && !hash_equals($storedPassword, $currentPassword)) {
            $this->flash('error', 'Current password is incorrect.');

            return $this->redirect($response, '/');
        }

        try {
            $updateStmt = $pdo->prepare('UPDATE user SET password = ? WHERE id = ?');
            $updateStmt->execute([
                password_hash($newPassword, PASSWORD_DEFAULT),
                (int) $userRow['id'],
            ]);

            $this->flash('success', 'Password updated successfully.');
        } catch (Throwable) {
            $this->flash('error', 'Unable to update password.');
        }

        return $this->redirect($response, '/');
    }

    public function unbindActivationKey(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->assertActivated($request);
        try {
            $this->kernel->deactivate();
            $this->flash('success', 'Activation key unbound from this device.');
        } catch (Throwable) {
            $this->flash('error', 'Unable to unbind activation key.');
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
