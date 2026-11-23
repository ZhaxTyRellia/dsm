<?php

declare(strict_types=1);

namespace Tests;

use App\Core\Kernel;
use App\Service\DateTimeService;
use App\Service\FileUploadService;
use App\Service\YoutubeEmbedService;
use App\Support\BasePathDetector;
use DateTimeImmutable;
use InvalidArgumentException;
use PDO;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Slim\Psr7\UploadedFile;

final class ServicesTest extends TestCase
{
    public function testBasePathDetector(): void
    {
        $this->assertSame('/dsm', BasePathDetector::detect('/dsm', []));
        $this->assertSame('/dsm', BasePathDetector::detect('/dsm/', []));

        $this->assertSame('/dsm', BasePathDetector::detect('', [
            'SCRIPT_NAME' => '/dsm/public/index.php',
            'REQUEST_URI' => '/dsm/login',
        ]));

        $this->assertSame('/dsm', BasePathDetector::detect('', [
            'SCRIPT_NAME' => '/dsm/public/index.php',
            'REQUEST_URI' => '/dsm',
        ]));

        $this->assertSame('', BasePathDetector::detect('', [
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/login',
        ]));

        $this->assertSame('', BasePathDetector::detect('', [
            'SCRIPT_NAME' => '/public/index.php',
            'REQUEST_URI' => '/potrait',
        ]));
    }

    public function testYoutubeEmbedService(): void
    {
        $service = new YoutubeEmbedService();

        $watch = $service->parse('https://www.youtube.com/watch?v=abc123XYZ');
        $this->assertSame('abc123XYZ', $watch['video_id']);
        $this->assertSame('https://www.youtube.com/embed/abc123XYZ', $watch['embed_src']);

        $short = $service->parse('https://youtu.be/xyz987');
        $this->assertSame('xyz987', $short['video_id']);
        $this->assertSame('https://www.youtube.com/embed/xyz987', $short['embed_src']);

        $live = $service->parse('https://www.youtube.com/live/liveId55?feature=share');
        $this->assertSame('liveId55', $live['video_id']);
        $this->assertSame('https://www.youtube.com/embed/liveId55', $live['embed_src']);

        $channel = $service->parse('https://www.youtube.com/?channel=UC12345');
        $this->assertSame('UC12345', $channel['channel_id']);
        $this->assertSame('https://www.youtube.com/embed/live_stream?channel=UC12345', $channel['embed_src']);

        $empty = $service->parse('');
        $this->assertSame('', $empty['embed_src']);
    }

    public function testDateTimeService(): void
    {
        $service = new DateTimeService();
        $friday = new DateTimeImmutable('2026-11-27 10:00:00');

        $this->assertSame('<b>Jumat</b>, 27 November 2026', $service->formatCurrentDateHtml($friday));

        $fridays = $service->upcomingFridays(3, $friday);
        $this->assertSame([
            'Jumat, 27 November 2026',
            'Jumat, 4 Desember 2026',
            'Jumat, 11 Desember 2026',
        ], $fridays);
    }

    public function testKernelEcdsaP256VerificationAndFactoryReset(): void
    {
        $cnf = 'C:/WampDock/apache/conf/openssl.cnf';
        $opts = ['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1'];
        if (is_file($cnf)) {
            $opts['config'] = $cnf;
        }
        $key = openssl_pkey_new($opts);
        $this->assertNotFalse($key);

        $privatePem = '';
        openssl_pkey_export($key, $privatePem, null, $opts);
        $details = openssl_pkey_get_details($key);
        $publicPem = (string) $details['key'];

        $tempUploads = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dsm_kernel_uploads_' . uniqid('', true);
        mkdir($tempUploads . DIRECTORY_SEPARATOR . 'banner', 0777, true);
        mkdir($tempUploads . DIRECTORY_SEPARATOR . 'audio', 0777, true);
        file_put_contents($tempUploads . DIRECTORY_SEPARATOR . 'banner' . DIRECTORY_SEPARATOR . 'custom.jpg', 'img');
        file_put_contents($tempUploads . DIRECTORY_SEPARATOR . 'audio' . DIRECTORY_SEPARATOR . 'audio.mp3', 'base');

        try {
            $kernel = new Kernel(
                ':memory:',
                '/dsm',
                $tempUploads,
                false,
                null,
                $publicPem,
                static fn (): string => '  ab12-cd34-ef56  '
            );

            $this->assertSame('AB12-CD34-EF56', $kernel->getDeviceId());
            $this->assertFalse($kernel->isActivated());

            $derSig = '';
            $this->assertTrue(openssl_sign('AB12-CD34-EF56', $derSig, $privatePem, OPENSSL_ALGO_SHA256));
            $activateKey = rtrim(strtr(base64_encode($derSig), '+/', '-_'), '=');

            $this->assertTrue($kernel->verifyActivateKey('  ab12-cd34-ef56 ', $activateKey));
            $this->assertFalse($kernel->verifyActivateKey('OTHER-DEVICE-ID', $activateKey));
            $this->assertFalse($kernel->verifyActivateKey('AB12-CD34-EF56', $activateKey . '='));
            $this->assertFalse($kernel->verifyActivateKey('AB12-CD34-EF56', 'tampered-key'));

            $this->assertFalse($kernel->activate('invalid')['success']);
            $this->assertTrue($kernel->activate($activateKey)['success']);
            $this->assertTrue($kernel->isActivated());

            // Verify license key is stored in identity.activate_key and system_meta does not exist
            $pdo = $kernel->pdo();
            $this->assertSame($activateKey, $pdo->query('SELECT activate_key FROM identity WHERE id = 1')->fetchColumn());
            $this->assertSame(0, (int) $pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = 'system_meta'")->fetchColumn());

            // Verify factory seeded default rows
            $this->assertSame('admin', $pdo->query("SELECT username FROM user LIMIT 1")->fetchColumn());
            $this->assertSame(3, (int) $pdo->query("SELECT COUNT(*) FROM data_rawatib")->fetchColumn());
            $this->assertSame(2, (int) $pdo->query("SELECT COUNT(*) FROM monthly_planner")->fetchColumn());
            $this->assertSame(2, (int) $pdo->query("SELECT COUNT(*) FROM data_list")->fetchColumn());
            $this->assertSame(3, (int) $pdo->query("SELECT COUNT(*) FROM staff")->fetchColumn());

            // Mutate database then factory reset (preserving license in identity.activate_key)
            $pdo->exec("DELETE FROM data_rawatib");
            $pdo->exec("UPDATE identity SET tittle = 'Modified Masjid' WHERE id = 1");

            $uploadService = new FileUploadService($tempUploads);
            $kernel->factoryReset($uploadService, false);

            $this->assertSame('Masjid Agung Al-Ikhlas', $pdo->query("SELECT tittle FROM identity WHERE id = 1")->fetchColumn());
            $this->assertSame($activateKey, $pdo->query('SELECT activate_key FROM identity WHERE id = 1')->fetchColumn());
            $this->assertSame(3, (int) $pdo->query("SELECT COUNT(*) FROM data_rawatib")->fetchColumn());
            $this->assertFileDoesNotExist($tempUploads . DIRECTORY_SEPARATOR . 'banner' . DIRECTORY_SEPARATOR . 'custom.jpg');
            $this->assertFileExists($tempUploads . DIRECTORY_SEPARATOR . 'audio' . DIRECTORY_SEPARATOR . 'audio.mp3');
            $this->assertTrue($kernel->isActivated());

            // Reset including license clears identity.activate_key
            $kernel->factoryReset($uploadService, true);
            $this->assertSame('', $pdo->query('SELECT activate_key FROM identity WHERE id = 1')->fetchColumn());
            $this->assertFalse($kernel->isActivated());
        } finally {
            foreach (['banner', 'audio'] as $sub) {
                $d = $tempUploads . DIRECTORY_SEPARATOR . $sub;
                foreach (glob($d . DIRECTORY_SEPARATOR . '*') ?: [] as $f) {
                    @unlink($f);
                }
                @rmdir($d);
            }
            @rmdir($tempUploads);
        }
    }

    public function testFileUploadServicePerModuleFolders(): void
    {
        $baseDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dsm_upload_test_' . uniqid('', true);
        mkdir($baseDir . DIRECTORY_SEPARATOR . 'rawatib', 0777, true);
        mkdir($baseDir . DIRECTORY_SEPARATOR . 'audio', 0777, true);

        $srcImg = $baseDir . DIRECTORY_SEPARATOR . 'source.png';
        $srcBad = $baseDir . DIRECTORY_SEPARATOR . 'source.exe';
        $srcAudio = $baseDir . DIRECTORY_SEPARATOR . 'source.mp3';
        file_put_contents($srcImg, 'png-bytes');
        file_put_contents($srcBad, 'exe-bytes');
        file_put_contents($srcAudio, 'mp3-bytes');
        file_put_contents($baseDir . DIRECTORY_SEPARATOR . 'audio' . DIRECTORY_SEPARATOR . 'audio.mp3', 'keep-default');

        try {
            $service = new FileUploadService($baseDir);

            $uploadedImg = new UploadedFile($srcImg, 'photo.png', 'image/png', filesize($srcImg), UPLOAD_ERR_OK, false);
            $savedName = $service->moveUploadedImage($uploadedImg, FileUploadService::CATEGORY_RAWATIB, 'avatar_', ['png', 'jpg'], true);
            $this->assertStringStartsWith('avatar_', $savedName);
            $this->assertTrue($service->imageExists(FileUploadService::CATEGORY_RAWATIB, $savedName));
            $this->assertSame('uploads/rawatib/' . $savedName, $service->publicImageUrl(FileUploadService::CATEGORY_RAWATIB, $savedName));
            $this->assertSame('assets/img/default.png', $service->publicImageUrl(FileUploadService::CATEGORY_RAWATIB, 'default.png'));

            $uploadedAudio = new UploadedFile($srcAudio, 'custom.mp3', 'audio/mpeg', filesize($srcAudio), UPLOAD_ERR_OK, false);
            $audioName = $service->moveUploadedAudio($uploadedAudio);
            $this->assertFileExists($baseDir . DIRECTORY_SEPARATOR . 'audio' . DIRECTORY_SEPARATOR . $audioName);

            $service->clearAllUploads();
            $this->assertFalse($service->imageExists(FileUploadService::CATEGORY_RAWATIB, $savedName));
            $this->assertFileDoesNotExist($baseDir . DIRECTORY_SEPARATOR . 'audio' . DIRECTORY_SEPARATOR . $audioName);
            $this->assertFileExists($baseDir . DIRECTORY_SEPARATOR . 'audio' . DIRECTORY_SEPARATOR . 'audio.mp3');

            $badFile = new UploadedFile($srcBad, 'bad.exe', 'application/octet-stream', filesize($srcBad), UPLOAD_ERR_OK, false);
            $this->expectException(InvalidArgumentException::class);
            $service->moveUploadedImage($badFile, FileUploadService::CATEGORY_RAWATIB, 'avatar_', ['png', 'jpg']);
        } finally {
            foreach (glob($baseDir . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . '*') ?: [] as $f) {
                @unlink($f);
            }
            foreach (glob($baseDir . DIRECTORY_SEPARATOR . '*') ?: [] as $f) {
                if (is_file($f)) {
                    @unlink($f);
                } elseif (is_dir($f)) {
                    @rmdir($f);
                }
            }
            @rmdir($baseDir);
        }
    }

    public function testZeroExternalCdnUrlsExistInTemplatesOrAssets(): void
    {
        $root = dirname(__DIR__);
        $scanDirs = [
            $root . '/public/assets/css',
            $root . '/public/assets/js',
            $root . '/templates',
        ];

        $allowedHosts = ['www.youtube.com', 'youtube.com', 'youtu.be', 'www.w3.org'];

        foreach ($scanDirs as $dir) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
            foreach ($iterator as $file) {
                if (!$file->isFile()) {
                    continue;
                }
                $content = (string) file_get_contents($file->getPathname());
                if (preg_match_all('#https?://[^\s"\'\)\<]+#i', $content, $matches)) {
                    foreach ($matches[0] as $url) {
                        $host = strtolower((string) (parse_url($url, PHP_URL_HOST) ?? ''));
                        $this->assertContains(
                            $host,
                            $allowedHosts,
                            "Disallowed external URL '{$url}' found in {$file->getPathname()}"
                        );
                    }
                }
            }
        }
    }

    public function testMigratesLegacySystemMetaLicenseIntoIdentityTable(): void
    {
        $cnf = 'C:/WampDock/apache/conf/openssl.cnf';
        $opts = ['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1'];
        if (is_file($cnf)) {
            $opts['config'] = $cnf;
        }
        $key = openssl_pkey_new($opts);
        $this->assertNotFalse($key);

        $privatePem = '';
        openssl_pkey_export($key, $privatePem, null, $opts);
        $publicPem = (string) openssl_pkey_get_details($key)['key'];

        $derSig = '';
        openssl_sign('LEGACY-DEVICE-01', $derSig, $privatePem, OPENSSL_ALGO_SHA256);
        $validKey = rtrim(strtr(base64_encode($derSig), '+/', '-_'), '=');

        $pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $pdo->exec('CREATE TABLE system_meta (meta_key TEXT PRIMARY KEY, meta_value TEXT NOT NULL, updated_at TEXT)');
        $pdo->exec("CREATE TABLE identity (id INTEGER PRIMARY KEY AUTOINCREMENT, logo TEXT, tittle TEXT, name TEXT, background TEXT, audio TEXT, marquee TEXT)");
        $pdo->exec("INSERT INTO identity (id, logo, tittle, name, background, audio, marquee) VALUES (1, 'default.png', 'Masjid Legacy', 'Jl. Lama', 'bg1.png', 'uploads/audio/audio.mp3', 'Hello')");
        $stmt = $pdo->prepare("INSERT INTO system_meta (meta_key, meta_value) VALUES ('activate_key', ?)");
        $stmt->execute([$validKey]);

        $kernel = new Kernel(
            ':memory:',
            '/dsm',
            sys_get_temp_dir(),
            false,
            $pdo,
            $publicPem,
            static fn (): string => 'legacy-device-01'
        );

        $this->assertTrue($kernel->isActivated());
        $this->assertSame($validKey, $pdo->query('SELECT activate_key FROM identity WHERE id = 1')->fetchColumn());
        $this->assertSame(0, (int) $pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type = 'table' AND name = 'system_meta'")->fetchColumn());
    }
}
