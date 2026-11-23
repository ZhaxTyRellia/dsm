<?php

declare(strict_types=1);

namespace App\Core;

use App\Middleware\SessionMiddleware;
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
     * Embedded ECDSA P-256 (prime256v1) SPKI X.509 Public Key.
     * Replace with your Cloudflare KV (GAUTAVARIS -> MASTER_KEY.public) PEM for production distribution.
     */
    public const MASTER_PUBLIC_KEY = <<<'PEM'
-----BEGIN PUBLIC KEY-----
MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAE6NrXJvVb5Ngqd/CQjZV7gJVF/GaG
lrUQNbJWVpVpBzq6eFgLn1EhSx6qGlnL1xRdsKMx0z5CUAAdg8jCex3mUw==
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

    private ?PDO $pdo;
    private bool $schemaReady = false;
    private string $publicKeyPem;

    /** @var (callable(): ?string)|null */
    private $uuidResolver;

    /**
     * @param (callable(): ?string)|null $uuidResolver
     */
    public function __construct(
        private readonly string $dbPath,
        private readonly string $basePath,
        private readonly string $picturesDir,
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
            $this->seedDefaults($this->pdo);
            $this->schemaReady = true;
        }

        return $this->pdo;
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

        $stmt = $this->pdo()->prepare("SELECT meta_value FROM system_meta WHERE meta_key = 'activate_key' LIMIT 1");
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
                'error' => 'Activate Key tidak valid untuk Device ID perangkat ini.',
            ];
        }

        $stmt = $this->pdo()->prepare(
            "INSERT INTO system_meta (meta_key, meta_value, updated_at)
             VALUES ('activate_key', ?, CURRENT_TIMESTAMP)
             ON CONFLICT(meta_key) DO UPDATE SET
                meta_value = excluded.meta_value,
                updated_at = CURRENT_TIMESTAMP"
        );
        $stmt->execute([$cleanKey]);

        return ['success' => true, 'error' => null];
    }

    public function assertActivated(ServerRequestInterface $request): void
    {
        if (!$this->isActivated()) {
            throw new HttpForbiddenException($request, 'Application license is not activated.');
        }
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

        $errorMiddleware = $app->addErrorMiddleware($this->debug, true, true);
        $errorMiddleware->setDefaultErrorHandler(
            function (
                ServerRequestInterface $request,
                Throwable $exception,
                bool $displayErrorDetails
            ) use ($app): ResponseInterface {
                $baseTarget = $this->baseUrl();
                $requestPath = $request->getUri()->getPath();
                $isAtBase = ($requestPath === $baseTarget)
                    || ($this->basePath !== '' && $requestPath === $this->basePath);

                if ($exception instanceof HttpException || (!$displayErrorDetails && !$isAtBase)) {
                    return $app->getResponseFactory()
                        ->createResponse(302)
                        ->withHeader('Location', $baseTarget);
                }

                $response = $app->getResponseFactory()->createResponse(500);
                $message = $displayErrorDetails
                    ? $exception->getMessage() . "\n" . $exception->getTraceAsString()
                    : 'Internal Server Error';
                $response->getBody()->write($message);

                return $response->withHeader('Content-Type', 'text/plain; charset=utf-8');
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
<button type="button" onclick="copyDeviceID()" class="btn-copy" id="copyDeviceBtn" title="Salin Device ID">
    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
    <span>Salin</span>
</button>
HTML;
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <base href="{$baseHref}">
    <title>Aktivasi Lisensi Offline | Digital Signage Masjid</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="shortcut icon" type="image/png" href="assets/img/favicon.png">
    <style>
        :root {
            --emerald-950: #041e19;
            --emerald-900: #062c24;
            --emerald-800: #0c4438;
            --gold-500: #d4af37;
            --gold-400: #e5c663;
            --surface: #ffffff;
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
        .license-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 999px;
            background: rgba(212, 175, 55, 0.14);
            color: #8b6d11;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-bottom: 16px;
        }
        .license-title {
            font-size: 24px;
            font-weight: 800;
            color: var(--emerald-900);
            margin-bottom: 8px;
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
        <div class="license-badge">ECDSA P-256 &bull; Offline Activation</div>
        <h1 class="license-title">Aktivasi Lisensi Aplikasi</h1>
        <p class="license-desc">Perangkat ini belum teraktivasi. Salin Device ID di bawah dan masukkan Activate Key resmi untuk membuka panel kontrol.</p>
        {$errorHtml}
        <div class="device-box">
            <div class="device-meta">
                <span class="device-label">Device ID</span>
                <span class="device-value" id="deviceIdText">{$deviceLabel}</span>
            </div>
            {$copyButtonHtml}
        </div>
        <form method="POST" action="">
            <label class="field-label" for="activate_key">Activate Key</label>
            <input type="text" id="activate_key" name="activate_key" class="field-input" placeholder="Tempel Activate Key (Base64URL)..." required autocomplete="off">
            <button type="submit" class="btn-activate">Aktivasi Sekarang</button>
        </form>
        <p class="license-foot">Verifikasi kriptografi offline terintegrasi pada inti sistem.</p>
    </div>
    <script>
        function copyDeviceID() {
            var text = document.getElementById('deviceIdText').innerText;
            navigator.clipboard.writeText(text).then(function() {
                var span = document.querySelector('#copyDeviceBtn span');
                if (!span) return;
                span.innerText = 'Tersalin!';
                setTimeout(function() { span.innerText = 'Salin'; }, 1500);
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
            'CREATE TABLE IF NOT EXISTS system_meta (
                meta_key TEXT PRIMARY KEY,
                meta_value TEXT NOT NULL,
                updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            )',
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
                audio TEXT NOT NULL DEFAULT 'assets/audio/audio.mp3',
                marquee TEXT NOT NULL DEFAULT ''
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
    }

    private function seedDefaults(PDO $pdo): void
    {
        if ((int) $pdo->query('SELECT COUNT(*) FROM user')->fetchColumn() === 0) {
            $stmt = $pdo->prepare('INSERT INTO user (username, password) VALUES (?, ?)');
            $stmt->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT)]);
        }

        if ((int) $pdo->query('SELECT COUNT(*) FROM identity')->fetchColumn() === 0) {
            $logo = is_file($this->picturesDir . DIRECTORY_SEPARATOR . 'Profil_20260922_085925.png')
                ? 'Profil_20260922_085925.png'
                : 'default.png';
            $stmt = $pdo->prepare(
                'INSERT INTO identity (id, logo, tittle, name, background, audio, marquee) VALUES (1, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $logo,
                'Masjid Digital Signage',
                'Jl. Masjid Raya No. 1',
                'bg1.png',
                'assets/audio/audio.mp3',
                'Selamat datang — Luruskan dan rapatkan shaf sebelum menunaikan shalat berjamaah.',
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
                 VALUES (1, 'Jumat Pekan Ini', 'default.png', 'Khotib Jum''at')"
            );
        }
    }
}
