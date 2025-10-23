<?php
/**
 * Database Setup and Verification Script
 * This script will check if the database is properly set up and create it if needed
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'm_ecommerce';

echo "<h1>ModernShop Database Setup</h1>";
echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px;'>";

try {
    // Step 1: Connect to MySQL server (without database)
    echo "<h2>Step 1: Connecting to MySQL Server...</h2>";
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color: green;'>✓ Successfully connected to MySQL server</p>";

    // Step 2: Create database if it doesn't exist
    echo "<h2>Step 2: Creating Database...</h2>";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p style='color: green;'>✓ Database '$database' created or already exists</p>";

    // Step 3: Connect to the specific database
    echo "<h2>Step 3: Connecting to Database...</h2>";
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<p style='color: green;'>✓ Connected to database '$database'</p>";

    // Step 4: Check existing tables
    echo "<h2>Step 4: Checking Existing Tables...</h2>";
    $stmt = $pdo->query("SHOW TABLES");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredTables = [
        'users', 'categories', 'products', 'cart', 'favorites', 'orders', 'order_items',
        'suppliers', 'purchase_orders', 'purchase_order_items', 'inventory', 'stock_movements',
        'sales', 'financial_categories', 'financial_transactions', 'support_tickets', 'support_ticket_replies'
    ];
    
    $missingTables = array_diff($requiredTables, $existingTables);
    
    if (empty($missingTables)) {
        echo "<p style='color: green;'>✓ All required tables exist</p>";
        echo "<p>Existing tables: " . implode(', ', $existingTables) . "</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Missing tables: " . implode(', ', $missingTables) . "</p>";
        echo "<p>Will create missing tables...</p>";
    }

    // Step 5: Create tables if needed
    if (!empty($missingTables) || empty($existingTables)) {
        echo "<h2>Step 5: Creating Database Tables...</h2>";
        
        // Original tables
        $originalSQL = "
        -- Users table
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            is_admin TINYINT(1) DEFAULT 0,
            avatar VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );

        -- Categories table
        CREATE TABLE IF NOT EXISTS categories (
            id VARCHAR(50) PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            icon VARCHAR(50) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );

        -- Products table
        CREATE TABLE IF NOT EXISTS products (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(200) NOT NULL,
            category_id VARCHAR(50) NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            cost_price DECIMAL(10,2) DEFAULT 0,
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
        );

        -- Shopping cart table
        CREATE TABLE IF NOT EXISTS cart (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_product (user_id, product_id)
        );

        -- Favorites table
        CREATE TABLE IF NOT EXISTS favorites (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            product_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            UNIQUE KEY unique_user_favorite (user_id, product_id)
        );

        -- Orders table
        CREATE TABLE IF NOT EXISTS orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            total_amount DECIMAL(10,2) NOT NULL,
            status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
            shipping_address TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        );

        -- Order items table
        CREATE TABLE IF NOT EXISTS order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        );
        ";

        // Enhanced business tables
        $enhancedSQL = "
        -- Suppliers table for purchase management
        CREATE TABLE IF NOT EXISTS suppliers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(200) NOT NULL,
            contact_person VARCHAR(100),
            email VARCHAR(100),
            phone VARCHAR(20),
            address TEXT,
            status ENUM('active', 'inactive') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        );

        -- Purchase orders table
        CREATE TABLE IF NOT EXISTS purchase_orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            supplier_id INT NOT NULL,
            order_number VARCHAR(50) UNIQUE NOT NULL,
            total_amount DECIMAL(12,2) NOT NULL,
            status ENUM('pending', 'ordered', 'received', 'cancelled') DEFAULT 'pending',
            order_date DATE NOT NULL,
            expected_delivery DATE,
            received_date DATE,
            notes TEXT,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE RESTRICT,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
        );

        -- Purchase order items table
        CREATE TABLE IF NOT EXISTS purchase_order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            purchase_order_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL,
            unit_cost DECIMAL(10,2) NOT NULL,
            total_cost DECIMAL(12,2) NOT NULL,
            received_quantity INT DEFAULT 0,
            FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
        );

        -- Stock/Inventory table
        CREATE TABLE IF NOT EXISTS inventory (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            quantity_in_stock INT NOT NULL DEFAULT 0,
            reserved_quantity INT NOT NULL DEFAULT 0,
            reorder_level INT DEFAULT 10,
            reorder_quantity INT DEFAULT 50,
            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
            UNIQUE KEY unique_product_inventory (product_id)
        );

        -- Stock movements table
        CREATE TABLE IF NOT EXISTS stock_movements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            movement_type ENUM('in', 'out', 'adjustment') NOT NULL,
            quantity INT NOT NULL,
            reference_type ENUM('purchase', 'sale', 'adjustment', 'return') NOT NULL,
            reference_id INT,
            notes TEXT,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
        );

        -- Sales table
        CREATE TABLE IF NOT EXISTS sales (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT NOT NULL,
            sale_number VARCHAR(50) UNIQUE NOT NULL,
            customer_id INT NOT NULL,
            subtotal DECIMAL(12,2) NOT NULL,
            tax_amount DECIMAL(10,2) DEFAULT 0,
            discount_amount DECIMAL(10,2) DEFAULT 0,
            total_amount DECIMAL(12,2) NOT NULL,
            payment_method ENUM('cash', 'card', 'bank_transfer', 'mobile_payment') DEFAULT 'cash',
            payment_status ENUM('pending', 'paid', 'partial', 'refunded') DEFAULT 'pending',
            sale_date DATE NOT NULL,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE RESTRICT,
            FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE RESTRICT,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
        );

        -- Financial categories
        CREATE TABLE IF NOT EXISTS financial_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            type ENUM('income', 'expense') NOT NULL,
            description TEXT,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );

        -- Financial transactions
        CREATE TABLE IF NOT EXISTS financial_transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_id INT NOT NULL,
            type ENUM('income', 'expense') NOT NULL,
            amount DECIMAL(12,2) NOT NULL,
            description TEXT NOT NULL,
            transaction_date DATE NOT NULL,
            payment_method ENUM('cash', 'bank', 'card', 'mobile_payment') DEFAULT 'cash',
            reference_number VARCHAR(100),
            receipt_file VARCHAR(255),
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES financial_categories(id) ON DELETE RESTRICT,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
        );

        -- Support tickets
        CREATE TABLE IF NOT EXISTS support_tickets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ticket_number VARCHAR(50) UNIQUE NOT NULL,
            customer_id INT NOT NULL,
            subject VARCHAR(200) NOT NULL,
            description TEXT NOT NULL,
            priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
            status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
            category ENUM('general', 'technical', 'billing', 'product', 'complaint') DEFAULT 'general',
            assigned_to INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            resolved_at TIMESTAMP NULL,
            FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE RESTRICT,
            FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
        );

        -- Support ticket replies
        CREATE TABLE IF NOT EXISTS support_ticket_replies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ticket_id INT NOT NULL,
            user_id INT NOT NULL,
            message TEXT NOT NULL,
            is_internal TINYINT(1) DEFAULT 0,
            attachment VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
        );
        ";

        // Execute SQL statements
        $statements = array_merge(
            array_filter(explode(';', $originalSQL)),
            array_filter(explode(';', $enhancedSQL))
        );

        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }

        echo "<p style='color: green;'>✓ All database tables created successfully</p>";
    }

    // Step 6: Insert default data
    echo "<h2>Step 6: Inserting Default Data...</h2>";
    
    // Check if categories exist
    $stmt = $pdo->query("SELECT COUNT(*) FROM categories");
    $categoryCount = $stmt->fetchColumn();
    
    if ($categoryCount == 0) {
        echo "<p>Inserting default categories...</p>";
        $pdo->exec("
            INSERT INTO categories (id, name, icon) VALUES
            ('laptops', 'Laptops', 'laptop'),
            ('desktops', 'Desktops', 'monitor'),
            ('components', 'Components', 'cpu'),
            ('peripherals', 'Peripherals', 'mouse'),
            ('networking', 'Networking', 'wifi'),
            ('accessories', 'Accessories', 'headphones')
        ");
        echo "<p style='color: green;'>✓ Default categories inserted</p>";
    }

    // Check if admin user exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute(['admin@modernshop.com']);
    $adminCount = $stmt->fetchColumn();
    
    if ($adminCount == 0) {
        echo "<p>Creating default admin user...</p>";
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password, is_admin) VALUES (?, ?, ?, ?)");
        $stmt->execute(['Admin User', 'admin@modernshop.com', $hashedPassword, 1]);
        echo "<p style='color: green;'>✓ Default admin user created (admin@modernshop.com / admin123)</p>";
    }

    // Check if financial categories exist
    $stmt = $pdo->query("SELECT COUNT(*) FROM financial_categories");
    $finCategoryCount = $stmt->fetchColumn();
    
    if ($finCategoryCount == 0) {
        echo "<p>Inserting default financial categories...</p>";
        $pdo->exec("
            INSERT INTO financial_categories (name, type, description) VALUES
            ('Product Sales', 'income', 'Revenue from product sales'),
            ('Service Revenue', 'income', 'Revenue from services'),
            ('Other Income', 'income', 'Miscellaneous income'),
            ('Cost of Goods Sold', 'expense', 'Direct costs of products sold'),
            ('Marketing & Advertising', 'expense', 'Marketing and promotional expenses'),
            ('Office Rent', 'expense', 'Monthly office rent'),
            ('Utilities', 'expense', 'Electricity, internet, phone bills'),
            ('Staff Salaries', 'expense', 'Employee salaries and benefits'),
            ('Office Supplies', 'expense', 'Stationery and office materials'),
            ('Transportation', 'expense', 'Delivery and travel expenses'),
            ('Maintenance', 'expense', 'Equipment and facility maintenance'),
            ('Other Expenses', 'expense', 'Miscellaneous expenses')
        ");
        echo "<p style='color: green;'>✓ Default financial categories inserted</p>";
    }

    // Check if suppliers exist
    $stmt = $pdo->query("SELECT COUNT(*) FROM suppliers");
    $supplierCount = $stmt->fetchColumn();
    
    if ($supplierCount == 0) {
        echo "<p>Inserting default suppliers...</p>";
        $pdo->exec("
            INSERT INTO suppliers (name, contact_person, email, phone, address) VALUES
            ('Tech Distributors Ltd', 'John Smith', 'john@techdist.com', '+880-1234567890', 'Dhaka, Bangladesh'),
            ('Global Electronics', 'Sarah Johnson', 'sarah@globalelec.com', '+880-1234567891', 'Chittagong, Bangladesh'),
            ('Component World', 'Mike Chen', 'mike@compworld.com', '+880-1234567892', 'Sylhet, Bangladesh')
        ");
        echo "<p style='color: green;'>✓ Default suppliers inserted</p>";
    }

    // Check if sample products exist
    $stmt = $pdo->query("SELECT COUNT(*) FROM products");
    $productCount = $stmt->fetchColumn();
    
    if ($productCount == 0) {
        echo "<p>Inserting sample products...</p>";
        $pdo->exec("
            INSERT INTO products (name, category_id, price, cost_price, original_price, image, rating, reviews, in_stock, featured, description, specs, tags) VALUES
            ('MacBook Pro 16\" M3 Max', 'laptops', 374900.00, 262430.00, 427900.00, 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=500&q=80', 4.9, 234, 1, 1, 'Powerful laptop with M3 Max chip, 36GB RAM, 1TB SSD', '[\"M3 Max Chip\", \"36GB RAM\", \"1TB SSD\", \"16\\\" Liquid Retina XDR\"]', '[\"new\", \"bestseller\"]'),
            ('Dell XPS 15 OLED', 'laptops', 246100.00, 172270.00, 278400.00, 'https://images.unsplash.com/photo-1593642632823-8f785ba67e45?w=500&q=80', 4.7, 189, 1, 1, 'Premium Windows laptop with stunning OLED display', '[\"Intel i9-13900H\", \"32GB RAM\", \"1TB SSD\", \"15.6\\\" OLED 4K\"]', '[\"sale\"]'),
            ('ASUS ROG Zephyrus G14', 'laptops', 203400.00, 142380.00, NULL, 'https://images.unsplash.com/photo-1603302576837-37561b2e2302?w=500&q=80', 4.8, 312, 1, 0, 'Gaming laptop with AMD Ryzen 9 and RTX 4070', '[\"AMD Ryzen 9 7940HS\", \"16GB RAM\", \"1TB SSD\", \"RTX 4070\"]', '[\"gaming\"]'),
            ('Mac Studio M2 Ultra', 'desktops', 535000.00, 374500.00, NULL, 'https://images.unsplash.com/photo-1587831990711-23ca6441447b?w=500&q=80', 4.9, 98, 1, 1, 'Ultimate desktop performance for professionals', '[\"M2 Ultra Chip\", \"64GB RAM\", \"2TB SSD\", \"Compact Design\"]', '[\"new\", \"professional\"]'),
            ('NVIDIA RTX 4080 Super', 'components', 128400.00, 89880.00, 139200.00, 'https://images.unsplash.com/photo-1591488320449-011701bb6704?w=500&q=80', 4.7, 423, 1, 1, 'Flagship graphics card for 4K gaming', '[\"16GB GDDR6X\", \"Ray Tracing\", \"DLSS 3.5\", \"320W TDP\"]', '[\"new\", \"gaming\"]'),
            ('Logitech MX Master 3S', 'peripherals', 10600.00, 7420.00, NULL, 'https://images.unsplash.com/photo-1527864550417-7fd91fc51a46?w=500&q=80', 4.8, 1234, 1, 1, 'Premium wireless mouse for productivity', '[\"8K DPI\", \"Quiet Clicks\", \"USB-C\", \"Multi-Device\"]', '[\"bestseller\"]')
        ");
        echo "<p style='color: green;'>✓ Sample products inserted</p>";
        
        // Initialize inventory for products
        echo "<p>Initializing inventory...</p>";
        $pdo->exec("
            INSERT INTO inventory (product_id, quantity_in_stock, reorder_level, reorder_quantity)
            SELECT id, 100, 10, 50 FROM products
        ");
        
        $pdo->exec("
            INSERT INTO stock_movements (product_id, movement_type, quantity, reference_type, notes, created_by)
            SELECT id, 'in', 100, 'adjustment', 'Initial stock setup', 1 FROM products
        ");
        echo "<p style='color: green;'>✓ Initial inventory setup completed</p>";
    }

    // Step 7: Create indexes for performance
    echo "<h2>Step 7: Creating Database Indexes...</h2>";
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_purchase_orders_supplier ON purchase_orders(supplier_id)",
        "CREATE INDEX IF NOT EXISTS idx_purchase_orders_status ON purchase_orders(status)",
        "CREATE INDEX IF NOT EXISTS idx_purchase_orders_date ON purchase_orders(order_date)",
        "CREATE INDEX IF NOT EXISTS idx_inventory_product ON inventory(product_id)",
        "CREATE INDEX IF NOT EXISTS idx_stock_movements_product ON stock_movements(product_id)",
        "CREATE INDEX IF NOT EXISTS idx_stock_movements_date ON stock_movements(created_at)",
        "CREATE INDEX IF NOT EXISTS idx_sales_date ON sales(sale_date)",
        "CREATE INDEX IF NOT EXISTS idx_sales_customer ON sales(customer_id)",
        "CREATE INDEX IF NOT EXISTS idx_financial_transactions_date ON financial_transactions(transaction_date)",
        "CREATE INDEX IF NOT EXISTS idx_financial_transactions_type ON financial_transactions(type)",
        "CREATE INDEX IF NOT EXISTS idx_support_tickets_status ON support_tickets(status)",
        "CREATE INDEX IF NOT EXISTS idx_support_tickets_customer ON support_tickets(customer_id)"
    ];

    foreach ($indexes as $index) {
        try {
            $pdo->exec($index);
        } catch (PDOException $e) {
            // Index might already exist, continue
        }
    }
    echo "<p style='color: green;'>✓ Database indexes created</p>";

    // Final verification
    echo "<h2>Final Verification</h2>";
    $stmt = $pdo->query("SHOW TABLES");
    $finalTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<p style='color: green;'>✓ Database setup completed successfully!</p>";
    echo "<p><strong>Total tables created:</strong> " . count($finalTables) . "</p>";
    echo "<p><strong>Tables:</strong> " . implode(', ', $finalTables) . "</p>";

    echo "<h2>Next Steps</h2>";
    echo "<ol>";
    echo "<li><strong>Access Admin Panel:</strong> <a href='admin/dashboard.php' target='_blank'>admin/dashboard.php</a></li>";
    echo "<li><strong>Login Credentials:</strong> admin@modernshop.com / admin123</li>";
    echo "<li><strong>Main Website:</strong> <a href='index.php' target='_blank'>index.php</a></li>";
    echo "<li><strong>Customer Support:</strong> <a href='pages/support.php' target='_blank'>pages/support.php</a></li>";
    echo "</ol>";

    echo "<div style='background: #f0f9ff; border: 1px solid #0ea5e9; border-radius: 8px; padding: 15px; margin: 20px 0;'>";
    echo "<h3 style='color: #0369a1; margin-top: 0;'>🎉 Setup Complete!</h3>";
    echo "<p>Your ModernShop e-commerce platform with complete business management features is now ready to use!</p>";
    echo "<p><strong>Features available:</strong></p>";
    echo "<ul>";
    echo "<li>Sales Management & Reporting</li>";
    echo "<li>Purchase Order Management</li>";
    echo "<li>Inventory & Stock Tracking</li>";
    echo "<li>Financial Management</li>";
    echo "<li>Customer Support System</li>";
    echo "<li>Comprehensive Admin Dashboard</li>";
    echo "</ul>";
    echo "</div>";

} catch (PDOException $e) {
    echo "<div style='background: #fef2f2; border: 1px solid #ef4444; border-radius: 8px; padding: 15px; margin: 20px 0;'>";
    echo "<h3 style='color: #dc2626; margin-top: 0;'>❌ Database Setup Error</h3>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Possible solutions:</strong></p>";
    echo "<ul>";
    echo "<li>Make sure XAMPP/MySQL is running</li>";
    echo "<li>Check database credentials in config/database.php</li>";
    echo "<li>Ensure MySQL user has proper permissions</li>";
    echo "<li>Try restarting XAMPP services</li>";
    echo "</ul>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='background: #fef2f2; border: 1px solid #ef4444; border-radius: 8px; padding: 15px; margin: 20px 0;'>";
    echo "<h3 style='color: #dc2626; margin-top: 0;'>❌ General Error</h3>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</div>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    line-height: 1.6;
    margin: 0;
    padding: 20px;
    background-color: #f5f5f5;
}
h1, h2, h3 {
    color: #333;
}
p {
    margin: 10px 0;
}
a {
    color: #0ea5e9;
    text-decoration: none;
}
a:hover {
    text-decoration: underline;
}
</style>
