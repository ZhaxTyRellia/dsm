<?php

declare(strict_types=1);

namespace App\Support;

use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Interfaces\ErrorRendererInterface;
use Slim\Views\PhpRenderer;
use Throwable;

final class HtmlErrorRenderer implements ErrorRendererInterface
{
    public function __construct(
        private readonly PhpRenderer $view,
        private readonly string $basePath,
        private readonly bool $displayErrorDetails = false
    ) {
    }

    public function __invoke(Throwable $exception, bool $displayErrorDetails): string
    {
        if ($exception instanceof HttpNotFoundException) {
            return $this->view->fetch('errors/404.php', [
                'basePath' => $this->basePath,
            ]);
        }

        if ($exception instanceof HttpForbiddenException) {
            return $this->view->fetch('errors/403.php', [
                'basePath' => $this->basePath,
            ]);
        }

        $showDetails = $displayErrorDetails || $this->displayErrorDetails;
        $message = $showDetails
            ? htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8')
            : 'Terjadi kesalahan internal pada server.';
        $trace = $showDetails
            ? '<pre style="text-align:left;max-width:900px;margin:20px auto;padding:16px;background:#1a1a1a;color:#eee;overflow:auto;border-radius:8px;">'
                . htmlspecialchars((string) $exception, ENT_QUOTES, 'UTF-8')
                . '</pre>'
            : '';
        $homeUrl = htmlspecialchars(($this->basePath !== '' ? $this->basePath : '') . '/', ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>500 Internal Server Error</title>
    <style>
        body { background: #0d0d0d; color: #fff; font-family: sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .box { text-align: center; padding: 24px; }
        .code { font-size: 6rem; font-weight: 800; color: #e74c3c; margin: 0; }
        .msg { font-size: 1.4rem; color: #ff6b6b; margin-top: 12px; }
        .btn { display: inline-block; margin-top: 20px; padding: 12px 24px; border: 2px solid #e74c3c; color: #e74c3c; text-decoration: none; font-weight: bold; }
        .btn:hover { background: #e74c3c; color: #fff; }
    </style>
</head>
<body>
    <div class="box">
        <h1 class="code">500</h1>
        <p class="msg">{$message}</p>
        {$trace}
        <a href="{$homeUrl}" class="btn">BACK TO DASHBOARD</a>
    </div>
</body>
</html>
HTML;
    }
}
