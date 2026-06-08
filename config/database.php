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
        // Detect local environment based on operating system and/or HTTP host
        $isLocal = false;
        
        // 1. Check if running on Windows (the local development OS)
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $isLocal = true;
        }
        
        // 2. Check if running under a local domain/address
        if (isset($_SERVER['HTTP_HOST'])) {
            $hostHeader = strtolower($_SERVER['HTTP_HOST']);
            if (strpos($hostHeader, 'localhost') !== false || 
                strpos($hostHeader, '127.0.0.1') !== false || 
                $hostHeader === '[::1]') {
                $isLocal = true;
            }
        }

        $dbHost = getenv('DB_HOST');
        $dbPort = getenv('DB_PORT');
        $dbName = getenv('DB_NAME');
        $dbUser = getenv('DB_USER');
        $dbPass = getenv('DB_PASS');

        $this->host     = ($dbHost !== false) ? $dbHost : 'localhost';
        $this->port     = ($dbPort !== false) ? $dbPort : ($isLocal ? '3307' : '3306');
        $this->dbname   = ($dbName !== false) ? $dbName : ($isLocal ? 'prompt_gateway' : 'astrozup_aipromptg');
        $this->username = ($dbUser !== false) ? $dbUser : ($isLocal ? 'root' : 'astrozup_aipromptgu');
        $this->password = ($dbPass !== false) ? $dbPass : ($isLocal ? '' : 'v{Zt(9!PF_6J');
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
