-- M-EcommerceCRM Database Setup
-- Complete CRM system with email functionality
-- Run this after the main setup.sql

USE m_ecommerce;

-- CRM Customers table (extends users table functionality)
CREATE TABLE crm_customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL, -- Link to existing users table if customer is also a user
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20) NULL,
    company VARCHAR(200) NULL,
    address TEXT NULL,
    city VARCHAR(100) NULL,
    state VARCHAR(100) NULL,
    country VARCHAR(100) NULL,
    postal_code VARCHAR(20) NULL,
    customer_type ENUM('lead', 'prospect', 'customer', 'vip') DEFAULT 'lead',
    status ENUM('active', 'inactive', 'blocked') DEFAULT 'active',
    source VARCHAR(100) NULL, -- How they found us
    notes TEXT NULL,
    last_contact_date DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_email (email),
    INDEX idx_customer_type (customer_type),
    INDEX idx_status (status)
);

-- Email domains configuration
CREATE TABLE email_domains (
    id INT AUTO_INCREMENT PRIMARY KEY,
    domain_name VARCHAR(255) NOT NULL UNIQUE,
    smtp_host VARCHAR(255) NOT NULL,
    smtp_port INT NOT NULL DEFAULT 587,
    smtp_username VARCHAR(255) NOT NULL,
    smtp_password VARCHAR(500) NOT NULL, -- Encrypted
    smtp_encryption ENUM('tls', 'ssl', 'none') DEFAULT 'tls',
    imap_host VARCHAR(255) NULL,
    imap_port INT DEFAULT 993,
    imap_username VARCHAR(255) NULL,
    imap_password VARCHAR(500) NULL, -- Encrypted
    imap_encryption ENUM('tls', 'ssl', 'none') DEFAULT 'tls',
    from_name VARCHAR(255) NOT NULL,
    from_email VARCHAR(255) NOT NULL,
    reply_to_email VARCHAR(255) NULL,
    is_active TINYINT(1) DEFAULT 1,
    is_default TINYINT(1) DEFAULT 0,
    daily_limit INT DEFAULT 500,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_domain (domain_name),
    INDEX idx_active (is_active)
);

-- Email templates
CREATE TABLE email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    subject VARCHAR(500) NOT NULL,
    body_html TEXT NOT NULL,
    body_text TEXT NULL,
    template_type ENUM('welcome', 'promotional', 'transactional', 'newsletter', 'custom') DEFAULT 'custom',
    variables JSON NULL, -- Available template variables
    is_active TINYINT(1) DEFAULT 1,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_type (template_type),
    INDEX idx_active (is_active)
);

-- Email campaigns
CREATE TABLE email_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    subject VARCHAR(500) NOT NULL,
    template_id INT NULL,
    email_domain_id INT NOT NULL,
    sender_name VARCHAR(255) NOT NULL,
    sender_email VARCHAR(255) NOT NULL,
    reply_to_email VARCHAR(255) NULL,
    body_html TEXT NOT NULL,
    body_text TEXT NULL,
    campaign_type ENUM('immediate', 'scheduled', 'recurring') DEFAULT 'immediate',
    status ENUM('draft', 'scheduled', 'sending', 'sent', 'paused', 'cancelled') DEFAULT 'draft',
    scheduled_at DATETIME NULL,
    sent_at DATETIME NULL,
    total_recipients INT DEFAULT 0,
    total_sent INT DEFAULT 0,
    total_delivered INT DEFAULT 0,
    total_opened INT DEFAULT 0,
    total_clicked INT DEFAULT 0,
    total_bounced INT DEFAULT 0,
    total_unsubscribed INT DEFAULT 0,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (template_id) REFERENCES email_templates(id) ON DELETE SET NULL,
    FOREIGN KEY (email_domain_id) REFERENCES email_domains(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_type (campaign_type),
    INDEX idx_scheduled (scheduled_at)
);

-- Email logs (sent emails)
CREATE TABLE email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NULL,
    customer_id INT NULL,
    email_domain_id INT NOT NULL,
    recipient_email VARCHAR(255) NOT NULL,
    recipient_name VARCHAR(255) NULL,
    subject VARCHAR(500) NOT NULL,
    body_html TEXT NULL,
    body_text TEXT NULL,
    status ENUM('pending', 'sent', 'delivered', 'bounced', 'failed', 'opened', 'clicked') DEFAULT 'pending',
    error_message TEXT NULL,
    tracking_id VARCHAR(100) UNIQUE NULL,
    sent_at DATETIME NULL,
    delivered_at DATETIME NULL,
    opened_at DATETIME NULL,
    clicked_at DATETIME NULL,
    bounced_at DATETIME NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES email_campaigns(id) ON DELETE SET NULL,
    FOREIGN KEY (customer_id) REFERENCES crm_customers(id) ON DELETE SET NULL,
    FOREIGN KEY (email_domain_id) REFERENCES email_domains(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_recipient (recipient_email),
    INDEX idx_tracking (tracking_id),
    INDEX idx_sent_at (sent_at)
);

-- Received emails (inbox)
CREATE TABLE received_emails (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email_domain_id INT NOT NULL,
    message_id VARCHAR(255) UNIQUE NOT NULL,
    from_email VARCHAR(255) NOT NULL,
    from_name VARCHAR(255) NULL,
    to_email VARCHAR(255) NOT NULL,
    cc_emails TEXT NULL,
    bcc_emails TEXT NULL,
    subject VARCHAR(500) NOT NULL,
    body_html TEXT NULL,
    body_text TEXT NULL,
    attachments JSON NULL,
    is_read TINYINT(1) DEFAULT 0,
    is_replied TINYINT(1) DEFAULT 0,
    is_forwarded TINYINT(1) DEFAULT 0,
    is_spam TINYINT(1) DEFAULT 0,
    priority ENUM('low', 'normal', 'high') DEFAULT 'normal',
    received_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (email_domain_id) REFERENCES email_domains(id) ON DELETE CASCADE,
    INDEX idx_from_email (from_email),
    INDEX idx_to_email (to_email),
    INDEX idx_read (is_read),
    INDEX idx_received_at (received_at)
);

-- Customer segments for targeted campaigns
CREATE TABLE customer_segments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    conditions JSON NOT NULL, -- Segment criteria
    customer_count INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_active (is_active)
);

-- Campaign recipients (many-to-many)
CREATE TABLE campaign_recipients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    campaign_id INT NOT NULL,
    customer_id INT NOT NULL,
    segment_id INT NULL,
    status ENUM('pending', 'sent', 'failed', 'bounced', 'unsubscribed') DEFAULT 'pending',
    sent_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (campaign_id) REFERENCES email_campaigns(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES crm_customers(id) ON DELETE CASCADE,
    FOREIGN KEY (segment_id) REFERENCES customer_segments(id) ON DELETE SET NULL,
    UNIQUE KEY unique_campaign_customer (campaign_id, customer_id),
    INDEX idx_status (status)
);

-- Email unsubscribes
CREATE TABLE email_unsubscribes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    customer_id INT NULL,
    campaign_id INT NULL,
    reason VARCHAR(500) NULL,
    unsubscribed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45) NULL,
    FOREIGN KEY (customer_id) REFERENCES crm_customers(id) ON DELETE SET NULL,
    FOREIGN KEY (campaign_id) REFERENCES email_campaigns(id) ON DELETE SET NULL,
    UNIQUE KEY unique_email (email),
    INDEX idx_email (email)
);

-- CRM activities/interactions log
CREATE TABLE crm_activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    activity_type ENUM('email_sent', 'email_received', 'call', 'meeting', 'note', 'purchase', 'support_ticket') NOT NULL,
    subject VARCHAR(255) NULL,
    description TEXT NULL,
    activity_date DATETIME NOT NULL,
    created_by INT NULL,
    metadata JSON NULL, -- Additional activity data
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES crm_customers(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_customer (customer_id),
    INDEX idx_type (activity_type),
    INDEX idx_date (activity_date)
);

-- Insert sample email domain
INSERT INTO email_domains (domain_name, smtp_host, smtp_port, smtp_username, smtp_password, from_name, from_email, is_default) VALUES
('example.com', 'smtp.gmail.com', 587, 'your-email@gmail.com', 'encrypted_password_here', 'M-EcommerceCRM', 'noreply@example.com', 1);

-- Insert sample email templates
INSERT INTO email_templates (name, subject, body_html, body_text, template_type, variables, created_by) VALUES
('Welcome Email', 'Welcome to {{company_name}}!', 
'<h1>Welcome {{customer_name}}!</h1><p>Thank you for joining {{company_name}}. We are excited to have you as our customer.</p>', 
'Welcome {{customer_name}}! Thank you for joining {{company_name}}. We are excited to have you as our customer.', 
'welcome', 
'["customer_name", "company_name", "email"]', 
1),
('Order Confirmation', 'Order Confirmation #{{order_id}}', 
'<h1>Order Confirmed!</h1><p>Hi {{customer_name}},</p><p>Your order #{{order_id}} has been confirmed and will be processed shortly.</p><p>Total: {{order_total}}</p>', 
'Hi {{customer_name}}, Your order #{{order_id}} has been confirmed and will be processed shortly. Total: {{order_total}}', 
'transactional', 
'["customer_name", "order_id", "order_total", "order_items"]', 
1);

-- Create indexes for better performance
CREATE INDEX idx_customers_email ON crm_customers(email);
CREATE INDEX idx_customers_type_status ON crm_customers(customer_type, status);
CREATE INDEX idx_email_logs_campaign_status ON email_logs(campaign_id, status);
CREATE INDEX idx_received_emails_domain_read ON received_emails(email_domain_id, is_read);
