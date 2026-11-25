<?php

declare(strict_types=1);

namespace App\Core;

use App\Middleware\SessionMiddleware;
use App\Service\FileUploadService;
use App\Support\DisplaySlideConfig;
use App\Support\DisplayThemeCatalog;
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
        '/display',
        '/display/',
        '/display/index.php',
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
        '/api/audio',
        '/api/display-config',
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

    public function deactivate(): void
    {
        $this->pdo()->exec("UPDATE identity SET activate_key = '' WHERE id = 1");
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

        if ((int) $pdo->query('SELECT COUNT(*) FROM user')->fetchColumn() === 0) {
            $stmt = $pdo->prepare('INSERT INTO user (username, password) VALUES (?, ?)');
            $stmt->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT)]);
        }

        if ((int) $pdo->query('SELECT COUNT(*) FROM identity')->fetchColumn() === 0) {
            $stmt = $pdo->prepare(
                'INSERT INTO identity (id, logo, tittle, name, background, display_mode, audio, audio_enabled, marquee, slide_settings, activate_key) VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                'default.png',
                'Masjid Agung Al-Ikhlas',
                'Jl. Masjid Raya No. 1',
                DisplayThemeCatalog::DEFAULT_FILE,
                'auto',
                'uploads/audio/audio.mp3',
                0,
                'Selamat datang di Masjid Agung Al-Ikhlas — Luruskan dan rapatkan shaf sebelum menunaikan shalat berjamaah.',
                DisplaySlideConfig::defaultJson(),
                $activateKey,
            ]);
        }

        if ((int) $pdo->query('SELECT COUNT(*) FROM shalattime')->fetchColumn() === 0) {
            $pdo->exec(
                "INSERT INTO shalattime (id, subuh, dzuhur, ashar, maghrib, isya, jumat)
                 VALUES (1, '04:06:00', '11:43:00', '15:06:00', '17:56:00', '19:10:00', '11:43:00')"
            );
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
                            $_SESSION['flash'] = [
                                'type' => 'success',
                                'message' => 'Application activated successfully.',
                            ];
                            return $this->responseFactory
                                ->createResponse(302)
                                ->withHeader('Location', $this->kernel->baseUrl());
                        }
                        $errorMsg = $result['error'];
                    } elseif (array_key_exists('activate_key', $body) || array_key_exists('serial_number', $body)) {
                        $errorMsg = 'Please enter your activation key.';
                    }
                }

                $flash = null;
                if ($errorMsg === null && isset($_SESSION['flash']) && is_array($_SESSION['flash'])) {
                    $flash = [
                        'type' => (string) ($_SESSION['flash']['type'] ?? 'success'),
                        'message' => (string) ($_SESSION['flash']['message'] ?? ''),
                    ];
                    unset($_SESSION['flash']);
                }

                $response = $this->responseFactory->createResponse(200);
                $response->getBody()->write($this->kernel->renderActivationHtml($errorMsg, $flash));

                return $response->withHeader('Content-Type', 'text/html; charset=utf-8');
            }
        };
    }

    /**
     * @param array{type: string, message: string}|null $flash
     */
    public function renderActivationHtml(?string $errorMsg = null, ?array $flash = null): string
    {
        $e = static fn (mixed $v): string => htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
        $baseHref = $e($this->baseUrl());
        $deviceId = $this->getDeviceId();
        $deviceLabel = $deviceId !== null ? $e($deviceId) : 'Not Detected';

        $errorHtml = '';
        $toastBootJs = '';
        if ($errorMsg !== null && $errorMsg !== '') {
            $errorHtml = '<div class="license-alert">' . $e($errorMsg) . '</div>';
            $toastBootJs = 'showActivationToast("error", ' . json_encode($errorMsg, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ');';
        } elseif ($flash !== null && ($flash['message'] ?? '') !== '') {
            $flashType = ($flash['type'] ?? 'success') === 'error' ? 'error' : 'success';
            $toastBootJs = 'showActivationToast(' . json_encode($flashType) . ', ' . json_encode($flash['message'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ');';
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
        .act-toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 500;
            display: flex;
            flex-direction: column;
            gap: 10px;
            max-width: calc(100vw - 32px);
        }
        .act-toast {
            position: relative;
            overflow: hidden;
            min-width: 280px;
            max-width: 400px;
            padding: 13px 16px;
            border-radius: 14px;
            color: #ffffff;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: flex-start;
            gap: 11px;
            box-shadow: 0 20px 44px rgba(0, 0, 0, 0.35);
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        .act-toast.toast-success {
            background: #062c24;
            border: 1px solid rgba(212, 175, 55, 0.45);
            border-left: 4px solid #10b981;
        }
        .act-toast.toast-error {
            background: #7f1d1d;
            border: 1px solid rgba(252, 165, 165, 0.45);
            border-left: 4px solid #f87171;
        }
        .act-toast-title {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 2px;
        }
        .act-toast.toast-success .act-toast-title { color: #6ee7b7; }
        .act-toast.toast-error .act-toast-title { color: #fecaca; }
        .act-toast-close {
            width: 24px;
            height: 24px;
            border-radius: 7px;
            border: none;
            background: rgba(255, 255, 255, 0.14);
            color: #ffffff;
            cursor: pointer;
            flex-shrink: 0;
        }
        .act-toast-progress {
            position: absolute;
            left: 0;
            bottom: 0;
            height: 3px;
            width: 100%;
            background: #10b981;
            transition: width 4.5s linear;
        }
        .act-toast.toast-error .act-toast-progress {
            background: #f87171;
        }
    </style>
</head>
<body>
    <div class="act-toast-container" id="activationToastContainer" aria-live="polite"></div>
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
                <div class="license-badge">Activation</div>
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
        <p class="license-foot">Enter your key to unlock this device.</p>
    </div>
    <script>
        function showActivationToast(type, message) {
            var container = document.getElementById('activationToastContainer');
            if (!container || !message) return;
            var isErr = type === 'error';
            var toast = document.createElement('div');
            toast.className = 'act-toast ' + (isErr ? 'toast-error' : 'toast-success');
            var body = document.createElement('div');
            body.style.flex = '1';
            var title = document.createElement('div');
            title.className = 'act-toast-title';
            title.textContent = isErr ? 'Error' : 'Success';
            var msg = document.createElement('div');
            msg.textContent = String(message);
            body.appendChild(title);
            body.appendChild(msg);
            var close = document.createElement('button');
            close.type = 'button';
            close.className = 'act-toast-close';
            close.innerHTML = '&times;';
            close.onclick = function () { toast.remove(); };
            var progress = document.createElement('div');
            progress.className = 'act-toast-progress';
            toast.appendChild(body);
            toast.appendChild(close);
            toast.appendChild(progress);
            container.appendChild(toast);
            requestAnimationFrame(function () {
                requestAnimationFrame(function () { progress.style.width = '0%'; });
            });
            setTimeout(function () {
                toast.style.opacity = '0';
                setTimeout(function () { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 300);
            }, 4500);
        }
        function copyDeviceID() {
            var text = document.getElementById('deviceIdText').innerText;
            navigator.clipboard.writeText(text).then(function() {
                var span = document.querySelector('#copyDeviceBtn span');
                if (span) {
                    span.innerText = 'Copied!';
                    setTimeout(function() { span.innerText = 'Copy'; }, 1500);
                }
                showActivationToast('success', 'Device ID copied to clipboard.');
            });
        }
        {$toastBootJs}
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
                background TEXT NOT NULL DEFAULT 'emerald-gold.svg',
                display_mode TEXT NOT NULL DEFAULT 'auto',
                audio TEXT NOT NULL DEFAULT 'uploads/audio/audio.mp3',
                audio_enabled INTEGER NOT NULL DEFAULT 0,
                marquee TEXT NOT NULL DEFAULT '',
                slide_settings TEXT NOT NULL DEFAULT '',
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
                subuh TEXT NOT NULL DEFAULT '04:06:00',
                dzuhur TEXT NOT NULL DEFAULT '11:43:00',
                ashar TEXT NOT NULL DEFAULT '15:06:00',
                maghrib TEXT NOT NULL DEFAULT '17:56:00',
                isya TEXT NOT NULL DEFAULT '19:10:00',
                jumat TEXT NOT NULL DEFAULT '11:43:00'
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
        $hasDisplayModeCol = false;
        $hasAudioEnabledCol = false;
        $hasSlideSettingsCol = false;
        foreach ($identityCols as $col) {
            $colName = (string) ($col['name'] ?? '');
            if ($colName === 'activate_key') {
                $hasActivateKeyCol = true;
            } elseif ($colName === 'display_mode') {
                $hasDisplayModeCol = true;
            } elseif ($colName === 'audio_enabled') {
                $hasAudioEnabledCol = true;
            } elseif ($colName === 'slide_settings') {
                $hasSlideSettingsCol = true;
            }
        }
        if (!$hasActivateKeyCol) {
            $pdo->exec("ALTER TABLE identity ADD COLUMN activate_key TEXT NOT NULL DEFAULT ''");
        }
        if (!$hasDisplayModeCol) {
            $pdo->exec("ALTER TABLE identity ADD COLUMN display_mode TEXT NOT NULL DEFAULT 'auto'");
        }
        if (!$hasAudioEnabledCol) {
            $pdo->exec("ALTER TABLE identity ADD COLUMN audio_enabled INTEGER NOT NULL DEFAULT 0");
        }
        if (!$hasSlideSettingsCol) {
            $pdo->exec("ALTER TABLE identity ADD COLUMN slide_settings TEXT NOT NULL DEFAULT ''");
        }

        $identityRow = $pdo->query('SELECT background, slide_settings FROM identity WHERE id = 1 LIMIT 1')->fetch();
        if ($identityRow !== false) {
            $currentBg = (string) ($identityRow['background'] ?? '');
            if (!DisplayThemeCatalog::isValidFile($currentBg)) {
                $normalizedBg = DisplayThemeCatalog::normalize($currentBg);
                $bgStmt = $pdo->prepare('UPDATE identity SET background = ? WHERE id = 1');
                $bgStmt->execute([$normalizedBg]);
            }
            if (trim((string) ($identityRow['slide_settings'] ?? '')) === '') {
                $slStmt = $pdo->prepare('UPDATE identity SET slide_settings = ? WHERE id = 1');
                $slStmt->execute([DisplaySlideConfig::defaultJson()]);
            }
        }

        $pdo->exec(
            "UPDATE shalattime
             SET subuh = '04:06:00', dzuhur = '11:43:00', ashar = '15:06:00', maghrib = '17:56:00', isya = '19:10:00', jumat = '11:43:00'
             WHERE id = 1
               AND subuh = '04:30:00' AND dzuhur = '12:00:00' AND ashar = '15:15:00'
               AND maghrib = '18:00:00' AND isya = '19:15:00' AND jumat = '12:00:00'"
        );

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
