<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$auth = getAuth();
$error = '';
$success = '';

// Require login to access account page
$auth->requireLogin();

$user = $auth->getCurrentUser();
$db = getDB();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $name = sanitizeInput($_POST['name'] ?? '');
                $email = sanitizeInput($_POST['email'] ?? '');
                
                if (empty($name) || empty($email)) {
                    $error = 'Please fill in all fields';
                } else {
                    try {
                        // Check if email is already taken by another user
                        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                        $stmt->execute([$email, $user['id']]);
                        
                        if ($stmt->fetch()) {
                            $error = 'Email is already taken by another user';
                        } else {
                            // Update user profile
                            $stmt = $db->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
                            if ($stmt->execute([$name, $email, $user['id']])) {
                                // Update session
                                $_SESSION['user_name'] = $name;
                                $_SESSION['user_email'] = $email;
                                $user['name'] = $name;
                                $user['email'] = $email;
                                $success = 'Profile updated successfully';
                            } else {
                                $error = 'Failed to update profile';
                            }
                        }
                    } catch (Exception $e) {
                        $error = 'Database error: ' . $e->getMessage();
                    }
                }
                break;
                
            case 'change_password':
                $currentPassword = $_POST['current_password'] ?? '';
                $newPassword = $_POST['new_password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';
                
                if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                    $error = 'Please fill in all password fields';
                } elseif ($newPassword !== $confirmPassword) {
                    $error = 'New passwords do not match';
                } elseif (strlen($newPassword) < 6) {
                    $error = 'New password must be at least 6 characters long';
                } else {
                    try {
                        // Verify current password
                        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
                        $stmt->execute([$user['id']]);
                        $userData = $stmt->fetch();
                        
                        if ($userData && password_verify($currentPassword, $userData['password'])) {
                            // Update password
                            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
                            if ($stmt->execute([$hashedPassword, $user['id']])) {
                                $success = 'Password changed successfully';
                            } else {
                                $error = 'Failed to change password';
                            }
                        } else {
                            $error = 'Current password is incorrect';
                        }
                    } catch (Exception $e) {
                        $error = 'Database error: ' . $e->getMessage();
                    }
                }
                break;
        }
    }
}

// Get user's orders
try {
    $stmt = $db->prepare("
        SELECT o.*, COUNT(oi.id) as item_count 
        FROM orders o 
        LEFT JOIN order_items oi ON o.id = oi.order_id 
        WHERE o.user_id = ? 
        GROUP BY o.id 
        ORDER BY o.created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$user['id']]);
    $orders = $stmt->fetchAll();
} catch (Exception $e) {
    $orders = [];
}

// Get user's favorites count
try {
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM favorites WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $favoritesCount = $stmt->fetch()['count'];
} catch (Exception $e) {
    $favoritesCount = 0;
}

// Get user's cart count
try {
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $cartCount = $stmt->fetch()['count'];
} catch (Exception $e) {
    $cartCount = 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account - ModernShop</title>
    <meta name="description" content="Manage your ModernShop account, view orders, and update your profile.">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="<?= getBaseUrl() ?>/css/style.css">
    
    <style>
        .account-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .account-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .account-grid {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .account-sidebar {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 1.5rem;
            height: fit-content;
            border: 1px solid var(--border-color);
        }
        
        .account-content {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 2rem;
            border: 1px solid var(--border-color);
        }
        
        .user-info {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            margin-bottom: 1rem;
        }
        
        .nav-menu {
            list-style: none;
            padding: 0;
        }
        
        .nav-menu li {
            margin-bottom: 0.5rem;
        }
        
        .nav-menu a {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            text-decoration: none;
            color: var(--text-secondary);
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        
        .nav-menu a:hover,
        .nav-menu a.active {
            background: var(--primary-color);
            color: white;
        }
        
        .nav-menu svg {
            margin-right: 0.75rem;
            width: 20px;
            height: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .section {
            display: none;
        }
        
        .section.active {
            display: block;
        }
        
        .form-section {
            margin-bottom: 2rem;
        }
        
        .form-section h3 {
            margin-bottom: 1rem;
            color: var(--text-primary);
        }
        
        .orders-list {
            margin-top: 1rem;
        }
        
        .order-item {
            display: flex;
            justify-content: between;
            align-items: center;
            padding: 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .order-info {
            flex: 1;
        }
        
        .order-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #d4edda; color: #155724; }
        .status-shipped { background: #d1ecf1; color: #0c5460; }
        .status-delivered { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        @media (max-width: 768px) {
            .account-grid {
                grid-template-columns: 1fr;
            }
            
            .account-container {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../components/header.php'; ?>
    
    <main id="main-content">
        <div class="account-container">
            <!-- Header -->
            <div class="account-header">
                <h1 class="gradient-text font-display">My Account</h1>
                <p>Manage your profile, orders, and preferences</p>
            </div>
            
            <!-- Messages -->
            <?php if ($error): ?>
                <div class="alert alert-error" role="alert">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success" role="alert">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20,6 9,17 4,12"></polyline>
                    </svg>
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <div class="account-grid">
                <!-- Sidebar -->
                <div class="account-sidebar">
                    <div class="user-info">
                        <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="User Avatar" class="user-avatar">
                        <h3><?= htmlspecialchars($user['name']) ?></h3>
                        <p><?= htmlspecialchars($user['email']) ?></p>
                        <?php if ($user['is_admin']): ?>
                            <span class="badge badge-admin">Admin</span>
                        <?php endif; ?>
                    </div>
                    
                    <nav>
                        <ul class="nav-menu">
                            <li>
                                <a href="#" onclick="showSection('overview')" class="nav-link active">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                        <polyline points="9,22 9,12 15,12 15,22"></polyline>
                                    </svg>
                                    Overview
                                </a>
                            </li>
                            <li>
                                <a href="#" onclick="showSection('profile')" class="nav-link">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="12" cy="7" r="4"></circle>
                                    </svg>
                                    Profile
                                </a>
                            </li>
                            <li>
                                <a href="#" onclick="showSection('orders')" class="nav-link">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                                        <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
                                    </svg>
                                    Orders
                                </a>
                            </li>
                            <li>
                                <a href="#" onclick="showSection('security')" class="nav-link">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                        <circle cx="12" cy="16" r="1"></circle>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                    </svg>
                                    Security
                                </a>
                            </li>
                            <?php if ($user['is_admin']): ?>
                            <li>
                                <a href="<?= getBaseUrl() ?>/admin/products.php" class="nav-link">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
                                    </svg>
                                    Admin Panel
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
                
                <!-- Content -->
                <div class="account-content">
                    <!-- Overview Section -->
                    <div id="overview" class="section active">
                        <h2>Account Overview</h2>
                        
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-number"><?= count($orders) ?></div>
                                <div>Total Orders</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number"><?= $favoritesCount ?></div>
                                <div>Favorites</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-number"><?= $cartCount ?></div>
                                <div>Cart Items</div>
                            </div>
                        </div>
                        
                        <h3>Recent Orders</h3>
                        <div class="orders-list">
                            <?php if (empty($orders)): ?>
                                <p>No orders found. <a href="<?= getBaseUrl() ?>/pages/products.php">Start shopping!</a></p>
                            <?php else: ?>
                                <?php foreach (array_slice($orders, 0, 3) as $order): ?>
                                    <div class="order-item">
                                        <div class="order-info">
                                            <strong>Order #<?= $order['id'] ?></strong>
                                            <p><?= $order['item_count'] ?> items - <?= formatPrice($order['total_amount']) ?></p>
                                            <small><?= date('M j, Y', strtotime($order['created_at'])) ?></small>
                                        </div>
                                        <span class="order-status status-<?= $order['status'] ?>">
                                            <?= ucfirst($order['status']) ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Profile Section -->
                    <div id="profile" class="section">
                        <h2>Profile Information</h2>
                        
                        <form method="POST" class="form-section">
                            <input type="hidden" name="action" value="update_profile">
                            <h3>Personal Information</h3>
                            
                            <div class="form-group">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" id="name" name="name" class="input" value="<?= htmlspecialchars($user['name']) ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" id="email" name="email" class="input" value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </form>
                    </div>
                    
                    <!-- Orders Section -->
                    <div id="orders" class="section">
                        <h2>Order History</h2>
                        
                        <div class="orders-list">
                            <?php if (empty($orders)): ?>
                                <p>No orders found. <a href="<?= getBaseUrl() ?>/pages/products.php">Start shopping!</a></p>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <div class="order-item">
                                        <div class="order-info">
                                            <strong>Order #<?= $order['id'] ?></strong>
                                            <p><?= $order['item_count'] ?> items - <?= formatPrice($order['total_amount']) ?></p>
                                            <small><?= date('M j, Y g:i A', strtotime($order['created_at'])) ?></small>
                                        </div>
                                        <span class="order-status status-<?= $order['status'] ?>">
                                            <?= ucfirst($order['status']) ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Security Section -->
                    <div id="security" class="section">
                        <h2>Security Settings</h2>
                        
                        <form method="POST" class="form-section">
                            <input type="hidden" name="action" value="change_password">
                            <h3>Change Password</h3>
                            
                            <div class="form-group">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" id="current_password" name="current_password" class="input" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" id="new_password" name="new_password" class="input" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="input" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Change Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/../components/footer.php'; ?>
    
    <script>
        function showSection(sectionId) {
            // Hide all sections
            document.querySelectorAll('.section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Remove active class from all nav links
            document.querySelectorAll('.nav-link').forEach(link => {
                link.classList.remove('active');
            });
            
            // Show selected section
            document.getElementById(sectionId).classList.add('active');
            
            // Add active class to clicked nav link
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
