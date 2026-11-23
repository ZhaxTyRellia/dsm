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
    private string $picDir;
    private string $audioDir;
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
        $this->picDir = $this->tempDir . DIRECTORY_SEPARATOR . 'pictures';
        $this->audioDir = $this->tempDir . DIRECTORY_SEPARATOR . 'audio';
        mkdir($this->picDir, 0777, true);
        mkdir($this->audioDir, 0777, true);
        file_put_contents($this->picDir . DIRECTORY_SEPARATOR . 'default.png', 'default-img');

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
        foreach ([$this->picDir, $this->audioDir, $this->tempDir] as $dir) {
            if (!is_dir($dir)) {
                continue;
            }
            foreach (glob($dir . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }
        @rmdir($this->picDir);
        @rmdir($this->audioDir);
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
            $this->picDir,
            false,
            $this->pdo,
            $this->publicKeyPem,
            fn (): string => $this->deviceUuid
        );

        // Initialize schema & default seed data via Kernel::pdo()
        $kernel->pdo();

        if ($licensed) {
            $kernel->activate($this->signDeviceId($this->deviceUuid));
        }

        $factory = require dirname(__DIR__) . '/config/bootstrap.php';

        return $factory([
            'basePath' => $basePath,
            Kernel::class => $kernel,
            FileUploadService::class => new FileUploadService($this->picDir, $this->audioDir),
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

        // Dashboard, Login, Control Panel, and Admin POST actions are locked while unlicensed
        $resDash = $this->dispatch($app, 'GET', '/dsm/');
        $this->assertSame(200, $resDash->getStatusCode());
        $dashBody = (string) $resDash->getBody();
        $this->assertStringContainsString('<base href="/dsm/">', $dashBody);
        $this->assertStringContainsString('AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE', $dashBody);
        $this->assertStringContainsString('ECDSA P-256', $dashBody);

        $resLogin = $this->dispatch($app, 'GET', '/dsm/login');
        $this->assertSame(200, $resLogin->getStatusCode());
        $this->assertStringContainsString('Aktivasi Lisensi Aplikasi', (string) $resLogin->getBody());

        $resControl = $this->dispatch($app, 'GET', '/dsm/control/identity');
        $this->assertSame(200, $resControl->getStatusCode());
        $this->assertStringContainsString('Aktivasi Lisensi Aplikasi', (string) $resControl->getBody());

        $this->dispatch($app, 'POST', '/dsm/youtube', ['youtube_url' => 'https://youtu.be/hacked']);
        $this->assertSame('', $this->pdo->query('SELECT url FROM youtube WHERE id = 1')->fetchColumn());

        // Public Display & APIs remain accessible without license activation
        $resPortrait = $this->dispatch($app, 'GET', '/dsm/potrait');
        $this->assertSame(200, $resPortrait->getStatusCode());
        $this->assertStringContainsString('<base href="/dsm/">', (string) $resPortrait->getBody());
        $this->assertStringContainsString("fetch('api/money", (string) $resPortrait->getBody());

        $this->assertSame(200, $this->dispatch($app, 'GET', '/dsm/api/money')->getStatusCode());
        $this->assertSame(200, $this->dispatch($app, 'GET', '/dsm/api/shalat-time')->getStatusCode());
        $this->assertSame(200, $this->dispatch($app, 'GET', '/dsm/api/khotbah')->getStatusCode());
        $this->assertSame(200, $this->dispatch($app, 'GET', '/dsm/api/scene-plus')->getStatusCode());
        $this->assertSame(200, $this->dispatch($app, 'GET', '/dsm/api/time')->getStatusCode());

        // Invalid Activate Key fails gracefully
        $resBad = $this->dispatch($app, 'POST', '/dsm/', ['activate_key' => 'invalid-key']);
        $this->assertSame(200, $resBad->getStatusCode());
        $this->assertStringContainsString('Activate Key tidak valid', (string) $resBad->getBody());

        // Valid Activate Key unlocks application offline and persists in SQLite system_meta
        $validActivateKey = $this->signDeviceId($this->deviceUuid);
        $resOk = $this->dispatch($app, 'POST', '/dsm/', ['activate_key' => $validActivateKey]);
        $this->assertSame(302, $resOk->getStatusCode());
        $this->assertSame('/dsm/', $resOk->getHeaderLine('Location'));

        $stored = $this->pdo->query("SELECT meta_value FROM system_meta WHERE meta_key = 'activate_key'")->fetchColumn();
        $this->assertSame($validActivateKey, $stored);

        // Now /dsm/ enforces AuthMiddleware -> redirects to /dsm/login
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

        // Default seeded SQLite account: admin / admin123
        $okLogin = $this->dispatch($app, 'POST', '/dsm/login', ['username' => 'admin', 'password' => 'admin123']);
        $this->assertSame(302, $okLogin->getStatusCode());
        $this->assertSame('/dsm/', $okLogin->getHeaderLine('Location'));
        $this->assertTrue($_SESSION['logged_in']);

        $dash = $this->dispatch($app, 'GET', '/dsm/');
        $this->assertSame(200, $dash->getStatusCode());
        $html = (string) $dash->getBody();
        $this->assertStringContainsString('<base href="/dsm/">', $html);
        $this->assertStringContainsString('assets/css/admin.css', $html);
        $this->assertStringContainsString('assets/js/admin.js', $html);
        $this->assertStringContainsString('id="appSidebar"', $html);
        $this->assertStringContainsString('Laporan Kas Masjid', $html);
        $this->assertStringContainsString('Daftar Imam Rawatib', $html);

        $logout = $this->dispatch($app, 'GET', '/dsm/logout');
        $this->assertSame(302, $logout->getStatusCode());
        $this->assertSame('/dsm/login', $logout->getHeaderLine('Location'));
        $this->assertEmpty($_SESSION);
    }

    public function testCrudOperationsAndValidation(): void
    {
        $app = $this->createTestApp(true);
        $_SESSION['logged_in'] = true;

        // 1. YouTube
        $resYt = $this->dispatch($app, 'POST', '/dsm/youtube', ['youtube_url' => 'https://youtu.be/newVid01']);
        $this->assertSame(302, $resYt->getStatusCode());
        $this->assertSame('/dsm/', $resYt->getHeaderLine('Location'));
        $this->assertSame('https://youtu.be/newVid01', $this->pdo->query('SELECT url FROM youtube WHERE id = 1')->fetchColumn());

        // 2. Money
        $resMoneyBad = $this->dispatch($app, 'POST', '/dsm/money', ['closing_umat' => 'not-a-number']);
        $this->assertSame(422, $resMoneyBad->getStatusCode());
        $resMoneyOk = $this->dispatch($app, 'POST', '/dsm/money', [
            'opening_date' => '10.11.2026',
            'opening_umat' => '100',
            'opening_masjid' => '200',
            'closing_date' => '17.11.2026',
            'closing_umat' => '300',
            'closing_masjid' => '450',
        ]);
        $this->assertSame(302, $resMoneyOk->getStatusCode());
        $this->assertSame(750.0, (float) $this->pdo->query('SELECT closing_total FROM data_money WHERE id = 1')->fetchColumn());

        // 3. Rawatib
        $tmpImg = $this->tempDir . DIRECTORY_SEPARATOR . 'imam.png';
        file_put_contents($tmpImg, 'fake-png');
        $uploadedImam = new UploadedFile($tmpImg, 'imam.png', 'image/png', filesize($tmpImg), UPLOAD_ERR_OK, false);

        $resRawInsert = $this->dispatch($app, 'POST', '/dsm/rawatib', [
            'data_shalat' => 'ISYA',
            'hari' => 'SELASA',
            'name' => 'Ustadz Zaid',
        ], ['profile' => $uploadedImam]);
        $this->assertSame(302, $resRawInsert->getStatusCode());

        $newId = (int) $this->pdo->query("SELECT id FROM data_rawatib WHERE name = 'Ustadz Zaid'")->fetchColumn();
        $this->assertGreaterThan(0, $newId);

        $resRawDup = $this->dispatch($app, 'POST', '/dsm/rawatib', [
            'data_shalat' => 'ISYA',
            'hari' => 'SELASA',
            'name' => 'Ustadz Zaid',
        ]);
        $this->assertSame(409, $resRawDup->getStatusCode());

        $tmpExe = $this->tempDir . DIRECTORY_SEPARATOR . 'bad.exe';
        file_put_contents($tmpExe, 'bad');
        $resRawBadExt = $this->dispatch($app, 'POST', '/dsm/rawatib/update', [
            'id' => (string) $newId,
            'data_shalat' => 'ISYA',
            'hari' => 'SELASA',
            'name' => 'Ustadz Zaid Updated',
        ], ['profile' => new UploadedFile($tmpExe, 'bad.exe', 'application/octet-stream', 3, UPLOAD_ERR_OK, false)]);
        $this->assertSame(422, $resRawBadExt->getStatusCode());

        $resRawUpd = $this->dispatch($app, 'POST', '/dsm/rawatib/update', [
            'id' => (string) $newId,
            'data_shalat' => 'MAGHRIB',
            'hari' => 'RABU',
            'name' => 'Ustadz Zaid Updated',
        ]);
        $this->assertSame(302, $resRawUpd->getStatusCode());
        $this->assertSame('Ustadz Zaid Updated', $this->pdo->query("SELECT name FROM data_rawatib WHERE id = {$newId}")->fetchColumn());

        $resRawDel = $this->dispatch($app, 'GET', "/dsm/rawatib/{$newId}/delete");
        $this->assertSame(302, $resRawDel->getStatusCode());
        $this->assertFalse($this->pdo->query("SELECT id FROM data_rawatib WHERE id = {$newId}")->fetch());

        // 4. Activity
        $this->dispatch($app, 'POST', '/dsm/activity', [
            'id_desc' => '2',
            'desc' => 'KAJIAN UMUM',
            'time' => '09:00',
            'speaker' => 'Ustadz Umar',
        ]);
        $actId = (int) $this->pdo->query("SELECT id FROM monthly_planner WHERE speaker = 'Ustadz Umar'")->fetchColumn();
        $this->assertGreaterThan(0, $actId);
        $this->dispatch($app, 'GET', "/dsm/activity/{$actId}/delete");
        $this->assertFalse($this->pdo->query("SELECT id FROM monthly_planner WHERE id = {$actId}")->fetch());

        // 5. Khotbah List
        $this->dispatch($app, 'POST', '/dsm/khotbah-list', [
            'moon' => 'Desember',
            'date' => '04',
            'identity' => 'Ustadz Bilal',
        ]);
        $kId = (int) $this->pdo->query("SELECT id FROM data_list WHERE identity = 'Ustadz Bilal'")->fetchColumn();
        $this->dispatch($app, 'POST', '/dsm/khotbah-list/update', [
            'id' => (string) $kId,
            'moon' => 'Desember',
            'date' => '11',
            'identity' => 'Ustadz Bilal Baru',
        ]);
        $this->assertSame('Ustadz Bilal Baru', $this->pdo->query("SELECT identity FROM data_list WHERE id = {$kId}")->fetchColumn());
        $this->dispatch($app, 'GET', "/dsm/khotbah-list/{$kId}/delete");
        $this->assertFalse($this->pdo->query("SELECT id FROM data_list WHERE id = {$kId}")->fetch());

        // 6. Staff
        $this->dispatch($app, 'POST', '/dsm/staff', ['tier' => 'BENDAHARA', 'identity' => 'H. Salim']);
        $staffRow = $this->pdo->query("SELECT * FROM staff WHERE identity = 'H. Salim'")->fetch();
        $this->assertSame(5, (int) $staffRow['id_no']);
        $this->dispatch($app, 'GET', '/dsm/staff/' . (int) $staffRow['id'] . '/delete');

        // 7. Banner
        $this->dispatch($app, 'POST', '/dsm/banner/slides', ['active_slides' => '2']);
        $tmpBanner = $this->tempDir . DIRECTORY_SEPARATOR . 'slide2.jpg';
        file_put_contents($tmpBanner, 'jpg-bytes');
        $this->dispatch($app, 'POST', '/dsm/banner/upload', ['pict2' => 'save'], [
            'pict2' => new UploadedFile($tmpBanner, 'slide2.jpg', 'image/jpeg', 9, UPLOAD_ERR_OK, false),
        ]);
        $uploadedPict2 = (string) $this->pdo->query('SELECT pict2 FROM banner WHERE id = 1')->fetchColumn();
        $this->assertStringStartsWith('image_', $uploadedPict2);
        $this->assertFileExists($this->picDir . DIRECTORY_SEPARATOR . $uploadedPict2);

        $this->dispatch($app, 'POST', '/dsm/banner/slides', ['active_slides' => '1']);
        $this->assertSame(1, (int) $this->pdo->query('SELECT active_slides FROM banner WHERE id = 1')->fetchColumn());
        $this->assertSame('default.png', $this->pdo->query('SELECT pict2 FROM banner WHERE id = 1')->fetchColumn());
        $this->assertFileDoesNotExist($this->picDir . DIRECTORY_SEPARATOR . $uploadedPict2);
    }

    public function testControlPanelRelativePathsAndShutdown(): void
    {
        $app = $this->createTestApp(true);
        $_SESSION['logged_in'] = true;

        $resIdPage = $this->dispatch($app, 'GET', '/dsm/control/identity');
        $this->assertSame(200, $resIdPage->getStatusCode());
        $this->assertStringContainsString('id="appSidebar"', (string) $resIdPage->getBody());

        $this->dispatch($app, 'POST', '/dsm/control/identity', [
            'tittle' => 'Masjid Baru',
            'name' => 'Jl. Baru No. 2',
            'marquee' => 'Info Baru',
        ]);
        $this->assertSame('Masjid Baru', $this->pdo->query('SELECT tittle FROM identity WHERE id = 1')->fetchColumn());

        $this->assertSame(200, $this->dispatch($app, 'GET', '/dsm/control/background')->getStatusCode());
        $this->dispatch($app, 'POST', '/dsm/control/background', ['background' => 'bg4.jpg']);
        $this->assertSame('bg4.jpg', $this->pdo->query('SELECT background FROM identity WHERE id = 1')->fetchColumn());

        $this->assertSame(200, $this->dispatch($app, 'GET', '/dsm/control/audio')->getStatusCode());
        $tmpMp3 = $this->tempDir . DIRECTORY_SEPARATOR . 'sound.mp3';
        file_put_contents($tmpMp3, 'mp3-data');
        $this->dispatch($app, 'POST', '/dsm/control/audio', [], [
            'audio_file' => new UploadedFile($tmpMp3, 'sound.mp3', 'audio/mpeg', 8, UPLOAD_ERR_OK, false),
        ]);
        $audioPath = (string) $this->pdo->query('SELECT audio FROM identity WHERE id = 1')->fetchColumn();
        $this->assertStringStartsWith('assets/audio/audio_', $audioPath);

        $this->assertSame(200, $this->dispatch($app, 'GET', '/dsm/control/shalat-time')->getStatusCode());
        $resShalat = $this->dispatch($app, 'POST', '/dsm/control/shalat-time', [
            'shalat' => [
                'subuh' => '04:15:00',
                'dzuhur' => '11:55:00',
                'ashar' => '15:10:00',
                'maghrib' => '17:55:00',
                'isya' => '19:05:00',
                'jumat' => '11:55:00',
            ],
        ]);
        $this->assertSame(302, $resShalat->getStatusCode());
        $this->assertSame('/dsm/', $resShalat->getHeaderLine('Location'));
        $this->assertSame('04:15:00', $this->pdo->query('SELECT subuh FROM shalattime WHERE id = 1')->fetchColumn());

        $this->assertSame(200, $this->dispatch($app, 'GET', '/dsm/control/khotbah-day')->getStatusCode());
        $resKhotbah = $this->dispatch($app, 'POST', '/dsm/control/khotbah-day', [
            'id' => '1',
            'speaker' => 'Ustadz Fulan',
            'title' => 'Jumat, 4 Desember 2026',
        ]);
        $this->assertSame(302, $resKhotbah->getStatusCode());
        $this->assertSame('Ustadz Fulan', $this->pdo->query('SELECT name FROM khotbah_view WHERE id = 1')->fetchColumn());

        $resShutdown = $this->dispatch($app, 'GET', '/dsm/control/shutdown');
        $this->assertSame(200, $resShutdown->getStatusCode());
        $this->assertTrue($this->shutdownCalled);
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

        $res404Legacy = $this->dispatch($appSub, 'GET', '/dsm/404.php');
        $this->assertSame(302, $res404Legacy->getStatusCode());
        $this->assertSame('/dsm/', $res404Legacy->getHeaderLine('Location'));

        // Root-hosted relative base URL redirects to '/'
        $appRoot = $this->createTestApp(true, '');
        $resRootNotFound = $this->dispatch($appRoot, 'GET', '/does-not-exist');
        $this->assertSame(302, $resRootNotFound->getStatusCode());
        $this->assertSame('/', $resRootNotFound->getHeaderLine('Location'));
    }
}
