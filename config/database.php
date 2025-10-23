<?php
/**
 * Database Configuration
 * Modern E-commerce Platform - PHP Version
 */

class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;
    
    public function __construct() {
        // Auto-detect environment (local vs live)
        $httpHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        if ($httpHost === 'localhost' || strpos($httpHost, '127.0.0.1') !== false || php_sapi_name() === 'cli') {
            // Local development environment or CLI
            $this->host = 'localhost';
            $this->db_name = 'm_ecommerce';
            $this->username = 'root';
            $this->password = '';
        } else {
            // Live production environment - Update these with your live server details
            $this->host = $_ENV['DB_HOST'] ?? 'localhost';
            $this->db_name = $_ENV['DB_NAME'] ?? 'm_ecommerce';
            $this->username = $_ENV['DB_USER'] ?? 'your_db_username';
            $this->password = $_ENV['DB_PASS'] ?? 'your_db_password';
        }
    }

    public function connect() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            echo "Connection Error: " . $e->getMessage();
        }
        
        return $this->conn;
    }
}

// Global database connection
function getDB() {
    static $database = null;
    if ($database === null) {
        $database = new Database();
    }
    return $database->connect();
}
?>
