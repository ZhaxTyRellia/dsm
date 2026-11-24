<?php

declare(strict_types=1);

namespace App\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AuthController extends AbstractController
{
    public function showLogin(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->assertActivated($request);

        if (($_SESSION['logged_in'] ?? false) === true) {
            return $this->redirect($response, '/');
        }

        return $this->render($response, 'auth/login.php', [
            'error' => '',
            'flash' => $this->pullFlash(),
        ]);
    }

    public function login(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->assertActivated($request);

        if (($_SESSION['logged_in'] ?? false) === true) {
            return $this->redirect($response, '/');
        }

        $body = $this->body($request);
        $username = trim((string) ($body['username'] ?? ''));
        $password = (string) ($body['password'] ?? '');

        if ($username === '' || $password === '') {
            return $this->render($response, 'auth/login.php', [
                'error' => 'Please enter both username and password.',
                'flash' => null,
            ]);
        }

        $stmt = $this->pdo()->prepare('SELECT password FROM user WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $row = $stmt->fetch();

        if ($row !== false) {
            $storedPassword = (string) ($row['password'] ?? '');
            if (password_verify($password, $storedPassword) || hash_equals($storedPassword, $password)) {
                $_SESSION['logged_in'] = true;
                if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
                    session_regenerate_id(true);
                }
                $this->flash('success', 'Signed in successfully.');

                return $this->redirect($response, '/');
            }

            $error = 'Incorrect password!';
        } else {
            $error = 'Username not found!';
        }

        return $this->render($response, 'auth/login.php', [
            'error' => $error,
            'flash' => null,
        ]);
    }

    public function logout(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        unset($_SESSION['logged_in']);
        foreach (array_keys($_SESSION) as $key) {
            unset($_SESSION[$key]);
        }
        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            session_regenerate_id(true);
        }
        $this->flash('success', 'You have been signed out.');

        return $this->redirect($response, '/login');
    }
}
