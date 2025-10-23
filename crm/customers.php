<?php
/**
 * Customer Management
 * M-EcommerceCRM - Customer Management Interface
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/customer_manager.php';

// Check if user is admin
$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../pages/login.php');
    exit;
}

$customerManager = new CustomerManager();
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
            try {
                $customerId = $customerManager->addCustomer($_POST);
                $message = 'Customer added successfully!';
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            break;
            
        case 'update':
            try {
                $customerManager->updateCustomer($_POST['id'], $_POST);
                $message = 'Customer updated successfully!';
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            break;
            
        case 'delete':
            try {
                $customerManager->deleteCustomer($_POST['id']);
                $message = 'Customer deleted successfully!';
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            break;
            
        case 'import':
            try {
                $customerManager->importFromUsers();
                $message = 'Customers imported from users successfully!';
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            break;
    }
}

// Get filters
$filters = [
    'search' => $_GET['search'] ?? '',
    'customer_type' => $_GET['customer_type'] ?? '',
    'status' => $_GET['status'] ?? ''
];

// Get customers
$page = (int)($_GET['page'] ?? 1);
$customersData = $customerManager->getCustomers($page, 20, $filters);

// Get single customer for editing
$editCustomer = null;
if (isset($_GET['edit'])) {
    $editCustomer = $customerManager->getCustomer($_GET['edit']);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Management - M-EcommerceCRM</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
        }

        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
        }

        .btn-small {
            padding: 8px 16px;
            font-size: 12px;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .filters {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 15px;
            margin-bottom: 20px;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            font-weight: 500;
            margin-bottom: 5px;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }

        .table-container {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .table th,
        .table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .table th {
            background: rgba(102, 126, 234, 0.1);
            font-weight: 600;
            color: #333;
        }

        .table tr:hover {
            background: rgba(102, 126, 234, 0.05);
        }

        .customer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            margin-right: 10px;
        }

        .customer-info {
            display: flex;
            align-items: center;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #f8d7da; color: #721c24; }
        .status-blocked { background: #fff3cd; color: #856404; }

        .type-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .type-lead { background: #cce5ff; color: #004085; }
        .type-prospect { background: #fff2cc; color: #856404; }
        .type-customer { background: #d4edda; color: #155724; }
        .type-vip { background: #f8d7da; color: #721c24; }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }

        .pagination a {
            padding: 10px 15px;
            background: rgba(255, 255, 255, 0.9);
            color: #333;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .pagination a:hover,
        .pagination a.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .close {
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #999;
        }

        .close:hover {
            color: #333;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .form-grid .form-group.full-width {
            grid-column: 1 / -1;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .filters {
                grid-template-columns: 1fr;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div>
                <h1><i class="fas fa-users"></i> Customer Management</h1>
                <p>Manage your customer database</p>
            </div>
            <div>
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                <button onclick="openModal('addModal')" class="btn"><i class="fas fa-user-plus"></i> Add Customer</button>
            </div>
        </div>

        <!-- Alerts -->
        <?php if ($message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <!-- Filters and Actions -->
        <div class="card">
            <form method="GET" action="">
                <div class="filters">
                    <div class="form-group">
                        <label>Search Customers</label>
                        <input type="text" name="search" placeholder="Search by name, email, or company..." 
                               value="<?php echo htmlspecialchars($filters['search']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Customer Type</label>
                        <select name="customer_type">
                            <option value="">All Types</option>
                            <option value="lead" <?php echo $filters['customer_type'] === 'lead' ? 'selected' : ''; ?>>Lead</option>
                            <option value="prospect" <?php echo $filters['customer_type'] === 'prospect' ? 'selected' : ''; ?>>Prospect</option>
                            <option value="customer" <?php echo $filters['customer_type'] === 'customer' ? 'selected' : ''; ?>>Customer</option>
                            <option value="vip" <?php echo $filters['customer_type'] === 'vip' ? 'selected' : ''; ?>>VIP</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="">All Status</option>
                            <option value="active" <?php echo $filters['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $filters['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="blocked" <?php echo $filters['status'] === 'blocked' ? 'selected' : ''; ?>>Blocked</option>
                        </select>
                    </div>
                    
                    <div>
                        <button type="submit" class="btn"><i class="fas fa-search"></i> Filter</button>
                    </div>
                </div>
            </form>
            
            <div style="margin-top: 15px;">
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="import">
                    <button type="submit" class="btn btn-secondary btn-small">
                        <i class="fas fa-download"></i> Import from Users
                    </button>
                </form>
            </div>
        </div>

        <!-- Customer Table -->
        <div class="card">
            <h2>Customers (<?php echo $customersData['total']; ?> total)</h2>
            
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Contact</th>
                            <th>Company</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Activities</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customersData['customers'] as $customer): ?>
                        <tr>
                            <td>
                                <div class="customer-info">
                                    <div class="customer-avatar">
                                        <?php echo strtoupper(substr($customer['first_name'], 0, 1) . substr($customer['last_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></strong>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div><?php echo htmlspecialchars($customer['email']); ?></div>
                                <?php if ($customer['phone']): ?>
                                <small><?php echo htmlspecialchars($customer['phone']); ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($customer['company'] ?? '-'); ?></td>
                            <td>
                                <span class="type-badge type-<?php echo $customer['customer_type']; ?>">
                                    <?php echo ucfirst($customer['customer_type']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $customer['status']; ?>">
                                    <?php echo ucfirst($customer['status']); ?>
                                </span>
                            </td>
                            <td><?php echo $customer['activity_count']; ?></td>
                            <td><?php echo date('M j, Y', strtotime($customer['created_at'])); ?></td>
                            <td>
                                <a href="?edit=<?php echo $customer['id']; ?>" class="btn btn-small btn-secondary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button onclick="deleteCustomer(<?php echo $customer['id']; ?>)" class="btn btn-small btn-danger">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($customersData['total_pages'] > 1): ?>
            <div class="pagination">
                <?php for ($i = 1; $i <= $customersData['total_pages']; $i++): ?>
                <a href="?page=<?php echo $i; ?>&<?php echo http_build_query($filters); ?>" 
                   class="<?php echo $i === $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Customer Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-plus"></i> Add New Customer</h2>
                <span class="close" onclick="closeModal('addModal')">&times;</span>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" name="first_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" name="last_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="tel" name="phone">
                    </div>
                    
                    <div class="form-group">
                        <label>Company</label>
                        <input type="text" name="company">
                    </div>
                    
                    <div class="form-group">
                        <label>Customer Type</label>
                        <select name="customer_type">
                            <option value="lead">Lead</option>
                            <option value="prospect">Prospect</option>
                            <option value="customer">Customer</option>
                            <option value="vip">VIP</option>
                        </select>
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Address</label>
                        <textarea name="address" rows="3"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>City</label>
                        <input type="text" name="city">
                    </div>
                    
                    <div class="form-group">
                        <label>State</label>
                        <input type="text" name="state">
                    </div>
                    
                    <div class="form-group">
                        <label>Country</label>
                        <input type="text" name="country">
                    </div>
                    
                    <div class="form-group">
                        <label>Postal Code</label>
                        <input type="text" name="postal_code">
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Notes</label>
                        <textarea name="notes" rows="3"></textarea>
                    </div>
                </div>
                
                <div style="margin-top: 20px; text-align: right;">
                    <button type="button" onclick="closeModal('addModal')" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn">Add Customer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Customer Modal -->
    <?php if ($editCustomer): ?>
    <div id="editModal" class="modal" style="display: block;">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> Edit Customer</h2>
                <a href="customers.php" class="close">&times;</a>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?php echo $editCustomer['id']; ?>">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" name="first_name" value="<?php echo htmlspecialchars($editCustomer['first_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" name="last_name" value="<?php echo htmlspecialchars($editCustomer['last_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($editCustomer['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="tel" name="phone" value="<?php echo htmlspecialchars($editCustomer['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Company</label>
                        <input type="text" name="company" value="<?php echo htmlspecialchars($editCustomer['company'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Customer Type</label>
                        <select name="customer_type">
                            <option value="lead" <?php echo $editCustomer['customer_type'] === 'lead' ? 'selected' : ''; ?>>Lead</option>
                            <option value="prospect" <?php echo $editCustomer['customer_type'] === 'prospect' ? 'selected' : ''; ?>>Prospect</option>
                            <option value="customer" <?php echo $editCustomer['customer_type'] === 'customer' ? 'selected' : ''; ?>>Customer</option>
                            <option value="vip" <?php echo $editCustomer['customer_type'] === 'vip' ? 'selected' : ''; ?>>VIP</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="active" <?php echo $editCustomer['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $editCustomer['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="blocked" <?php echo $editCustomer['status'] === 'blocked' ? 'selected' : ''; ?>>Blocked</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Source</label>
                        <input type="text" name="source" value="<?php echo htmlspecialchars($editCustomer['source'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Address</label>
                        <textarea name="address" rows="3"><?php echo htmlspecialchars($editCustomer['address'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>City</label>
                        <input type="text" name="city" value="<?php echo htmlspecialchars($editCustomer['city'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>State</label>
                        <input type="text" name="state" value="<?php echo htmlspecialchars($editCustomer['state'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Country</label>
                        <input type="text" name="country" value="<?php echo htmlspecialchars($editCustomer['country'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Postal Code</label>
                        <input type="text" name="postal_code" value="<?php echo htmlspecialchars($editCustomer['postal_code'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Notes</label>
                        <textarea name="notes" rows="3"><?php echo htmlspecialchars($editCustomer['notes'] ?? ''); ?></textarea>
                    </div>
                </div>
                
                <div style="margin-top: 20px; text-align: right;">
                    <a href="customers.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn">Update Customer</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function deleteCustomer(id) {
            if (confirm('Are you sure you want to delete this customer? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>
