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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #<?= htmlspecialchars($sale['sale_number'] ?? $sale['id']) ?> - M-Ecommerce</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { font-size: 12px; }
            .receipt-container { max-width: none; margin: 0; box-shadow: none; }
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Action Bar -->
    <div class="no-print bg-white shadow-sm border-b p-4">
        <div class="max-w-4xl mx-auto flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <a href="sales-receipt.php" class="text-gray-600 hover:text-gray-800">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Sales
                </a>
                <h1 class="text-xl font-semibold">Receipt #<?= htmlspecialchars($sale['sale_number'] ?? $sale['id']) ?></h1>
            </div>
            <div class="flex space-x-2">
                <button onclick="window.print()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    <i class="fas fa-print mr-2"></i>Print
                </button>
                <a href="receipt-pdf.php?id=<?= $sale['id'] ?>&print=true" target="_blank" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                    <i class="fas fa-file-pdf mr-2"></i>Print as PDF
                </a>
                <button onclick="emailReceipt()" class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700">
                    <i class="fas fa-envelope mr-2"></i>Email
                </button>
            </div>
        </div>
    </div>

    <!-- Receipt -->
    <div class="max-w-4xl mx-auto p-8">
        <div class="receipt-container bg-white shadow-lg rounded-lg overflow-hidden">
            <!-- Header -->
            <div class="bg-gray-50 p-6 border-b">
                <div class="text-center">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2"><?= htmlspecialchars($companyName) ?></h1>
                    <div class="text-gray-600">
                        <?= nl2br(htmlspecialchars($companyAddress)) ?>
                    </div>
                    <div class="mt-2 text-gray-600">
                        <span class="mr-4"><i class="fas fa-phone mr-1"></i><?= htmlspecialchars($companyPhone) ?></span>
                        <span><i class="fas fa-envelope mr-1"></i><?= htmlspecialchars($companyEmail) ?></span>
                    </div>
                </div>
            </div>

            <!-- Receipt Info -->
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">Receipt Information</h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Receipt Number:</span>
                                <span class="font-semibold"><?= htmlspecialchars($sale['sale_number'] ?? 'R' . $sale['id']) ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Date:</span>
                                <span><?= date('F j, Y', strtotime($sale['sale_date'] ?? $sale['created_at'])) ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Time:</span>
                                <span><?= date('g:i A', strtotime($sale['created_at'])) ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Payment Method:</span>
                                <span class="capitalize"><?= htmlspecialchars($sale['payment_method'] ?? 'Cash') ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Status:</span>
                                <span class="capitalize text-green-600 font-semibold"><?= htmlspecialchars($sale['payment_status'] ?? 'Paid') ?></span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">Customer Information</h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Name:</span>
                                <span><?= htmlspecialchars($sale['customer_name'] ?? 'Walk-in Customer') ?></span>
                            </div>
                            <?php if (!empty($sale['customer_email'])): ?>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Email:</span>
                                <span><?= htmlspecialchars($sale['customer_email']) ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($sale['customer_phone'])): ?>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Phone:</span>
                                <span><?= htmlspecialchars($sale['customer_phone']) ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($sale['created_by_name'])): ?>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Served by:</span>
                                <span><?= htmlspecialchars($sale['created_by_name']) ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Items Purchased</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse border border-gray-300">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="border border-gray-300 px-4 py-2 text-left">Item</th>
                                    <th class="border border-gray-300 px-4 py-2 text-center">Qty</th>
                                    <th class="border border-gray-300 px-4 py-2 text-right">Unit Price</th>
                                    <th class="border border-gray-300 px-4 py-2 text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($sale['items'])): ?>
                                    <?php foreach ($sale['items'] as $item): ?>
                                    <tr>
                                        <td class="border border-gray-300 px-4 py-2">
                                            <div class="font-medium"><?= htmlspecialchars($item['product_name']) ?></div>
                                            <?php if (!empty($item['product_sku'])): ?>
                                            <div class="text-sm text-gray-500">SKU: <?= htmlspecialchars($item['product_sku']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="border border-gray-300 px-4 py-2 text-center"><?= $item['quantity'] ?></td>
                                        <td class="border border-gray-300 px-4 py-2 text-right"><?= formatCurrency($item['unit_price']) ?></td>
                                        <td class="border border-gray-300 px-4 py-2 text-right font-semibold"><?= formatCurrency($item['total_price']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="border border-gray-300 px-4 py-8 text-center text-gray-500">No items found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Totals -->
                <div class="flex justify-end">
                    <div class="w-full max-w-sm">
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Subtotal:</span>
                                <span><?= formatCurrency($sale['subtotal'] ?? 0) ?></span>
                            </div>
                            <?php if (!empty($sale['discount_amount']) && $sale['discount_amount'] > 0): ?>
                            <div class="flex justify-between text-sm text-red-600">
                                <span>Discount:</span>
                                <span>-<?= formatCurrency($sale['discount_amount']) ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">VAT (15%):</span>
                                <span><?= formatCurrency($sale['tax_amount'] ?? 0) ?></span>
                            </div>
                            <div class="border-t pt-2">
                                <div class="flex justify-between text-lg font-bold">
                                    <span>Total:</span>
                                    <span><?= formatCurrency($sale['total_amount'] ?? 0) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <?php if (!empty($sale['notes'])): ?>
                <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded">
                    <h4 class="font-semibold text-gray-900 mb-2">Notes:</h4>
                    <p class="text-sm text-gray-700"><?= nl2br(htmlspecialchars($sale['notes'])) ?></p>
                </div>
                <?php endif; ?>

                <!-- Footer -->
                <div class="mt-8 pt-6 border-t text-center">
                    <p class="text-lg font-semibold text-gray-900 mb-2"><?= htmlspecialchars($receiptFooter) ?></p>
                    <p class="text-sm text-gray-600">
                        This receipt serves as proof of purchase. Please retain for your records.
                    </p>
                    <?php if (!empty($settings['receipt_terms'])): ?>
                    <p class="text-xs text-gray-500 mt-2">
                        <?= htmlspecialchars($settings['receipt_terms']) ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function emailReceipt() {
            const customerEmail = '<?= htmlspecialchars($sale['customer_email'] ?? '') ?>';
            
            if (!customerEmail) {
                const email = prompt('Enter customer email address:');
                if (email) {
                    sendReceiptEmail(email);
                }
            } else {
                if (confirm(`Send receipt to ${customerEmail}?`)) {
                    sendReceiptEmail(customerEmail);
                }
            }
        }

        function sendReceiptEmail(email) {
            fetch('receipt-email.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    sale_id: <?= $sale['id'] ?>,
                    email: email
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Receipt sent successfully!');
                } else {
                    alert('Failed to send receipt: ' + data.error);
                }
            })
            .catch(error => {
                alert('Error sending receipt: ' + error.message);
            });
        }
    </script>
</body>
</html>
