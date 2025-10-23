<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/business_functions.php';

header('Content-Type: application/json');

$auth = getAuth();
if (!$auth->isLoggedIn() || !$auth->isAdmin()) {
    jsonResponse(['success' => false, 'error' => 'Unauthorized']);
}

$input = json_decode(file_get_contents('php://input'), true);
$saleId = $input['sale_id'] ?? null;
$email = $input['email'] ?? null;

if (!$saleId || !$email) {
    jsonResponse(['success' => false, 'error' => 'Sale ID and email are required']);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['success' => false, 'error' => 'Invalid email address']);
}

$salesManager = new SalesManager();
$sale = $salesManager->getSaleWithItems($saleId);

if (!$sale) {
    jsonResponse(['success' => false, 'error' => 'Sale not found']);
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
$companyEmail = $settings['company_email'] ?? 'info@m-ecommerce.com';
$receiptNumber = $sale['sale_number'] ?? 'R' . $sale['id'];

// Create email content
$subject = "Receipt #{$receiptNumber} from {$companyName}";

$emailBody = generateReceiptEmailHTML($sale, $settings);

// Send email (using PHP's mail function - for production, use PHPMailer or similar)
$headers = [
    'From: ' . $companyName . ' <' . $companyEmail . '>',
    'Reply-To: ' . $companyEmail,
    'Content-Type: text/html; charset=UTF-8',
    'MIME-Version: 1.0'
];

$success = mail($email, $subject, $emailBody, implode("\r\n", $headers));

if ($success) {
    // Update receipt email status
    try {
        $stmt = $db->prepare("UPDATE sales SET receipt_emailed = 1 WHERE id = ?");
        $stmt->execute([$saleId]);
    } catch (Exception $e) {
        // Log error but don't fail the response
        error_log("Failed to update receipt email status: " . $e->getMessage());
    }
    
    jsonResponse(['success' => true, 'message' => 'Receipt sent successfully']);
} else {
    jsonResponse(['success' => false, 'error' => 'Failed to send email']);
}

function generateReceiptEmailHTML($sale, $settings) {
    $companyName = $settings['company_name'] ?? 'M-Ecommerce Store';
    $companyAddress = $settings['company_address'] ?? '123 Business Street\nCity, State 12345';
    $companyPhone = $settings['company_phone'] ?? '+1 (555) 123-4567';
    $companyEmail = $settings['company_email'] ?? 'info@m-ecommerce.com';
    $receiptFooter = $settings['receipt_footer'] ?? 'Thank you for your business!';
    $receiptNumber = $sale['sale_number'] ?? 'R' . $sale['id'];
    
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Receipt <?= htmlspecialchars($receiptNumber) ?></title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                max-width: 600px;
                margin: 0 auto;
                padding: 20px;
                background-color: #f9f9f9;
            }
            .email-container {
                background-color: white;
                padding: 30px;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            .header {
                text-align: center;
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 2px solid #007bff;
            }
            .company-name {
                font-size: 28px;
                font-weight: bold;
                color: #007bff;
                margin-bottom: 10px;
            }
            .company-info {
                color: #666;
                font-size: 14px;
            }
            .receipt-title {
                font-size: 24px;
                font-weight: bold;
                text-align: center;
                margin: 30px 0;
                color: #333;
            }
            .info-grid {
                display: table;
                width: 100%;
                margin-bottom: 30px;
            }
            .info-section {
                display: table-cell;
                width: 50%;
                vertical-align: top;
                padding: 0 10px;
            }
            .info-title {
                font-weight: bold;
                font-size: 16px;
                margin-bottom: 15px;
                color: #007bff;
                border-bottom: 1px solid #eee;
                padding-bottom: 5px;
            }
            .info-row {
                margin-bottom: 8px;
                font-size: 14px;
            }
            .info-label {
                color: #666;
                display: inline-block;
                width: 100px;
            }
            .info-value {
                font-weight: 500;
            }
            .items-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 30px;
                font-size: 14px;
            }
            .items-table th,
            .items-table td {
                border: 1px solid #ddd;
                padding: 12px 8px;
                text-align: left;
            }
            .items-table th {
                background-color: #f8f9fa;
                font-weight: bold;
                color: #333;
            }
            .items-table .text-right {
                text-align: right;
            }
            .totals-section {
                float: right;
                width: 300px;
                margin-bottom: 30px;
            }
            .total-row {
                display: flex;
                justify-content: space-between;
                padding: 8px 0;
                font-size: 14px;
            }
            .total-row.final {
                border-top: 2px solid #007bff;
                font-weight: bold;
                font-size: 18px;
                margin-top: 10px;
                padding-top: 15px;
                color: #007bff;
            }
            .footer {
                text-align: center;
                margin-top: 40px;
                padding-top: 20px;
                border-top: 1px solid #eee;
            }
            .footer-message {
                font-size: 18px;
                font-weight: bold;
                color: #007bff;
                margin-bottom: 15px;
            }
            .footer-text {
                color: #666;
                font-size: 14px;
                margin-bottom: 10px;
            }
            .clearfix::after {
                content: "";
                display: table;
                clear: both;
            }
            @media only screen and (max-width: 600px) {
                .info-section {
                    display: block;
                    width: 100%;
                    margin-bottom: 20px;
                }
                .totals-section {
                    float: none;
                    width: 100%;
                }
            }
        </style>
    </head>
    <body>
        <div class="email-container">
            <!-- Header -->
            <div class="header">
                <div class="company-name"><?= htmlspecialchars($companyName) ?></div>
                <div class="company-info">
                    <?= nl2br(htmlspecialchars($companyAddress)) ?><br>
                    Phone: <?= htmlspecialchars($companyPhone) ?> | Email: <?= htmlspecialchars($companyEmail) ?>
                </div>
            </div>

            <div class="receipt-title">Receipt #<?= htmlspecialchars($receiptNumber) ?></div>

            <!-- Receipt Information -->
            <div class="info-grid">
                <div class="info-section">
                    <div class="info-title">Receipt Details</div>
                    <div class="info-row">
                        <span class="info-label">Receipt #:</span>
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
                        <span class="info-label">Payment:</span>
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
                </div>
            </div>

            <!-- Items Table -->
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Item Description</th>
                        <th class="text-right">Qty</th>
                        <th class="text-right">Unit Price</th>
                        <th class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($sale['items'])): ?>
                        <?php foreach ($sale['items'] as $item): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($item['product_name']) ?>
                                <?php if (!empty($item['product_sku'])): ?>
                                <br><small style="color: #666;">SKU: <?= htmlspecialchars($item['product_sku']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td class="text-right"><?= $item['quantity'] ?></td>
                            <td class="text-right">$<?= number_format($item['unit_price'], 2) ?></td>
                            <td class="text-right"><strong>$<?= number_format($item['total_price'], 2) ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Totals -->
            <div class="totals-section">
                <div class="total-row">
                    <span>Subtotal:</span>
                    <span>$<?= number_format($sale['subtotal'] ?? 0, 2) ?></span>
                </div>
                <?php if (!empty($sale['discount_amount']) && $sale['discount_amount'] > 0): ?>
                <div class="total-row" style="color: #dc3545;">
                    <span>Discount:</span>
                    <span>-$<?= number_format($sale['discount_amount'], 2) ?></span>
                </div>
                <?php endif; ?>
                <div class="total-row">
                    <span>Tax (10%):</span>
                    <span>$<?= number_format($sale['tax_amount'] ?? 0, 2) ?></span>
                </div>
                <div class="total-row final">
                    <span>TOTAL:</span>
                    <span>$<?= number_format($sale['total_amount'] ?? 0, 2) ?></span>
                </div>
            </div>

            <div class="clearfix"></div>

            <!-- Footer -->
            <div class="footer">
                <div class="footer-message"><?= htmlspecialchars($receiptFooter) ?></div>
                <div class="footer-text">
                    This receipt serves as proof of purchase. Please retain for your records.
                </div>
                <?php if (!empty($settings['receipt_terms'])): ?>
                <div class="footer-text" style="font-size: 12px; margin-top: 15px;">
                    <?= htmlspecialchars($settings['receipt_terms']) ?>
                </div>
                <?php endif; ?>
                <div style="margin-top: 20px; font-size: 12px; color: #999;">
                    Email sent on <?= date('F j, Y \a\t g:i A') ?>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}
?>
