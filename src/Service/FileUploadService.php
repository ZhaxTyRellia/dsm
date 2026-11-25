<?php

declare(strict_types=1);

namespace App\Service;

use InvalidArgumentException;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;

final class FileUploadService
{
    public const CATEGORY_IDENTITY = 'identity';
    public const CATEGORY_RAWATIB = 'rawatib';
    public const CATEGORY_BANNER = 'banner';
    public const CATEGORY_KHOTBAH = 'khotbah';
    public const CATEGORY_AUDIO = 'audio';

    private const ALLOWED_CATEGORIES = [
        self::CATEGORY_IDENTITY,
        self::CATEGORY_RAWATIB,
        self::CATEGORY_BANNER,
        self::CATEGORY_KHOTBAH,
        self::CATEGORY_AUDIO,
    ];

    public function __construct(
        private readonly string $uploadsBaseDir
    ) {
    }

    public function categoryDir(string $category): string
    {
        if (!in_array($category, self::ALLOWED_CATEGORIES, true)) {
            throw new InvalidArgumentException("Invalid upload folder: {$category}");
        }

        return rtrim($this->uploadsBaseDir, '/\\') . DIRECTORY_SEPARATOR . $category;
    }

    /**
     * @param string[]|null $allowedExtensions
     */
    public function moveUploadedImage(
        UploadedFileInterface $file,
        string $category,
        string $prefix,
        ?array $allowedExtensions = null,
        bool $appendRandom = false
    ): string {
        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Unable to upload the image file.');
        }

        $clientFilename = (string) ($file->getClientFilename() ?? '');
        $ext = strtolower(pathinfo($clientFilename, PATHINFO_EXTENSION));

        if ($allowedExtensions !== null && !in_array($ext, $allowedExtensions, true)) {
            throw new InvalidArgumentException('Image format must be PNG, JPG, or JPEG.');
        }

        $targetDir = $this->categoryDir($category);
        if (!is_dir($targetDir) && !@mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
            throw new RuntimeException('Unable to prepare the upload folder.');
        }

        $suffix = $appendRandom
            ? date('YmdHis') . '_' . random_int(1000, 9999)
            : date('Ymd_His');

        $filename = $prefix . $suffix . ($ext !== '' ? '.' . $ext : '');
        $file->moveTo($targetDir . DIRECTORY_SEPARATOR . $filename);

        return $filename;
    }

    private function isDefaultPlaceholder(?string $filename): bool
    {
        $safeName = strtolower(basename(trim((string) $filename)));

        return $safeName === '' || $safeName === 'default.png' || $safeName === 'default.svg';
    }

    public function deleteImage(string $category, ?string $filename): void
    {
        $safeName = basename(trim((string) $filename));
        if ($this->isDefaultPlaceholder($safeName)) {
            return;
        }

        $path = $this->categoryDir($category) . DIRECTORY_SEPARATOR . $safeName;
        if (is_file($path)) {
            @unlink($path);
        }
    }

    public function imageExists(string $category, string $filename): bool
    {
        $safeName = basename(trim($filename));
        if ($this->isDefaultPlaceholder($safeName)) {
            return false;
        }

        return is_file($this->categoryDir($category) . DIRECTORY_SEPARATOR . $safeName);
    }

    public function publicImageUrl(string $category, ?string $filename): string
    {
        $this->categoryDir($category);
        $safeName = basename(trim((string) $filename));

        if ($this->isDefaultPlaceholder($safeName)) {
            return 'assets/img/default.svg';
        }

        return 'uploads/' . $category . '/' . $safeName;
    }

    public function moveUploadedAudio(UploadedFileInterface $file): string
    {
        if ($file->getError() !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Unable to upload the audio file.');
        }

        $targetDir = $this->categoryDir(self::CATEGORY_AUDIO);
        if (!is_dir($targetDir) && !@mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
            throw new RuntimeException('Unable to prepare the audio folder.');
        }

        $clientFilename = (string) ($file->getClientFilename() ?? '');
        $ext = strtolower(pathinfo($clientFilename, PATHINFO_EXTENSION));
        $filename = 'audio_' . time() . ($ext !== '' ? '.' . $ext : '');

        $file->moveTo($targetDir . DIRECTORY_SEPARATOR . $filename);

        return $filename;
    }

    public function deleteAudio(?string $storedPath): void
    {
        $safeName = basename(trim((string) $storedPath));
        if ($safeName === '' || strtolower($safeName) === 'audio.mp3') {
            return;
        }

        $path = $this->categoryDir(self::CATEGORY_AUDIO) . DIRECTORY_SEPARATOR . $safeName;
        if (is_file($path)) {
            @unlink($path);
        }
    }

    public function clearAllUploads(): void
    {
        foreach (self::ALLOWED_CATEGORIES as $category) {
            $dir = $this->categoryDir($category);
            if (!is_dir($dir)) {
                continue;
            }

            foreach (scandir($dir) ?: [] as $item) {
                if ($item === '.' || $item === '..' || $item === '.gitkeep' || $item === '.gitignore') {
                    continue;
                }
                if ($category === self::CATEGORY_AUDIO && strtolower($item) === 'audio.mp3') {
                    continue;
                }
                $full = $dir . DIRECTORY_SEPARATOR . $item;
                if (is_file($full)) {
                    @unlink($full);
                }
            }
        }
    }
}
