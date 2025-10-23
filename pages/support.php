<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once __DIR__ . '/../includes/auth.php';
    require_once __DIR__ . '/../includes/functions.php';
    require_once __DIR__ . '/../includes/business_functions.php';

    $auth = getAuth();
    $user = $auth->getCurrentUser();
} catch (Exception $e) {
    // If there's an error with auth or functions, set defaults
    $user = null;
    $error = "System error: " . $e->getMessage();
}

$supportManager = null;
$error = '';
$success = '';

// Try to initialize support manager
try {
    if (class_exists('SupportManager')) {
        $supportManager = new SupportManager();
    }
} catch (Exception $e) {
    $error = "Support system not available: " . $e->getMessage();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if (!$user) {
        $error = "Please log in to submit a support ticket.";
    } else {
        switch ($action) {
            case 'create_ticket':
                handleCreateTicket($supportManager, $user['id']);
                break;
            case 'add_reply':
                handleAddReply($supportManager, $user['id']);
                break;
        }
    }
}

function handleCreateTicket($supportManager, $userId) {
    global $error, $success;
    
    try {
        $data = [
            'subject' => sanitizeInput($_POST['subject']),
            'description' => sanitizeInput($_POST['description']),
            'priority' => sanitizeInput($_POST['priority']),
            'category' => sanitizeInput($_POST['category'])
        ];
        
        $supportManager->createTicket($data, $userId);
        $success = "Support ticket created successfully! We'll get back to you soon.";
    } catch (Exception $e) {
        $error = "Error creating ticket: " . $e->getMessage();
    }
}

function handleAddReply($supportManager, $userId) {
    global $error, $success;
    
    try {
        $ticketId = intval($_POST['ticket_id']);
        $message = sanitizeInput($_POST['message']);
        
        // Verify ticket belongs to user
        $ticket = $supportManager->getTicketById($ticketId);
        if (!$ticket || $ticket['customer_id'] != $userId) {
            throw new Exception("Ticket not found or access denied.");
        }
        
        $supportManager->addReply($ticketId, $userId, $message, false);
        $success = "Reply added successfully!";
    } catch (Exception $e) {
        $error = "Error adding reply: " . $e->getMessage();
    }
}

// Get user's tickets if logged in
$userTickets = [];
if ($user) {
    $userTickets = $supportManager->getAllTickets(null, null, null, 50);
    // Filter to only user's tickets
    $userTickets = array_filter($userTickets, function($ticket) use ($user) {
        return $ticket['customer_id'] == $user['id'];
    });
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Support - ModernShop</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'inter': ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 font-inter">
    <!-- Header -->
    <?php include __DIR__ . '/../components/header.php'; ?>

    <div class="min-h-screen py-12">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Page Header -->
            <div class="text-center mb-12">
                <h1 class="text-3xl font-bold text-gray-900 mb-4">Customer Support</h1>
                <p class="text-lg text-gray-600">We're here to help! Submit a ticket or check your existing requests.</p>
            </div>

            <!-- Alerts -->
            <?php if ($error): ?>
                <div class="mb-6 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="mb-6 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if (!$user): ?>
                <!-- Login Required -->
                <div class="bg-white shadow rounded-lg p-8 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Login Required</h3>
                    <p class="text-gray-600 mb-6">Please log in to submit support tickets and view your requests.</p>
                    <div class="space-x-4">
                        <a href="login.php" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700">
                            Login
                        </a>
                        <a href="register.php" class="bg-gray-600 text-white px-6 py-2 rounded-md hover:bg-gray-700">
                            Register
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Support Interface -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Create New Ticket -->
                    <div class="lg:col-span-2">
                        <div class="bg-white shadow rounded-lg">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h2 class="text-lg font-medium text-gray-900">Submit New Ticket</h2>
                            </div>
                            <div class="p-6">
                                <form method="POST">
                                    <input type="hidden" name="action" value="create_ticket">
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                                            <select name="category" required class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                                <option value="">Select Category</option>
                                                <option value="general">General Inquiry</option>
                                                <option value="technical">Technical Issue</option>
                                                <option value="billing">Billing Question</option>
                                                <option value="product">Product Support</option>
                                                <option value="complaint">Complaint</option>
                                            </select>
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Priority</label>
                                            <select name="priority" required class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                                <option value="low">Low</option>
                                                <option value="medium" selected>Medium</option>
                                                <option value="high">High</option>
                                                <option value="urgent">Urgent</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-6">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Subject</label>
                                        <input type="text" name="subject" required maxlength="200" 
                                               class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                               placeholder="Brief description of your issue">
                                    </div>
                                    
                                    <div class="mb-6">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                                        <textarea name="description" rows="6" required 
                                                  class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                                  placeholder="Please provide detailed information about your issue..."></textarea>
                                    </div>
                                    
                                    <button type="submit" class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                        Submit Ticket
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Support Info -->
                    <div class="space-y-6">
                        <!-- Contact Info -->
                        <div class="bg-white shadow rounded-lg">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h3 class="text-lg font-medium text-gray-900">Contact Information</h3>
                            </div>
                            <div class="p-6 space-y-4">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                    <span class="text-sm text-gray-600">support@modernshop.com</span>
                                </div>
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                    </svg>
                                    <span class="text-sm text-gray-600">+880-1234-567890</span>
                                </div>
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span class="text-sm text-gray-600">Mon-Fri: 9AM-6PM</span>
                                </div>
                            </div>
                        </div>

                        <!-- FAQ -->
                        <div class="bg-white shadow rounded-lg">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h3 class="text-lg font-medium text-gray-900">Frequently Asked Questions</h3>
                            </div>
                            <div class="p-6 space-y-4">
                                <div>
                                    <h4 class="font-medium text-gray-900 mb-1">How long does shipping take?</h4>
                                    <p class="text-sm text-gray-600">Standard shipping takes 3-5 business days within Bangladesh.</p>
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-900 mb-1">What's your return policy?</h4>
                                    <p class="text-sm text-gray-600">We accept returns within 30 days of purchase for unused items.</p>
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-900 mb-1">How can I track my order?</h4>
                                    <p class="text-sm text-gray-600">You'll receive a tracking number via email once your order ships.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User's Tickets -->
                <?php if (!empty($userTickets)): ?>
                <div class="mt-12">
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-medium text-gray-900">Your Support Tickets</h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ticket #</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php foreach ($userTickets as $ticket): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($ticket['ticket_number']); ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <?php echo htmlspecialchars($ticket['subject']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo getPriorityBadgeClass($ticket['priority']); ?>">
                                                <?php echo ucfirst($ticket['priority']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo getStatusBadgeClass($ticket['status']); ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo formatDate($ticket['created_at']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button onclick="viewTicket(<?php echo $ticket['id']; ?>, '<?php echo htmlspecialchars($ticket['ticket_number']); ?>')" 
                                                    class="text-blue-600 hover:text-blue-900">
                                                View Details
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Ticket Detail Modal -->
    <div id="ticketModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg w-full max-w-2xl max-h-screen overflow-y-auto">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h3 class="text-lg font-medium text-gray-900" id="modalTitle">Ticket Details</h3>
                        <button onclick="closeTicketModal()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="p-6" id="modalContent">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include __DIR__ . '/../components/footer.php'; ?>

    <script>
        function viewTicket(ticketId, ticketNumber) {
            document.getElementById('modalTitle').textContent = 'Ticket #' + ticketNumber;
            document.getElementById('modalContent').innerHTML = '<div class="text-center py-4">Loading...</div>';
            document.getElementById('ticketModal').classList.remove('hidden');
            
            // In a real implementation, you would fetch ticket details via AJAX
            // For now, we'll show a placeholder
            setTimeout(() => {
                document.getElementById('modalContent').innerHTML = `
                    <div class="space-y-4">
                        <p class="text-gray-600">Ticket details would be loaded here via AJAX.</p>
                        <p class="text-sm text-gray-500">This feature requires additional implementation for the full conversation view and reply functionality.</p>
                    </div>
                `;
            }, 500);
        }
        
        function closeTicketModal() {
            document.getElementById('ticketModal').classList.add('hidden');
        }
    </script>
</body>
</html>
