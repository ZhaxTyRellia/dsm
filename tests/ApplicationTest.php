<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Kernel;
use App\Service\FileUploadService;
use PDO;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\UploadedFile;

final class ApplicationTest extends TestCase
{
    private PDO $pdo;
    private string $tempDir;
    private string $uploadsDir;
    private string $privateKeyPem;
    private string $publicKeyPem;
    private string $deviceUuid = 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee';
    private bool $shutdownCalled = false;

    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
        $this->shutdownCalled = false;

        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dsm_app_test_' . uniqid('', true);
        $this->uploadsDir = $this->tempDir . DIRECTORY_SEPARATOR . 'uploads';
        foreach (['identity', 'rawatib', 'banner', 'khotbah', 'audio'] as $cat) {
            mkdir($this->uploadsDir . DIRECTORY_SEPARATOR . $cat, 0777, true);
        }
        file_put_contents($this->uploadsDir . DIRECTORY_SEPARATOR . 'audio' . DIRECTORY_SEPARATOR . 'audio.mp3', 'default-audio');

        $cnf = 'C:/WampDock/apache/conf/openssl.cnf';
        $opts = ['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1'];
        if (is_file($cnf)) {
            $opts['config'] = $cnf;
        }
        $key = openssl_pkey_new($opts);
        $priv = '';
        openssl_pkey_export($key, $priv, null, $opts);
        $details = openssl_pkey_get_details($key);
        $this->privateKeyPem = $priv;
        $this->publicKeyPem = (string) $details['key'];

        $this->pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    protected function tearDown(): void
    {
        foreach (['identity', 'rawatib', 'banner', 'khotbah', 'audio'] as $cat) {
            $dir = $this->uploadsDir . DIRECTORY_SEPARATOR . $cat;
            foreach (glob($dir . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
                @unlink($file);
            }
            @rmdir($dir);
        }
        @rmdir($this->uploadsDir);
        foreach (glob($this->tempDir . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
        @rmdir($this->tempDir);
        $_SESSION = [];
        parent::tearDown();
    }

    private function signDeviceId(string $deviceId): string
    {
        $normalized = strtoupper(trim($deviceId));
        $der = '';
        openssl_sign($normalized, $der, $this->privateKeyPem, OPENSSL_ALGO_SHA256);

        return rtrim(strtr(base64_encode($der), '+/', '-_'), '=');
    }

    private function createTestApp(bool $licensed = true, string $basePath = '/dsm'): App
    {
        $kernel = new Kernel(
            ':memory:',
            $basePath,
            $this->uploadsDir,
            false,
            $this->pdo,
            $this->publicKeyPem,
            fn (): string => $this->deviceUuid
        );

        $kernel->pdo();

        if ($licensed) {
            $kernel->activate($this->signDeviceId($this->deviceUuid));
        }

        $factory = require dirname(__DIR__) . '/config/bootstrap.php';

        return $factory([
            'basePath' => $basePath,
            Kernel::class => $kernel,
            FileUploadService::class => new FileUploadService($this->uploadsDir),
            'shutdownRunner' => function (): void {
                $this->shutdownCalled = true;
            },
        ]);
    }

    /**
     * @param array<string, mixed> $body
     * @param array<string, UploadedFile> $files
     */
    private function dispatch(
        App $app,
        string $method,
        string $uri,
        array $body = [],
        array $files = []
    ): ResponseInterface {
        $request = (new ServerRequestFactory())->createServerRequest($method, $uri);
        if ($body !== []) {
            $request = $request->withParsedBody($body);
        }
        if ($files !== []) {
            $request = $request->withUploadedFiles($files);
        }

        return $app->handle($request);
    }

    public function testEmbeddedEcdsaLicenseLockAndOfflineActivation(): void
    {
        $app = $this->createTestApp(false);

        $resDash = $this->dispatch($app, 'GET', '/dsm/');
        $this->assertSame(200, $resDash->getStatusCode());
        $dashBody = (string) $resDash->getBody();
        $this->assertStringContainsString('<base href="/dsm/">', $dashBody);
        $this->assertStringContainsString('assets/img/favicon.svg', $dashBody);
        $this->assertStringContainsString('AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE', $dashBody);

        $resLogin = $this->dispatch($app, 'GET', '/dsm/login');
        $this->assertSame(200, $resLogin->getStatusCode());
        $this->assertStringContainsString('Aktivasi Lisensi Aplikasi', (string) $resLogin->getBody());

        $this->dispatch($app, 'POST', '/dsm/youtube', ['youtube_url' => 'https://youtu.be/hacked']);
        $this->assertSame('', $this->pdo->query('SELECT url FROM youtube WHERE id = 1')->fetchColumn());

        // Public Display & APIs remain accessible without license activation
        $resPortrait = $this->dispatch($app, 'GET', '/dsm/potrait');
        $this->assertSame(200, $resPortrait->getStatusCode());
        $portraitBody = (string) $resPortrait->getBody();
        $this->assertStringContainsString('<base href="/dsm/">', $portraitBody);
        $this->assertStringContainsString('assets/img/favicon.svg', $portraitBody);
        $this->assertStringNotContainsString('font-awesome', $portraitBody);
        $this->assertStringNotContainsString('jquery', $portraitBody);

        $this->assertSame(200, $this->dispatch($app, 'GET', '/dsm/api/money')->getStatusCode());
        $this->assertSame(200, $this->dispatch($app, 'GET', '/dsm/api/shalat-time')->getStatusCode());
        $this->assertSame(200, $this->dispatch($app, 'GET', '/dsm/api/khotbah')->getStatusCode());
        $this->assertSame(200, $this->dispatch($app, 'GET', '/dsm/api/scene-plus')->getStatusCode());
        $this->assertSame(200, $this->dispatch($app, 'GET', '/dsm/api/time')->getStatusCode());

        $resBad = $this->dispatch($app, 'POST', '/dsm/', ['activate_key' => 'invalid-key']);
        $this->assertSame(200, $resBad->getStatusCode());
        $this->assertStringContainsString('Activate Key tidak valid', (string) $resBad->getBody());

        $validActivateKey = $this->signDeviceId($this->deviceUuid);
        $resOk = $this->dispatch($app, 'POST', '/dsm/', ['activate_key' => $validActivateKey]);
        $this->assertSame(302, $resOk->getStatusCode());
        $this->assertSame('/dsm/', $resOk->getHeaderLine('Location'));

        $stored = $this->pdo->query("SELECT meta_value FROM system_meta WHERE meta_key = 'activate_key'")->fetchColumn();
        $this->assertSame($validActivateKey, $stored);

        $resNow = $this->dispatch($app, 'GET', '/dsm/');
        $this->assertSame(302, $resNow->getStatusCode());
        $this->assertSame('/dsm/login', $resNow->getHeaderLine('Location'));
    }

    public function testAuthAndLuxurySidebarDashboardFlow(): void
    {
        $app = $this->createTestApp(true);

        $badUser = $this->dispatch($app, 'POST', '/dsm/login', ['username' => 'nobody', 'password' => 'x']);
        $this->assertSame(200, $badUser->getStatusCode());
        $this->assertStringContainsString('Username tidak ditemukan!', (string) $badUser->getBody());

        $badPass = $this->dispatch($app, 'POST', '/dsm/login', ['username' => 'admin', 'password' => 'wrong']);
        $this->assertSame(200, $badPass->getStatusCode());
        $this->assertStringContainsString('Password salah!', (string) $badPass->getBody());

        $okLogin = $this->dispatch($app, 'POST', '/dsm/login', ['username' => 'admin', 'password' => 'admin123']);
        $this->assertSame(302, $okLogin->getStatusCode());
        $this->assertSame('/dsm/', $okLogin->getHeaderLine('Location'));

        $dash = $this->dispatch($app, 'GET', '/dsm/');
        $this->assertSame(200, $dash->getStatusCode());
        $html = (string) $dash->getBody();
        $this->assertStringContainsString('<base href="/dsm/">', $html);
        $this->assertStringContainsString('assets/img/favicon.svg', $html);
        $this->assertStringContainsString('id="appSidebar"', $html);
        $this->assertStringContainsString('control/factory-reset', $html);

        $logout = $this->dispatch($app, 'GET', '/dsm/logout');
        $this->assertSame(302, $logout->getStatusCode());
        $this->assertSame('/dsm/login', $logout->getHeaderLine('Location'));
    }

    public function testCrudPerModuleUploadsAndFactoryResetEndpoint(): void
    {
        $app = $this->createTestApp(true);
        $_SESSION['logged_in'] = true;

        // 1. YouTube
        $resYt = $this->dispatch($app, 'POST', '/dsm/youtube', ['youtube_url' => 'https://youtu.be/newVid01']);
        $this->assertSame(302, $resYt->getStatusCode());
        $this->assertSame('/dsm/', $resYt->getHeaderLine('Location'));

        // 2. Money
        $this->assertSame(422, $this->dispatch($app, 'POST', '/dsm/money', ['closing_umat' => 'NaN'])->getStatusCode());
        $this->dispatch($app, 'POST', '/dsm/money', [
            'opening_date' => '10.11.2026',
            'closing_date' => '17.11.2026',
            'closing_umat' => '300',
            'closing_masjid' => '450',
        ]);
        $this->assertSame(750.0, (float) $this->pdo->query('SELECT closing_total FROM data_money WHERE id = 1')->fetchColumn());

        // 3. Rawatib upload lands in uploads/rawatib/
        $tmpImg = $this->tempDir . DIRECTORY_SEPARATOR . 'imam.png';
        file_put_contents($tmpImg, 'fake-png');
        $this->dispatch($app, 'POST', '/dsm/rawatib', [
            'data_shalat' => 'ISYA',
            'hari' => 'SELASA',
            'name' => 'Ustadz Zaid',
        ], ['profile' => new UploadedFile($tmpImg, 'imam.png', 'image/png', filesize($tmpImg), UPLOAD_ERR_OK, false)]);

        $rawRow = $this->pdo->query("SELECT * FROM data_rawatib WHERE name = 'Ustadz Zaid'")->fetch();
        $this->assertNotFalse($rawRow);
        $this->assertFileExists($this->uploadsDir . DIRECTORY_SEPARATOR . 'rawatib' . DIRECTORY_SEPARATOR . $rawRow['profile']);

        $this->assertSame(409, $this->dispatch($app, 'POST', '/dsm/rawatib', [
            'data_shalat' => 'ISYA',
            'hari' => 'SELASA',
            'name' => 'Ustadz Zaid',
        ])->getStatusCode());

        // 4. Banner upload lands in uploads/banner/
        $this->dispatch($app, 'POST', '/dsm/banner/slides', ['active_slides' => '2']);
        $tmpBanner = $this->tempDir . DIRECTORY_SEPARATOR . 'slide2.jpg';
        file_put_contents($tmpBanner, 'jpg-bytes');
        $this->dispatch($app, 'POST', '/dsm/banner/upload', ['pict2' => 'save'], [
            'pict2' => new UploadedFile($tmpBanner, 'slide2.jpg', 'image/jpeg', 9, UPLOAD_ERR_OK, false),
        ]);
        $uploadedPict2 = (string) $this->pdo->query('SELECT pict2 FROM banner WHERE id = 1')->fetchColumn();
        $this->assertFileExists($this->uploadsDir . DIRECTORY_SEPARATOR . 'banner' . DIRECTORY_SEPARATOR . $uploadedPict2);

        // 5. Identity logo upload lands in uploads/identity/
        $tmpLogo = $this->tempDir . DIRECTORY_SEPARATOR . 'logo.png';
        file_put_contents($tmpLogo, 'logo-bytes');
        $this->dispatch($app, 'POST', '/dsm/control/identity', [
            'tittle' => 'Masjid Custom',
            'name' => 'Jl. Custom',
            'marquee' => 'Marquee Custom',
        ], ['logo' => new UploadedFile($tmpLogo, 'logo.png', 'image/png', 10, UPLOAD_ERR_OK, false)]);
        $uploadedLogo = (string) $this->pdo->query('SELECT logo FROM identity WHERE id = 1')->fetchColumn();
        $this->assertFileExists($this->uploadsDir . DIRECTORY_SEPARATOR . 'identity' . DIRECTORY_SEPARATOR . $uploadedLogo);

        // 6. Khotbah avatar upload lands in uploads/khotbah/
        $tmpKhotib = $this->tempDir . DIRECTORY_SEPARATOR . 'khotib.png';
        file_put_contents($tmpKhotib, 'khotib-bytes');
        $this->dispatch($app, 'POST', '/dsm/control/khotbah-day', [
            'id' => '1',
            'speaker' => 'Ustadz Fulan',
            'title' => 'Jumat, 4 Desember 2026',
        ], ['avatar' => new UploadedFile($tmpKhotib, 'khotib.png', 'image/png', 12, UPLOAD_ERR_OK, false)]);
        $uploadedAvatar = (string) $this->pdo->query('SELECT avatar FROM khotbah_view WHERE id = 1')->fetchColumn();
        $this->assertFileExists($this->uploadsDir . DIRECTORY_SEPARATOR . 'khotbah' . DIRECTORY_SEPARATOR . $uploadedAvatar);

        // 7. Audio upload lands in uploads/audio/
        $tmpMp3 = $this->tempDir . DIRECTORY_SEPARATOR . 'sound.mp3';
        file_put_contents($tmpMp3, 'mp3-data');
        $this->dispatch($app, 'POST', '/dsm/control/audio', [], [
            'audio_file' => new UploadedFile($tmpMp3, 'sound.mp3', 'audio/mpeg', 8, UPLOAD_ERR_OK, false),
        ]);
        $audioPath = (string) $this->pdo->query('SELECT audio FROM identity WHERE id = 1')->fetchColumn();
        $this->assertStringStartsWith('uploads/audio/audio_', $audioPath);

        // 8. Factory Reset via endpoint resets DB & deletes uploaded files while preserving license
        $resReset = $this->dispatch($app, 'POST', '/dsm/control/factory-reset');
        $this->assertSame(302, $resReset->getStatusCode());
        $this->assertSame('/dsm/', $resReset->getHeaderLine('Location'));

        $this->assertSame('Masjid Agung Al-Ikhlas', $this->pdo->query('SELECT tittle FROM identity WHERE id = 1')->fetchColumn());
        $this->assertSame(0.0, (float) $this->pdo->query('SELECT closing_total FROM data_money WHERE id = 1')->fetchColumn());
        $this->assertSame(1, (int) $this->pdo->query('SELECT active_slides FROM banner WHERE id = 1')->fetchColumn());
        $this->assertSame(3, (int) $this->pdo->query('SELECT COUNT(*) FROM data_rawatib')->fetchColumn());
        $this->assertFileDoesNotExist($this->uploadsDir . DIRECTORY_SEPARATOR . 'rawatib' . DIRECTORY_SEPARATOR . $rawRow['profile']);
        $this->assertFileDoesNotExist($this->uploadsDir . DIRECTORY_SEPARATOR . 'banner' . DIRECTORY_SEPARATOR . $uploadedPict2);
        $this->assertFileDoesNotExist($this->uploadsDir . DIRECTORY_SEPARATOR . 'identity' . DIRECTORY_SEPARATOR . $uploadedLogo);
        $this->assertFileDoesNotExist($this->uploadsDir . DIRECTORY_SEPARATOR . 'khotbah' . DIRECTORY_SEPARATOR . $uploadedAvatar);
        $this->assertFileExists($this->uploadsDir . DIRECTORY_SEPARATOR . 'audio' . DIRECTORY_SEPARATOR . 'audio.mp3');

        // License key still preserved after admin factory reset
        $this->assertNotFalse($this->pdo->query("SELECT meta_value FROM system_meta WHERE meta_key = 'activate_key'")->fetchColumn());
    }

    public function testErrorsRedirectDirectlyToBaseUrl(): void
    {
        $appSub = $this->createTestApp(true, '/dsm');

        $resNotFound = $this->dispatch($appSub, 'GET', '/dsm/does-not-exist');
        $this->assertSame(302, $resNotFound->getStatusCode());
        $this->assertSame('/dsm/', $resNotFound->getHeaderLine('Location'));

        $resEnv = $this->dispatch($appSub, 'GET', '/dsm/.env');
        $this->assertSame(302, $resEnv->getStatusCode());
        $this->assertSame('/dsm/', $resEnv->getHeaderLine('Location'));

        $appRoot = $this->createTestApp(true, '');
        $resRootNotFound = $this->dispatch($appRoot, 'GET', '/does-not-exist');
        $this->assertSame(302, $resRootNotFound->getStatusCode());
        $this->assertSame('/', $resRootNotFound->getHeaderLine('Location'));
    }
}
