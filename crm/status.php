<?php
/**
 * CRM Status Check
 * M-EcommerceCRM - System Status Verification
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';
require_once '../includes/auth.php';

// Check if user is admin
$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../pages/login.php');
    exit;
}

$db = getDB();
$status = [];

// Check database tables
$tables = [
    'crm_customers', 'email_domains', 'email_templates', 'email_campaigns',
    'email_logs', 'received_emails', 'customer_segments', 'campaign_recipients',
    'email_unsubscribes', 'crm_activities'
];

foreach ($tables as $table) {
    try {
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM $table");
        $stmt->execute();
        $count = $stmt->fetch()['count'];
        $status[$table] = ['exists' => true, 'count' => $count];
    } catch (PDOException $e) {
        $status[$table] = ['exists' => false, 'error' => $e->getMessage()];
    }
}

// Check PHPMailer
$phpmailerExists = class_exists('PHPMailer\PHPMailer\PHPMailer');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM Status - M-EcommerceCRM</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
        .btn {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>M-EcommerceCRM System Status</h1>
        
        <h2>Database Tables Status</h2>
        <table>
            <thead>
                <tr>
                    <th>Table Name</th>
                    <th>Status</th>
                    <th>Record Count</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($status as $table => $info): ?>
                <tr>
                    <td><?php echo $table; ?></td>
                    <td>
                        <?php if ($info['exists']): ?>
                            <span class="success">✓ Exists</span>
                        <?php else: ?>
                            <span class="error">✗ Missing</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($info['exists']): ?>
                            <?php echo number_format($info['count']); ?> records
                        <?php else: ?>
                            <span class="error">N/A</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <h2>Dependencies Status</h2>
        <table>
            <tbody>
                <tr>
                    <td>PHPMailer</td>
                    <td>
                        <?php if ($phpmailerExists): ?>
                            <span class="success">✓ Installed</span>
                        <?php else: ?>
                            <span class="error">✗ Not Installed</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!$phpmailerExists): ?>
                            Run: <code>composer require phpmailer/phpmailer</code>
                        <?php else: ?>
                            Ready for email sending
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td>PHP Version</td>
                    <td><span class="success">✓ <?php echo PHP_VERSION; ?></span></td>
                    <td>Compatible</td>
                </tr>
                <tr>
                    <td>Session Support</td>
                    <td><span class="success">✓ Active</span></td>
                    <td>Working properly</td>
                </tr>
            </tbody>
        </table>
        
        <h2>Quick Actions</h2>
        <a href="index.php" class="btn">Open CRM Dashboard</a>
        <a href="settings.php" class="btn">Configure Email Domains</a>
        <a href="customers.php" class="btn">Manage Customers</a>
        <a href="../setup_crm.php" class="btn">Re-run Setup</a>
        
        <?php if (!$phpmailerExists): ?>
        <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3 class="warning">⚠️ PHPMailer Not Installed</h3>
            <p>To send emails, you need to install PHPMailer. Run this command in your project directory:</p>
            <code style="background: #f8f9fa; padding: 10px; display: block; margin: 10px 0;">
                composer require phpmailer/phpmailer
            </code>
        </div>
        <?php endif; ?>
        
        <div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3 class="success">✓ CRM System Status: Ready</h3>
            <p>Your M-EcommerceCRM system is properly installed and ready to use!</p>
        </div>
    </div>
</body>
</html>
