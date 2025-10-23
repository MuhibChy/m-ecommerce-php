<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/business_functions.php';

$auth = getAuth();
$auth->requireAdmin();

$supportManager = new SupportManager();
$error = '';
$success = '';

// Get ticket ID
$ticketId = intval($_GET['id'] ?? 0);
if (!$ticketId) {
    header('Location: support.php');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_reply':
            handleAddReply($supportManager, $ticketId);
            break;
        case 'update_status':
            handleUpdateStatus($supportManager, $ticketId);
            break;
    }
}

function handleAddReply($supportManager, $ticketId) {
    global $error, $success;
    
    try {
        $message = sanitizeInput($_POST['message']);
        $isInternal = isset($_POST['is_internal']);
        $userId = $_SESSION['user_id'];
        
        $supportManager->addReply($ticketId, $userId, $message, $isInternal);
        $success = "Reply added successfully!";
    } catch (Exception $e) {
        $error = "Error adding reply: " . $e->getMessage();
    }
}

function handleUpdateStatus($supportManager, $ticketId) {
    global $error, $success;
    
    try {
        $status = sanitizeInput($_POST['status']);
        $assignedTo = !empty($_POST['assigned_to']) ? intval($_POST['assigned_to']) : null;
        
        $supportManager->updateTicketStatus($ticketId, $status, $assignedTo);
        $success = "Ticket status updated successfully!";
    } catch (Exception $e) {
        $error = "Error updating ticket status: " . $e->getMessage();
    }
}

// Get ticket details and replies
$ticket = $supportManager->getTicketById($ticketId);
if (!$ticket) {
    header('Location: support.php');
    exit;
}

$replies = $supportManager->getTicketReplies($ticketId);

// Get admin users for assignment
$stmt = getDB()->prepare("SELECT id, name FROM users WHERE is_admin = 1");
$stmt->execute();
$adminUsers = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket #<?php echo htmlspecialchars($ticket['ticket_number']); ?> - ModernShop Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <a href="support.php" class="text-blue-600 hover:text-blue-800 mr-4">← Back to Support</a>
                        <h1 class="text-xl font-semibold text-gray-900">Ticket #<?php echo htmlspecialchars($ticket['ticket_number']); ?></h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="dashboard.php" class="text-gray-600 hover:text-gray-900">Dashboard</a>
                        <a href="../pages/logout.php" class="text-red-600 hover:text-red-700">Logout</a>
                    </div>
                </div>
            </div>
        </nav>

        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- Alerts -->
            <?php if ($error): ?>
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2">
                    <!-- Ticket Details -->
                    <div class="bg-white shadow rounded-lg mb-6">
                        <div class="px-4 py-5 sm:p-6">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h2 class="text-xl font-semibold text-gray-900"><?php echo htmlspecialchars($ticket['subject']); ?></h2>
                                    <p class="text-sm text-gray-500 mt-1">
                                        Created on <?php echo formatDate($ticket['created_at'], 'M d, Y \a\t H:i'); ?>
                                    </p>
                                </div>
                                <div class="flex space-x-2">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo getPriorityBadgeClass($ticket['priority']); ?>">
                                        <?php echo ucfirst($ticket['priority']); ?>
                                    </span>
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo getStatusBadgeClass($ticket['status']); ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="border-t border-gray-200 pt-4">
                                <h3 class="text-sm font-medium text-gray-900 mb-2">Description</h3>
                                <div class="text-sm text-gray-700 whitespace-pre-wrap"><?php echo htmlspecialchars($ticket['description']); ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Conversation -->
                    <div class="bg-white shadow rounded-lg mb-6">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Conversation</h3>
                            
                            <div class="space-y-4">
                                <?php foreach ($replies as $reply): ?>
                                <div class="<?php echo $reply['is_admin'] ? 'ml-8' : 'mr-8'; ?>">
                                    <div class="bg-<?php echo $reply['is_admin'] ? 'blue' : 'gray'; ?>-50 rounded-lg p-4">
                                        <div class="flex justify-between items-start mb-2">
                                            <div class="flex items-center">
                                                <span class="font-medium text-sm text-gray-900">
                                                    <?php echo htmlspecialchars($reply['user_name']); ?>
                                                </span>
                                                <?php if ($reply['is_admin']): ?>
                                                    <span class="ml-2 inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                                        Staff
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($reply['is_internal']): ?>
                                                    <span class="ml-2 inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                        Internal Note
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <span class="text-xs text-gray-500">
                                                <?php echo formatDate($reply['created_at'], 'M d, Y H:i'); ?>
                                            </span>
                                        </div>
                                        <div class="text-sm text-gray-700 whitespace-pre-wrap">
                                            <?php echo htmlspecialchars($reply['message']); ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Add Reply -->
                    <?php if ($ticket['status'] !== 'closed'): ?>
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Add Reply</h3>
                            <form method="POST">
                                <input type="hidden" name="action" value="add_reply">
                                
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700">Message</label>
                                    <textarea name="message" rows="4" required 
                                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="flex items-center">
                                        <input type="checkbox" name="is_internal" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                        <span class="ml-2 text-sm text-gray-700">Internal note (not visible to customer)</span>
                                    </label>
                                </div>
                                
                                <div class="flex justify-end">
                                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                        Add Reply
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1">
                    <!-- Customer Info -->
                    <div class="bg-white shadow rounded-lg mb-6">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Customer Information</h3>
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Name</dt>
                                    <dd class="text-sm text-gray-900"><?php echo htmlspecialchars($ticket['customer_name']); ?></dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Email</dt>
                                    <dd class="text-sm text-gray-900"><?php echo htmlspecialchars($ticket['customer_email']); ?></dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Category</dt>
                                    <dd class="text-sm text-gray-900"><?php echo ucfirst($ticket['category']); ?></dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Ticket Actions -->
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-4 py-5 sm:p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Ticket Actions</h3>
                            
                            <form method="POST" class="space-y-4">
                                <input type="hidden" name="action" value="update_status">
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Status</label>
                                    <select name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        <option value="open" <?php echo $ticket['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                                        <option value="in_progress" <?php echo $ticket['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="resolved" <?php echo $ticket['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                        <option value="closed" <?php echo $ticket['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Assign To</label>
                                    <select name="assigned_to" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Unassigned</option>
                                        <?php foreach ($adminUsers as $admin): ?>
                                        <option value="<?php echo $admin['id']; ?>" <?php echo $ticket['assigned_to'] == $admin['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($admin['name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                    Update Ticket
                                </button>
                            </form>
                            
                            <div class="mt-4 pt-4 border-t border-gray-200">
                                <dl class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <dt class="text-gray-500">Created:</dt>
                                        <dd class="text-gray-900"><?php echo formatDate($ticket['created_at'], 'M d, Y'); ?></dd>
                                    </div>
                                    <div class="flex justify-between">
                                        <dt class="text-gray-500">Last Updated:</dt>
                                        <dd class="text-gray-900"><?php echo formatDate($ticket['updated_at'], 'M d, Y'); ?></dd>
                                    </div>
                                    <?php if ($ticket['resolved_at']): ?>
                                    <div class="flex justify-between">
                                        <dt class="text-gray-500">Resolved:</dt>
                                        <dd class="text-gray-900"><?php echo formatDate($ticket['resolved_at'], 'M d, Y'); ?></dd>
                                    </div>
                                    <?php endif; ?>
                                    <div class="flex justify-between">
                                        <dt class="text-gray-500">Assigned To:</dt>
                                        <dd class="text-gray-900"><?php echo $ticket['assigned_to_name'] ?: 'Unassigned'; ?></dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
