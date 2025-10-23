<?php
/**
 * Helper Functions
 * Modern E-commerce Platform - PHP Version
 */

require_once __DIR__ . '/../config/database.php';

class ProductManager {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    public function getAllProducts($category = null, $featured = null, $search = null) {
        $sql = "SELECT p.*, c.name as category_name FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id WHERE 1=1";
        $params = [];
        
        if ($category) {
            $sql .= " AND p.category_id = ?";
            $params[] = $category;
        }
        
        if ($featured !== null) {
            $sql .= " AND p.featured = ?";
            $params[] = $featured;
        }
        
        if ($search) {
            $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }
        
        $sql .= " ORDER BY p.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getProductById($id) {
        $stmt = $this->db->prepare("SELECT p.*, c.name as category_name FROM products p 
                                   LEFT JOIN categories c ON p.category_id = c.id 
                                   WHERE p.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function getFeaturedProducts() {
        return $this->getAllProducts(null, 1);
    }
    
    public function getCategories() {
        $stmt = $this->db->prepare("SELECT * FROM categories ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    public function addProduct($data) {
        try {
            $sql = "INSERT INTO products (name, category_id, price, original_price, image, 
                    rating, reviews, in_stock, featured, description, specs, tags) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['name'],
                $data['category_id'],
                $data['price'],
                $data['original_price'] ?: null,
                $data['image'],
                $data['rating'] ?: 4.5,
                $data['reviews'] ?: 0,
                $data['in_stock'] ? 1 : 0,
                $data['featured'] ? 1 : 0,
                $data['description'],
                json_encode($data['specs'] ?: []),
                json_encode($data['tags'] ?: [])
            ]);
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function updateProduct($id, $data) {
        try {
            $sql = "UPDATE products SET name=?, category_id=?, price=?, original_price=?, 
                    image=?, rating=?, reviews=?, in_stock=?, featured=?, description=?, 
                    specs=?, tags=? WHERE id=?";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['name'],
                $data['category_id'],
                $data['price'],
                $data['original_price'] ?: null,
                $data['image'],
                $data['rating'] ?: 4.5,
                $data['reviews'] ?: 0,
                $data['in_stock'] ? 1 : 0,
                $data['featured'] ? 1 : 0,
                $data['description'],
                json_encode($data['specs'] ?: []),
                json_encode($data['tags'] ?: []),
                $id
            ]);
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function deleteProduct($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM products WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            return false;
        }
    }
}

class CartManager {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    public function addToCart($userId, $productId, $quantity = 1) {
        try {
            $stmt = $this->db->prepare("INSERT INTO cart (user_id, product_id, quantity) 
                                       VALUES (?, ?, ?) 
                                       ON DUPLICATE KEY UPDATE quantity = quantity + ?");
            return $stmt->execute([$userId, $productId, $quantity, $quantity]);
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function getCartItems($userId) {
        $stmt = $this->db->prepare("SELECT c.*, p.name, p.price, p.image 
                                   FROM cart c 
                                   JOIN products p ON c.product_id = p.id 
                                   WHERE c.user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    public function getCartCount($userId) {
        $stmt = $this->db->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result['count'] ?: 0;
    }
    
    public function updateCartItem($userId, $productId, $quantity) {
        if ($quantity <= 0) {
            return $this->removeFromCart($userId, $productId);
        }
        
        $stmt = $this->db->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
        return $stmt->execute([$quantity, $userId, $productId]);
    }
    
    public function removeFromCart($userId, $productId) {
        $stmt = $this->db->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
        return $stmt->execute([$userId, $productId]);
    }
    
    public function clearCart($userId) {
        $stmt = $this->db->prepare("DELETE FROM cart WHERE user_id = ?");
        return $stmt->execute([$userId]);
    }
}

// Helper functions
function formatPrice($price) {
    return '৳' . number_format($price, 2);
}

function formatCurrency($amount) {
    return '৳' . number_format($amount, 2);
}

function getBaseUrl() {
    // Auto-detect base URL for both local and live environments
    if ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false) {
        return '/m-ecommerce-php';
    } else {
        // For live hosting, return empty string if site is in root directory
        // or return subdirectory path if site is in a subfolder
        return '';
    }
}

function redirect($url) {
    header("Location: " . getBaseUrl() . $url);
    exit;
}

function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

function jsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?>
