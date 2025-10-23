-- Enhanced E-commerce Database Setup with Business Management Features
-- Run this SQL to add new tables for sales, purchases, stock, income/expenses, and customer support

USE m_ecommerce;

-- Suppliers table for purchase management
CREATE TABLE suppliers (
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
CREATE TABLE purchase_orders (
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
CREATE TABLE purchase_order_items (
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
CREATE TABLE inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    quantity_in_stock INT NOT NULL DEFAULT 0,
    reserved_quantity INT NOT NULL DEFAULT 0, -- For pending orders
    reorder_level INT DEFAULT 10,
    reorder_quantity INT DEFAULT 50,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product_inventory (product_id)
);

-- Stock movements table for tracking all inventory changes
CREATE TABLE stock_movements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    movement_type ENUM('in', 'out', 'adjustment') NOT NULL,
    quantity INT NOT NULL,
    reference_type ENUM('purchase', 'sale', 'adjustment', 'return') NOT NULL,
    reference_id INT, -- ID of the related order/purchase
    notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
);

-- Sales table (enhanced orders table for better sales tracking)
CREATE TABLE sales (
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

-- Income and Expenses categories
CREATE TABLE financial_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type ENUM('income', 'expense') NOT NULL,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Income and Expenses transactions
CREATE TABLE financial_transactions (
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

-- Customer support tickets
CREATE TABLE support_tickets (
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

-- Support ticket replies/messages
CREATE TABLE support_ticket_replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    is_internal TINYINT(1) DEFAULT 0, -- Internal notes for staff
    attachment VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE RESTRICT
);

-- Insert default suppliers
INSERT INTO suppliers (name, contact_person, email, phone, address) VALUES
('Tech Distributors Ltd', 'John Smith', 'john@techdist.com', '+880-1234567890', 'Dhaka, Bangladesh'),
('Global Electronics', 'Sarah Johnson', 'sarah@globalelec.com', '+880-1234567891', 'Chittagong, Bangladesh'),
('Component World', 'Mike Chen', 'mike@compworld.com', '+880-1234567892', 'Sylhet, Bangladesh');

-- Insert default financial categories
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
('Other Expenses', 'expense', 'Miscellaneous expenses');

-- Initialize inventory for existing products
INSERT INTO inventory (product_id, quantity_in_stock, reorder_level, reorder_quantity)
SELECT id, 100, 10, 50 FROM products;

-- Add stock movements for initial inventory
INSERT INTO stock_movements (product_id, movement_type, quantity, reference_type, notes, created_by)
SELECT id, 'in', 100, 'adjustment', 'Initial stock setup', 1 FROM products;

-- Update products table to add cost price for better profit tracking
ALTER TABLE products ADD COLUMN cost_price DECIMAL(10,2) DEFAULT 0 AFTER price;

-- Update existing products with sample cost prices (70% of selling price)
UPDATE products SET cost_price = price * 0.7;

-- Add indexes for better performance
CREATE INDEX idx_purchase_orders_supplier ON purchase_orders(supplier_id);
CREATE INDEX idx_purchase_orders_status ON purchase_orders(status);
CREATE INDEX idx_purchase_orders_date ON purchase_orders(order_date);
CREATE INDEX idx_inventory_product ON inventory(product_id);
CREATE INDEX idx_stock_movements_product ON stock_movements(product_id);
CREATE INDEX idx_stock_movements_date ON stock_movements(created_at);
CREATE INDEX idx_sales_date ON sales(sale_date);
CREATE INDEX idx_sales_customer ON sales(customer_id);
CREATE INDEX idx_financial_transactions_date ON financial_transactions(transaction_date);
CREATE INDEX idx_financial_transactions_type ON financial_transactions(type);
CREATE INDEX idx_support_tickets_status ON support_tickets(status);
CREATE INDEX idx_support_tickets_customer ON support_tickets(customer_id);
