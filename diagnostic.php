<?php
/**
 * Diagnostic Page - Check System Status
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>ModernShop System Diagnostic</h1>";
echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px;'>";

// Check PHP version
echo "<h2>1. PHP Environment</h2>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>Server:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "</p>";

// Check database connection
echo "<h2>2. Database Connection</h2>";
try {
    require_once __DIR__ . '/config/database.php';
    $db = getDB();
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Check if tables exist
    $stmt = $db->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p><strong>Tables found:</strong> " . count($tables) . "</p>";
    
    if (count($tables) > 0) {
        echo "<details><summary>View all tables</summary>";
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul></details>";
    } else {
        echo "<p style='color: red;'>⚠ No tables found. Please run the database setup.</p>";
        echo "<p><a href='setup_database.php' style='background: #0ea5e9; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Run Database Setup</a></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
    echo "<p><strong>Solutions:</strong></p>";
    echo "<ul>";
    echo "<li>Make sure XAMPP MySQL is running</li>";
    echo "<li>Check database credentials in config/database.php</li>";
    echo "<li>Run the database setup script</li>";
    echo "</ul>";
}

// Check required files
echo "<h2>3. Required Files</h2>";
$requiredFiles = [
    'includes/auth.php',
    'includes/functions.php',
    'includes/business_functions.php',
    'config/database.php',
    'admin/dashboard.php',
    'pages/support.php'
];

foreach ($requiredFiles as $file) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "<p style='color: green;'>✓ $file</p>";
    } else {
        echo "<p style='color: red;'>❌ $file (missing)</p>";
    }
}

// Check admin user
echo "<h2>4. Admin User Status</h2>";
try {
    if (isset($db)) {
        $stmt = $db->prepare("SELECT id, name, email, is_admin FROM users WHERE email = ?");
        $stmt->execute(['admin@modernshop.com']);
        $admin = $stmt->fetch();
        
        if ($admin) {
            echo "<p style='color: green;'>✓ Admin user exists</p>";
            echo "<p><strong>Name:</strong> " . htmlspecialchars($admin['name']) . "</p>";
            echo "<p><strong>Email:</strong> " . htmlspecialchars($admin['email']) . "</p>";
            echo "<p><strong>Admin Status:</strong> " . ($admin['is_admin'] ? 'Yes' : 'No') . "</p>";
        } else {
            echo "<p style='color: red;'>❌ Admin user not found</p>";
            echo "<p>Default admin should be: admin@modernshop.com / admin123</p>";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Could not check admin user: " . $e->getMessage() . "</p>";
}

// Check session
echo "<h2>5. Session Status</h2>";
session_start();
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>Logged In:</strong> " . (isset($_SESSION['user_id']) ? 'Yes (User ID: ' . $_SESSION['user_id'] . ')' : 'No') . "</p>";

// Quick links
echo "<h2>6. Quick Access Links</h2>";
echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin: 20px 0;'>";

$links = [
    'Main Website' => 'index.php',
    'Admin Dashboard' => 'admin/dashboard.php',
    'Admin Products' => 'admin/products.php',
    'Customer Support' => 'pages/support.php',
    'Login Page' => 'pages/login.php',
    'Database Setup' => 'setup_database.php'
];

foreach ($links as $name => $url) {
    echo "<a href='$url' style='background: #f3f4f6; border: 1px solid #d1d5db; padding: 15px; text-align: center; text-decoration: none; color: #374151; border-radius: 8px; display: block;'>$name</a>";
}

echo "</div>";

// System recommendations
echo "<h2>7. Recommendations</h2>";
echo "<div style='background: #f0f9ff; border: 1px solid #0ea5e9; border-radius: 8px; padding: 15px;'>";
echo "<h3 style='color: #0369a1; margin-top: 0;'>Getting Started</h3>";
echo "<ol>";
echo "<li><strong>First time setup:</strong> Run <a href='setup_database.php'>Database Setup</a> if you haven't already</li>";
echo "<li><strong>Login as admin:</strong> Use admin@modernshop.com / admin123</li>";
echo "<li><strong>Access admin panel:</strong> Go to <a href='admin/dashboard.php'>Admin Dashboard</a></li>";
echo "<li><strong>Test customer features:</strong> Visit <a href='pages/support.php'>Customer Support</a></li>";
echo "</ol>";
echo "</div>";

// Troubleshooting
echo "<h2>8. Common Issues & Solutions</h2>";
echo "<div style='background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; padding: 15px;'>";
echo "<h3 style='color: #92400e; margin-top: 0;'>If pages don't load:</h3>";
echo "<ul>";
echo "<li><strong>Database not set up:</strong> Run the database setup script first</li>";
echo "<li><strong>Access denied:</strong> Make sure you're logged in as admin</li>";
echo "<li><strong>File not found:</strong> Check that all files are uploaded correctly</li>";
echo "<li><strong>PHP errors:</strong> Check XAMPP error logs</li>";
echo "</ul>";
echo "</div>";

echo "</div>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    line-height: 1.6;
    margin: 0;
    padding: 20px;
    background-color: #f9fafb;
}
h1, h2, h3 {
    color: #111827;
}
details {
    margin: 10px 0;
}
summary {
    cursor: pointer;
    font-weight: bold;
    color: #374151;
}
a {
    color: #0ea5e9;
}
a:hover {
    color: #0284c7;
}
</style>
