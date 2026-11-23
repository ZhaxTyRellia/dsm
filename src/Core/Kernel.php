<?php

declare(strict_types=1);

namespace App\Core;

use App\Middleware\SessionMiddleware;
use App\Service\FileUploadService;
use PDO;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\App;
use Slim\Exception\HttpException;
use Slim\Exception\HttpForbiddenException;
use Throwable;

final class Kernel
{
    /**
     * Embedded ECDSA P-256 (prime256v1) SPKI X.509 Public Key (Gautavaris MASTER_KEY.public).
     */
    public const MASTER_PUBLIC_KEY = <<<'PEM'
-----BEGIN PUBLIC KEY-----
MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAEa4EhCDAJbrR3FrB883Q7cy/jP0Y+
dOOKynJavjU3HKpOBkr/xaPCGJTwm0wiBNTcnboSDFz6SXgsI6b21XwTmg==
-----END PUBLIC KEY-----
PEM;

    private const PUBLIC_DISPLAY_PATHS = [
        '/potrait',
        '/portrait',
        '/potrait.php',
        '/api/money',
        '/money_update',
        '/money_update.php',
        '/api/shalat-time',
        '/potrait_update',
        '/potrait_update.php',
        '/api/khotbah',
        '/special_update',
        '/special_update.php',
        '/api/scene-plus',
        '/scene_plus_update',
        '/scene_plus_update.php',
        '/api/time',
        '/app/control/get_time',
        '/app/control/get_time.php',
    ];

    private const RESETTABLE_TABLES = [
        'user',
        'identity',
        'youtube',
        'data_money',
        'data_rawatib',
        'monthly_planner',
        'data_list',
        'staff',
        'banner',
        'shalattime',
        'khotbah_view',
    ];

    private ?PDO $pdo;
    private bool $schemaReady = false;
    private string $publicKeyPem;
    private ?string $pendingMigratedKey = null;

    /** @var (callable(): ?string)|null */
    private $uuidResolver;

    /**
     * @param (callable(): ?string)|null $uuidResolver
     */
    public function __construct(
        private readonly string $dbPath,
        private readonly string $basePath,
        private readonly string $uploadsDir,
        private readonly bool $debug = false,
        ?PDO $pdo = null,
        ?string $publicKeyPem = null,
        ?callable $uuidResolver = null
    ) {
        $this->pdo = $pdo;
        $this->publicKeyPem = $publicKeyPem ?? self::MASTER_PUBLIC_KEY;
        $this->uuidResolver = $uuidResolver;
    }

    public function pdo(): PDO
    {
        if (!$this->pdo instanceof PDO) {
            if ($this->dbPath !== ':memory:') {
                $dir = dirname($this->dbPath);
                if ($dir !== '' && !is_dir($dir)) {
                    @mkdir($dir, 0755, true);
                }
            }

            $this->pdo = new PDO('sqlite:' . $this->dbPath, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            if ($this->dbPath !== ':memory:') {
                $this->pdo->exec('PRAGMA journal_mode = WAL;');
            }
            $this->pdo->exec('PRAGMA foreign_keys = ON;');
            $this->pdo->exec('PRAGMA busy_timeout = 5000;');
        }

        if (!$this->schemaReady) {
            $this->ensureSchema($this->pdo);
            $this->schemaReady = true;
            $this->seedFactoryData(false, $this->pendingMigratedKey ?? '');
            $this->pendingMigratedKey = null;
        }

        return $this->pdo;
    }

    public function uploadsDir(): string
    {
        return $this->uploadsDir;
    }

    public function normalizeDeviceId(string $deviceId): string
    {
        return strtoupper(trim($deviceId));
    }

    public function getDeviceId(): ?string
    {
        if ($this->uuidResolver !== null) {
            $raw = (string) (($this->uuidResolver)() ?? '');
        } elseif (PHP_OS_FAMILY === 'Windows') {
            $cmd = 'powershell -NoProfile -Command "(Get-CimInstance Win32_ComputerSystemProduct).UUID"';
            $raw = (string) @shell_exec($cmd);
        } else {
            $raw = '';
            foreach (['/sys/class/dmi/id/product_uuid', '/etc/machine-id'] as $file) {
                if (is_readable($file)) {
                    $raw = (string) @file_get_contents($file);
                    if (trim($raw) !== '') {
                        break;
                    }
                }
            }
        }

        $normalized = $this->normalizeDeviceId($raw);

        return $normalized !== '' ? $normalized : null;
    }

    public function base64UrlDecode(string $input): ?string
    {
        $trimmed = trim($input);
        if ($trimmed === '' || !preg_match('/^[A-Za-z0-9\-_]+$/', $trimmed)) {
            return null;
        }

        $remainder = strlen($trimmed) % 4;
        if ($remainder === 1) {
            return null;
        }
        if ($remainder > 0) {
            $trimmed .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode(strtr($trimmed, '-_', '+/'), true);

        return $decoded !== false && $decoded !== '' ? $decoded : null;
    }

    public function verifyActivateKey(?string $deviceId, string $activateKey): bool
    {
        if ($deviceId === null || trim($deviceId) === '') {
            return false;
        }

        $normalizedDeviceId = $this->normalizeDeviceId($deviceId);
        $derSignature = $this->base64UrlDecode($activateKey);
        if ($derSignature === null) {
            return false;
        }

        $result = @openssl_verify(
            $normalizedDeviceId,
            $derSignature,
            $this->publicKeyPem,
            OPENSSL_ALGO_SHA256
        );

        return $result === 1;
    }

    public function isActivated(): bool
    {
        $deviceId = $this->getDeviceId();
        if ($deviceId === null) {
            return false;
        }

        $stmt = $this->pdo()->prepare('SELECT activate_key FROM identity WHERE id = 1 LIMIT 1');
        $stmt->execute();
        $storedKey = $stmt->fetchColumn();

        if (!is_string($storedKey) || $storedKey === '') {
            return false;
        }

        return $this->verifyActivateKey($deviceId, $storedKey);
    }

    /**
     * @return array{success: bool, error: ?string}
     */
    public function activate(string $activateKey): array
    {
        $deviceId = $this->getDeviceId();
        $cleanKey = trim($activateKey);

        if (!$this->verifyActivateKey($deviceId, $cleanKey)) {
            return [
                'success' => false,
                'error' => 'The activation key is not valid for this device.',
            ];
        }

        $pdo = $this->pdo();
        $hasIdentity = (int) $pdo->query('SELECT COUNT(*) FROM identity WHERE id = 1')->fetchColumn() > 0;
        if ($hasIdentity) {
            $stmt = $pdo->prepare('UPDATE identity SET activate_key = ? WHERE id = 1');
            $stmt->execute([$cleanKey]);
        } else {
            $this->seedFactoryData(false, $cleanKey);
        }

        return ['success' => true, 'error' => null];
    }

    public function assertActivated(ServerRequestInterface $request): void
    {
        if (!$this->isActivated()) {
            throw new HttpForbiddenException($request, 'This application has not been activated yet.');
        }
    }

    public function factoryReset(?FileUploadService $fileUploadService = null, bool $resetLicense = false): void
    {
        $pdo = $this->pdo();
        $preservedKey = '';
        if (!$resetLicense) {
            $existingKey = $pdo->query('SELECT activate_key FROM identity WHERE id = 1 LIMIT 1')->fetchColumn();
            if (is_string($existingKey)) {
                $preservedKey = $existingKey;
            }
        }

        $pdo->beginTransaction();
        try {
            foreach (self::RESETTABLE_TABLES as $table) {
                $pdo->exec("DELETE FROM {$table}");
            }

            $hasSeq = (int) $pdo->query(
                "SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = 'sqlite_sequence'"
            )->fetchColumn();
            if ($hasSeq > 0) {
                $placeholders = implode(', ', array_fill(0, count(self::RESETTABLE_TABLES), '?'));
                $seqStmt = $pdo->prepare("DELETE FROM sqlite_sequence WHERE name IN ({$placeholders})");
                $seqStmt->execute(self::RESETTABLE_TABLES);
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        if ($fileUploadService !== null) {
            $fileUploadService->clearAllUploads();
        }

        $this->seedFactoryData(true, $preservedKey);
    }

    public function seedFactoryData(bool $force = false, string $activateKey = ''): void
    {
        $pdo = $this->pdo();
        $isFreshInstall = (int) $pdo->query('SELECT COUNT(*) FROM identity')->fetchColumn() === 0;
        $shouldSeedCollections = $force || $isFreshInstall;

        if ((int) $pdo->query('SELECT COUNT(*) FROM user')->fetchColumn() === 0) {
            $stmt = $pdo->prepare('INSERT INTO user (username, password) VALUES (?, ?)');
            $stmt->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT)]);
        }

        if ((int) $pdo->query('SELECT COUNT(*) FROM identity')->fetchColumn() === 0) {
            $stmt = $pdo->prepare(
                'INSERT INTO identity (id, logo, tittle, name, background, audio, marquee, activate_key) VALUES (1, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                'default.png',
                'Masjid Agung Al-Ikhlas',
                'Jl. Masjid Raya No. 1',
                'bg1.png',
                'uploads/audio/audio.mp3',
                'Selamat datang di Masjid Agung Al-Ikhlas — Luruskan dan rapatkan shaf sebelum menunaikan shalat berjamaah.',
                $activateKey,
            ]);
        }

        if ((int) $pdo->query('SELECT COUNT(*) FROM youtube')->fetchColumn() === 0) {
            $pdo->exec("INSERT INTO youtube (id, url) VALUES (1, '')");
        }

        if ((int) $pdo->query('SELECT COUNT(*) FROM data_money')->fetchColumn() === 0) {
            $pdo->exec(
                "INSERT INTO data_money (
                    id, opening_date, period_start, opening_umat, opening_masjid, opening_total,
                    income_umat, income_masjid, income_total, position_date, position_umat, position_masjid, position_total,
                    expense_date, expense_umat, expense_masjid, expense_total, closing_date, closing_umat, closing_masjid, closing_total
                ) VALUES (
                    1, '-', '-', 0, 0, 0,
                    0, 0, 0, '-', 0, 0, 0,
                    '-', 0, 0, 0, '-', 0, 0, 0
                )"
            );
        }

        if ((int) $pdo->query('SELECT COUNT(*) FROM banner')->fetchColumn() === 0) {
            $pdo->exec(
                "INSERT INTO banner (id, active_slides, pict1, pict2, pict3, pict4, pict5, pict6, pict7, pict8, pict9)
                 VALUES (1, 1, 'default.png', 'default.png', 'default.png', 'default.png', 'default.png', 'default.png', 'default.png', 'default.png', 'default.png')"
            );
        }

        if ((int) $pdo->query('SELECT COUNT(*) FROM shalattime')->fetchColumn() === 0) {
            $pdo->exec(
                "INSERT INTO shalattime (id, subuh, dzuhur, ashar, maghrib, isya, jumat)
                 VALUES (1, '04:30:00', '12:00:00', '15:15:00', '18:00:00', '19:15:00', '12:00:00')"
            );
        }

        if ((int) $pdo->query('SELECT COUNT(*) FROM khotbah_view')->fetchColumn() === 0) {
            $pdo->exec(
                "INSERT INTO khotbah_view (id, title, avatar, name)
                 VALUES (1, 'Jumat Pekan Ini', 'default.png', 'Ustadz Ahmad Fauzi')"
            );
        }

        if ($shouldSeedCollections) {
            if ((int) $pdo->query('SELECT COUNT(*) FROM data_rawatib')->fetchColumn() === 0) {
                $stmt = $pdo->prepare('INSERT INTO data_rawatib (data_shalat, hari, name, profile) VALUES (?, ?, ?, ?)');
                $stmt->execute(['SUBUH', 'SENIN - MINGGU', 'Ustadz Ahmad Fauzi', 'default.png']);
                $stmt->execute(['DZUHUR', 'SENIN - KAMIS', 'Ustadz Hasanuddin', 'default.png']);
                $stmt->execute(['MAGHRIB', 'SENIN - MINGGU', 'Ustadz Abdul Karim', 'default.png']);
            }

            if ((int) $pdo->query('SELECT COUNT(*) FROM monthly_planner')->fetchColumn() === 0) {
                $stmt = $pdo->prepare('INSERT INTO monthly_planner (id_desc, `desc`, `time`, speaker) VALUES (?, ?, ?, ?)');
                $stmt->execute([1, 'KAJIAN HADITS "BULUGHUL MAROM"', "Setiap Ahad Ba'da Subuh", 'Ustadz Ahmad Fauzi']);
                $stmt->execute([5, 'KAJIAN FIQIH "SAFINATUN NAJAH"', "Setiap Rabu Ba'da Maghrib", 'Ustadz Hasanuddin']);
            }

            if ((int) $pdo->query('SELECT COUNT(*) FROM data_list')->fetchColumn() === 0) {
                $stmt = $pdo->prepare('INSERT INTO data_list (moon, `date`, identity) VALUES (?, ?, ?)');
                $stmt->execute(['Januari', '03', 'Ustadz Ahmad Fauzi']);
                $stmt->execute(['Januari', '10', 'Ustadz Abdul Karim']);
            }

            if ((int) $pdo->query('SELECT COUNT(*) FROM staff')->fetchColumn() === 0) {
                $stmt = $pdo->prepare('INSERT INTO staff (tier, id_no, identity) VALUES (?, ?, ?)');
                $stmt->execute(['PENASEHAT', 1, 'KH. Abdullah Mansur']);
                $stmt->execute(['KETUA', 2, 'H. Muhammad Ridwan']);
                $stmt->execute(['SEKRETARIS', 4, 'Ahmad Syafi\'i, S.Pd']);
            }
        }
    }

    public function basePath(): string
    {
        return $this->basePath;
    }

    public function baseUrl(): string
    {
        return ($this->basePath !== '' ? $this->basePath : '') . '/';
    }

    public function boot(App $app): void
    {
        $app->add($this->createLicenseGateMiddleware($app->getResponseFactory()));
        $app->add(new SessionMiddleware());
        $app->addBodyParsingMiddleware();
        $app->addRoutingMiddleware();

        $kernel = $this;
        $errorMiddleware = $app->addErrorMiddleware($this->debug, true, true);
        $errorMiddleware->setDefaultErrorHandler(
            new class ($app, $kernel) {
                public function __construct(
                    private readonly App $app,
                    private readonly Kernel $kernel
                ) {
                }

                public function __invoke(
                    ServerRequestInterface $request,
                    Throwable $exception,
                    bool $displayErrorDetails,
                    bool $logErrors,
                    bool $logErrorDetails
                ): ResponseInterface {
                    $baseTarget = $this->kernel->baseUrl();
                    $basePath = $this->kernel->basePath();
                    $requestPath = $request->getUri()->getPath();
                    $isAtBase = ($requestPath === $baseTarget)
                        || ($basePath !== '' && $requestPath === $basePath);

                    if ($exception instanceof HttpException || (!$displayErrorDetails && !$isAtBase)) {
                        return $this->app->getResponseFactory()
                            ->createResponse(302)
                            ->withHeader('Location', $baseTarget);
                    }

                    $response = $this->app->getResponseFactory()->createResponse(500);
                    $message = $displayErrorDetails
                        ? $exception->getMessage() . "\n" . $exception->getTraceAsString()
                        : 'Something went wrong. Please try again.';
                    $response->getBody()->write($message);

                    return $response->withHeader('Content-Type', 'text/plain; charset=utf-8');
                }
            }
        );
    }

    public function isPublicDisplayPath(string $requestPath): bool
    {
        $relative = $requestPath;
        if ($this->basePath !== '' && str_starts_with($relative, $this->basePath)) {
            $relative = substr($relative, strlen($this->basePath));
        }

        if ($relative === '' || $relative[0] !== '/') {
            $relative = '/' . $relative;
        }

        return in_array($relative, self::PUBLIC_DISPLAY_PATHS, true);
    }

    private function createLicenseGateMiddleware(ResponseFactoryInterface $responseFactory): MiddlewareInterface
    {
        $kernel = $this;

        return new class ($kernel, $responseFactory) implements MiddlewareInterface {
            public function __construct(
                private readonly Kernel $kernel,
                private readonly ResponseFactoryInterface $responseFactory
            ) {
            }

            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $path = $request->getUri()->getPath();
                if ($this->kernel->isPublicDisplayPath($path) || $this->kernel->isActivated()) {
                    return $handler->handle($request);
                }

                $errorMsg = null;
                if (strtoupper($request->getMethod()) === 'POST') {
                    $parsed = $request->getParsedBody();
                    $body = is_array($parsed) ? $parsed : [];
                    $submittedKey = (string) ($body['activate_key'] ?? $body['serial_number'] ?? '');

                    if ($submittedKey !== '') {
                        $result = $this->kernel->activate($submittedKey);
                        if ($result['success']) {
                            return $this->responseFactory
                                ->createResponse(302)
                                ->withHeader('Location', $this->kernel->baseUrl());
                        }
                        $errorMsg = $result['error'];
                    }
                }

                $response = $this->responseFactory->createResponse(200);
                $response->getBody()->write($this->kernel->renderActivationHtml($errorMsg));

                return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
            }
        };
    }

    public function renderActivationHtml(?string $errorMsg = null): string
    {
        $e = static fn (mixed $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
        $baseHref = $e($this->baseUrl());
        $deviceId = $this->getDeviceId();
        $deviceLabel = $deviceId !== null ? $e($deviceId) : 'Not Detected';

        $errorHtml = '';
        if ($errorMsg !== null && $errorMsg !== '') {
            $errorHtml = '<div class="license-alert">' . $e($errorMsg) . '</div>';
        }

        $copyButtonHtml = '';
        if ($deviceId !== null) {
            $copyButtonHtml = <<<'HTML'
<button type="button" onclick="copyDeviceID()" class="btn-copy" id="copyDeviceBtn" title="Copy Device ID">
    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
    <span>Copy</span>
</button>
HTML;
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <base href="{$baseHref}">
    <title>Application Activation | Masjid Display</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
    <style>
        :root {
            --emerald-950: #041e19;
            --emerald-900: #062c24;
            --emerald-800: #0c4438;
            --gold-500: #d4af37;
            --gold-400: #e5c663;
            --text-dark: #102a24;
            --text-muted: #5b6e68;
            --danger: #dc2626;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            font-family: "Plus Jakarta Sans", "Segoe UI", system-ui, -apple-system, sans-serif;
            background: radial-gradient(circle at 18% 20%, rgba(212, 175, 55, 0.16), transparent 45%),
                        radial-gradient(circle at 85% 80%, rgba(16, 185, 129, 0.18), transparent 45%),
                        linear-gradient(135deg, var(--emerald-950), var(--emerald-900) 55%, var(--emerald-800));
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: var(--text-dark);
        }
        .license-card {
            width: 100%;
            max-width: 500px;
            background: rgba(255, 255, 255, 0.97);
            border-radius: 22px;
            padding: 36px 32px;
            border: 1px solid rgba(212, 175, 55, 0.4);
            box-shadow: 0 28px 60px rgba(0, 0, 0, 0.42);
        }
        .license-head {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 16px;
        }
        .license-emblem {
            width: 52px;
            height: 52px;
            flex-shrink: 0;
        }
        .license-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 999px;
            background: rgba(212, 175, 55, 0.14);
            color: #8b6d11;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-bottom: 6px;
        }
        .license-title {
            font-size: 22px;
            font-weight: 800;
            color: var(--emerald-900);
        }
        .license-desc {
            font-size: 14px;
            line-height: 1.6;
            color: var(--text-muted);
            margin-bottom: 22px;
        }
        .license-alert {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: var(--danger);
            padding: 12px 14px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 18px;
        }
        .device-box {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            background: #f4f7f6;
            border: 1px solid #dce6e2;
            border-radius: 14px;
            padding: 14px 16px;
            margin-bottom: 22px;
        }
        .device-meta { min-width: 0; }
        .device-label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--text-muted);
            margin-bottom: 4px;
        }
        .device-value {
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 13px;
            font-weight: 700;
            color: var(--emerald-900);
            word-break: break-all;
        }
        .btn-copy {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            flex-shrink: 0;
            background: #ffffff;
            border: 1px solid #cbd7d2;
            color: var(--emerald-900);
            padding: 8px 12px;
            border-radius: 10px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .btn-copy:hover {
            border-color: var(--gold-500);
            background: #fffbeb;
        }
        .field-label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: var(--emerald-900);
            margin-bottom: 8px;
        }
        .field-input {
            width: 100%;
            padding: 13px 15px;
            border: 1.5px solid #d2deda;
            border-radius: 12px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 13px;
            color: var(--text-dark);
            background: #ffffff;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            margin-bottom: 16px;
        }
        .field-input:focus {
            border-color: var(--gold-500);
            box-shadow: 0 0 0 4px rgba(212, 175, 55, 0.18);
        }
        .btn-activate {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 14px 20px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--emerald-900), var(--emerald-800));
            color: var(--gold-400);
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            cursor: pointer;
            box-shadow: 0 10px 24px rgba(6, 44, 36, 0.28);
            transition: transform 0.15s ease, box-shadow 0.2s ease;
        }
        .btn-activate:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 28px rgba(6, 44, 36, 0.35);
        }
        .license-foot {
            margin-top: 18px;
            text-align: center;
            font-size: 12px;
            color: var(--text-muted);
        }
    </style>
</head>
<body>
    <div class="license-card">
        <div class="license-head">
            <svg class="license-emblem" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64">
                <rect width="64" height="64" rx="14" fill="#062c24"/>
                <rect x="3" y="3" width="58" height="58" rx="12" fill="none" stroke="#d4af37" stroke-width="2" opacity="0.65"/>
                <path d="M34.5 11.5a6 6 0 1 0 5.7 7.9 4.7 4.7 0 1 1-5.7-7.9z" fill="#d4af37"/>
                <path d="M32 20c-6.5 4.2-10 8.8-10 14v2h20v-2c0-5.2-3.5-9.8-10-14z" fill="#d4af37"/>
                <rect x="14" y="26" width="5" height="24" rx="1.5" fill="#d4af37"/>
                <rect x="45" y="26" width="5" height="24" rx="1.5" fill="#d4af37"/>
                <rect x="19" y="36" width="26" height="14" fill="#d4af37"/>
                <path d="M28 50v-6.5a4 4 0 0 1 8 0V50z" fill="#062c24"/>
                <rect x="11" y="49" width="42" height="3.5" rx="1.75" fill="#d4af37"/>
            </svg>
            <div>
                <div class="license-badge">Offline Activation</div>
                <h1 class="license-title">Activate Application</h1>
            </div>
        </div>
        <p class="license-desc">This device is not activated yet. Copy the Device ID below and enter your activation key to open the management panel.</p>
        {$errorHtml}
        <div class="device-box">
            <div class="device-meta">
                <span class="device-label">Device ID</span>
                <span class="device-value" id="deviceIdText">{$deviceLabel}</span>
            </div>
            {$copyButtonHtml}
        </div>
        <form method="POST" action="./">
            <label class="field-label" for="activate_key">Activation Key</label>
            <input type="text" id="activate_key" name="activate_key" class="field-input" placeholder="Paste your activation key here..." required autocomplete="off">
            <button type="submit" class="btn-activate">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
                <span>Activate Now</span>
            </button>
        </form>
        <p class="license-foot">Built-in offline verification for this device.</p>
    </div>
    <script>
        function copyDeviceID() {
            var text = document.getElementById('deviceIdText').innerText;
            navigator.clipboard.writeText(text).then(function() {
                var span = document.querySelector('#copyDeviceBtn span');
                if (!span) return;
                span.innerText = 'Copied!';
                setTimeout(function() { span.innerText = 'Copy'; }, 1500);
            });
        }
    </script>
</body>
</html>
HTML;
    }

    private function ensureSchema(PDO $pdo): void
    {
        $tables = [
            'CREATE TABLE IF NOT EXISTS user (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL
            )',
            "CREATE TABLE IF NOT EXISTS identity (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                logo TEXT NOT NULL DEFAULT 'default.png',
                tittle TEXT NOT NULL DEFAULT '',
                name TEXT NOT NULL DEFAULT '',
                background TEXT NOT NULL DEFAULT 'bg1.png',
                audio TEXT NOT NULL DEFAULT 'uploads/audio/audio.mp3',
                marquee TEXT NOT NULL DEFAULT '',
                activate_key TEXT NOT NULL DEFAULT ''
            )",
            "CREATE TABLE IF NOT EXISTS youtube (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                url TEXT NOT NULL DEFAULT ''
            )",
            'CREATE TABLE IF NOT EXISTS data_money (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                opening_date TEXT,
                period_start TEXT,
                report_date TEXT,
                opening_umat REAL,
                opening_masjid REAL,
                opening_total REAL,
                income_umat REAL,
                income_masjid REAL,
                income_total REAL,
                position_date TEXT,
                position_umat REAL,
                position_masjid REAL,
                position_total REAL,
                expense_date TEXT,
                expense_umat REAL,
                expense_masjid REAL,
                expense_total REAL,
                closing_date TEXT,
                closing_umat REAL,
                closing_masjid REAL,
                closing_total REAL,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            )',
            'CREATE TABLE IF NOT EXISTS data_rawatib (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                data_shalat TEXT NOT NULL,
                hari TEXT NOT NULL,
                name TEXT NOT NULL,
                profile TEXT
            )',
            'CREATE TABLE IF NOT EXISTS monthly_planner (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                id_desc INTEGER NOT NULL DEFAULT 0,
                `desc` TEXT NOT NULL DEFAULT \'\',
                `time` TEXT NOT NULL DEFAULT \'\',
                speaker TEXT NOT NULL DEFAULT \'\'
            )',
            'CREATE TABLE IF NOT EXISTS data_list (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                moon TEXT NOT NULL DEFAULT \'\',
                `date` TEXT NOT NULL DEFAULT \'\',
                identity TEXT NOT NULL DEFAULT \'\'
            )',
            'CREATE TABLE IF NOT EXISTS staff (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                tier TEXT NOT NULL DEFAULT \'\',
                id_no INTEGER NOT NULL DEFAULT 99,
                identity TEXT NOT NULL DEFAULT \'\'
            )',
            "CREATE TABLE IF NOT EXISTS banner (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                active_slides INTEGER NOT NULL DEFAULT 1,
                pict1 TEXT DEFAULT 'default.png',
                pict2 TEXT DEFAULT 'default.png',
                pict3 TEXT DEFAULT 'default.png',
                pict4 TEXT DEFAULT 'default.png',
                pict5 TEXT DEFAULT 'default.png',
                pict6 TEXT DEFAULT 'default.png',
                pict7 TEXT DEFAULT 'default.png',
                pict8 TEXT DEFAULT 'default.png',
                pict9 TEXT DEFAULT 'default.png'
            )",
            "CREATE TABLE IF NOT EXISTS shalattime (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                subuh TEXT NOT NULL DEFAULT '04:30:00',
                dzuhur TEXT NOT NULL DEFAULT '12:00:00',
                ashar TEXT NOT NULL DEFAULT '15:15:00',
                maghrib TEXT NOT NULL DEFAULT '18:00:00',
                isya TEXT NOT NULL DEFAULT '19:15:00',
                jumat TEXT NOT NULL DEFAULT '12:00:00'
            )",
            "CREATE TABLE IF NOT EXISTS khotbah_view (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL DEFAULT '',
                avatar TEXT NOT NULL DEFAULT 'default.png',
                name TEXT NOT NULL DEFAULT ''
            )",
        ];

        foreach ($tables as $sql) {
            $pdo->exec($sql);
        }

        $identityCols = $pdo->query('PRAGMA table_info(identity)')->fetchAll();
        $hasActivateKeyCol = false;
        foreach ($identityCols as $col) {
            if (($col['name'] ?? '') === 'activate_key') {
                $hasActivateKeyCol = true;
                break;
            }
        }
        if (!$hasActivateKeyCol) {
            $pdo->exec("ALTER TABLE identity ADD COLUMN activate_key TEXT NOT NULL DEFAULT ''");
        }

        $hasLegacyMeta = (int) $pdo->query(
            "SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = 'system_meta'"
        )->fetchColumn() > 0;
        if ($hasLegacyMeta) {
            $legacyKey = $pdo->query(
                "SELECT meta_value FROM system_meta WHERE meta_key = 'activate_key' LIMIT 1"
            )->fetchColumn();
            if (is_string($legacyKey) && trim($legacyKey) !== '') {
                $cleanLegacyKey = trim($legacyKey);
                if ((int) $pdo->query('SELECT COUNT(*) FROM identity WHERE id = 1')->fetchColumn() > 0) {
                    $migrateStmt = $pdo->prepare(
                        "UPDATE identity SET activate_key = ? WHERE id = 1 AND (activate_key IS NULL OR activate_key = '')"
                    );
                    $migrateStmt->execute([$cleanLegacyKey]);
                } else {
                    $this->pendingMigratedKey = $cleanLegacyKey;
                }
            }
            $pdo->exec('DROP TABLE IF EXISTS system_meta');
        }
    }
}
