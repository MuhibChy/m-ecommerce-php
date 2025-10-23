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

$auth = getAuth();

try {
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'login':
                $email = $input['email'] ?? '';
                $password = $input['password'] ?? '';
                
                if (empty($email) || empty($password)) {
                    jsonResponse(['success' => false, 'error' => 'Email and password are required']);
                }
                
                $result = $auth->login($email, $password);
                if ($result['success']) {
                    $result['token'] = 'mobile_token_' . $result['user']['id']; // Simple token for mobile
                }
                jsonResponse($result);
                break;
                
            case 'register':
                $name = $input['name'] ?? '';
                $email = $input['email'] ?? '';
                $password = $input['password'] ?? '';
                
                if (empty($name) || empty($email) || empty($password)) {
                    jsonResponse(['success' => false, 'error' => 'All fields are required']);
                }
                
                $result = $auth->register($name, $email, $password);
                jsonResponse($result);
                break;
                
            case 'logout':
                $result = $auth->logout();
                jsonResponse($result);
                break;
                
            default:
                jsonResponse(['success' => false, 'error' => 'Invalid action']);
        }
    } elseif ($method === 'GET') {
        $action = $_GET['action'] ?? '';
        
        if ($action === 'current_user') {
            if ($auth->isLoggedIn()) {
                $user = $auth->getCurrentUser();
                jsonResponse(['success' => true, 'user' => $user]);
            } else {
                jsonResponse(['success' => false, 'error' => 'Not authenticated']);
            }
        } else {
            jsonResponse(['success' => false, 'error' => 'Invalid action']);
        }
    } else {
        jsonResponse(['success' => false, 'error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    jsonResponse(['success' => false, 'error' => $e->getMessage()]);
}
?>
