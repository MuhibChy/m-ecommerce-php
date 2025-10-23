<?php
require_once __DIR__ . '/config/database.php';

try {
    $db = getDB();
    
    echo "Updating currency from USD to Taka...\n\n";
    
    // Update currency symbol in company settings
    $stmt = $db->prepare("UPDATE company_settings SET setting_value = ? WHERE setting_key = 'currency_symbol'");
    $stmt->execute(['৳']);
    echo "✓ Updated currency symbol to ৳ (Taka)\n";
    
    // Add currency name setting
    $stmt = $db->prepare("INSERT INTO company_settings (setting_key, setting_value, setting_type, description) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $stmt->execute(['currency_name', 'Taka', 'text', 'Currency name']);
    echo "✓ Added currency name: Taka\n";
    
    // Add currency code setting
    $stmt = $db->prepare("INSERT INTO company_settings (setting_key, setting_value, setting_type, description) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $stmt->execute(['currency_code', 'BDT', 'text', 'Currency code (ISO 4217)']);
    echo "✓ Added currency code: BDT\n";
    
    echo "\n✅ Currency successfully updated to Taka (৳)!\n";
    echo "\nNext steps:\n";
    echo "1. Frontend currency display updated\n";
    echo "2. Mobile app currency updated\n";
    echo "3. Receipt templates updated\n";
    echo "4. All price displays now show in Taka\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
