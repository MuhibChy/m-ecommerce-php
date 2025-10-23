-- Sales Receipt System Database Tables

-- Update sales table to include receipt fields
ALTER TABLE sales 
ADD COLUMN IF NOT EXISTS sale_number VARCHAR(50) UNIQUE,
ADD COLUMN IF NOT EXISTS customer_name VARCHAR(255),
ADD COLUMN IF NOT EXISTS customer_email VARCHAR(255),
ADD COLUMN IF NOT EXISTS customer_phone VARCHAR(20),
ADD COLUMN IF NOT EXISTS subtotal DECIMAL(10,2) DEFAULT 0,
ADD COLUMN IF NOT EXISTS tax_amount DECIMAL(10,2) DEFAULT 0,
ADD COLUMN IF NOT EXISTS discount_amount DECIMAL(10,2) DEFAULT 0,
ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50),
ADD COLUMN IF NOT EXISTS payment_status ENUM('pending', 'paid', 'refunded') DEFAULT 'pending',
ADD COLUMN IF NOT EXISTS notes TEXT,
ADD COLUMN IF NOT EXISTS receipt_printed BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS receipt_emailed BOOLEAN DEFAULT FALSE;

-- Create sale_items table for detailed receipt items
CREATE TABLE IF NOT EXISTS sale_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sale_id INT NOT NULL,
    product_id INT,
    product_name VARCHAR(255) NOT NULL,
    product_sku VARCHAR(100),
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

-- Create receipts table for receipt metadata
CREATE TABLE IF NOT EXISTS receipts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sale_id INT NOT NULL,
    receipt_number VARCHAR(50) UNIQUE NOT NULL,
    receipt_type ENUM('original', 'duplicate', 'refund') DEFAULT 'original',
    generated_by INT,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    pdf_path VARCHAR(500),
    email_sent BOOLEAN DEFAULT FALSE,
    email_sent_at TIMESTAMP NULL,
    print_count INT DEFAULT 0,
    last_printed_at TIMESTAMP NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (generated_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Create company_settings table for receipt customization
CREATE TABLE IF NOT EXISTS company_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('text', 'number', 'boolean', 'json') DEFAULT 'text',
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default company settings for receipts
INSERT INTO company_settings (setting_key, setting_value, setting_type, description) VALUES
('company_name', 'M-Ecommerce Store', 'text', 'Company name for receipts'),
('company_address', '123 Business Street\nCity, State 12345', 'text', 'Company address for receipts'),
('company_phone', '+1 (555) 123-4567', 'text', 'Company phone number'),
('company_email', 'info@m-ecommerce.com', 'text', 'Company email address'),
('company_website', 'www.m-ecommerce.com', 'text', 'Company website'),
('tax_rate', '0.10', 'number', 'Default tax rate (10%)'),
('currency_symbol', '$', 'text', 'Currency symbol'),
('receipt_footer', 'Thank you for your business!', 'text', 'Receipt footer message'),
('receipt_terms', 'All sales are final. No returns without receipt.', 'text', 'Receipt terms and conditions')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- Add indexes for better performance
CREATE INDEX idx_sales_sale_number ON sales(sale_number);
CREATE INDEX idx_sales_customer_email ON sales(customer_email);
CREATE INDEX idx_sales_sale_date ON sales(sale_date);
CREATE INDEX idx_sale_items_sale_id ON sale_items(sale_id);
CREATE INDEX idx_sale_items_product_id ON sale_items(product_id);
CREATE INDEX idx_receipts_receipt_number ON receipts(receipt_number);
CREATE INDEX idx_receipts_sale_id ON receipts(sale_id);
