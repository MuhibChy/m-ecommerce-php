<?php
/**
 * Admin User Setup Script
 * This script checks if the database and admin user exist, and creates them if needed
 */

require_once __DIR__ . '/config/database.php';

echo "<h2>ModernShop Database & Admin Setup</h2>\n";

try {
    // First, try to connect to MySQL without specifying the database
    $host = 'localhost';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ Connected to MySQL server</p>\n";
    
    // Check if database exists
    $stmt = $pdo->query("SHOW DATABASES LIKE 'm_ecommerce'");
    $dbExists = $stmt->rowCount() > 0;
    
    if (!$dbExists) {
        echo "<p>❌ Database 'm_ecommerce' does not exist. Creating it...</p>\n";
        
        // Read and execute the setup.sql file
        $setupSQL = file_get_contents(__DIR__ . '/config/setup.sql');
        
        // Split the SQL into individual statements
        $statements = array_filter(array_map('trim', explode(';', $setupSQL)));
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
        
        echo "<p>✅ Database and tables created successfully!</p>\n";
        echo "<p>✅ Sample data inserted!</p>\n";
        echo "<p>✅ Admin user created: admin@modernshop.com (password: admin123)</p>\n";
    } else {
        echo "<p>✅ Database 'm_ecommerce' exists</p>\n";
        
        // Connect to the specific database
        $db = getDB();
        
        // Check if admin user exists
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND is_admin = 1");
        $stmt->execute(['admin@modernshop.com']);
        $adminUser = $stmt->fetch();
        
        if (!$adminUser) {
            echo "<p>❌ Admin user does not exist. Creating it...</p>\n";
            
            // Create admin user
            $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $avatar = "https://ui-avatars.com/api/?name=Admin%20User&background=667eea&color=fff";
            
            $stmt = $db->prepare("INSERT INTO users (name, email, password, avatar, is_admin) VALUES (?, ?, ?, ?, ?)");
            $result = $stmt->execute(['Admin User', 'admin@modernshop.com', $hashedPassword, $avatar, 1]);
            
            if ($result) {
                echo "<p>✅ Admin user created successfully!</p>\n";
                echo "<p><strong>Login Credentials:</strong></p>\n";
                echo "<p>Email: admin@modernshop.com</p>\n";
                echo "<p>Password: admin123</p>\n";
            } else {
                echo "<p>❌ Failed to create admin user</p>\n";
            }
        } else {
            echo "<p>✅ Admin user exists: " . $adminUser['name'] . " (" . $adminUser['email'] . ")</p>\n";
            echo "<p><strong>Login Credentials:</strong></p>\n";
            echo "<p>Email: admin@modernshop.com</p>\n";
            echo "<p>Password: admin123</p>\n";
        }
    }
    
    echo "<hr>\n";
    echo "<h3>Setup Complete!</h3>\n";
    echo "<p>You can now login with:</p>\n";
    echo "<ul>\n";
    echo "<li><strong>Email:</strong> admin@modernshop.com</li>\n";
    echo "<li><strong>Password:</strong> admin123</li>\n";
    echo "</ul>\n";
    echo "<p><a href='index.php'>Go to Homepage</a> | <a href='pages/login.php'>Go to Login</a></p>\n";
    
} catch (PDOException $e) {
    echo "<p>❌ Database Error: " . $e->getMessage() . "</p>\n";
    echo "<p>Please make sure:</p>\n";
    echo "<ul>\n";
    echo "<li>XAMPP is running</li>\n";
    echo "<li>MySQL service is started</li>\n";
    echo "<li>Database credentials are correct in config/database.php</li>\n";
    echo "</ul>\n";
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>\n";
}
?>
