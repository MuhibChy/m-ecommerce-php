<?php
/**
 * Login Test Script
 * This script tests the login functionality directly
 */

require_once __DIR__ . '/includes/auth.php';

echo "<!DOCTYPE html><html><head><title>Login Test</title><style>
body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
.success { color: #28a745; } .error { color: #dc3545; } .info { color: #17a2b8; }
.test { margin: 15px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
</style></head><body>";

echo "<h1>🔐 Login Functionality Test</h1>";

try {
    $auth = getAuth();
    
    echo "<div class='test'>";
    echo "<h3>Test 1: Database Connection</h3>";
    $db = getDB();
    if ($db) {
        echo "<p class='success'>✅ Database connection successful</p>";
    } else {
        echo "<p class='error'>❌ Database connection failed</p>";
    }
    echo "</div>";
    
    echo "<div class='test'>";
    echo "<h3>Test 2: Check Admin User in Database</h3>";
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute(['admin@modernshop.com']);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<p class='success'>✅ Admin user found in database</p>";
        echo "<p class='info'>ID: " . $user['id'] . "</p>";
        echo "<p class='info'>Name: " . $user['name'] . "</p>";
        echo "<p class='info'>Email: " . $user['email'] . "</p>";
        echo "<p class='info'>Is Admin: " . ($user['is_admin'] ? 'Yes' : 'No') . "</p>";
        echo "<p class='info'>Password Hash: " . substr($user['password'], 0, 20) . "...</p>";
    } else {
        echo "<p class='error'>❌ Admin user not found in database</p>";
    }
    echo "</div>";
    
    echo "<div class='test'>";
    echo "<h3>Test 3: Password Verification</h3>";
    if ($user) {
        $testPassword = 'admin123';
        if (password_verify($testPassword, $user['password'])) {
            echo "<p class='success'>✅ Password verification successful</p>";
        } else {
            echo "<p class='error'>❌ Password verification failed</p>";
            echo "<p class='info'>Testing password: $testPassword</p>";
        }
    } else {
        echo "<p class='error'>❌ Cannot test password - user not found</p>";
    }
    echo "</div>";
    
    echo "<div class='test'>";
    echo "<h3>Test 4: Auth Class Login Method</h3>";
    $result = $auth->login('admin@modernshop.com', 'admin123');
    
    if ($result['success']) {
        echo "<p class='success'>✅ Login method successful</p>";
        echo "<p class='info'>User logged in: " . $result['user']['name'] . "</p>";
        
        // Test session
        if ($auth->isLoggedIn()) {
            echo "<p class='success'>✅ Session created successfully</p>";
            if ($auth->isAdmin()) {
                echo "<p class='success'>✅ Admin privileges confirmed</p>";
            } else {
                echo "<p class='error'>❌ Admin privileges not detected</p>";
            }
        } else {
            echo "<p class='error'>❌ Session not created</p>";
        }
        
        // Logout for clean test
        $auth->logout();
        echo "<p class='info'>Logged out for clean test</p>";
        
    } else {
        echo "<p class='error'>❌ Login method failed: " . $result['error'] . "</p>";
    }
    echo "</div>";
    
    echo "<div class='test'>";
    echo "<h3>Test 5: Manual Login Form Test</h3>";
    echo "<form method='POST' action='pages/login.php'>";
    echo "<p><strong>Test the actual login form:</strong></p>";
    echo "<p>Email: <input type='email' name='email' value='admin@modernshop.com' style='width: 200px;'></p>";
    echo "<p>Password: <input type='password' name='password' value='admin123' style='width: 200px;'></p>";
    echo "<p><button type='submit' style='padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px;'>Test Login</button></p>";
    echo "</form>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='test'>";
    echo "<p class='error'>❌ Error during testing: " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<hr>";
echo "<h3>Quick Actions:</h3>";
echo "<p><a href='auto_setup.php'>🔧 Run Auto Setup</a> | <a href='pages/login.php'>🔐 Go to Login</a> | <a href='index.php'>🏠 Homepage</a></p>";

echo "</body></html>";
?>
