<?php
/**
 * Email Templates
 * M-EcommerceCRM - Email Template Management
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/email_manager.php';

// Check if user is admin
$auth = new Auth();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    header('Location: ../pages/login.php');
    exit;
}

$emailManager = new EmailManager();
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'save_template':
            try {
                $result = $emailManager->saveEmailTemplate($_POST);
                if ($result) {
                    $message = 'Email template saved successfully!';
                } else {
                    $error = 'Failed to save email template.';
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            break;
            
        case 'delete_template':
            try {
                $sql = "DELETE FROM email_templates WHERE id = ?";
                $stmt = getDB()->prepare($sql);
                $result = $stmt->execute([$_POST['id']]);
                if ($result) {
                    $message = 'Email template deleted successfully!';
                } else {
                    $error = 'Failed to delete email template.';
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            break;
    }
}

// Get email templates
$templates = $emailManager->getEmailTemplates(false);

// Get single template for editing
$editTemplate = null;
if (isset($_GET['edit'])) {
    foreach ($templates as $template) {
        if ($template['id'] == $_GET['edit']) {
            $editTemplate = $template;
            break;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Templates - M-EcommerceCRM</title>
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

        .template-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .template-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .template-card:hover {
            transform: translateY(-5px);
        }

        .template-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .template-name {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
        }

        .template-type {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }

        .type-welcome { background: #d4edda; color: #155724; }
        .type-promotional { background: #fff3cd; color: #856404; }
        .type-transactional { background: #cce5ff; color: #004085; }
        .type-newsletter { background: #f8d7da; color: #721c24; }
        .type-custom { background: #e2e3e5; color: #383d41; }

        .template-subject {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .template-preview {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            font-size: 13px;
            color: #666;
            max-height: 100px;
            overflow: hidden;
            margin-bottom: 15px;
        }

        .template-actions {
            display: flex;
            gap: 10px;
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
            min-height: 200px;
            resize: vertical;
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
            margin: 2% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 900px;
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

        .variable-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }

        .variable-tag {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            cursor: pointer;
        }

        .variable-tag:hover {
            background: rgba(102, 126, 234, 0.2);
        }

        .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }

        @media (max-width: 768px) {
            .template-grid {
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
                <h1><i class="fas fa-file-alt"></i> Email Templates</h1>
                <p>Create and manage email templates</p>
            </div>
            <div>
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                <button onclick="openModal('addTemplateModal')" class="btn"><i class="fas fa-plus"></i> New Template</button>
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

        <!-- Templates -->
        <div class="card">
            <h2>Email Templates (<?php echo count($templates); ?>)</h2>
            
            <?php if (empty($templates)): ?>
            <div style="text-align: center; padding: 40px; color: #666;">
                <i class="fas fa-file-alt" style="font-size: 3rem; margin-bottom: 20px; opacity: 0.3;"></i>
                <h3>No templates created yet</h3>
                <p>Create your first email template to get started.</p>
                <button onclick="openModal('addTemplateModal')" class="btn" style="margin-top: 20px;">
                    <i class="fas fa-plus"></i> Create Template
                </button>
            </div>
            <?php else: ?>
            <div class="template-grid">
                <?php foreach ($templates as $template): ?>
                <div class="template-card">
                    <div class="template-header">
                        <div class="template-name"><?php echo htmlspecialchars($template['name']); ?></div>
                        <span class="template-type type-<?php echo $template['template_type']; ?>">
                            <?php echo ucfirst($template['template_type']); ?>
                        </span>
                    </div>
                    
                    <div class="template-subject">
                        <strong>Subject:</strong> <?php echo htmlspecialchars($template['subject']); ?>
                    </div>
                    
                    <div class="template-preview">
                        <?php echo htmlspecialchars(substr(strip_tags($template['body_html']), 0, 150)) . '...'; ?>
                    </div>
                    
                    <?php if ($template['variables']): ?>
                    <div class="variable-tags">
                        <?php 
                        $variables = json_decode($template['variables'], true);
                        if ($variables) {
                            foreach ($variables as $var) {
                                echo '<span class="variable-tag">{{' . htmlspecialchars($var) . '}}</span>';
                            }
                        }
                        ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="template-actions">
                        <a href="?edit=<?php echo $template['id']; ?>" class="btn btn-small btn-secondary">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <button onclick="previewTemplate(<?php echo $template['id']; ?>)" class="btn btn-small btn-secondary">
                            <i class="fas fa-eye"></i> Preview
                        </button>
                        <button onclick="deleteTemplate(<?php echo $template['id']; ?>)" class="btn btn-small btn-danger">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Template Modal -->
    <div id="addTemplateModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-plus"></i> Create Email Template</h2>
                <span class="close" onclick="closeModal('addTemplateModal')">&times;</span>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="save_template">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Template Name *</label>
                        <input type="text" name="name" placeholder="Welcome Email" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Template Type</label>
                        <select name="template_type">
                            <option value="custom">Custom</option>
                            <option value="welcome">Welcome</option>
                            <option value="promotional">Promotional</option>
                            <option value="transactional">Transactional</option>
                            <option value="newsletter">Newsletter</option>
                        </select>
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Subject Line *</label>
                        <input type="text" name="subject" placeholder="Welcome to {{company_name}}!" required>
                        <div class="help-text">Use {{variable_name}} for dynamic content</div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label>HTML Content *</label>
                        <textarea name="body_html" placeholder="Enter your HTML email content here..." required></textarea>
                        <div class="help-text">Use HTML tags for formatting. Variables: {{customer_name}}, {{company_name}}, {{email}}</div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Plain Text Version</label>
                        <textarea name="body_text" placeholder="Plain text version (optional)"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Available Variables (comma-separated)</label>
                        <input type="text" name="variables" placeholder="customer_name,company_name,email">
                        <div class="help-text">List variables that can be used in this template</div>
                    </div>
                    
                    <div class="form-group">
                        <label>Status</label>
                        <select name="is_active">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                
                <div style="margin-top: 30px; text-align: right;">
                    <button type="button" onclick="closeModal('addTemplateModal')" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn">Create Template</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Template Modal -->
    <?php if ($editTemplate): ?>
    <div id="editTemplateModal" class="modal" style="display: block;">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-edit"></i> Edit Email Template</h2>
                <a href="templates.php" class="close">&times;</a>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="save_template">
                <input type="hidden" name="id" value="<?php echo $editTemplate['id']; ?>">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Template Name *</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($editTemplate['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Template Type</label>
                        <select name="template_type">
                            <option value="custom" <?php echo $editTemplate['template_type'] === 'custom' ? 'selected' : ''; ?>>Custom</option>
                            <option value="welcome" <?php echo $editTemplate['template_type'] === 'welcome' ? 'selected' : ''; ?>>Welcome</option>
                            <option value="promotional" <?php echo $editTemplate['template_type'] === 'promotional' ? 'selected' : ''; ?>>Promotional</option>
                            <option value="transactional" <?php echo $editTemplate['template_type'] === 'transactional' ? 'selected' : ''; ?>>Transactional</option>
                            <option value="newsletter" <?php echo $editTemplate['template_type'] === 'newsletter' ? 'selected' : ''; ?>>Newsletter</option>
                        </select>
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Subject Line *</label>
                        <input type="text" name="subject" value="<?php echo htmlspecialchars($editTemplate['subject']); ?>" required>
                    </div>
                    
                    <div class="form-group full-width">
                        <label>HTML Content *</label>
                        <textarea name="body_html" required><?php echo htmlspecialchars($editTemplate['body_html']); ?></textarea>
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Plain Text Version</label>
                        <textarea name="body_text"><?php echo htmlspecialchars($editTemplate['body_text'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Available Variables (comma-separated)</label>
                        <input type="text" name="variables" value="<?php 
                            $vars = json_decode($editTemplate['variables'], true);
                            echo htmlspecialchars($vars ? implode(',', $vars) : '');
                        ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Status</label>
                        <select name="is_active">
                            <option value="1" <?php echo $editTemplate['is_active'] ? 'selected' : ''; ?>>Active</option>
                            <option value="0" <?php echo !$editTemplate['is_active'] ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                
                <div style="margin-top: 30px; text-align: right;">
                    <a href="templates.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn">Update Template</button>
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

        function deleteTemplate(id) {
            if (confirm('Are you sure you want to delete this template? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_template">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function previewTemplate(id) {
            // Get template data
            const templates = <?php echo json_encode($templates); ?>;
            const template = templates.find(t => t.id == id);
            
            if (template) {
                const previewWindow = window.open('', '_blank', 'width=800,height=600');
                previewWindow.document.write(`
                    <html>
                    <head>
                        <title>Template Preview: ${template.name}</title>
                        <style>
                            body { font-family: Arial, sans-serif; margin: 20px; }
                            .header { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
                        </style>
                    </head>
                    <body>
                        <div class="header">
                            <h2>Template Preview: ${template.name}</h2>
                            <p><strong>Subject:</strong> ${template.subject}</p>
                            <p><strong>Type:</strong> ${template.template_type}</p>
                        </div>
                        <div>${template.body_html}</div>
                    </body>
                    </html>
                `);
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

        // Add variable tags to content
        document.addEventListener('click', function(event) {
            if (event.target.classList.contains('variable-tag')) {
                const variable = event.target.textContent;
                const textarea = document.querySelector('textarea[name="body_html"]:focus') || 
                               document.querySelector('textarea[name="subject"]:focus');
                
                if (textarea) {
                    const cursorPos = textarea.selectionStart;
                    const textBefore = textarea.value.substring(0, cursorPos);
                    const textAfter = textarea.value.substring(cursorPos);
                    textarea.value = textBefore + variable + textAfter;
                    textarea.focus();
                    textarea.setSelectionRange(cursorPos + variable.length, cursorPos + variable.length);
                }
            }
        });
    </script>
</body>
</html>
