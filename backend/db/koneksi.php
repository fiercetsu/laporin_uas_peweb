<?php
// ================================================================
//  db/koneksi.php
//  Wrapper koneksi database berbasis PDO untuk seluruh backend
// ================================================================

declare(strict_types=1);

namespace App\Db;

final class Database
{
    private static ?self $instance = null;
    private \PDO $pdo;
    private int $transactionDepth = 0;

    private function __construct()
    {
        $host    = $_ENV['DB_HOST'] ?? '127.0.0.1';
        $port    = $_ENV['DB_PORT'] ?? null;
        $name    = $_ENV['DB_NAME'] ?? '';
        $user    = $_ENV['DB_USER'] ?? 'root';
        $pass    = $_ENV['DB_PASS'] ?? '';
        $charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

        if ($host === 'localhost' && empty($_ENV['DB_SOCKET'])) {
            $host = '127.0.0.1';
        }

        $options = [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => false,
            \PDO::ATTR_TIMEOUT            => 2,
        ];

        if (!empty($_ENV['DB_SOCKET'])) {
            $dsn = "mysql:unix_socket={$_ENV['DB_SOCKET']};dbname={$name};charset={$charset}";
            $this->pdo = new \PDO($dsn, $user, $pass, $options);
            return;
        }

        $ports = $port !== null ? [(string)$port] : ['8889', '3306'];
        $lastError = null;

        foreach ($ports as $candidatePort) {
            try {
                $dsn = "mysql:host={$host};port={$candidatePort};dbname={$name};charset={$charset}";
                $this->pdo = new \PDO($dsn, $user, $pass, $options);
                return;
            } catch (\PDOException $e) {
                $lastError = $e;
            }
        }

        throw $lastError ?? new \PDOException('Koneksi database gagal.');
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function query(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);

        foreach (array_values($params) as $index => $value) {
            $type = is_int($value) ? \PDO::PARAM_INT : \PDO::PARAM_STR;
            if ($value === null) {
                $type = \PDO::PARAM_NULL;
            } elseif (is_bool($value)) {
                $type = \PDO::PARAM_BOOL;
            }

            $stmt->bindValue($index + 1, $value, $type);
        }

        $stmt->execute();
        return $stmt;
    }

    public function beginTransaction(): bool
    {
        if ($this->transactionDepth === 0) {
            $started = $this->pdo->beginTransaction();
        } else {
            $started = $this->pdo->exec('SAVEPOINT trans' . $this->transactionDepth) !== false;
        }

        if ($started) {
            $this->transactionDepth++;
        }

        return $started;
    }

    public function commit(): bool
    {
        if ($this->transactionDepth === 0) {
            return false;
        }

        $this->transactionDepth--;

        if ($this->transactionDepth === 0) {
            return $this->pdo->commit();
        }

        return $this->pdo->exec('RELEASE SAVEPOINT trans' . $this->transactionDepth) !== false;
    }

    public function rollback(): bool
    {
        if ($this->transactionDepth === 0 || !$this->pdo->inTransaction()) {
            return false;
        }

        $this->transactionDepth--;

        if ($this->transactionDepth === 0) {
            return $this->pdo->rollBack();
        }

        return $this->pdo->exec('ROLLBACK TO SAVEPOINT trans' . $this->transactionDepth) !== false;
    }

    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    public function pdo(): \PDO
    {
        return $this->pdo;
    }

    private function __clone()
    {
    }

    public function __wakeup(): void
    {
        throw new \RuntimeException('Database connection tidak boleh di-unserialize.');
    }
}
