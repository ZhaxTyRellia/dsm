<?php

declare(strict_types=1);

namespace App\Controller;

use App\Database\Connection;
use App\Service\LicenseService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Views\PhpRenderer;

final class LicenseController extends AbstractController
{
    public function __construct(
        Connection $db,
        PhpRenderer $view,
        string $basePath,
        private readonly LicenseService $licenseService
    ) {
        parent::__construct($db, $view, $basePath);
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($this->licenseService->isLicensed()) {
            return $this->redirect($response, '/');
        }

        return $this->render($response, 'license/activate.php', [
            'deviceId' => $this->licenseService->getDeviceUuid(),
            'errorMsg' => null,
        ]);
    }

    public function activate(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $body = $this->body($request);
        $serial = (string) ($body['serial_number'] ?? '');
        $result = $this->licenseService->activate($serial);

        if ($result['success']) {
            return $this->redirect($response, '/');
        }

        return $this->render($response, 'license/activate.php', [
            'deviceId' => $this->licenseService->getDeviceUuid(),
            'errorMsg' => $result['error'],
        ]);
    }
}
