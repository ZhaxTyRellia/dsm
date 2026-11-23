<?php

declare(strict_types=1);

namespace Tests;

use App\Service\DateTimeService;
use App\Service\FileUploadService;
use App\Service\LicenseService;
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

    public function testLicenseServiceActivationAndVerification(): void
    {
        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dsm_lic_test_' . uniqid('', true);
        mkdir($tempDir, 0777, true);
        $licenseFile = $tempDir . DIRECTORY_SEPARATOR . 'license.key';

        try {
            $uuid = '12345678-1234-1234-1234-1234567890AB';
            $service = new LicenseService($licenseFile, 'MTP-Production!!!', static fn (): string => $uuid);

            $this->assertSame($uuid, $service->getDeviceUuid());
            $this->assertFalse($service->isLicensed());

            $badResult = $service->activate('invalid-serial');
            $this->assertFalse($badResult['success']);
            $this->assertSame('The serial number you entered is not valid for this PC!', $badResult['error']);
            $this->assertFalse($service->isLicensed());

            $validKey = $service->generateValidKey($uuid);
            $this->assertNotNull($validKey);

            $okResult = $service->activate($validKey);
            $this->assertTrue($okResult['success']);
            $this->assertNull($okResult['error']);
            $this->assertTrue($service->isLicensed());
        } finally {
            @unlink($licenseFile);
            @rmdir($tempDir);
        }
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
        $srcAudio = $baseDir . DIRECTORY_SEPARATOR . 'source.mp3';
        file_put_contents($srcImg, 'png-bytes');
        file_put_contents($srcBad, 'exe-bytes');
        file_put_contents($srcAudio, 'mp3-bytes');
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
