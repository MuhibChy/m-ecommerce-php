<?php
/**
 * PHPMailer Installation Guide
 * M-EcommerceCRM - Installation Helper
 */

echo "<!DOCTYPE html>
<html>
<head>
    <title>Install PHPMailer - M-EcommerceCRM</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; } .error { color: red; } .info { color: blue; }
        .code { background: #f5f5f5; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .btn { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>PHPMailer Installation Guide</h1>";

// Check if PHPMailer is already installed
if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    echo "<div class='success'>
        <h2>✅ PHPMailer is Already Installed!</h2>
        <p>PHPMailer is properly installed and ready to use.</p>
        <a href='crm/index.php' class='btn'>Go to CRM Dashboard</a>
    </div>";
} else {
    echo "<div class='error'>
        <h2>❌ PHPMailer Not Found</h2>
        <p>PHPMailer is required for sending emails. Please follow the installation steps below:</p>
    </div>";
    
    echo "<h2>Installation Methods:</h2>";
    
    echo "<h3>Method 1: Using Composer (Recommended)</h3>
    <ol>
        <li>Open Command Prompt or Terminal</li>
        <li>Navigate to your project directory:</li>
        <div class='code'>cd c:\\xampp\\xampp\\htdocs\\m-ecommerce-php</div>
        <li>Run the composer install command:</li>
        <div class='code'>composer require phpmailer/phpmailer</div>
        <li>Wait for installation to complete</li>
        <li>Refresh this page to verify installation</li>
    </ol>";
    
    echo "<h3>Method 2: Download and Install Composer First</h3>
    <p>If you don't have Composer installed:</p>
    <ol>
        <li>Download Composer from: <a href='https://getcomposer.org/download/' target='_blank'>https://getcomposer.org/download/</a></li>
        <li>Install Composer on your system</li>
        <li>Follow Method 1 above</li>
    </ol>";
    
    echo "<h3>Method 3: Manual Installation (Not Recommended)</h3>
    <ol>
        <li>Download PHPMailer from: <a href='https://github.com/PHPMailer/PHPMailer' target='_blank'>GitHub</a></li>
        <li>Extract to your project's vendor folder</li>
        <li>Include the autoloader manually</li>
    </ol>";
    
    echo "<h2>Verification:</h2>
    <p>After installation, you should be able to:</p>
    <ul>
        <li>Send emails through the CRM</li>
        <li>Configure SMTP settings</li>
        <li>Receive email delivery confirmations</li>
    </ul>";
    
    echo "<div class='info'>
        <h3>💡 Quick Test</h3>
        <p>After installing PHPMailer, refresh this page to verify the installation was successful.</p>
        <button onclick='location.reload()' class='btn'>Refresh Page</button>
    </div>";
}

echo "<h2>Next Steps:</h2>
<ol>
    <li><a href='crm/settings.php'>Configure Email Domains</a> - Add your SMTP settings</li>
    <li><a href='crm/templates.php'>Create Email Templates</a> - Design your email templates</li>
    <li><a href='crm/customers.php'>Manage Customers</a> - Add customer contacts</li>
    <li><a href='crm/emails.php'>Send Emails</a> - Start your email campaigns</li>
</ol>";

echo "</body></html>";
?>
