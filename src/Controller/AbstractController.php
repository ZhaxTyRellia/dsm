<?php

declare(strict_types=1);

namespace App\Controller;

use App\Database\Connection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Slim\Views\PhpRenderer;

abstract class AbstractController
{
    public function __construct(
        protected readonly Connection $db,
        protected readonly PhpRenderer $view,
        protected readonly string $basePath
    ) {
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

    protected function alertAndRedirect(
        ResponseInterface $response,
        string $message,
        string $path
    ): ResponseInterface {
        $prefix = $this->basePath !== '' ? $this->basePath : '';
        $target = str_starts_with($path, '/') ? $prefix . $path : $prefix . '/' . $path;
        $jsMessage = json_encode($message, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        $jsTarget = json_encode($target, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

        $script = "<script>alert({$jsMessage}); window.location.href = {$jsTarget};</script>";

        return $this->html($response, $script);
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
