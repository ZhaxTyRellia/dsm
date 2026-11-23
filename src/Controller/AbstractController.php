<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Kernel;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Slim\Views\PhpRenderer;

abstract class AbstractController
{
    public function __construct(
        protected readonly Kernel $kernel,
        protected readonly PhpRenderer $view,
        protected readonly string $basePath
    ) {
    }

    protected function pdo(): PDO
    {
        return $this->kernel->pdo();
    }

    protected function assertActivated(ServerRequestInterface $request): void
    {
        $this->kernel->assertActivated($request);
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function render(
        ResponseInterface $response,
        string $template,
        array $data = [],
        int $status = 200
    ): ResponseInterface {
        $rendered = $this->view->render($response, $template, $data);
        return $rendered->withStatus($status);
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function renderAdmin(
        ServerRequestInterface $request,
        ResponseInterface $response,
        string $template,
        array $data = [],
        string $pageTitle = 'Dashboard',
        string $activeNav = 'dashboard',
        int $status = 200
    ): ResponseInterface {
        $this->assertActivated($request);

        $brandIdentity = $this->pdo()
            ->query('SELECT logo, tittle, name FROM identity WHERE id = 1 LIMIT 1')
            ->fetch() ?: [
                'logo' => 'default.png',
                'tittle' => 'Masjid Digital Signage',
                'name' => 'Panel Manajemen',
            ];

        $flash = $this->pullFlash();
        $content = $this->view->fetch($template, $data);

        return $this->render($response, 'layouts/admin.php', [
            'pageTitle' => $pageTitle,
            'activeNav' => $activeNav,
            'brandIdentity' => $brandIdentity,
            'flash' => $flash,
            'content' => $content,
        ], $status);
    }

    protected function flash(string $type, string $message): void
    {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message,
        ];
    }

    /**
     * @return array{type: string, message: string}|null
     */
    protected function pullFlash(): ?array
    {
        if (!isset($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
            return null;
        }

        $flash = [
            'type' => (string) ($_SESSION['flash']['type'] ?? 'info'),
            'message' => (string) ($_SESSION['flash']['message'] ?? ''),
        ];
        unset($_SESSION['flash']);

        return $flash['message'] !== '' ? $flash : null;
    }

    protected function redirect(ResponseInterface $response, string $path, int $status = 302): ResponseInterface
    {
        $prefix = $this->basePath !== '' ? $this->basePath : '';
        $target = str_starts_with($path, '/') ? $prefix . $path : $prefix . '/' . $path;

        return $response
            ->withHeader('Location', $target)
            ->withStatus($status);
    }

    protected function json(ResponseInterface $response, mixed $data, int $status = 200): ResponseInterface
    {
        $payload = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $response->getBody()->write($payload !== false ? $payload : '{}');

        return $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withStatus($status);
    }

    protected function text(ResponseInterface $response, string $content, int $status = 200): ResponseInterface
    {
        $response->getBody()->write($content);

        return $response
            ->withHeader('Content-Type', 'text/plain; charset=utf-8')
            ->withStatus($status);
    }

    protected function html(ResponseInterface $response, string $content, int $status = 200): ResponseInterface
    {
        $response->getBody()->write($content);

        return $response
            ->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->withStatus($status);
    }

    /**
     * @return array<string, mixed>
     */
    protected function body(ServerRequestInterface $request): array
    {
        $parsed = $request->getParsedBody();
        return is_array($parsed) ? $parsed : [];
    }

    /**
     * @return array<string, UploadedFileInterface>
     */
    protected function files(ServerRequestInterface $request): array
    {
        $files = $request->getUploadedFiles();
        $result = [];
        foreach ($files as $key => $file) {
            if ($file instanceof UploadedFileInterface) {
                $result[(string) $key] = $file;
            }
        }
        return $result;
    }
}
