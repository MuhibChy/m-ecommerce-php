-- Modern E-commerce Database Setup
-- Run this SQL to create the database and tables

CREATE DATABASE IF NOT EXISTS m_ecommerce;
USE m_ecommerce;

-- Users table
CREATE TABLE users (
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
CREATE TABLE categories (
    id VARCHAR(50) PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE products (
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
);

-- Shopping cart table
CREATE TABLE cart (
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
CREATE TABLE favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_favorite (user_id, product_id)
);

-- Orders table
CREATE TABLE orders (
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
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Insert default categories
INSERT INTO categories (id, name, icon) VALUES
('laptops', 'Laptops', 'laptop'),
('desktops', 'Desktops', 'monitor'),
('components', 'Components', 'cpu'),
('peripherals', 'Peripherals', 'mouse'),
('networking', 'Networking', 'wifi'),
('accessories', 'Accessories', 'headphones');

-- Insert sample admin user (password: admin123)
INSERT INTO users (name, email, password, is_admin) VALUES
('Admin User', 'admin@modernshop.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1);

-- Insert sample products (Prices in Bangladeshi Taka)
INSERT INTO products (name, category_id, price, original_price, image, rating, reviews, in_stock, featured, description, specs, tags) VALUES
('MacBook Pro 16" M3 Max', 'laptops', 374900.00, 427900.00, 'https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=500&q=80', 4.9, 234, 1, 1, 'Powerful laptop with M3 Max chip, 36GB RAM, 1TB SSD', '["M3 Max Chip", "36GB RAM", "1TB SSD", "16\\" Liquid Retina XDR"]', '["new", "bestseller"]'),
('Dell XPS 15 OLED', 'laptops', 246100.00, 278400.00, 'https://images.unsplash.com/photo-1593642632823-8f785ba67e45?w=500&q=80', 4.7, 189, 1, 1, 'Premium Windows laptop with stunning OLED display', '["Intel i9-13900H", "32GB RAM", "1TB SSD", "15.6\\" OLED 4K"]', '["sale"]'),
('ASUS ROG Zephyrus G14', 'laptops', 203400.00, NULL, 'https://images.unsplash.com/photo-1603302576837-37561b2e2302?w=500&q=80', 4.8, 312, 1, 0, 'Gaming laptop with AMD Ryzen 9 and RTX 4070', '["AMD Ryzen 9 7940HS", "16GB RAM", "1TB SSD", "RTX 4070"]', '["gaming"]'),
('Mac Studio M2 Ultra', 'desktops', 535000.00, NULL, 'https://images.unsplash.com/photo-1587831990711-23ca6441447b?w=500&q=80', 4.9, 98, 1, 1, 'Ultimate desktop performance for professionals', '["M2 Ultra Chip", "64GB RAM", "2TB SSD", "Compact Design"]', '["new", "professional"]'),
('NVIDIA RTX 4080 Super', 'components', 128400.00, 139200.00, 'https://images.unsplash.com/photo-1591488320449-011701bb6704?w=500&q=80', 4.7, 423, 1, 1, 'Flagship graphics card for 4K gaming', '["16GB GDDR6X", "Ray Tracing", "DLSS 3.5", "320W TDP"]', '["new", "gaming"]'),
('Logitech MX Master 3S', 'peripherals', 10600.00, NULL, 'https://images.unsplash.com/photo-1527864550417-7fd91fc51a46?w=500&q=80', 4.8, 1234, 1, 1, 'Premium wireless mouse for productivity', '["8K DPI", "Quiet Clicks", "USB-C", "Multi-Device"]', '["bestseller"]');
