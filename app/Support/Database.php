<?php
declare(strict_types=1);
namespace App\Support;
use PDO;
use PDOException;

final class Database {
    private PDO $pdo;
    public function __construct(array $config) { $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $config['host'], $config['port'], $config['database'], $config['charset']); $this->pdo = new PDO($dsn, $config['username'], $config['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]); }
    public function pdo(): PDO { return $this->pdo; }
    public function query(string $sql, array $params = []): \PDOStatement { $statement = $this->pdo->prepare($sql); $statement->execute($params); return $statement; }
    public function select(string $sql, array $params = []): array { return $this->query($sql, $params)->fetchAll(); }
    public function one(string $sql, array $params = []): ?array { $row = $this->query($sql, $params)->fetch(); return $row ?: null; }
    public function scalar(string $sql, array $params = []): mixed { return $this->query($sql, $params)->fetchColumn(); }
    public function execute(string $sql, array $params = []): int { return $this->query($sql, $params)->rowCount(); }
    public function transaction(callable $callback): mixed { if($this->pdo->inTransaction()) return $callback($this); $this->pdo->beginTransaction(); try { $result = $callback($this); $this->pdo->commit(); return $result; } catch (\Throwable $e) { if ($this->pdo->inTransaction()) $this->pdo->rollBack(); throw $e; } }
    public function lockForUpdate(string $sql, array $params = []): ?array { return $this->one(rtrim($sql, ';') . ' FOR UPDATE', $params); }
}
