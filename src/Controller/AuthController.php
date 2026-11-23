<?php

declare(strict_types=1);

namespace App\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AuthController extends AbstractController
{
    public function showLogin(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (($_SESSION['logged_in'] ?? false) === true) {
            return $this->redirect($response, '/');
        }

        return $this->render($response, 'auth/login.php', [
            'error' => '',
        ]);
    }

    public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (($_SESSION['logged_in'] ?? false) === true) {
            return $this->redirect($response, '/');
        }

        $body = $this->body($request);
        $username = (string) ($body['username'] ?? '');
        $password = (string) ($body['password'] ?? '');

        $stmt = $this->db->pdo()->prepare('SELECT password FROM user WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $row = $stmt->fetch();

        $error = '';
        if ($row !== false) {
            $storedPassword = (string) ($row['password'] ?? '');
            if (password_verify($password, $storedPassword) || hash_equals($storedPassword, $password)) {
                $_SESSION['logged_in'] = true;
                if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
                    session_regenerate_id(true);
                }

                return $this->redirect($response, '/');
            }

            $error = 'Password salah!';
        } else {
            $error = 'Username tidak ditemukan!';
        }

        return $this->render($response, 'auth/login.php', [
            'error' => $error,
        ]);
    }

    public function logout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            session_unset();
            session_destroy();
        }

        return $this->redirect($response, '/login');
    }
}
