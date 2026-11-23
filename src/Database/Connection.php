<?php

declare(strict_types=1);

namespace App\Database;

use PDO;

final class Connection
{
    private ?PDO $pdo;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private readonly array $config,
        ?PDO $pdo = null
    ) {
        $this->pdo = $pdo;
    }

    public function pdo(): PDO
    {
        if ($this->pdo instanceof PDO) {
            return $this->pdo;
        }

        $host = (string) ($this->config['host'] ?? 'localhost');
        $port = (int) ($this->config['port'] ?? 3306);
        $database = (string) ($this->config['database'] ?? 'dsm');
        $username = (string) ($this->config['username'] ?? 'root');
        $password = (string) ($this->config['password'] ?? '');
        $charset = (string) ($this->config['charset'] ?? 'utf8mb4');

        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $database, $charset);

        $this->pdo = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        return $this->pdo;
    }
}
