<?php
/**
 * Email Center
 * M-EcommerceCRM - Email Management Interface
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

$emailManager = new EmailManager();
$customerManager = new CustomerManager();
$crmConfig = new CRMConfig();
$message = '';
$error = '';

// Check PHPMailer availability
$phpmailerAvailable = class_exists('PHPMailer\PHPMailer\PHPMailer');

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'send_email':
            try {
                $result = $emailManager->sendEmail($_POST);
                if ($result['success']) {
                    $message = 'Email sent successfully!';
                } else {
                    $error = $result['error'];
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            break;
            
        case 'receive_emails':
            try {
                $domainId = $_POST['domain_id'];
                $result = $emailManager->receiveEmails($domainId);
                if ($result['success']) {
                    $message = "Received {$result['new_emails']} new emails.";
                } else {
                    $error = $result['error'];
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            break;
    }
}

// Get email domains
$emailDomains = $crmConfig->getEmailDomains();

// Get email templates
$emailTemplates = $emailManager->getEmailTemplates();

// Get recent sent emails
$recentEmailsQuery = "SELECT el.*, c.first_name, c.last_name, ed.domain_name 
                      FROM email_logs el 
                      LEFT JOIN crm_customers c ON el.customer_id = c.id 
                      LEFT JOIN email_domains ed ON el.email_domain_id = ed.id 
                      ORDER BY el.created_at DESC 
                      LIMIT 20";
$recentEmailsStmt = getDB()->prepare($recentEmailsQuery);
$recentEmailsStmt->execute();
$recentEmails = $recentEmailsStmt->fetchAll();

// Get received emails
$receivedEmailsQuery = "SELECT re.*, ed.domain_name 
                        FROM received_emails re 
                        LEFT JOIN email_domains ed ON re.email_domain_id = ed.id 
                        ORDER BY re.received_at DESC 
                        LIMIT 20";
$receivedEmailsStmt = getDB()->prepare($receivedEmailsQuery);
$receivedEmailsStmt->execute();
$receivedEmails = $receivedEmailsStmt->fetchAll();

$activeTab = $_GET['tab'] ?? 'compose';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Center - M-EcommerceCRM</title>
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

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .table th,
        .table td {
            padding: 12px;
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

        .status-sent { background: #d4edda; color: #155724; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-failed { background: #f8d7da; color: #721c24; }
        .status-opened { background: #cce5ff; color: #004085; }
        .status-clicked { background: #e2e3e5; color: #383d41; }

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

        .email-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            transition: background-color 0.3s ease;
        }

        .email-item:hover {
            background: rgba(102, 126, 234, 0.05);
        }

        .email-item:last-child {
            border-bottom: none;
        }

        .email-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .email-subject {
            font-weight: 600;
            color: #333;
        }

        .email-meta {
            font-size: 12px;
            color: #666;
        }

        .email-preview {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }

        .customer-search {
            position: relative;
        }

        .customer-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }

        .customer-suggestion {
            padding: 10px;
            cursor: pointer;
            border-bottom: 1px solid #eee;
        }

        .customer-suggestion:hover {
            background: #f8f9fa;
        }

        .customer-suggestion:last-child {
            border-bottom: none;
        }

        .toolbar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
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
            
            .toolbar {
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
                <h1><i class="fas fa-envelope"></i> Email Center</h1>
                <p>Send and manage emails to your customers</p>
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
            <a href="?tab=compose" class="tab <?php echo $activeTab === 'compose' ? 'active' : ''; ?>">
                <i class="fas fa-edit"></i> Compose Email
            </a>
            <a href="?tab=sent" class="tab <?php echo $activeTab === 'sent' ? 'active' : ''; ?>">
                <i class="fas fa-paper-plane"></i> Sent Emails
            </a>
            <a href="?tab=inbox" class="tab <?php echo $activeTab === 'inbox' ? 'active' : ''; ?>">
                <i class="fas fa-inbox"></i> Inbox
            </a>
        </div>

        <?php if ($activeTab === 'compose'): ?>
        <!-- Compose Email -->
        <div class="card">
            <h2><i class="fas fa-edit"></i> Compose New Email</h2>
            
            <?php if (!$phpmailerAvailable): ?>
            <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <h3 style="color: #856404; margin: 0 0 10px 0;">⚠️ PHPMailer Required</h3>
                <p style="margin: 0 0 10px 0; color: #856404;">
                    To send emails, you need to install PHPMailer first.
                </p>
                <a href="../install_phpmailer.php" style="background: #ffc107; color: #212529; padding: 8px 16px; text-decoration: none; border-radius: 4px; font-weight: 500;">
                    📦 Install PHPMailer
                </a>
            </div>
            <?php endif; ?>
            
            <form method="POST" id="composeForm" <?php echo !$phpmailerAvailable ? 'style="opacity: 0.6; pointer-events: none;"' : ''; ?>>
                <input type="hidden" name="action" value="send_email">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Email Domain</label>
                        <select name="email_domain_id" required>
                            <option value="">Select Email Domain</option>
                            <?php foreach ($emailDomains as $domain): ?>
                            <option value="<?php echo $domain['id']; ?>" <?php echo $domain['is_default'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($domain['from_email'] . ' (' . $domain['domain_name'] . ')'); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Email Template (Optional)</label>
                        <select id="templateSelect" onchange="loadTemplate()">
                            <option value="">Select Template</option>
                            <?php foreach ($emailTemplates as $template): ?>
                            <option value="<?php echo $template['id']; ?>" 
                                    data-subject="<?php echo htmlspecialchars($template['subject']); ?>"
                                    data-body="<?php echo htmlspecialchars($template['body_html']); ?>">
                                <?php echo htmlspecialchars($template['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group customer-search">
                        <label>To Email *</label>
                        <input type="email" name="to_email" id="toEmail" placeholder="customer@example.com" required 
                               onkeyup="searchCustomers(this.value)">
                        <div id="customerSuggestions" class="customer-suggestions"></div>
                    </div>
                    
                    <div class="form-group">
                        <label>To Name</label>
                        <input type="text" name="to_name" id="toName" placeholder="Customer Name">
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Subject *</label>
                        <input type="text" name="subject" id="emailSubject" placeholder="Email subject" required>
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Message (HTML) *</label>
                        <textarea name="body_html" id="emailBody" placeholder="Enter your email message here..." required></textarea>
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Plain Text Version</label>
                        <textarea name="body_text" placeholder="Plain text version (optional)"></textarea>
                    </div>
                </div>
                
                <div style="margin-top: 20px; text-align: right;">
                    <button type="button" onclick="previewEmail()" class="btn btn-secondary">
                        <i class="fas fa-eye"></i> Preview
                    </button>
                    <button type="submit" class="btn">
                        <i class="fas fa-paper-plane"></i> Send Email
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <?php if ($activeTab === 'sent'): ?>
        <!-- Sent Emails -->
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2><i class="fas fa-paper-plane"></i> Sent Emails</h2>
                <div class="toolbar">
                    <button onclick="refreshEmails()" class="btn btn-secondary btn-small">
                        <i class="fas fa-refresh"></i> Refresh
                    </button>
                </div>
            </div>
            
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Recipient</th>
                            <th>Subject</th>
                            <th>Domain</th>
                            <th>Status</th>
                            <th>Sent At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentEmails as $email): ?>
                        <tr>
                            <td>
                                <div>
                                    <strong><?php echo htmlspecialchars($email['recipient_name'] ?? $email['recipient_email']); ?></strong>
                                    <br><small><?php echo htmlspecialchars($email['recipient_email']); ?></small>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($email['subject']); ?></td>
                            <td><?php echo htmlspecialchars($email['domain_name'] ?? 'Unknown'); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $email['status']; ?>">
                                    <?php echo ucfirst($email['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($email['sent_at']): ?>
                                    <?php echo date('M j, Y H:i', strtotime($email['sent_at'])); ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <button onclick="viewEmail(<?php echo $email['id']; ?>)" class="btn btn-small btn-secondary">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($activeTab === 'inbox'): ?>
        <!-- Inbox -->
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2><i class="fas fa-inbox"></i> Inbox</h2>
                <div class="toolbar">
                    <?php foreach ($emailDomains as $domain): ?>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="receive_emails">
                        <input type="hidden" name="domain_id" value="<?php echo $domain['id']; ?>">
                        <button type="submit" class="btn btn-secondary btn-small">
                            <i class="fas fa-download"></i> Check <?php echo htmlspecialchars($domain['domain_name']); ?>
                        </button>
                    </form>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <?php if (empty($receivedEmails)): ?>
            <div style="text-align: center; padding: 40px; color: #666;">
                <i class="fas fa-inbox" style="font-size: 3rem; margin-bottom: 20px; opacity: 0.3;"></i>
                <h3>No emails received yet</h3>
                <p>Configure IMAP settings in email domains to receive emails automatically.</p>
            </div>
            <?php else: ?>
            <div>
                <?php foreach ($receivedEmails as $email): ?>
                <div class="email-item">
                    <div class="email-header">
                        <div class="email-subject"><?php echo htmlspecialchars($email['subject']); ?></div>
                        <div class="email-meta">
                            <?php echo date('M j, Y H:i', strtotime($email['received_at'])); ?>
                        </div>
                    </div>
                    <div class="email-meta">
                        From: <?php echo htmlspecialchars($email['from_name'] ?? $email['from_email']); ?> 
                        &lt;<?php echo htmlspecialchars($email['from_email']); ?>&gt;
                        | To: <?php echo htmlspecialchars($email['to_email']); ?>
                    </div>
                    <?php if ($email['body_text']): ?>
                    <div class="email-preview">
                        <?php echo htmlspecialchars(substr($email['body_text'], 0, 200)) . '...'; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
        let customerSearchTimeout;
        
        function searchCustomers(query) {
            clearTimeout(customerSearchTimeout);
            
            if (query.length < 2) {
                document.getElementById('customerSuggestions').style.display = 'none';
                return;
            }
            
            customerSearchTimeout = setTimeout(() => {
                fetch('../api/search_customers.php?q=' + encodeURIComponent(query))
                    .then(response => response.json())
                    .then(data => {
                        const suggestions = document.getElementById('customerSuggestions');
                        suggestions.innerHTML = '';
                        
                        if (data.length > 0) {
                            data.forEach(customer => {
                                const div = document.createElement('div');
                                div.className = 'customer-suggestion';
                                div.innerHTML = `
                                    <strong>${customer.name}</strong><br>
                                    <small>${customer.email}</small>
                                `;
                                div.onclick = () => selectCustomer(customer);
                                suggestions.appendChild(div);
                            });
                            suggestions.style.display = 'block';
                        } else {
                            suggestions.style.display = 'none';
                        }
                    })
                    .catch(error => {
                        console.error('Error searching customers:', error);
                    });
            }, 300);
        }
        
        function selectCustomer(customer) {
            document.getElementById('toEmail').value = customer.email;
            document.getElementById('toName').value = customer.name;
            document.getElementById('customerSuggestions').style.display = 'none';
        }
        
        function loadTemplate() {
            const select = document.getElementById('templateSelect');
            const option = select.options[select.selectedIndex];
            
            if (option.value) {
                document.getElementById('emailSubject').value = option.dataset.subject || '';
                document.getElementById('emailBody').value = option.dataset.body || '';
            }
        }
        
        function previewEmail() {
            const subject = document.getElementById('emailSubject').value;
            const body = document.getElementById('emailBody').value;
            
            const previewWindow = window.open('', '_blank', 'width=800,height=600');
            previewWindow.document.write(`
                <html>
                <head>
                    <title>Email Preview: ${subject}</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .header { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h2>Email Preview</h2>
                        <p><strong>Subject:</strong> ${subject}</p>
                    </div>
                    <div>${body}</div>
                </body>
                </html>
            `);
        }
        
        function viewEmail(emailId) {
            // Implementation for viewing email details
            alert('Email details view - ID: ' + emailId);
        }
        
        function refreshEmails() {
            location.reload();
        }
        
        // Hide customer suggestions when clicking outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.customer-search')) {
                document.getElementById('customerSuggestions').style.display = 'none';
            }
        });
    </script>
</body>
</html>
