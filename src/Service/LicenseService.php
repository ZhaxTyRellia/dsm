<?php

declare(strict_types=1);

namespace App\Service;

final class LicenseService
{
    /** @var (callable(): ?string)|null */
    private $uuidResolver;

    /**
     * @param (callable(): ?string)|null $uuidResolver
     */
    public function __construct(
        private readonly string $licenseFilePath,
        private readonly string $secretSalt,
        ?callable $uuidResolver = null
    ) {
        $this->uuidResolver = $uuidResolver;
    }

    public function getDeviceUuid(): ?string
    {
        if ($this->uuidResolver !== null) {
            $resolved = ($this->uuidResolver)();
            if ($resolved === null) {
                return null;
            }
            $uuid = trim((string) $resolved);
        } else {
            $command = 'powershell -NoProfile -Command "(Get-CimInstance Win32_ComputerSystemProduct).UUID"';
            $output = @shell_exec($command);
            $uuid = trim((string) $output);
        }

        if (preg_match('/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/', $uuid)) {
            return strtoupper($uuid);
        }

        return null;
    }

    public function generateValidKey(?string $deviceId): ?string
    {
        if ($deviceId === null || $deviceId === '') {
            return null;
        }

        return hash_hmac('sha256', $deviceId, $this->secretSalt);
    }

    public function isLicensed(): bool
    {
        $deviceId = $this->getDeviceUuid();
        $validSerial = $this->generateValidKey($deviceId);

        if ($validSerial === null || !is_file($this->licenseFilePath)) {
            return false;
        }

        $currentLicense = trim((string) @file_get_contents($this->licenseFilePath));

        return $currentLicense !== '' && hash_equals($validSerial, $currentLicense);
    }

    /**
     * @return array{success: bool, error: ?string}
     */
    public function activate(string $inputSerial): array
    {
        $deviceId = $this->getDeviceUuid();
        $validSerial = $this->generateValidKey($deviceId);
        $trimmedInput = trim($inputSerial);

        if ($validSerial !== null && hash_equals($validSerial, $trimmedInput)) {
            $dir = dirname($this->licenseFilePath);
            if ($dir !== '' && !is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }

            if (@file_put_contents($this->licenseFilePath, $trimmedInput) !== false) {
                return ['success' => true, 'error' => null];
            }

            return [
                'success' => false,
                'error' => 'Failed to save license file at ' . $this->licenseFilePath . '. Please ensure the folder has the correct access permissions.',
            ];
        }

        return [
            'success' => false,
            'error' => 'The serial number you entered is not valid for this PC!',
        ];
    }
}
