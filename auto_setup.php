<?php
/**
 * Automated Database Setup Script
 * This script will automatically configure the entire database and create admin user
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>ModernShop Auto Setup</title><style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
.success { color: #28a745; } .error { color: #dc3545; } .info { color: #17a2b8; }
.step { margin: 10px 0; padding: 10px; border-left: 4px solid #007bff; background: #f8f9fa; }
</style></head><body>";

echo "<h1>🚀 ModernShop Automated Setup</h1>";

$steps = [];
$errors = [];

// Step 1: Test MySQL Connection
echo "<div class='step'><h3>Step 1: Testing MySQL Connection</h3>";
try {
    $host = 'localhost';
    $username = 'root';
    $password = '';
    
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p class='success'>✅ MySQL connection successful</p>";
    $steps[] = "MySQL connection established";
} catch (PDOException $e) {
    echo "<p class='error'>❌ MySQL connection failed: " . $e->getMessage() . "</p>";
    echo "<p class='info'>Please ensure XAMPP MySQL service is running</p>";
    $errors[] = "MySQL connection failed";
}
echo "</div>";

if (empty($errors)) {
    // Step 2: Create Database
    echo "<div class='step'><h3>Step 2: Creating Database</h3>";
    try {
        $pdo->exec("CREATE DATABASE IF NOT EXISTS m_ecommerce CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "<p class='success'>✅ Database 'm_ecommerce' created/verified</p>";
        $steps[] = "Database created";
        
        // Switch to the database
        $pdo->exec("USE m_ecommerce");
        echo "<p class='success'>✅ Connected to m_ecommerce database</p>";
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Database creation failed: " . $e->getMessage() . "</p>";
        $errors[] = "Database creation failed";
    }
    echo "</div>";
}

if (empty($errors)) {
    // Step 3: Create Tables
    echo "<div class='step'><h3>Step 3: Creating Tables</h3>";
    
    $tables = [
        'users' => "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            is_admin TINYINT(1) DEFAULT 0,
            avatar VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'categories' => "CREATE TABLE IF NOT EXISTS categories (
            id VARCHAR(50) PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            icon VARCHAR(50) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'products' => "CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(200) NOT NULL,
            category_id VARCHAR(50) NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            original_price DECIMAL(10,2) DEFAULT NULL,
            image VARCHAR(500) NOT NULL,
            rating DECIMAL(3,2) DEFAULT 4.5,
            reviews INT DEFAULT 0,
            in_stock TINYINT(1) DEFAULT 1,
            featured TINYINT(1) DEFAULT 0,
            description TEXT,
            specs JSON,
            tags JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'cart' => "CREATE TABLE IF NOT EXISTS cart (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_product (user_id, product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'favorites' => "CREATE TABLE IF NOT EXISTS favorites (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            product_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_favorite (user_id, product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'orders' => "CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            total_amount DECIMAL(10,2) NOT NULL,
            status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
            shipping_address TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        
        'order_items' => "CREATE TABLE IF NOT EXISTS order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];
    
    foreach ($tables as $tableName => $sql) {
        try {
            $pdo->exec($sql);
            echo "<p class='success'>✅ Table '$tableName' created/verified</p>";
        } catch (PDOException $e) {
            echo "<p class='error'>❌ Failed to create table '$tableName': " . $e->getMessage() . "</p>";
            $errors[] = "Table creation failed: $tableName";
        }
    }
    echo "</div>";
}

if (empty($errors)) {
    // Step 4: Insert Categories
    echo "<div class='step'><h3>Step 4: Setting up Categories</h3>";
    try {
        $categories = [
            ['laptops', 'Laptops', 'laptop'],
            ['desktops', 'Desktops', 'monitor'],
            ['components', 'Components', 'cpu'],
            ['peripherals', 'Peripherals', 'mouse'],
            ['networking', 'Networking', 'wifi'],
            ['accessories', 'Accessories', 'headphones']
        ];
        
        $stmt = $pdo->prepare("INSERT IGNORE INTO categories (id, name, icon) VALUES (?, ?, ?)");
        foreach ($categories as $category) {
            $stmt->execute($category);
        }
        echo "<p class='success'>✅ Categories inserted/verified</p>";
        $steps[] = "Categories setup";
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Categories setup failed: " . $e->getMessage() . "</p>";
        $errors[] = "Categories setup failed";
    }
    echo "</div>";
}

if (empty($errors)) {
    // Step 5: Create Admin User
    echo "<div class='step'><h3>Step 5: Creating Admin User</h3>";
    try {
        // First check if admin user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute(['admin@modernshop.com']);
        
        if ($stmt->fetch()) {
            echo "<p class='info'>ℹ️ Admin user already exists, updating password...</p>";
            // Update existing admin user
            $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ?, is_admin = 1 WHERE email = ?");
            $stmt->execute([$hashedPassword, 'admin@modernshop.com']);
            echo "<p class='success'>✅ Admin user password updated</p>";
        } else {
            // Create new admin user
            $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $avatar = "https://ui-avatars.com/api/?name=Admin%20User&background=667eea&color=fff";
            
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, avatar, is_admin) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute(['Admin User', 'admin@modernshop.com', $hashedPassword, $avatar, 1]);
            echo "<p class='success'>✅ Admin user created successfully</p>";
        }
        
        // Verify the admin user
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_admin = 1");
        $stmt->execute(['admin@modernshop.com']);
        $adminUser = $stmt->fetch();
        
        if ($adminUser) {
            echo "<p class='success'>✅ Admin user verified in database</p>";
            echo "<p class='info'>User ID: " . $adminUser['id'] . "</p>";
            echo "<p class='info'>Name: " . $adminUser['name'] . "</p>";
            echo "<p class='info'>Email: " . $adminUser['email'] . "</p>";
            echo "<p class='info'>Is Admin: " . ($adminUser['is_admin'] ? 'Yes' : 'No') . "</p>";
            
            // Test password verification
            if (password_verify('admin123', $adminUser['password'])) {
                echo "<p class='success'>✅ Password verification test passed</p>";
                $steps[] = "Admin user created and verified";
            } else {
                echo "<p class='error'>❌ Password verification test failed</p>";
                $errors[] = "Password verification failed";
            }
        } else {
            echo "<p class='error'>❌ Admin user verification failed</p>";
            $errors[] = "Admin user verification failed";
        }
        
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Admin user creation failed: " . $e->getMessage() . "</p>";
        $errors[] = "Admin user creation failed";
    }
    echo "</div>";
}

if (empty($errors)) {
    // Step 6: Insert Sample Products
    echo "<div class='step'><h3>Step 6: Adding Sample Products</h3>";
    try {
        $products = [
            ['MacBook Pro 16" M3 Max', 'laptops', 3499.00, 3999.00, 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=500&q=80', 4.9, 234, 1, 1, 'Powerful laptop with M3 Max chip, 36GB RAM, 1TB SSD', '["M3 Max Chip", "36GB RAM", "1TB SSD", "16\\" Liquid Retina XDR"]', '["new", "bestseller"]'],
            ['Dell XPS 15 OLED', 'laptops', 2299.00, 2599.00, 'https://images.unsplash.com/photo-1593642632823-8f785ba67e45?w=500&q=80', 4.7, 189, 1, 1, 'Premium Windows laptop with stunning OLED display', '["Intel i9-13900H", "32GB RAM", "1TB SSD", "15.6\\" OLED 4K"]', '["sale"]'],
            ['NVIDIA RTX 4080 Super', 'components', 1199.00, 1299.00, 'https://images.unsplash.com/photo-1591488320449-011701bb6704?w=500&q=80', 4.7, 423, 1, 1, 'Flagship graphics card for 4K gaming', '["16GB GDDR6X", "Ray Tracing", "DLSS 3.5", "320W TDP"]', '["new", "gaming"]']
        ];
        
        $stmt = $pdo->prepare("INSERT IGNORE INTO products (name, category_id, price, original_price, image, rating, reviews, in_stock, featured, description, specs, tags) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($products as $product) {
            $stmt->execute($product);
        }
        echo "<p class='success'>✅ Sample products added</p>";
        $steps[] = "Sample products added";
    } catch (PDOException $e) {
        echo "<p class='error'>❌ Sample products failed: " . $e->getMessage() . "</p>";
        // This is not critical, so don't add to errors
    }
    echo "</div>";
}

// Final Results
echo "<div class='step'><h3>🎉 Setup Complete!</h3>";

if (empty($errors)) {
    echo "<p class='success'>✅ All setup steps completed successfully!</p>";
    echo "<h4>Login Credentials:</h4>";
    echo "<ul>";
    echo "<li><strong>Email:</strong> admin@modernshop.com</li>";
    echo "<li><strong>Password:</strong> admin123</li>";
    echo "</ul>";
    
    echo "<h4>Quick Links:</h4>";
    echo "<ul>";
    echo "<li><a href='index.php' target='_blank'>🏠 Homepage</a></li>";
    echo "<li><a href='pages/login.php' target='_blank'>🔐 Login Page</a></li>";
    echo "<li><a href='admin/products.php' target='_blank'>⚙️ Admin Panel</a></li>";
    echo "</ul>";
    
    echo "<h4>Completed Steps:</h4>";
    echo "<ul>";
    foreach ($steps as $step) {
        echo "<li>✅ $step</li>";
    }
    echo "</ul>";
    
} else {
    echo "<p class='error'>❌ Setup encountered errors:</p>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>❌ $error</li>";
    }
    echo "</ul>";
    echo "<p class='info'>Please check XAMPP services and try again.</p>";
}

echo "</div>";
echo "</body></html>";
?>
