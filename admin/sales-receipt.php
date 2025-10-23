<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/business_functions.php';

$auth = getAuth();
$auth->requireAdmin();

$salesManager = new SalesManager();
$productManager = new ProductManager();
$error = '';
$success = '';
$saleId = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create_sale') {
        $customerData = [
            'name' => sanitizeInput($_POST['customer_name'] ?? ''),
            'email' => sanitizeInput($_POST['customer_email'] ?? ''),
            'phone' => sanitizeInput($_POST['customer_phone'] ?? '')
        ];
        
        $paymentMethod = sanitizeInput($_POST['payment_method'] ?? 'cash');
        $items = [];
        
        // Process items
        if (isset($_POST['items']) && is_array($_POST['items'])) {
            foreach ($_POST['items'] as $item) {
                if (!empty($item['product_id']) && !empty($item['quantity']) && $item['quantity'] > 0) {
                    $product = $productManager->getProductById($item['product_id']);
                    if ($product) {
                        $items[] = [
                            'product_id' => $product['id'],
                            'product_name' => $product['name'],
                            'quantity' => (int)$item['quantity'],
                            'price' => (float)$product['price']
                        ];
                    }
                }
            }
        }
        
        if (empty($customerData['name'])) {
            $error = 'Customer name is required';
        } elseif (empty($items)) {
            $error = 'At least one item is required';
        } else {
            $result = $salesManager->createDirectSale($customerData, $items, $paymentMethod, $auth->getCurrentUser()['id']);
            
            if ($result['success']) {
                $success = 'Sale created successfully! Receipt #' . $result['sale_number'];
                $saleId = $result['sale_id'];
            } else {
                $error = $result['error'];
            }
        }
    }
}

// Get all products for selection
$products = $productManager->getAllProducts();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Receipt - M-Ecommerce Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center">
                        <a href="dashboard.php" class="text-gray-500 hover:text-gray-700 mr-4">
                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                        </a>
                        <h1 class="text-2xl font-bold text-gray-900">Sales Receipt</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="sales.php" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200">
                            <i class="fas fa-list mr-2"></i>View All Sales
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Sales Form -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Create New Sale</h2>
                    
                    <?php if ($error): ?>
                        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
                            <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-4">
                            <i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($success) ?>
                            <?php if ($saleId): ?>
                                <div class="mt-2">
                                    <a href="receipt-view.php?id=<?= $saleId ?>" target="_blank" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 text-sm">
                                        <i class="fas fa-receipt mr-1"></i>View Receipt
                                    </a>
                                    <a href="receipt-pdf.php?id=<?= $saleId ?>&print=true" target="_blank" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm ml-2">
                                        <i class="fas fa-file-pdf mr-1"></i>Print as PDF
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="salesForm">
                        <input type="hidden" name="action" value="create_sale">
                        
                        <!-- Customer Information -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Customer Name *</label>
                                <input type="text" name="customer_name" required 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                       placeholder="Enter customer name">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Customer Email</label>
                                <input type="email" name="customer_email" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                       placeholder="customer@email.com">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Customer Phone</label>
                                <input type="tel" name="customer_phone" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                       placeholder="+1 (555) 123-4567">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Payment Method</label>
                                <select name="payment_method" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="cash">Cash</option>
                                    <option value="credit_card">Credit Card</option>
                                    <option value="debit_card">Debit Card</option>
                                    <option value="check">Check</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                </select>
                            </div>
                        </div>

                        <!-- Items Section -->
                        <div class="mb-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-medium text-gray-900">Items</h3>
                                <button type="button" onclick="addItem()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                                    <i class="fas fa-plus mr-2"></i>Add Item
                                </button>
                            </div>
                            
                            <div id="itemsContainer">
                                <!-- Items will be added here -->
                            </div>
                        </div>

                        <!-- Total Section -->
                        <div class="bg-gray-50 p-4 rounded-lg mb-6">
                            <div class="flex justify-between items-center text-lg font-semibold">
                                <span>Total Amount:</span>
                                <span id="totalAmount">৳0.00</span>
                            </div>
                        </div>

                        <div class="flex space-x-4">
                            <button type="submit" class="flex-1 bg-green-600 text-white py-3 px-4 rounded-lg hover:bg-green-700 font-medium">
                                <i class="fas fa-receipt mr-2"></i>Create Sale & Generate Receipt
                            </button>
                            <button type="button" onclick="clearForm()" class="bg-gray-300 text-gray-700 py-3 px-4 rounded-lg hover:bg-gray-400">
                                <i class="fas fa-times mr-2"></i>Clear
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Recent Sales -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Recent Sales</h2>
                    
                    <?php
                    $recentSales = $salesManager->getAllSales(null, null, 10);
                    if ($recentSales):
                    ?>
                        <div class="space-y-4">
                            <?php foreach ($recentSales as $sale): ?>
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex justify-between items-start mb-2">
                                        <div>
                                            <h4 class="font-medium text-gray-900">
                                                <?= htmlspecialchars($sale['sale_number'] ?? 'Sale #' . $sale['id']) ?>
                                            </h4>
                                            <p class="text-sm text-gray-600">
                                                <?= htmlspecialchars($sale['customer_name'] ?? 'N/A') ?>
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-semibold text-green-600">
                                                <?= formatCurrency($sale['total_amount'] ?? 0) ?>
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                <?= date('M j, Y', strtotime($sale['created_at'])) ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex space-x-2">
                                        <a href="receipt-view.php?id=<?= $sale['id'] ?>" target="_blank" 
                                           class="text-blue-600 hover:text-blue-800 text-sm">
                                            <i class="fas fa-eye mr-1"></i>View
                                        </a>
                                        <a href="receipt-pdf.php?id=<?= $sale['id'] ?>&print=true" target="_blank" 
                                           class="text-green-600 hover:text-green-800 text-sm">
                                            <i class="fas fa-file-pdf mr-1"></i>PDF
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500 text-center py-8">No recent sales found</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        const products = <?= json_encode($products) ?>;
        let itemCount = 0;

        function addItem() {
            itemCount++;
            const container = document.getElementById('itemsContainer');
            const itemDiv = document.createElement('div');
            itemDiv.className = 'border border-gray-200 rounded-lg p-4 mb-4';
            itemDiv.id = `item-${itemCount}`;
            
            itemDiv.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Product</label>
                        <select name="items[${itemCount}][product_id]" onchange="updatePrice(${itemCount})" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            <option value="">Select Product</option>
                            ${products.map(p => `<option value="${p.id}" data-price="${p.price}">${p.name} - ৳${p.price}</option>`).join('')}
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                        <input type="number" name="items[${itemCount}][quantity]" min="1" value="1" 
                               onchange="calculateTotal()" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Unit Price</label>
                        <input type="text" id="price-${itemCount}" readonly 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50" placeholder="$0.00">
                    </div>
                    <div class="flex items-end">
                        <button type="button" onclick="removeItem(${itemCount})" 
                                class="w-full bg-red-500 text-white py-2 px-3 rounded hover:bg-red-600">
                            <i class="fas fa-trash"></i> Remove
                        </button>
                    </div>
                </div>
            `;
            
            container.appendChild(itemDiv);
        }

        function removeItem(itemId) {
            const item = document.getElementById(`item-${itemId}`);
            if (item) {
                item.remove();
                calculateTotal();
            }
        }

        function updatePrice(itemId) {
            const select = document.querySelector(`select[name="items[${itemId}][product_id]"]`);
            const priceInput = document.getElementById(`price-${itemId}`);
            
            if (select.value) {
                const selectedOption = select.options[select.selectedIndex];
                const price = selectedOption.getAttribute('data-price');
                priceInput.value = `৳${parseFloat(price).toFixed(2)}`;
            } else {
                priceInput.value = '৳0.00';
            }
            
            calculateTotal();
        }

        function calculateTotal() {
            let total = 0;
            
            document.querySelectorAll('[name*="[product_id]"]').forEach((select, index) => {
                if (select.value) {
                    const selectedOption = select.options[select.selectedIndex];
                    const price = parseFloat(selectedOption.getAttribute('data-price')) || 0;
                    const quantityInput = select.closest('.grid').querySelector('[name*="[quantity]"]');
                    const quantity = parseInt(quantityInput.value) || 0;
                    
                    total += price * quantity;
                }
            });
            
            // Add 15% VAT (Bangladesh)
            const tax = total * 0.15;
            const finalTotal = total + tax;
            
            document.getElementById('totalAmount').textContent = `৳${finalTotal.toFixed(2)}`;
        }

        function clearForm() {
            document.getElementById('salesForm').reset();
            document.getElementById('itemsContainer').innerHTML = '';
            document.getElementById('totalAmount').textContent = '৳0.00';
            itemCount = 0;
        }

        // Add first item by default
        addItem();
    </script>
</body>
</html>
