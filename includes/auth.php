<?php
/**
 * Authentication System
 * Modern E-commerce Platform - PHP Version
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    public function register($name, $email, $password) {
        try {
            // Check if user already exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                return ['success' => false, 'error' => 'Email already exists'];
            }
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Generate avatar URL
            $avatar = "https://ui-avatars.com/api/?name=" . urlencode($name) . "&background=667eea&color=fff";
            
            // Insert user
            $stmt = $this->db->prepare("INSERT INTO users (name, email, password, avatar, is_admin) VALUES (?, ?, ?, ?, ?)");
            $isAdmin = strpos(strtolower($email), 'admin') !== false ? 1 : 0;
            
            if ($stmt->execute([$name, $email, $hashedPassword, $avatar, $isAdmin])) {
                return ['success' => true, 'message' => 'Registration successful'];
            } else {
                return ['success' => false, 'error' => 'Registration failed'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    public function login($email, $password) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_avatar'] = $user['avatar'];
                $_SESSION['is_admin'] = $user['is_admin'];
                
                return ['success' => true, 'user' => $user];
            } else {
                return ['success' => false, 'error' => 'Invalid email or password'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    public function logout() {
        session_destroy();
        return ['success' => true, 'message' => 'Logged out successfully'];
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function isAdmin() {
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
    }
    
    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'name' => $_SESSION['user_name'],
                'email' => $_SESSION['user_email'],
                'avatar' => $_SESSION['user_avatar'],
                'is_admin' => $_SESSION['is_admin']
            ];
        }
        return null;
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: ' . getBaseUrl() . '/pages/login.php');
            exit;
        }
    }
    
    public function requireAdmin() {
        $this->requireLogin();
        if (!$this->isAdmin()) {
            header('Location: ' . getBaseUrl() . '/index.php');
            exit;
        }
    }
}

// Global auth instance
function getAuth() {
    static $auth = null;
    if ($auth === null) {
        $auth = new Auth();
    }
    return $auth;
}
?>
