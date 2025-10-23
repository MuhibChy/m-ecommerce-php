<?php
require_once __DIR__ . '/config/database.php';

try {
    $db = getDB();
    
    echo "Setting up sales receipt tables...\n\n";
    
    // Read and execute SQL file
    $sql = file_get_contents(__DIR__ . '/database/sales_receipt_tables.sql');
    
    // Split by semicolons and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            try {
                $db->exec($statement);
                echo "✓ Executed: " . substr($statement, 0, 50) . "...\n";
            } catch (PDOException $e) {
                echo "⚠ Warning: " . $e->getMessage() . "\n";
                echo "  Statement: " . substr($statement, 0, 50) . "...\n";
            }
        }
    }
    
    echo "\n✅ Sales receipt system setup completed!\n";
    echo "\nYou can now:\n";
    echo "1. Visit /admin/sales-receipt.php to create sales receipts\n";
    echo "2. Generate PDF receipts\n";
    echo "3. Email receipts to customers\n";
    echo "4. Automatic stock adjustment on sales\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
