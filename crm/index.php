<?php
/**
 * CRM Dashboard
 * M-EcommerceCRM - Main Dashboard
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/crm_config.php';
require_once '../includes/customer_manager.php';
require_once '../includes/email_manager.php';

// Check if user is admin
$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../pages/login.php');
    exit;
}

$customerManager = new CustomerManager();
$emailManager = new EmailManager();

// Get dashboard statistics
$customerStats = $customerManager->getCustomerStats();

// Get recent activities
$recentCustomers = $customerManager->getCustomers(1, 5);

// Get email statistics
$emailStatsQuery = "SELECT 
    COUNT(*) as total_sent,
    SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered,
    SUM(CASE WHEN status = 'opened' THEN 1 ELSE 0 END) as opened,
    SUM(CASE WHEN status = 'clicked' THEN 1 ELSE 0 END) as clicked
    FROM email_logs 
    WHERE sent_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
$emailStatsStmt = getDB()->prepare($emailStatsQuery);
$emailStatsStmt->execute();
$emailStats = $emailStatsStmt->fetch();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>M-EcommerceCRM Dashboard</title>
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
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }

        .header p {
            color: #666;
            font-size: 1.1rem;
        }

        .nav-menu {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .nav-menu ul {
            list-style: none;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .nav-menu a {
            text-decoration: none;
            color: #333;
            padding: 12px 24px;
            border-radius: 10px;
            background: rgba(102, 126, 234, 0.1);
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .nav-menu a:hover, .nav-menu a.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            transform: translateY(-2px);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card .icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            margin-bottom: 15px;
        }

        .stat-card .icon.customers { background: linear-gradient(135deg, #667eea, #764ba2); }
        .stat-card .icon.emails { background: linear-gradient(135deg, #f093fb, #f5576c); }
        .stat-card .icon.campaigns { background: linear-gradient(135deg, #4facfe, #00f2fe); }
        .stat-card .icon.revenue { background: linear-gradient(135deg, #43e97b, #38f9d7); }

        .stat-card h3 {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-card p {
            color: #666;
            font-weight: 500;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .card h2 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
        }

        .customer-list {
            list-style: none;
        }

        .customer-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .customer-item:last-child {
            border-bottom: none;
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
            margin-right: 15px;
        }

        .customer-info h4 {
            font-weight: 600;
            color: #333;
            margin-bottom: 2px;
        }

        .customer-info p {
            color: #666;
            font-size: 0.9rem;
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
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
        }

        .btn-secondary:hover {
            background: rgba(102, 126, 234, 0.2);
        }

        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .nav-menu ul {
                flex-direction: column;
            }
            
            .header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-chart-line"></i> M-EcommerceCRM</h1>
            <p>Complete Customer Relationship Management System</p>
        </div>

        <!-- Navigation -->
        <div class="nav-menu">
            <ul>
                <li><a href="index.php" class="active"><i class="fas fa-dashboard"></i> Dashboard</a></li>
                <li><a href="customers.php"><i class="fas fa-users"></i> Customers</a></li>
                <li><a href="emails.php"><i class="fas fa-envelope"></i> Email Center</a></li>
                <li><a href="campaigns.php"><i class="fas fa-bullhorn"></i> Campaigns</a></li>
                <li><a href="templates.php"><i class="fas fa-file-alt"></i> Templates</a></li>
                <li><a href="settings.php"><i class="fas fa-cog"></i> Settings</a></li>
                <li><a href="../admin/products.php"><i class="fas fa-arrow-left"></i> Back to E-commerce</a></li>
            </ul>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon customers">
                    <i class="fas fa-users"></i>
                </div>
                <h3><?php echo number_format($customerStats['total']); ?></h3>
                <p>Total Customers</p>
            </div>
            
            <div class="stat-card">
                <div class="icon emails">
                    <i class="fas fa-envelope"></i>
                </div>
                <h3><?php echo number_format($emailStats['total_sent'] ?? 0); ?></h3>
                <p>Emails Sent (30 days)</p>
            </div>
            
            <div class="stat-card">
                <div class="icon campaigns">
                    <i class="fas fa-bullhorn"></i>
                </div>
                <h3><?php echo number_format($emailStats['opened'] ?? 0); ?></h3>
                <p>Emails Opened</p>
            </div>
            
            <div class="stat-card">
                <div class="icon revenue">
                    <i class="fas fa-mouse-pointer"></i>
                </div>
                <h3><?php echo number_format($emailStats['clicked'] ?? 0); ?></h3>
                <p>Emails Clicked</p>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Recent Customers -->
            <div class="card">
                <h2><i class="fas fa-user-plus"></i> Recent Customers</h2>
                <ul class="customer-list">
                    <?php foreach ($recentCustomers['customers'] as $customer): ?>
                    <li class="customer-item">
                        <div class="customer-avatar">
                            <?php echo strtoupper(substr($customer['first_name'], 0, 1) . substr($customer['last_name'], 0, 1)); ?>
                        </div>
                        <div class="customer-info">
                            <h4><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']); ?></h4>
                            <p><?php echo htmlspecialchars($customer['email']); ?> • <?php echo ucfirst($customer['customer_type']); ?></p>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <div style="margin-top: 20px;">
                    <a href="customers.php" class="btn">View All Customers</a>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
                <div style="display: grid; gap: 15px;">
                    <a href="customers.php?action=add" class="btn">
                        <i class="fas fa-user-plus"></i> Add New Customer
                    </a>
                    <a href="emails.php?action=compose" class="btn">
                        <i class="fas fa-envelope"></i> Send Email
                    </a>
                    <a href="campaigns.php?action=create" class="btn">
                        <i class="fas fa-bullhorn"></i> Create Campaign
                    </a>
                    <a href="templates.php?action=create" class="btn btn-secondary">
                        <i class="fas fa-file-alt"></i> New Template
                    </a>
                    <a href="settings.php?tab=domains" class="btn btn-secondary">
                        <i class="fas fa-cog"></i> Configure Email
                    </a>
                </div>
            </div>
        </div>

        <!-- Customer Type Breakdown -->
        <div class="card">
            <h2><i class="fas fa-chart-pie"></i> Customer Breakdown</h2>
            <div class="stats-grid">
                <?php foreach ($customerStats['by_type'] as $type): ?>
                <div class="stat-card">
                    <h3><?php echo $type['count']; ?></h3>
                    <p><?php echo ucfirst($type['customer_type']); ?>s</p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh dashboard every 5 minutes
        setTimeout(() => {
            location.reload();
        }, 300000);
    </script>
</body>
</html>
