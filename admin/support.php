<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/business_functions.php';

$auth = getAuth();
$auth->requireAdmin();

$supportManager = new SupportManager();
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_status':
            handleUpdateStatus($supportManager);
            break;
        case 'add_reply':
            handleAddReply($supportManager);
            break;
        case 'assign_ticket':
            handleAssignTicket($supportManager);
            break;
    }
}

function handleUpdateStatus($supportManager) {
    global $error, $success;
    
    try {
        $ticketId = intval($_POST['ticket_id']);
        $status = sanitizeInput($_POST['status']);
        $assignedTo = !empty($_POST['assigned_to']) ? intval($_POST['assigned_to']) : null;
        
        $supportManager->updateTicketStatus($ticketId, $status, $assignedTo);
        $success = "Ticket status updated successfully!";
    } catch (Exception $e) {
        $error = "Error updating ticket status: " . $e->getMessage();
    }
}

function handleAddReply($supportManager) {
    global $error, $success;
    
    try {
        $ticketId = intval($_POST['ticket_id']);
        $message = sanitizeInput($_POST['message']);
        $isInternal = isset($_POST['is_internal']);
        $userId = $_SESSION['user_id'];
        
        $supportManager->addReply($ticketId, $userId, $message, $isInternal);
        $success = "Reply added successfully!";
    } catch (Exception $e) {
        $error = "Error adding reply: " . $e->getMessage();
    }
}

function handleAssignTicket($supportManager) {
    global $error, $success;
    
    try {
        $ticketId = intval($_POST['ticket_id']);
        $assignedTo = intval($_POST['assigned_to']);
        
        $supportManager->updateTicketStatus($ticketId, 'in_progress', $assignedTo);
        $success = "Ticket assigned successfully!";
    } catch (Exception $e) {
        $error = "Error assigning ticket: " . $e->getMessage();
    }
}

// Get data for display
$status = $_GET['status'] ?? '';
$priority = $_GET['priority'] ?? '';
$assignedTo = $_GET['assigned_to'] ?? '';

$tickets = $supportManager->getAllTickets($status, $priority, $assignedTo, 50);
$supportStats = $supportManager->getSupportStats();

// Get admin users for assignment
$stmt = getDB()->prepare("SELECT id, name FROM users WHERE is_admin = 1");
$stmt->execute();
$adminUsers = $stmt->fetchAll();

// Convert stats to associative array
$statsArray = [];
foreach ($supportStats as $stat) {
    $statsArray[$stat['status']] = $stat['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Support - ModernShop Admin</title>
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
                        <h1 class="text-xl font-semibold text-gray-900">Customer Support</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="dashboard.php" class="text-gray-600 hover:text-gray-900">Dashboard</a>
                        <a href="products.php" class="text-gray-600 hover:text-gray-900">Products</a>
                        <a href="sales.php" class="text-gray-600 hover:text-gray-900">Sales</a>
                        <a href="finances.php" class="text-gray-600 hover:text-gray-900">Finances</a>
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

            <!-- Support Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Open Tickets</dt>
                                    <dd class="text-lg font-medium text-gray-900"><?php echo $statsArray['open'] ?? 0; ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">In Progress</dt>
                                    <dd class="text-lg font-medium text-gray-900"><?php echo $statsArray['in_progress'] ?? 0; ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Resolved</dt>
                                    <dd class="text-lg font-medium text-gray-900"><?php echo $statsArray['resolved'] ?? 0; ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-gray-500 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Closed</dt>
                                    <dd class="text-lg font-medium text-gray-900"><?php echo $statsArray['closed'] ?? 0; ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Options -->
            <div class="mb-6 bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Filter Tickets</h3>
                    <form method="GET" class="flex flex-wrap gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Status</option>
                                <option value="open" <?php echo $status === 'open' ? 'selected' : ''; ?>>Open</option>
                                <option value="in_progress" <?php echo $status === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="resolved" <?php echo $status === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                <option value="closed" <?php echo $status === 'closed' ? 'selected' : ''; ?>>Closed</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Priority</label>
                            <select name="priority" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Priorities</option>
                                <option value="low" <?php echo $priority === 'low' ? 'selected' : ''; ?>>Low</option>
                                <option value="medium" <?php echo $priority === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                <option value="high" <?php echo $priority === 'high' ? 'selected' : ''; ?>>High</option>
                                <option value="urgent" <?php echo $priority === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Assigned To</label>
                            <select name="assigned_to" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Agents</option>
                                <?php foreach ($adminUsers as $admin): ?>
                                <option value="<?php echo $admin['id']; ?>" <?php echo $assignedTo == $admin['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($admin['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="flex items-end">
                            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                                Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Support Tickets Table -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Support Tickets</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ticket #</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subject</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Priority</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned To</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($tickets as $ticket): ?>
                                <tr class="<?php echo $ticket['priority'] === 'urgent' ? 'bg-red-50' : ($ticket['priority'] === 'high' ? 'bg-orange-50' : ''); ?>">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($ticket['ticket_number']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($ticket['customer_name']); ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <?php echo htmlspecialchars($ticket['customer_email']); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">
                                            <?php echo htmlspecialchars($ticket['subject']); ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            <?php echo ucfirst($ticket['category']); ?>
                                        </div>
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
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo $ticket['assigned_to_name'] ? htmlspecialchars($ticket['assigned_to_name']) : 'Unassigned'; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo formatDate($ticket['created_at'], 'M d, Y'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <a href="support-detail.php?id=<?php echo $ticket['id']; ?>" 
                                               class="text-blue-600 hover:text-blue-900">View</a>
                                            
                                            <?php if ($ticket['status'] !== 'closed'): ?>
                                                <button onclick="updateTicketStatus(<?php echo $ticket['id']; ?>, '<?php echo $ticket['status']; ?>')" 
                                                        class="text-green-600 hover:text-green-900">Update</button>
                                                
                                                <?php if (!$ticket['assigned_to']): ?>
                                                    <button onclick="assignTicket(<?php echo $ticket['id']; ?>)" 
                                                            class="text-purple-600 hover:text-purple-900">Assign</button>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div id="statusModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
        <div class="flex items-center justify-center min-h-screen">
            <div class="bg-white rounded-lg p-6 w-full max-w-md">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Update Ticket Status</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="ticket_id" id="status_ticket_id">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status" id="status_select" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="open">Open</option>
                            <option value="in_progress">In Progress</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Assign To (Optional)</label>
                        <select name="assigned_to" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Unassigned</option>
                            <?php foreach ($adminUsers as $admin): ?>
                            <option value="<?php echo $admin['id']; ?>"><?php echo htmlspecialchars($admin['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeStatusModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                            Update Status
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Assign Ticket Modal -->
    <div id="assignModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
        <div class="flex items-center justify-center min-h-screen">
            <div class="bg-white rounded-lg p-6 w-full max-w-md">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Assign Ticket</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="assign_ticket">
                    <input type="hidden" name="ticket_id" id="assign_ticket_id">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Assign To</label>
                        <select name="assigned_to" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select Agent</option>
                            <?php foreach ($adminUsers as $admin): ?>
                            <option value="<?php echo $admin['id']; ?>"><?php echo htmlspecialchars($admin['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeAssignModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700">
                            Assign Ticket
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function updateTicketStatus(ticketId, currentStatus) {
            document.getElementById('status_ticket_id').value = ticketId;
            document.getElementById('status_select').value = currentStatus;
            document.getElementById('statusModal').classList.remove('hidden');
        }
        
        function closeStatusModal() {
            document.getElementById('statusModal').classList.add('hidden');
        }
        
        function assignTicket(ticketId) {
            document.getElementById('assign_ticket_id').value = ticketId;
            document.getElementById('assignModal').classList.remove('hidden');
        }
        
        function closeAssignModal() {
            document.getElementById('assignModal').classList.add('hidden');
        }
    </script>
</body>
</html>
