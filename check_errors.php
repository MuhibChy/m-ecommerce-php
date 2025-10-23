<?php
/**
 * Error Checking Script
 * This script validates all PHP files for syntax errors and common issues
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Error Check</title><style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
.success { color: #28a745; } .error { color: #dc3545; } .warning { color: #ffc107; }
.file-check { margin: 10px 0; padding: 10px; border-left: 4px solid #007bff; background: #f8f9fa; }
</style></head><body>";

echo "<h1>🔍 ModernShop Error Check</h1>";

$errors = [];
$warnings = [];
$success = [];

// Files to check
$filesToCheck = [
    'config/database.php',
    'includes/auth.php', 
    'includes/functions.php',
    'pages/login.php',
    'pages/register.php',
    'pages/account.php',
    'pages/products.php',
    'pages/cart.php',
    'components/header.php',
    'admin/products.php',
    'index.php'
];

echo "<h2>📁 File Syntax Check</h2>";

foreach ($filesToCheck as $file) {
    echo "<div class='file-check'>";
    echo "<h3>Checking: $file</h3>";
    
    if (!file_exists($file)) {
        echo "<p class='error'>❌ File does not exist</p>";
        $errors[] = "Missing file: $file";
    } else {
        // Check PHP syntax
        $output = [];
        $return_var = 0;
        exec("php -l \"$file\" 2>&1", $output, $return_var);
        
        if ($return_var === 0) {
            echo "<p class='success'>✅ Syntax OK</p>";
            $success[] = "Syntax OK: $file";
            
            // Check for common issues
            $content = file_get_contents($file);
            
            // Check for hardcoded localhost URLs
            if (strpos($content, 'localhost/') !== false && !strpos($content, "HTTP_HOST") !== false) {
                echo "<p class='warning'>⚠️ Warning: Contains hardcoded localhost URL</p>";
                $warnings[] = "Hardcoded localhost in: $file";
            }
            
            // Check for hardcoded dollar signs (should use formatPrice)
            if (preg_match('/\$[0-9]/', $content) && !strpos($file, 'setup.sql')) {
                echo "<p class='warning'>⚠️ Warning: Contains hardcoded currency symbols</p>";
                $warnings[] = "Hardcoded currency in: $file";
            }
            
            // Check for missing includes
            if (strpos($content, 'formatPrice') !== false && strpos($content, 'functions.php') === false) {
                echo "<p class='warning'>⚠️ Warning: Uses formatPrice but doesn't include functions.php</p>";
                $warnings[] = "Missing functions.php include in: $file";
            }
            
        } else {
            echo "<p class='error'>❌ Syntax Error:</p>";
            echo "<pre>" . implode("\n", $output) . "</pre>";
            $errors[] = "Syntax error in: $file";
        }
    }
    echo "</div>";
}

echo "<h2>🔧 Configuration Check</h2>";

echo "<div class='file-check'>";
echo "<h3>Database Configuration</h3>";
try {
    require_once 'config/database.php';
    $db = getDB();
    if ($db) {
        echo "<p class='success'>✅ Database connection successful</p>";
        $success[] = "Database connection OK";
    } else {
        echo "<p class='error'>❌ Database connection failed</p>";
        $errors[] = "Database connection failed";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Database error: " . $e->getMessage() . "</p>";
    $errors[] = "Database configuration error";
}
echo "</div>";

echo "<div class='file-check'>";
echo "<h3>Functions Check</h3>";
try {
    require_once 'includes/functions.php';
    
    // Test formatPrice function
    $testPrice = formatPrice(1000);
    if (strpos($testPrice, '৳') !== false) {
        echo "<p class='success'>✅ Currency format correct (Taka)</p>";
        $success[] = "Currency format OK";
    } else {
        echo "<p class='error'>❌ Currency format incorrect</p>";
        $errors[] = "Currency format error";
    }
    
    // Test getBaseUrl function
    $baseUrl = getBaseUrl();
    echo "<p class='success'>✅ Base URL: " . ($baseUrl ?: 'Root directory') . "</p>";
    $success[] = "Base URL function OK";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Functions error: " . $e->getMessage() . "</p>";
    $errors[] = "Functions configuration error";
}
echo "</div>";

echo "<h2>📊 Summary</h2>";

echo "<div class='file-check'>";
echo "<h3>Results</h3>";
echo "<p><strong>✅ Success:</strong> " . count($success) . " items</p>";
echo "<p><strong>⚠️ Warnings:</strong> " . count($warnings) . " items</p>";
echo "<p><strong>❌ Errors:</strong> " . count($errors) . " items</p>";

if (!empty($errors)) {
    echo "<h4>Errors to Fix:</h4>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li class='error'>$error</li>";
    }
    echo "</ul>";
}

if (!empty($warnings)) {
    echo "<h4>Warnings to Review:</h4>";
    echo "<ul>";
    foreach ($warnings as $warning) {
        echo "<li class='warning'>$warning</li>";
    }
    echo "</ul>";
}

if (empty($errors) && empty($warnings)) {
    echo "<p class='success'>🎉 All checks passed! Your site is ready for deployment.</p>";
} elseif (empty($errors)) {
    echo "<p class='warning'>⚠️ No critical errors, but please review warnings before deployment.</p>";
} else {
    echo "<p class='error'>❌ Please fix errors before deployment.</p>";
}
echo "</div>";

echo "<hr>";
echo "<p><a href='index.php'>🏠 Go to Homepage</a> | <a href='auto_setup.php'>🔧 Run Setup</a></p>";

echo "</body></html>";
?>
