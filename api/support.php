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
            // Get single ticket
            $ticketId = (int)$_GET['id'];
            $supportManager = new SupportManager();
            $ticket = $supportManager->getTicketById($ticketId);
            
            if ($ticket && $ticket['customer_id'] == $user['id']) {
                jsonResponse(['success' => true, 'ticket' => $ticket]);
            } else {
                jsonResponse(['success' => false, 'error' => 'Ticket not found']);
            }
        } else {
            // Get user's tickets
            $supportManager = new SupportManager();
            $tickets = $supportManager->getTicketsByCustomer($user['id']);
            jsonResponse(['success' => true, 'tickets' => $tickets]);
        }
        
    } elseif ($method === 'POST') {
        // Create new ticket
        $input = json_decode(file_get_contents('php://input'), true);
        
        $subject = $input['subject'] ?? '';
        $message = $input['message'] ?? '';
        $priority = $input['priority'] ?? 'medium';
        
        if (empty($subject) || empty($message)) {
            jsonResponse(['success' => false, 'error' => 'Subject and message are required']);
        }
        
        $supportManager = new SupportManager();
        $ticketData = [
            'customer_id' => $user['id'],
            'subject' => $subject,
            'message' => $message,
            'priority' => $priority,
            'status' => 'open'
        ];
        
        $result = $supportManager->createTicket($ticketData, $user['id']);
        
        if ($result) {
            jsonResponse(['success' => true, 'ticket_id' => $result, 'message' => 'Support ticket created successfully']);
        } else {
            jsonResponse(['success' => false, 'error' => 'Failed to create ticket']);
        }
    } else {
        jsonResponse(['success' => false, 'error' => 'Method not allowed']);
    }
    
} catch (Exception $e) {
    jsonResponse(['success' => false, 'error' => $e->getMessage()]);
}
?>
