<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/business_functions.php';

$auth = getAuth();
$auth->requireAdmin();

$saleId = $_GET['id'] ?? null;
if (!$saleId) {
    header('Location: sales-receipt.php');
    exit;
}

$salesManager = new SalesManager();
$sale = $salesManager->getSaleWithItems($saleId);

if (!$sale) {
    header('Location: sales-receipt.php?error=Sale not found');
    exit;
}

// Get company settings
$db = getDB();
$stmt = $db->prepare("SELECT setting_key, setting_value FROM company_settings");
$stmt->execute();
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$companyName = $settings['company_name'] ?? 'M-Ecommerce Store';
$companyAddress = $settings['company_address'] ?? '123 Business Street\nCity, State 12345';
$companyPhone = $settings['company_phone'] ?? '+1 (555) 123-4567';
$companyEmail = $settings['company_email'] ?? 'info@m-ecommerce.com';
$receiptFooter = $settings['receipt_footer'] ?? 'Thank you for your business!';

// Simple PDF generation using HTML to PDF conversion
// For production, consider using libraries like TCPDF or mPDF

$receiptNumber = $sale['sale_number'] ?? 'R' . $sale['id'];

// Set headers for HTML page optimized for printing as PDF
header('Content-Type: text/html; charset=utf-8');

// Add JavaScript to auto-open print dialog
$autoPrint = isset($_GET['print']) ? $_GET['print'] === 'true' : false;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Receipt <?= htmlspecialchars($receiptNumber) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .company-info {
            color: #666;
            margin-bottom: 5px;
        }
        .receipt-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .info-section {
            width: 48%;
        }
        .info-title {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 10px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .info-label {
            color: #666;
        }
        .info-value {
            font-weight: bold;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .items-table th,
        .items-table td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }
        .items-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .items-table .qty-col,
        .items-table .price-col {
            text-align: right;
            width: 80px;
        }
        .totals {
            float: right;
            width: 300px;
            margin-bottom: 30px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            padding: 5px 0;
        }
        .total-row.final {
            border-top: 2px solid #333;
            font-weight: bold;
            font-size: 16px;
            margin-top: 10px;
            padding-top: 10px;
        }
        .footer {
            text-align: center;
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #ccc;
        }
        .footer-message {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .footer-terms {
            font-size: 10px;
            color: #666;
            margin-top: 10px;
        }
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
        
        /* Print-specific styles */
        @media print {
            body {
                margin: 0;
                padding: 20px;
            }
            .no-print {
                display: none;
            }
        }
        
        /* Print button styles */
        .print-controls {
            position: fixed;
            top: 10px;
            right: 10px;
            background: white;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .print-btn {
            background: #007cba;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 3px;
            cursor: pointer;
            margin-right: 5px;
        }
        
        .print-btn:hover {
            background: #005a87;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="company-name"><?= htmlspecialchars($companyName) ?></div>
        <div class="company-info"><?= nl2br(htmlspecialchars($companyAddress)) ?></div>
        <div class="company-info">
            Phone: <?= htmlspecialchars($companyPhone) ?> | Email: <?= htmlspecialchars($companyEmail) ?>
        </div>
    </div>

    <!-- Receipt Information -->
    <div class="receipt-info">
        <div class="info-section">
            <div class="info-title">Receipt Information</div>
            <div class="info-row">
                <span class="info-label">Receipt Number:</span>
                <span class="info-value"><?= htmlspecialchars($receiptNumber) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Date:</span>
                <span class="info-value"><?= date('F j, Y', strtotime($sale['sale_date'] ?? $sale['created_at'])) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Time:</span>
                <span class="info-value"><?= date('g:i A', strtotime($sale['created_at'])) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Payment Method:</span>
                <span class="info-value"><?= ucfirst(str_replace('_', ' ', $sale['payment_method'] ?? 'Cash')) ?></span>
            </div>
        </div>

        <div class="info-section">
            <div class="info-title">Customer Information</div>
            <div class="info-row">
                <span class="info-label">Name:</span>
                <span class="info-value"><?= htmlspecialchars($sale['customer_name'] ?? 'Walk-in Customer') ?></span>
            </div>
            <?php if (!empty($sale['customer_email'])): ?>
            <div class="info-row">
                <span class="info-label">Email:</span>
                <span class="info-value"><?= htmlspecialchars($sale['customer_email']) ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($sale['customer_phone'])): ?>
            <div class="info-row">
                <span class="info-label">Phone:</span>
                <span class="info-value"><?= htmlspecialchars($sale['customer_phone']) ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($sale['created_by_name'])): ?>
            <div class="info-row">
                <span class="info-label">Served by:</span>
                <span class="info-value"><?= htmlspecialchars($sale['created_by_name']) ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th>Item Description</th>
                <th class="qty-col">Qty</th>
                <th class="price-col">Unit Price</th>
                <th class="price-col">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($sale['items'])): ?>
                <?php foreach ($sale['items'] as $item): ?>
                <tr>
                    <td>
                        <?= htmlspecialchars($item['product_name']) ?>
                        <?php if (!empty($item['product_sku'])): ?>
                        <br><small>SKU: <?= htmlspecialchars($item['product_sku']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td class="qty-col"><?= $item['quantity'] ?></td>
                    <td class="price-col"><?= formatCurrency($item['unit_price']) ?></td>
                    <td class="price-col"><?= formatCurrency($item['total_price']) ?></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" style="text-align: center; padding: 20px; color: #666;">No items found</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Totals -->
    <div class="totals">
        <div class="total-row">
            <span>Subtotal:</span>
            <span><?= formatCurrency($sale['subtotal'] ?? 0) ?></span>
        </div>
        <?php if (!empty($sale['discount_amount']) && $sale['discount_amount'] > 0): ?>
        <div class="total-row" style="color: #d32f2f;">
            <span>Discount:</span>
            <span>-<?= formatCurrency($sale['discount_amount']) ?></span>
        </div>
        <?php endif; ?>
        <div class="total-row">
            <span>VAT (15%):</span>
            <span><?= formatCurrency($sale['tax_amount'] ?? 0) ?></span>
        </div>
        <div class="total-row final">
            <span>TOTAL:</span>
            <span><?= formatCurrency($sale['total_amount'] ?? 0) ?></span>
        </div>
    </div>

    <div class="clearfix"></div>

    <!-- Notes -->
    <?php if (!empty($sale['notes'])): ?>
    <div style="margin-top: 30px; padding: 15px; background-color: #f9f9f9; border: 1px solid #ddd;">
        <strong>Notes:</strong><br>
        <?= nl2br(htmlspecialchars($sale['notes'])) ?>
    </div>
    <?php endif; ?>

    <!-- Footer -->
    <div class="footer">
        <div class="footer-message"><?= htmlspecialchars($receiptFooter) ?></div>
        <div>This receipt serves as proof of purchase. Please retain for your records.</div>
        <?php if (!empty($settings['receipt_terms'])): ?>
        <div class="footer-terms">
            <?= htmlspecialchars($settings['receipt_terms']) ?>
        </div>
        <?php endif; ?>
        <div style="margin-top: 20px; font-size: 10px; color: #999;">
            Generated on <?= date('F j, Y \a\t g:i A') ?>
        </div>
    </div>

    <!-- Print Controls -->
    <div class="print-controls no-print">
        <button class="print-btn" onclick="window.print()">🖨️ Print Receipt</button>
        <button class="print-btn" onclick="window.close()">❌ Close</button>
    </div>

    <script>
        <?php if ($autoPrint): ?>
        // Auto-print when page loads
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
        <?php endif; ?>
        
        // Print function
        function printReceipt() {
            window.print();
        }
        
        // Handle keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
        });
    </script>
</body>
</html>

<?php
// Note: For production use, implement proper PDF generation using libraries like:
// - TCPDF: https://tcpdf.org/
// - mPDF: https://mpdf.github.io/
// - DomPDF: https://github.com/dompdf/dompdf
// 
// This current implementation creates an HTML page that can be printed to PDF
// by the browser, which works for basic needs but may not be suitable for
// all production environments.
?>
