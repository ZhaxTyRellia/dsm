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
use PHPUnit\Framework\TestCase;
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

    public function testKernelEcdsaP256VerificationAndSqliteBootstrap(): void
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

        $kernel = new Kernel(
            ':memory:',
            '/dsm',
            dirname(__DIR__) . '/public/assets/img/pictures',
            false,
            null,
            $publicPem,
            static fn (): string => '  ab12-cd34-ef56  '
        );

        $this->assertSame('AB12-CD34-EF56', $kernel->getDeviceId());
        $this->assertFalse($kernel->isActivated());

        // Sign normalized device ID using ECDSA SHA-256 (DER format) -> unpadded Base64URL
        $derSig = '';
        $this->assertTrue(openssl_sign('AB12-CD34-EF56', $derSig, $privatePem, OPENSSL_ALGO_SHA256));
        $activateKey = rtrim(strtr(base64_encode($derSig), '+/', '-_'), '=');

        $this->assertTrue($kernel->verifyActivateKey('  ab12-cd34-ef56 ', $activateKey));
        $this->assertFalse($kernel->verifyActivateKey('OTHER-DEVICE-ID', $activateKey));
        $this->assertFalse($kernel->verifyActivateKey('AB12-CD34-EF56', $activateKey . '='));
        $this->assertFalse($kernel->verifyActivateKey('AB12-CD34-EF56', 'tampered-key'));

        $bad = $kernel->activate('invalid');
        $this->assertFalse($bad['success']);
        $this->assertFalse($kernel->isActivated());

        $ok = $kernel->activate($activateKey);
        $this->assertTrue($ok['success']);
        $this->assertTrue($kernel->isActivated());

        // Verify SQLite auto-schema and default seed rows
        $pdo = $kernel->pdo();
        $this->assertSame('admin', $pdo->query("SELECT username FROM user LIMIT 1")->fetchColumn());
        $this->assertSame(1, (int) $pdo->query("SELECT COUNT(*) FROM banner")->fetchColumn());
        $this->assertSame(1, (int) $pdo->query("SELECT COUNT(*) FROM data_money")->fetchColumn());
    }

    public function testFileUploadService(): void
    {
        $baseDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dsm_upload_test_' . uniqid('', true);
        $picDir = $baseDir . DIRECTORY_SEPARATOR . 'pictures';
        $audioDir = $baseDir . DIRECTORY_SEPARATOR . 'audio';
        mkdir($picDir, 0777, true);
        mkdir($audioDir, 0777, true);

        $srcImg = $baseDir . DIRECTORY_SEPARATOR . 'source.png';
        $srcBad = $baseDir . DIRECTORY_SEPARATOR . 'source.exe';
        file_put_contents($srcImg, 'png-bytes');
        file_put_contents($srcBad, 'exe-bytes');
        file_put_contents($picDir . DIRECTORY_SEPARATOR . 'default.png', 'keep-me');

        try {
            $service = new FileUploadService($picDir, $audioDir);

            $uploadedImg = new UploadedFile($srcImg, 'photo.png', 'image/png', filesize($srcImg), UPLOAD_ERR_OK, false);
            $savedName = $service->moveUploadedImage($uploadedImg, 'avatar_', ['png', 'jpg'], true);
            $this->assertStringStartsWith('avatar_', $savedName);
            $this->assertStringEndsWith('.png', $savedName);
            $this->assertTrue($service->pictureExists($savedName));

            // default.png must never be deleted
            $service->deletePicture('default.png');
            $this->assertTrue($service->pictureExists('default.png'));

            $service->deletePicture($savedName);
            $this->assertFalse($service->pictureExists($savedName));

            $badFile = new UploadedFile($srcBad, 'bad.exe', 'application/octet-stream', filesize($srcBad), UPLOAD_ERR_OK, false);
            $this->expectException(InvalidArgumentException::class);
            $service->moveUploadedImage($badFile, 'avatar_', ['png', 'jpg']);
        } finally {
            foreach (glob($picDir . '/*') ?: [] as $f) {
                @unlink($f);
            }
            foreach (glob($audioDir . '/*') ?: [] as $f) {
                @unlink($f);
            }
            foreach (glob($baseDir . '/*') ?: [] as $f) {
                if (is_file($f)) {
                    @unlink($f);
                }
            }
            @rmdir($picDir);
            @rmdir($audioDir);
            @rmdir($baseDir);
        }
    }
}
