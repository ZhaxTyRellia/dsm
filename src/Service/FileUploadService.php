<?php

declare(strict_types=1);

namespace App\Service;

use InvalidArgumentException;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

final class FileUploadService
{
    public function __construct(
        private readonly string $picturesDir,
        private readonly string $audioDir
    ) {
    }

    /**
     * @param string[]|null $allowedExtensions
     */
    public function moveUploadedImage(
        UploadedFileInterface $file,
        string $prefix,
        ?array $allowedExtensions = null,
        bool $appendRandom = false
    ): string {
        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Gagal mengunggah file gambar.');
        }

        $clientFilename = (string) ($file->getClientFilename() ?? '');
        $ext = strtolower(pathinfo($clientFilename, PATHINFO_EXTENSION));

        if ($allowedExtensions !== null && !in_array($ext, $allowedExtensions, true)) {
            throw new InvalidArgumentException('Format foto harus PNG, JPG, atau JPEG.');
        }

        if (!is_dir($this->picturesDir) && !@mkdir($this->picturesDir, 0755, true) && !is_dir($this->picturesDir)) {
            throw new RuntimeException('Gagal membuat direktori gambar.');
        }

        $suffix = $appendRandom
            ? date('YmdHis') . '_' . random_int(1000, 9999)
            : date('Ymd_His');

        $filename = $prefix . $suffix . ($ext !== '' ? '.' . $ext : '');
        $targetPath = $this->picturesDir . DIRECTORY_SEPARATOR . $filename;

        $file->moveTo($targetPath);

        return $filename;
    }

    public function deletePicture(?string $filename): void
    {
        $safeName = basename(trim((string) $filename));
        if ($safeName === '' || strtolower($safeName) === 'default.png') {
            return;
        }

        $path = $this->picturesDir . DIRECTORY_SEPARATOR . $safeName;
        if (is_file($path)) {
            @unlink($path);
        }
    }

    public function moveUploadedAudio(UploadedFileInterface $file): string
    {
        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Gagal mengunggah file audio.');
        }

        if (!is_dir($this->audioDir) && !@mkdir($this->audioDir, 0755, true) && !is_dir($this->audioDir)) {
            throw new RuntimeException('Gagal membuat direktori audio.');
        }

        $clientFilename = (string) ($file->getClientFilename() ?? '');
        $ext = strtolower(pathinfo($clientFilename, PATHINFO_EXTENSION));
        $filename = 'audio_' . time() . ($ext !== '' ? '.' . $ext : '');
        $targetPath = $this->audioDir . DIRECTORY_SEPARATOR . $filename;

        $file->moveTo($targetPath);

        return $filename;
    }

    public function deleteAudio(?string $storedPath): void
    {
        $safeName = basename(trim((string) $storedPath));
        if ($safeName === '') {
            return;
        }

        $path = $this->audioDir . DIRECTORY_SEPARATOR . $safeName;
        if (is_file($path)) {
            @unlink($path);
        }
    }

    public function pictureExists(string $filename): bool
    {
        $safeName = basename(trim($filename));
        if ($safeName === '') {
            return false;
        }

        return is_file($this->picturesDir . DIRECTORY_SEPARATOR . $safeName);
    }
}
