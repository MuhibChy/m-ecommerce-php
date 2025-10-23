<?php
/**
 * Cart API Endpoint
 * Handles cart operations via AJAX
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$auth = getAuth();

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    jsonResponse(['success' => false, 'error' => 'Please log in first']);
}

$user = $auth->getCurrentUser();
$cartManager = new CartManager();

// Get request method and data
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

switch ($method) {
    case 'POST':
        handleCartAdd($cartManager, $user['id'], $input);
        break;
    case 'PUT':
        handleCartUpdate($cartManager, $user['id'], $input);
        break;
    case 'DELETE':
        handleCartRemove($cartManager, $user['id'], $input);
        break;
    case 'GET':
        handleCartGet($cartManager, $user['id']);
        break;
    default:
        jsonResponse(['success' => false, 'error' => 'Method not allowed']);
}

function handleCartAdd($cartManager, $userId, $input) {
    $productId = $input['product_id'] ?? null;
    $quantity = $input['quantity'] ?? 1;
    
    if (!$productId) {
        jsonResponse(['success' => false, 'error' => 'Product ID is required']);
    }
    
    // Validate product exists
    $productManager = new ProductManager();
    $product = $productManager->getProductById($productId);
    
    if (!$product) {
        jsonResponse(['success' => false, 'error' => 'Product not found']);
    }
    
    if (!$product['in_stock']) {
        jsonResponse(['success' => false, 'error' => 'Product is out of stock']);
    }
    
    $success = $cartManager->addToCart($userId, $productId, $quantity);
    
    if ($success) {
        $cartCount = $cartManager->getCartCount($userId);
        jsonResponse([
            'success' => true,
            'message' => 'Product added to cart',
            'cart_count' => $cartCount
        ]);
    } else {
        jsonResponse(['success' => false, 'error' => 'Failed to add product to cart']);
    }
}

function handleCartUpdate($cartManager, $userId, $input) {
    $productId = $input['product_id'] ?? null;
    $quantity = $input['quantity'] ?? 0;
    
    if (!$productId) {
        jsonResponse(['success' => false, 'error' => 'Product ID is required']);
    }
    
    $success = $cartManager->updateCartItem($userId, $productId, $quantity);
    
    if ($success) {
        $cartCount = $cartManager->getCartCount($userId);
        $cartItems = $cartManager->getCartItems($userId);
        
        jsonResponse([
            'success' => true,
            'message' => 'Cart updated',
            'cart_count' => $cartCount,
            'cart_items' => $cartItems
        ]);
    } else {
        jsonResponse(['success' => false, 'error' => 'Failed to update cart']);
    }
}

function handleCartRemove($cartManager, $userId, $input) {
    $productId = $input['product_id'] ?? null;
    
    if (!$productId) {
        jsonResponse(['success' => false, 'error' => 'Product ID is required']);
    }
    
    $success = $cartManager->removeFromCart($userId, $productId);
    
    if ($success) {
        $cartCount = $cartManager->getCartCount($userId);
        $cartItems = $cartManager->getCartItems($userId);
        
        jsonResponse([
            'success' => true,
            'message' => 'Product removed from cart',
            'cart_count' => $cartCount,
            'cart_items' => $cartItems
        ]);
    } else {
        jsonResponse(['success' => false, 'error' => 'Failed to remove product from cart']);
    }
}

function handleCartGet($cartManager, $userId) {
    $cartItems = $cartManager->getCartItems($userId);
    $cartCount = $cartManager->getCartCount($userId);
    
    // Calculate total
    $total = 0;
    foreach ($cartItems as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    
    jsonResponse([
        'success' => true,
        'cart_items' => $cartItems,
        'cart_count' => $cartCount,
        'total' => $total
    ]);
}
?>
