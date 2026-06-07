<?php
/**
 * AI Prompt Security Gateway — Database Configuration
 * Reads from environment variables; falls back to local XAMPP defaults.
 */

class Database {
    private static $instance = null;
    private $pdo;

    private $host;
    private $port;
    private $dbname;
    private $username;
    private $password;

    private function loadConfig() {
        $this->host     = getenv('DB_HOST')  ?: 'localhost';
        $this->port     = getenv('DB_PORT')  ?: '3307';
        $this->dbname   = getenv('DB_NAME')  ?: 'prompt_gateway';
        $this->username = getenv('DB_USER')  ?: 'root';
        $this->password = getenv('DB_PASS')  ?: '';
    }

    private function __construct() {
        $this->loadConfig();
        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->dbname};charset=utf8mb4";
            $this->pdo = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    // Prevent cloning
    private function __clone() {}
}
