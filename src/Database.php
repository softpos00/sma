<?php
class Database
{
    private \PDO $pdo;

    public function __construct(array $config)
    {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $config['host'], $config['database']);
        $this->pdo = new \PDO($dsn, $config['user'], $config['password'], [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ]);
    }

    public function pdo(): \PDO
    {
        return $this->pdo;
    }
}
