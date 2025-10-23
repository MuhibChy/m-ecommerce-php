<?php
require_once __DIR__ . '/config/database.php';

try {
    $db = getDB();
    
    echo "Setting up currency configuration...\n\n";
    
    // Create company_settings table if it doesn't exist
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS company_settings (
        id INT PRIMARY KEY AUTO_INCREMENT,
        setting_key VARCHAR(100) UNIQUE NOT NULL,
        setting_value TEXT,
        setting_type ENUM('text', 'number', 'boolean', 'json') DEFAULT 'text',
        description TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $db->exec($createTableSQL);
    echo "✓ Created/verified company_settings table\n";
    
    // Insert currency settings
    $settings = [
        ['currency_symbol', '৳', 'text', 'Currency symbol for Taka'],
        ['currency_name', 'Taka', 'text', 'Currency name'],
        ['currency_code', 'BDT', 'text', 'Currency code (ISO 4217)'],
        ['company_name', 'M-Ecommerce Store', 'text', 'Company name'],
        ['company_address', '123 Business Street\nDhaka, Bangladesh', 'text', 'Company address'],
        ['company_phone', '+880 1234-567890', 'text', 'Company phone number'],
        ['company_email', 'info@m-ecommerce.com', 'text', 'Company email address'],
        ['tax_rate', '0.15', 'number', 'Default tax rate (15% VAT for Bangladesh)'],
        ['receipt_footer', 'ধন্যবাদ! Thank you for your business!', 'text', 'Receipt footer message'],
        ['receipt_terms', 'All sales are final. No returns without receipt.', 'text', 'Receipt terms and conditions']
    ];
    
    foreach ($settings as $setting) {
        $stmt = $db->prepare("INSERT INTO company_settings (setting_key, setting_value, setting_type, description) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute($setting);
        echo "✓ Set {$setting[0]}: {$setting[1]}\n";
    }
    
    echo "\n✅ Currency successfully configured for Taka (৳)!\n";
    echo "\nSettings applied:\n";
    echo "- Currency Symbol: ৳ (Taka)\n";
    echo "- Currency Code: BDT\n";
    echo "- Tax Rate: 15% (Bangladesh VAT)\n";
    echo "- Address updated for Bangladesh\n";
    echo "- Phone format updated for Bangladesh\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
