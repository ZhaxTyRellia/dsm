<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Service\LicenseService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

final class LicenseMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly LicenseService $licenseService,
        private readonly string $basePath
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->licenseService->isLicensed()) {
            $response = new Response(302);
            return $response->withHeader('Location', $this->basePath . '/license');
        }

        return $handler->handle($request);
    }
}
