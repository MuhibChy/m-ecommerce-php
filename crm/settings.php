<?php
/**
 * CRM Settings
 * M-EcommerceCRM - Email Domain Configuration
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/crm_config.php';

// Check if user is admin
$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../pages/login.php');
    exit;
}

$crmConfig = new CRMConfig();
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_domain':
            try {
                $crmConfig->addEmailDomain($_POST);
                $message = 'Email domain added successfully!';
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            break;
            
        case 'update_domain':
            try {
                $crmConfig->updateEmailDomain($_POST['id'], $_POST);
                $message = 'Email domain updated successfully!';
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            break;
            
        case 'delete_domain':
            try {
                $crmConfig->deleteEmailDomain($_POST['id']);
                $message = 'Email domain deleted successfully!';
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            break;
    }
}

// Get email domains
$emailDomains = $crmConfig->getEmailDomains(false);

// Get single domain for editing
$editDomain = null;
if (isset($_GET['edit'])) {
    foreach ($emailDomains as $domain) {
        if ($domain['id'] == $_GET['edit']) {
            $editDomain = $domain;
            break;
        }
    }
}

$activeTab = $_GET['tab'] ?? 'domains';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM Settings - M-EcommerceCRM</title>
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
            max-width: 1200px;
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

        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
        }

        .tab {
            padding: 12px 24px;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 10px;
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .tab.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-group label {
            font-weight: 500;
            margin-bottom: 8px;
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

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #f8d7da; color: #721c24; }

        .default-badge {
            background: #fff3cd;
            color: #856404;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
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
            margin: 3% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
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

        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        .section {
            margin-bottom: 30px;
        }

        .section h3 {
            margin-bottom: 15px;
            color: #333;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .tabs {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div>
                <h1><i class="fas fa-cog"></i> CRM Settings</h1>
                <p>Configure your CRM system</p>
            </div>
            <div>
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
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

        <!-- Tabs -->
        <div class="tabs">
            <a href="?tab=domains" class="tab <?php echo $activeTab === 'domains' ? 'active' : ''; ?>">
                <i class="fas fa-envelope"></i> Email Domains
            </a>
            <a href="?tab=general" class="tab <?php echo $activeTab === 'general' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i> General Settings
            </a>
        </div>

        <?php if ($activeTab === 'domains'): ?>
        <!-- Email Domains -->
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2><i class="fas fa-envelope"></i> Email Domain Configuration</h2>
                <button onclick="openModal('addDomainModal')" class="btn">
                    <i class="fas fa-plus"></i> Add Email Domain
                </button>
            </div>
            
            <p style="color: #666; margin-bottom: 20px;">
                Configure multiple email domains for sending and receiving emails. Each domain can have different SMTP/IMAP settings.
            </p>

            <table class="table">
                <thead>
                    <tr>
                        <th>Domain</th>
                        <th>SMTP Server</th>
                        <th>From Email</th>
                        <th>Daily Limit</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($emailDomains as $domain): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($domain['domain_name']); ?></strong>
                            <?php if ($domain['is_default']): ?>
                            <span class="default-badge">Default</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($domain['smtp_host'] . ':' . $domain['smtp_port']); ?></td>
                        <td><?php echo htmlspecialchars($domain['from_email']); ?></td>
                        <td><?php echo number_format($domain['daily_limit']); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $domain['is_active'] ? 'active' : 'inactive'; ?>">
                                <?php echo $domain['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td>
                            <a href="?tab=domains&edit=<?php echo $domain['id']; ?>" class="btn btn-small btn-secondary">
                                <i class="fas fa-edit"></i>
                            </a>
                            <?php if (!$domain['is_default']): ?>
                            <button onclick="deleteDomain(<?php echo $domain['id']; ?>)" class="btn btn-small btn-danger">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <?php if ($activeTab === 'general'): ?>
        <!-- General Settings -->
        <div class="card">
            <h2><i class="fas fa-cog"></i> General CRM Settings</h2>
            
            <div class="section">
                <h3>System Information</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label>CRM Version</label>
                        <input type="text" value="1.0.0" readonly>
                    </div>
                    <div class="form-group">
                        <label>Database Status</label>
                        <input type="text" value="Connected" readonly>
                    </div>
                    <div class="form-group">
                        <label>PHP Version</label>
                        <input type="text" value="<?php echo PHP_VERSION; ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label>Server Time</label>
                        <input type="text" value="<?php echo date('Y-m-d H:i:s'); ?>" readonly>
                    </div>
                </div>
            </div>

            <div class="section">
                <h3>Installation Instructions</h3>
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                    <h4>1. Install PHPMailer</h4>
                    <p>Run the following command in your project directory:</p>
                    <code style="background: #e9ecef; padding: 5px 10px; border-radius: 4px; display: block; margin: 10px 0;">
                        composer require phpmailer/phpmailer
                    </code>
                    
                    <h4 style="margin-top: 20px;">2. Setup Database</h4>
                    <p>Import the CRM database schema:</p>
                    <code style="background: #e9ecef; padding: 5px 10px; border-radius: 4px; display: block; margin: 10px 0;">
                        mysql -u root -p m_ecommerce < config/crm_setup.sql
                    </code>
                    
                    <h4 style="margin-top: 20px;">3. Configure Email Domains</h4>
                    <p>Add your email domains in the "Email Domains" tab with proper SMTP/IMAP settings.</p>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Add Domain Modal -->
    <div id="addDomainModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-plus"></i> Add Email Domain</h2>
                <span class="close" onclick="closeModal('addDomainModal')">&times;</span>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="add_domain">
                
                <div class="section">
                    <h3>Domain Information</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Domain Name *</label>
                            <input type="text" name="domain_name" placeholder="example.com" required>
                            <div class="help-text">The domain name (e.g., gmail.com, outlook.com)</div>
                        </div>
                        
                        <div class="form-group">
                            <label>From Name *</label>
                            <input type="text" name="from_name" placeholder="Your Company Name" required>
                        </div>
                        
                        <div class="form-group">
                            <label>From Email *</label>
                            <input type="email" name="from_email" placeholder="noreply@example.com" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Reply To Email</label>
                            <input type="email" name="reply_to_email" placeholder="support@example.com">
                        </div>
                        
                        <div class="form-group">
                            <label>Daily Email Limit</label>
                            <input type="number" name="daily_limit" value="500" min="1">
                            <div class="help-text">Maximum emails per day for this domain</div>
                        </div>
                        
                        <div class="form-group">
                            <label>Set as Default</label>
                            <select name="is_default">
                                <option value="0">No</option>
                                <option value="1">Yes</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="section">
                    <h3>SMTP Configuration (Outgoing Mail)</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>SMTP Host *</label>
                            <input type="text" name="smtp_host" placeholder="smtp.gmail.com" required>
                        </div>
                        
                        <div class="form-group">
                            <label>SMTP Port *</label>
                            <input type="number" name="smtp_port" value="587" required>
                        </div>
                        
                        <div class="form-group">
                            <label>SMTP Username *</label>
                            <input type="text" name="smtp_username" placeholder="your-email@gmail.com" required>
                        </div>
                        
                        <div class="form-group">
                            <label>SMTP Password *</label>
                            <input type="password" name="smtp_password" required>
                            <div class="help-text">Use app password for Gmail/Outlook</div>
                        </div>
                        
                        <div class="form-group">
                            <label>SMTP Encryption</label>
                            <select name="smtp_encryption">
                                <option value="tls">TLS (recommended)</option>
                                <option value="ssl">SSL</option>
                                <option value="none">None</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="section">
                    <h3>IMAP Configuration (Incoming Mail) - Optional</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>IMAP Host</label>
                            <input type="text" name="imap_host" placeholder="imap.gmail.com">
                        </div>
                        
                        <div class="form-group">
                            <label>IMAP Port</label>
                            <input type="number" name="imap_port" value="993">
                        </div>
                        
                        <div class="form-group">
                            <label>IMAP Username</label>
                            <input type="text" name="imap_username" placeholder="your-email@gmail.com">
                        </div>
                        
                        <div class="form-group">
                            <label>IMAP Password</label>
                            <input type="password" name="imap_password">
                        </div>
                        
                        <div class="form-group">
                            <label>IMAP Encryption</label>
                            <select name="imap_encryption">
                                <option value="tls">TLS (recommended)</option>
                                <option value="ssl">SSL</option>
                                <option value="none">None</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div style="margin-top: 30px; text-align: right;">
                    <button type="button" onclick="closeModal('addDomainModal')" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn">Add Domain</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Domain Modal -->
    <?php if ($editDomain): ?>
    <div id="editDomainModal" class="modal" style="display: block;">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> Edit Email Domain</h2>
                <a href="?tab=domains" class="close">&times;</a>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="update_domain">
                <input type="hidden" name="id" value="<?php echo $editDomain['id']; ?>">
                
                <div class="section">
                    <h3>Domain Information</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Domain Name *</label>
                            <input type="text" name="domain_name" value="<?php echo htmlspecialchars($editDomain['domain_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>From Name *</label>
                            <input type="text" name="from_name" value="<?php echo htmlspecialchars($editDomain['from_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>From Email *</label>
                            <input type="email" name="from_email" value="<?php echo htmlspecialchars($editDomain['from_email']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Reply To Email</label>
                            <input type="email" name="reply_to_email" value="<?php echo htmlspecialchars($editDomain['reply_to_email'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Daily Email Limit</label>
                            <input type="number" name="daily_limit" value="<?php echo $editDomain['daily_limit']; ?>" min="1">
                        </div>
                        
                        <div class="form-group">
                            <label>Status</label>
                            <select name="is_active">
                                <option value="1" <?php echo $editDomain['is_active'] ? 'selected' : ''; ?>>Active</option>
                                <option value="0" <?php echo !$editDomain['is_active'] ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Set as Default</label>
                            <select name="is_default">
                                <option value="0" <?php echo !$editDomain['is_default'] ? 'selected' : ''; ?>>No</option>
                                <option value="1" <?php echo $editDomain['is_default'] ? 'selected' : ''; ?>>Yes</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="section">
                    <h3>SMTP Configuration</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>SMTP Host *</label>
                            <input type="text" name="smtp_host" value="<?php echo htmlspecialchars($editDomain['smtp_host']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>SMTP Port *</label>
                            <input type="number" name="smtp_port" value="<?php echo $editDomain['smtp_port']; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>SMTP Username *</label>
                            <input type="text" name="smtp_username" value="<?php echo htmlspecialchars($editDomain['smtp_username']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>SMTP Password</label>
                            <input type="password" name="smtp_password" placeholder="Leave blank to keep current password">
                        </div>
                        
                        <div class="form-group">
                            <label>SMTP Encryption</label>
                            <select name="smtp_encryption">
                                <option value="tls" <?php echo $editDomain['smtp_encryption'] === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                <option value="ssl" <?php echo $editDomain['smtp_encryption'] === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                <option value="none" <?php echo $editDomain['smtp_encryption'] === 'none' ? 'selected' : ''; ?>>None</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="section">
                    <h3>IMAP Configuration</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>IMAP Host</label>
                            <input type="text" name="imap_host" value="<?php echo htmlspecialchars($editDomain['imap_host'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>IMAP Port</label>
                            <input type="number" name="imap_port" value="<?php echo $editDomain['imap_port']; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>IMAP Username</label>
                            <input type="text" name="imap_username" value="<?php echo htmlspecialchars($editDomain['imap_username'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>IMAP Password</label>
                            <input type="password" name="imap_password" placeholder="Leave blank to keep current password">
                        </div>
                        
                        <div class="form-group">
                            <label>IMAP Encryption</label>
                            <select name="imap_encryption">
                                <option value="tls" <?php echo ($editDomain['imap_encryption'] ?? 'tls') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                <option value="ssl" <?php echo ($editDomain['imap_encryption'] ?? '') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                <option value="none" <?php echo ($editDomain['imap_encryption'] ?? '') === 'none' ? 'selected' : ''; ?>>None</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div style="margin-top: 30px; text-align: right;">
                    <a href="?tab=domains" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn">Update Domain</button>
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

        function deleteDomain(id) {
            if (confirm('Are you sure you want to delete this email domain? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_domain">
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
