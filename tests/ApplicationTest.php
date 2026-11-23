<?php

declare(strict_types=1);

namespace Tests;

use App\Service\FileUploadService;
use App\Service\LicenseService;
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
    private string $licenseFile;
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
        $this->licenseFile = $this->tempDir . DIRECTORY_SEPARATOR . 'license.key';

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $this->seedDatabase();
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

    private function seedDatabase(): void
    {
        $statements = [
            'CREATE TABLE user (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT, password TEXT)',
            'CREATE TABLE identity (id INTEGER PRIMARY KEY AUTOINCREMENT, logo TEXT, tittle TEXT, name TEXT, background TEXT, audio TEXT, marquee TEXT)',
            'CREATE TABLE youtube (id INTEGER PRIMARY KEY AUTOINCREMENT, url TEXT)',
            'CREATE TABLE data_money (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                opening_date TEXT, period_start TEXT, report_date TEXT,
                opening_umat REAL, opening_masjid REAL, opening_total REAL,
                income_umat REAL, income_masjid REAL, income_total REAL,
                position_date TEXT, position_umat REAL, position_masjid REAL, position_total REAL,
                expense_date TEXT, expense_umat REAL, expense_masjid REAL, expense_total REAL,
                closing_date TEXT, closing_umat REAL, closing_masjid REAL, closing_total REAL,
                updated_at TEXT
            )',
            'CREATE TABLE data_rawatib (id INTEGER PRIMARY KEY AUTOINCREMENT, data_shalat TEXT, hari TEXT, name TEXT, profile TEXT)',
            'CREATE TABLE monthly_planner (id INTEGER PRIMARY KEY AUTOINCREMENT, id_desc INTEGER, `desc` TEXT, `time` TEXT, speaker TEXT)',
            'CREATE TABLE data_list (id INTEGER PRIMARY KEY AUTOINCREMENT, moon TEXT, `date` TEXT, identity TEXT)',
            'CREATE TABLE staff (id INTEGER PRIMARY KEY AUTOINCREMENT, tier TEXT, id_no INTEGER, identity TEXT)',
            'CREATE TABLE banner (
                id INTEGER PRIMARY KEY AUTOINCREMENT, active_slides INTEGER,
                pict1 TEXT, pict2 TEXT, pict3 TEXT, pict4 TEXT, pict5 TEXT,
                pict6 TEXT, pict7 TEXT, pict8 TEXT, pict9 TEXT
            )',
            'CREATE TABLE shalattime (id INTEGER PRIMARY KEY AUTOINCREMENT, subuh TEXT, dzuhur TEXT, ashar TEXT, maghrib TEXT, isya TEXT, jumat TEXT)',
            'CREATE TABLE khotbah_view (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT, avatar TEXT, name TEXT)',
        ];

        foreach ($statements as $sql) {
            $this->pdo->exec($sql);
        }

        $this->pdo->exec("INSERT INTO user (username, password) VALUES ('admin', 'secret123')");
        $this->pdo->exec("INSERT INTO identity (id, logo, tittle, name, background, audio, marquee) VALUES (1, 'default.png', 'Masjid Al-Ikhlas', 'Jl. Merdeka No. 1', 'bg1.png', '/dsm/assets/audio/audio.mp3', 'Selamat Datang')");
        $this->pdo->exec("INSERT INTO youtube (id, url) VALUES (1, 'https://www.youtube.com/watch?v=dQw4w9WgXcQ')");
        $this->pdo->exec("INSERT INTO data_money (id, opening_date, opening_umat, opening_masjid, closing_date, closing_umat, closing_masjid, closing_total, updated_at) VALUES (1, '01.11.2026', 100000, 200000, '08.11.2026', 150000, 250000, 400000, '2026-11-08 10:00:00')");
        $this->pdo->exec("INSERT INTO data_rawatib (data_shalat, hari, name, profile) VALUES ('SUBUH', 'SENIN', 'Ustadz Ahmad', 'default.png')");
        $this->pdo->exec("INSERT INTO monthly_planner (id_desc, `desc`, `time`, speaker) VALUES (1, 'KAJIAN HADITS', 'Ba''da Maghrib', 'Ustadz Ali')");
        $this->pdo->exec("INSERT INTO data_list (moon, `date`, identity) VALUES ('November', '20', 'Ustadz Hasan')");
        $this->pdo->exec("INSERT INTO staff (tier, id_no, identity) VALUES ('KETUA', 2, 'H. Budi')");
        $this->pdo->exec("INSERT INTO banner (id, active_slides, pict1, pict2, pict3, pict4, pict5, pict6, pict7, pict8, pict9) VALUES (1, 2, 'default.png', 'default.png', 'default.png', 'default.png', 'default.png', 'default.png', 'default.png', 'default.png', 'default.png')");
        $this->pdo->exec("INSERT INTO shalattime (id, subuh, dzuhur, ashar, maghrib, isya, jumat) VALUES (1, '04:30:00', '12:00:00', '15:15:00', '18:00:00', '19:15:00', '12:00:00')");
        $this->pdo->exec("INSERT INTO khotbah_view (id, title, avatar, name) VALUES (1, 'Jumat, 27 November 2026', 'default.png', 'Ustadz Hasan')");
    }

    private function createTestApp(bool $licensed = true, string $basePath = '/dsm'): App
    {
        $uuid = 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
        $licenseService = new LicenseService($this->licenseFile, 'MTP-Production!!!', static fn (): string => $uuid);
        if ($licensed) {
            $validKey = $licenseService->generateValidKey($uuid);
            file_put_contents($this->licenseFile, (string) $validKey);
        } elseif (is_file($this->licenseFile)) {
            unlink($this->licenseFile);
        }

        $factory = require dirname(__DIR__) . '/config/bootstrap.php';

        return $factory([
            'basePath' => $basePath,
            PDO::class => $this->pdo,
            LicenseService::class => $licenseService,
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

    public function testLicenseMiddlewareAndActivationFlow(): void
    {
        $app = $this->createTestApp(false);

        // Unlicensed access to dashboard redirects to /dsm/license
        $res = $this->dispatch($app, 'GET', '/dsm/');
        $this->assertSame(302, $res->getStatusCode());
        $this->assertSame('/dsm/license', $res->getHeaderLine('Location'));

        // License page renders device ID
        $resPage = $this->dispatch($app, 'GET', '/dsm/license');
        $this->assertSame(200, $resPage->getStatusCode());
        $this->assertStringContainsString('AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE', (string) $resPage->getBody());

        // Invalid serial stays on license page with error
        $resInvalid = $this->dispatch($app, 'POST', '/dsm/license', ['serial_number' => 'WRONG']);
        $this->assertSame(200, $resInvalid->getStatusCode());
        $this->assertStringContainsString('not valid for this PC', (string) $resInvalid->getBody());

        // Valid serial activates license and redirects to dashboard
        $uuid = 'AAAAAAAA-BBBB-CCCC-DDDD-EEEEEEEEEEEE';
        $validKey = hash_hmac('sha256', $uuid, 'MTP-Production!!!');
        $resValid = $this->dispatch($app, 'POST', '/dsm/license', ['serial_number' => $validKey]);
        $this->assertSame(302, $resValid->getStatusCode());
        $this->assertSame('/dsm/', $resValid->getHeaderLine('Location'));
    }

    public function testAuthAndDashboardFlow(): void
    {
        $app = $this->createTestApp(true);

        // Unauthenticated dashboard redirects to /dsm/login
        $res = $this->dispatch($app, 'GET', '/dsm/');
        $this->assertSame(302, $res->getStatusCode());
        $this->assertSame('/dsm/login', $res->getHeaderLine('Location'));

        // Unknown username error
        $badUser = $this->dispatch($app, 'POST', '/dsm/login', ['username' => 'nobody', 'password' => 'x']);
        $this->assertSame(200, $badUser->getStatusCode());
        $this->assertStringContainsString('Username tidak ditemukan!', (string) $badUser->getBody());

        // Wrong password error
        $badPass = $this->dispatch($app, 'POST', '/dsm/login', ['username' => 'admin', 'password' => 'wrong']);
        $this->assertSame(200, $badPass->getStatusCode());
        $this->assertStringContainsString('Password salah!', (string) $badPass->getBody());

        // Valid login redirects to /dsm/
        $okLogin = $this->dispatch($app, 'POST', '/dsm/login', ['username' => 'admin', 'password' => 'secret123']);
        $this->assertSame(302, $okLogin->getStatusCode());
        $this->assertSame('/dsm/', $okLogin->getHeaderLine('Location'));
        $this->assertTrue($_SESSION['logged_in']);

        // Authenticated dashboard renders sections
        $dash = $this->dispatch($app, 'GET', '/dsm/');
        $this->assertSame(200, $dash->getStatusCode());
        $html = (string) $dash->getBody();
        $this->assertStringContainsString('Video Banner', $html);
        $this->assertStringContainsString('Data Keuangan', $html);
        $this->assertStringContainsString('Daftar Imam Rawatib', $html);
        $this->assertStringContainsString('Ustadz Ahmad', $html);
        $this->assertStringContainsString('H. Budi', $html);

        // Logout clears session
        $logout = $this->dispatch($app, 'GET', '/dsm/logout');
        $this->assertSame(302, $logout->getStatusCode());
        $this->assertSame('/dsm/login', $logout->getHeaderLine('Location'));
        $this->assertEmpty($_SESSION);
    }

    public function testCrudOperationsAndValidation(): void
    {
        $app = $this->createTestApp(true);
        $_SESSION['logged_in'] = true;

        // 1. YouTube update
        $resYt = $this->dispatch($app, 'POST', '/dsm/youtube', ['youtube_url' => 'https://youtu.be/newVid01']);
        $this->assertSame(302, $resYt->getStatusCode());
        $this->assertSame('/dsm/', $resYt->getHeaderLine('Location'));
        $this->assertSame('https://youtu.be/newVid01', $this->pdo->query('SELECT url FROM youtube WHERE id = 1')->fetchColumn());

        // 2. Money update (invalid vs valid)
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

        // 3. Rawatib insert, duplicate check, update, delete
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

        // Duplicate reject
        $resRawDup = $this->dispatch($app, 'POST', '/dsm/rawatib', [
            'data_shalat' => 'ISYA',
            'hari' => 'SELASA',
            'name' => 'Ustadz Zaid',
        ]);
        $this->assertSame(409, $resRawDup->getStatusCode());

        // Update with invalid extension -> 422
        $tmpExe = $this->tempDir . DIRECTORY_SEPARATOR . 'bad.exe';
        file_put_contents($tmpExe, 'bad');
        $resRawBadExt = $this->dispatch($app, 'POST', '/dsm/rawatib/update', [
            'id' => (string) $newId,
            'data_shalat' => 'ISYA',
            'hari' => 'SELASA',
            'name' => 'Ustadz Zaid Updated',
        ], ['profile' => new UploadedFile($tmpExe, 'bad.exe', 'application/octet-stream', 3, UPLOAD_ERR_OK, false)]);
        $this->assertSame(422, $resRawBadExt->getStatusCode());

        // Update valid
        $resRawUpd = $this->dispatch($app, 'POST', '/dsm/rawatib/update', [
            'id' => (string) $newId,
            'data_shalat' => 'MAGHRIB',
            'hari' => 'RABU',
            'name' => 'Ustadz Zaid Updated',
        ]);
        $this->assertSame(302, $resRawUpd->getStatusCode());
        $this->assertSame('Ustadz Zaid Updated', $this->pdo->query("SELECT name FROM data_rawatib WHERE id = {$newId}")->fetchColumn());

        // Delete
        $resRawDel = $this->dispatch($app, 'GET', "/dsm/rawatib/{$newId}/delete");
        $this->assertSame(302, $resRawDel->getStatusCode());
        $this->assertFalse($this->pdo->query("SELECT id FROM data_rawatib WHERE id = {$newId}")->fetch());

        // 4. Activity insert & delete
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

        // 5. Khotbah list insert, update, delete
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

        // 6. Staff insert maps id_no & delete
        $this->dispatch($app, 'POST', '/dsm/staff', ['tier' => 'BENDAHARA', 'identity' => 'H. Salim']);
        $staffRow = $this->pdo->query("SELECT * FROM staff WHERE identity = 'H. Salim'")->fetch();
        $this->assertSame(5, (int) $staffRow['id_no']);
        $this->dispatch($app, 'GET', '/dsm/staff/' . (int) $staffRow['id'] . '/delete');

        // 7. Banner upload & slide count reduction
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

    public function testControlPanelAndShutdown(): void
    {
        $app = $this->createTestApp(true);
        $_SESSION['logged_in'] = true;

        $this->assertSame(200, $this->dispatch($app, 'GET', '/dsm/control/identity')->getStatusCode());
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
        $this->assertStringStartsWith('/dsm/assets/audio/audio_', $audioPath);

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
        $this->assertStringContainsString('Jadwal shalat berhasil diperbarui!', (string) $resShalat->getBody());
        $this->assertSame('04:15:00', $this->pdo->query('SELECT subuh FROM shalattime WHERE id = 1')->fetchColumn());

        $this->assertSame(200, $this->dispatch($app, 'GET', '/dsm/control/khotbah-day')->getStatusCode());
        $resKhotbah = $this->dispatch($app, 'POST', '/dsm/control/khotbah-day', [
            'id' => '1',
            'speaker' => 'Ustadz Fulan',
            'title' => 'Jumat, 4 Desember 2026',
        ]);
        $this->assertStringContainsString('Data berhasil diupdate', (string) $resKhotbah->getBody());
        $this->assertSame('Ustadz Fulan', $this->pdo->query('SELECT name FROM khotbah_view WHERE id = 1')->fetchColumn());

        $resShutdown = $this->dispatch($app, 'GET', '/dsm/control/shutdown');
        $this->assertSame(200, $resShutdown->getStatusCode());
        $this->assertTrue($this->shutdownCalled);
    }

    public function testPublicDisplayAndApisAndErrors(): void
    {
        $app = $this->createTestApp(false); // Display & APIs work publicly regardless of login/license

        $portrait = $this->dispatch($app, 'GET', '/dsm/potrait');
        $this->assertSame(200, $portrait->getStatusCode());
        $portraitHtml = (string) $portrait->getBody();
        $this->assertStringContainsString('Masjid Al-Ikhlas', $portraitHtml);
        $this->assertStringContainsString('/dsm/api/money', $portraitHtml);

        $money = $this->dispatch($app, 'GET', '/dsm/api/money');
        $this->assertSame(200, $money->getStatusCode());
        $this->assertStringContainsString('TOTAL KAS MASJID', (string) $money->getBody());

        $shalat = $this->dispatch($app, 'GET', '/dsm/api/shalat-time');
        $this->assertSame(200, $shalat->getStatusCode());
        $shalatJson = json_decode((string) $shalat->getBody(), true);
        $this->assertSame('04:30:00', $shalatJson['subuh']);

        $khotbah = $this->dispatch($app, 'GET', '/dsm/api/khotbah');
        $this->assertSame(200, $khotbah->getStatusCode());
        $khotbahJson = json_decode((string) $khotbah->getBody(), true);
        $this->assertSame('Ustadz Hasan', $khotbahJson['name']);

        $scenePlus = $this->dispatch($app, 'GET', '/dsm/api/scene-plus');
        $this->assertSame(200, $scenePlus->getStatusCode());
        $scenePlusJson = json_decode((string) $scenePlus->getBody(), true);
        $this->assertSame(2, $scenePlusJson['active_slides']);
        $this->assertSame(['default.png', 'default.png'], $scenePlusJson['images']);

        $time = $this->dispatch($app, 'GET', '/dsm/api/time');
        $this->assertSame(200, $time->getStatusCode());
        $this->assertStringContainsString('<b>', (string) $time->getBody());

        // Legacy endpoint aliases
        $this->assertSame(200, $this->dispatch($app, 'GET', '/dsm/potrait.php')->getStatusCode());
        $this->assertSame(200, $this->dispatch($app, 'GET', '/dsm/money_update.php')->getStatusCode());
        $this->assertSame(200, $this->dispatch($app, 'GET', '/dsm/potrait_update.php')->getStatusCode());
        $this->assertSame(200, $this->dispatch($app, 'GET', '/dsm/special_update.php')->getStatusCode());
        $this->assertSame(200, $this->dispatch($app, 'GET', '/dsm/scene_plus_update.php')->getStatusCode());
        $this->assertSame(200, $this->dispatch($app, 'GET', '/dsm/app/control/get_time.php')->getStatusCode());

        // Custom error pages & 404 handler
        $res403 = $this->dispatch($app, 'GET', '/dsm/403');
        $this->assertSame(403, $res403->getStatusCode());
        $this->assertStringContainsString('ACCESS DENIED', (string) $res403->getBody());

        $res404 = $this->dispatch($app, 'GET', '/dsm/does-not-exist');
        $this->assertSame(404, $res404->getStatusCode());
        $this->assertStringContainsString('PAGE NOT FOUND', (string) $res404->getBody());
    }
}
