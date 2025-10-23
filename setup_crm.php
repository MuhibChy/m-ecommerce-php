<?php
/**
 * CRM Setup Script
 * M-EcommerceCRM - Automated Setup
 */

require_once 'config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>M-EcommerceCRM Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; } .error { color: red; } .info { color: blue; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>M-EcommerceCRM Setup</h1>";

try {
    $db = getDB();
    
    echo "<h2>Setting up CRM Database...</h2>";
    
    // Read and execute CRM setup SQL
    $sql = file_get_contents('config/crm_setup.sql');
    
    if ($sql) {
        // Split SQL into individual statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (!empty($statement) && !preg_match('/^(USE|CREATE DATABASE)/', $statement)) {
                try {
                    $db->exec($statement);
                    echo "<p class='success'>✓ Executed: " . substr($statement, 0, 50) . "...</p>";
                } catch (PDOException $e) {
                    $errorCode = $e->getCode();
                    $errorMessage = $e->getMessage();
                    
                    // Handle expected errors gracefully
                    if (strpos($errorMessage, 'already exists') !== false) {
                        echo "<p class='info'>ℹ Already exists: " . substr($statement, 0, 50) . "...</p>";
                    } elseif (strpos($errorMessage, 'Duplicate entry') !== false) {
                        echo "<p class='info'>ℹ Data already exists: " . substr($statement, 0, 50) . "...</p>";
                    } elseif (strpos($errorMessage, 'Duplicate key name') !== false) {
                        echo "<p class='info'>ℹ Index already exists: " . substr($statement, 0, 50) . "...</p>";
                    } else {
                        echo "<p class='error'>✗ Error: " . $errorMessage . "</p>";
                    }
                }
            }
        }
        
        echo "<h2 class='success'>✓ CRM Database Setup Complete!</h2>";
        
        echo "<h2>Next Steps:</h2>
        <ol>
            <li>Install PHPMailer: <code>composer require phpmailer/phpmailer</code></li>
            <li>Configure email domains in <a href='crm/settings.php'>CRM Settings</a></li>
            <li>Import existing customers from users table</li>
            <li>Create email templates</li>
            <li>Start sending campaigns!</li>
        </ol>";
        
        echo "<h2>Access Your CRM:</h2>
        <p><a href='crm/index.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Open CRM Dashboard</a></p>";
        
    } else {
        echo "<p class='error'>Could not read CRM setup SQL file.</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>Setup failed: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>
