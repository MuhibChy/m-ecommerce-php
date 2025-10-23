<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/business_functions.php';

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        $action = $_GET['action'] ?? '';
        
        if ($action === 'categories') {
            // Get categories
            $productManager = new ProductManager();
            $categories = $productManager->getCategories();
            jsonResponse(['success' => true, 'categories' => $categories]);
            
        } elseif (isset($_GET['id'])) {
            // Get single product
            $productId = (int)$_GET['id'];
            $productManager = new ProductManager();
            $product = $productManager->getProductById($productId);
            
            if ($product) {
                jsonResponse(['success' => true, 'product' => $product]);
            } else {
                jsonResponse(['success' => false, 'error' => 'Product not found']);
            }
            
        } else {
            // Get products list
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 20);
            $category = $_GET['category'] ?? null;
            $search = $_GET['search'] ?? null;
            
            $productManager = new ProductManager();
            $products = $productManager->getAllProducts($category, $search, $limit, ($page - 1) * $limit);
            
            jsonResponse(['success' => true, 'products' => $products]);
        }
    } else {
        jsonResponse(['success' => false, 'error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    jsonResponse(['success' => false, 'error' => $e->getMessage()]);
}
?>
