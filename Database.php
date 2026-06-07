<?php
class Database {
    private $connexion;

    public function __construct() {
        $host = getenv('DB_HOST') ?: 'localhost';
        $dbname = getenv('DB_NAME') ?: 'motus';
        $username = getenv('DB_USER') ?: 'root';
        $password = getenv('DB_PASSWORD') ?: '';

        try {
            if ($username === false || $username === '') {
                throw new RuntimeException('Database user is not configured.');
            }

            $this->connexion = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (Throwable $e) {
            error_log('Database connection failed: ' . $e->getMessage());
            $this->connexion = null;
        }
    }

    public function getConnection() {
        return $this->connexion;
    }
}
