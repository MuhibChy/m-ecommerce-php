<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/business_functions.php';

$auth = getAuth();

if (!$auth->isLoggedIn()) {
    jsonResponse(['success' => false, 'error' => 'Authentication required']);
}

$user = $auth->getCurrentUser();

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        if (isset($_GET['id'])) {
            // Get single order
            $orderId = (int)$_GET['id'];
            $salesManager = new SalesManager();
            $order = $salesManager->getSaleById($orderId);
            
            if ($order && $order['customer_id'] == $user['id']) {
                jsonResponse(['success' => true, 'order' => $order]);
            } else {
                jsonResponse(['success' => false, 'error' => 'Order not found']);
            }
        } else {
            // Get user's orders
            $salesManager = new SalesManager();
            $orders = $salesManager->getSalesByCustomer($user['id']);
            jsonResponse(['success' => true, 'orders' => $orders]);
        }
        
    } elseif ($method === 'POST') {
        // Create new order
        $input = json_decode(file_get_contents('php://input'), true);
        
        $items = $input['items'] ?? [];
        $total = $input['total'] ?? 0;
        $shippingAddress = $input['shipping_address'] ?? '';
        $paymentMethod = $input['payment_method'] ?? 'cash_on_delivery';
        
        if (empty($items)) {
            jsonResponse(['success' => false, 'error' => 'No items in order']);
        }
        
        $salesManager = new SalesManager();
        $orderData = [
            'customer_id' => $user['id'],
            'items' => $items,
            'total' => $total,
            'shipping_address' => $shippingAddress,
            'payment_method' => $paymentMethod,
            'status' => 'pending'
        ];
        
        $result = $salesManager->createSale($orderData, $user['id']);
        
        if ($result) {
            jsonResponse(['success' => true, 'order_id' => $result, 'message' => 'Order created successfully']);
        } else {
            jsonResponse(['success' => false, 'error' => 'Failed to create order']);
        }
    } else {
        jsonResponse(['success' => false, 'error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    jsonResponse(['success' => false, 'error' => $e->getMessage()]);
}
?>
