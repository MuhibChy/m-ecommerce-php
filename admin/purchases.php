<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once __DIR__ . '/../includes/auth.php';
    require_once __DIR__ . '/../includes/functions.php';
    require_once __DIR__ . '/../includes/business_functions.php';

    $auth = getAuth();
    $auth->requireAdmin();

    $purchaseManager = new PurchaseManager();
    $supplierManager = new SupplierManager();
    $productManager = new ProductManager();
} catch (Exception $e) {
    echo "<div style='background: #fef2f2; border: 1px solid #ef4444; border-radius: 8px; padding: 20px; margin: 20px; font-family: Arial, sans-serif;'>";
    echo "<h2 style='color: #dc2626;'>Purchase Management Error</h2>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Possible solutions:</strong></p>";
    echo "<ul>";
    echo "<li>Make sure the database is set up properly</li>";
    echo "<li>Run the <a href='../setup_database.php'>database setup script</a></li>";
    echo "<li>Check that XAMPP MySQL is running</li>";
    echo "<li>Go back to <a href='dashboard_simple.php'>Simple Dashboard</a></li>";
    echo "</ul>";
    echo "</div>";
    exit;
}

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_purchase':
            handleCreatePurchase($purchaseManager);
            break;
        case 'update_status':
            handleUpdateStatus($purchaseManager);
            break;
        case 'receive_items':
            handleReceiveItems($purchaseManager);
            break;
    }
}

function handleCreatePurchase($purchaseManager) {
    global $error, $success;
    
    try {
        $data = [
            'supplier_id' => intval($_POST['supplier_id']),
            'order_date' => $_POST['order_date'],
            'expected_delivery' => $_POST['expected_delivery'],
            'notes' => sanitizeInput($_POST['notes']),
            'total_amount' => 0
        ];
        
        $items = [];
        $totalAmount = 0;
        
        foreach ($_POST['items'] as $item) {
            if (!empty($item['product_id']) && !empty($item['quantity']) && !empty($item['unit_cost'])) {
                $quantity = intval($item['quantity']);
                $unitCost = floatval($item['unit_cost']);
                $totalCost = $quantity * $unitCost;
                
                $items[] = [
                    'product_id' => intval($item['product_id']),
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'total_cost' => $totalCost
                ];
                
                $totalAmount += $totalCost;
            }
        }
        
        if (empty($items)) {
            throw new Exception("Please add at least one item to the purchase order.");
        }
        
        $data['total_amount'] = $totalAmount;
        $userId = $_SESSION['user_id'];
        
        $purchaseOrderId = $purchaseManager->createPurchaseOrder($data, $items, $userId);
        $success = "Purchase order created successfully! PO ID: $purchaseOrderId";
    } catch (Exception $e) {
        $error = "Error creating purchase order: " . $e->getMessage();
    }
}

function handleUpdateStatus($purchaseManager) {
    global $error, $success;
    
    try {
        $id = intval($_POST['purchase_id']);
        $status = sanitizeInput($_POST['status']);
        $receivedDate = $status === 'received' ? date('Y-m-d') : null;
        
        $purchaseManager->updatePurchaseOrderStatus($id, $status, $receivedDate);
        $success = "Purchase order status updated successfully!";
    } catch (Exception $e) {
        $error = "Error updating status: " . $e->getMessage();
    }
}

function handleReceiveItems($purchaseManager) {
    global $error, $success;
    
    try {
        $purchaseOrderId = intval($_POST['purchase_order_id']);
        $inventoryManager = new InventoryManager();
        
        // Get purchase order items
        $items = $purchaseManager->getPurchaseOrderItems($purchaseOrderId);
        
        foreach ($items as $item) {
            $receivedQty = intval($_POST['received_qty'][$item['id']] ?? 0);
            
            if ($receivedQty > 0) {
                // Update received quantity
                $stmt = getDB()->prepare("UPDATE purchase_order_items SET received_quantity = ? WHERE id = ?");
                $stmt->execute([$receivedQty, $item['id']]);
                
                // Update inventory
                $inventoryManager->updateStock(
                    $item['product_id'],
                    $receivedQty,
                    'in',
                    'purchase',
                    $purchaseOrderId,
                    "Purchase order received",
                    $_SESSION['user_id']
                );
            }
        }
        
        // Update purchase order status to received
        $purchaseManager->updatePurchaseOrderStatus($purchaseOrderId, 'received', date('Y-m-d'));
        
        $success = "Items received and inventory updated successfully!";
    } catch (Exception $e) {
        $error = "Error receiving items: " . $e->getMessage();
    }
}

// Get data for display
$purchases = $purchaseManager->getAllPurchaseOrders(null, 50);
$suppliers = $supplierManager->getAllSuppliers('active');
$products = $productManager->getAllProducts();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Management - ModernShop Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'inter': ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 font-inter">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <h1 class="text-xl font-semibold text-gray-900">Purchase Management</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="dashboard.php" class="text-gray-600 hover:text-gray-900">Dashboard</a>
                        <a href="products.php" class="text-gray-600 hover:text-gray-900">Products</a>
                        <a href="sales.php" class="text-gray-600 hover:text-gray-900">Sales</a>
                        <a href="inventory.php" class="text-gray-600 hover:text-gray-900">Inventory</a>
                        <a href="../pages/logout.php" class="text-red-600 hover:text-red-700">Logout</a>
                    </div>
                </div>
            </div>
        </nav>

        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- Alerts -->
            <?php if ($error): ?>
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="mb-6 flex justify-between items-center">
                <h2 class="text-2xl font-bold text-gray-900">Purchase Orders</h2>
                <div class="space-x-3">
                    <button onclick="showCreateModal()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                        Create Purchase Order
                    </button>
                    <a href="suppliers.php" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">
                        Manage Suppliers
                    </a>
                </div>
            </div>

            <!-- Purchase Orders List -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PO Number</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($purchases as $purchase): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($purchase['order_number']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($purchase['supplier_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo formatCurrency($purchase['total_amount']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo getStatusBadgeClass($purchase['status']); ?>">
                                            <?php echo ucfirst($purchase['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo formatDate($purchase['order_date']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <?php if ($purchase['status'] === 'pending'): ?>
                                            <button onclick="updateStatus(<?php echo $purchase['id']; ?>, 'ordered')" 
                                                    class="text-blue-600 hover:text-blue-900 mr-3">
                                                Mark Ordered
                                            </button>
                                        <?php elseif ($purchase['status'] === 'ordered'): ?>
                                            <button onclick="showReceiveModal(<?php echo $purchase['id']; ?>)" 
                                                    class="text-green-600 hover:text-green-900 mr-3">
                                                Receive Items
                                            </button>
                                        <?php endif; ?>
                                        <a href="purchase-detail.php?id=<?php echo $purchase['id']; ?>" class="text-gray-600 hover:text-gray-900">
                                            View Details
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Purchase Order Modal -->
    <div id="createModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg p-6 w-full max-w-4xl max-h-screen overflow-y-auto">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Create Purchase Order</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="create_purchase">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Supplier</label>
                            <select name="supplier_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Supplier</option>
                                <?php foreach ($suppliers as $supplier): ?>
                                <option value="<?php echo $supplier['id']; ?>"><?php echo htmlspecialchars($supplier['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Order Date</label>
                            <input type="date" name="order_date" value="<?php echo date('Y-m-d'); ?>" required 
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Expected Delivery</label>
                            <input type="date" name="expected_delivery" 
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Notes</label>
                            <textarea name="notes" rows="2" 
                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Items</label>
                        <div id="itemsContainer">
                            <div class="grid grid-cols-12 gap-2 mb-2 font-medium text-sm text-gray-700">
                                <div class="col-span-5">Product</div>
                                <div class="col-span-2">Quantity</div>
                                <div class="col-span-2">Unit Cost</div>
                                <div class="col-span-2">Total</div>
                                <div class="col-span-1">Action</div>
                            </div>
                            <div class="item-row grid grid-cols-12 gap-2 mb-2">
                                <div class="col-span-5">
                                    <select name="items[0][product_id]" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Select Product</option>
                                        <?php foreach ($products as $product): ?>
                                        <option value="<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-span-2">
                                    <input type="number" name="items[0][quantity]" min="1" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" onchange="calculateTotal(this)">
                                </div>
                                <div class="col-span-2">
                                    <input type="number" name="items[0][unit_cost]" step="0.01" min="0" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" onchange="calculateTotal(this)">
                                </div>
                                <div class="col-span-2">
                                    <input type="text" class="total-field w-full border-gray-300 rounded-md shadow-sm bg-gray-100" readonly>
                                </div>
                                <div class="col-span-1">
                                    <button type="button" onclick="removeItem(this)" class="text-red-600 hover:text-red-900">Remove</button>
                                </div>
                            </div>
                        </div>
                        <button type="button" onclick="addItem()" class="mt-2 bg-gray-600 text-white px-3 py-1 rounded-md hover:bg-gray-700">
                            Add Item
                        </button>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeCreateModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                            Create Purchase Order
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Status Update Form (Hidden) -->
    <form id="statusForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="purchase_id" id="status_purchase_id">
        <input type="hidden" name="status" id="status_value">
    </form>

    <script>
        let itemIndex = 1;
        
        function showCreateModal() {
            document.getElementById('createModal').classList.remove('hidden');
        }
        
        function closeCreateModal() {
            document.getElementById('createModal').classList.add('hidden');
        }
        
        function addItem() {
            const container = document.getElementById('itemsContainer');
            const newItem = document.createElement('div');
            newItem.className = 'item-row grid grid-cols-12 gap-2 mb-2';
            newItem.innerHTML = `
                <div class="col-span-5">
                    <select name="items[${itemIndex}][product_id]" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Select Product</option>
                        <?php foreach ($products as $product): ?>
                        <option value="<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-span-2">
                    <input type="number" name="items[${itemIndex}][quantity]" min="1" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" onchange="calculateTotal(this)">
                </div>
                <div class="col-span-2">
                    <input type="number" name="items[${itemIndex}][unit_cost]" step="0.01" min="0" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" onchange="calculateTotal(this)">
                </div>
                <div class="col-span-2">
                    <input type="text" class="total-field w-full border-gray-300 rounded-md shadow-sm bg-gray-100" readonly>
                </div>
                <div class="col-span-1">
                    <button type="button" onclick="removeItem(this)" class="text-red-600 hover:text-red-900">Remove</button>
                </div>
            `;
            container.appendChild(newItem);
            itemIndex++;
        }
        
        function removeItem(button) {
            button.closest('.item-row').remove();
        }
        
        function calculateTotal(input) {
            const row = input.closest('.item-row');
            const quantity = parseFloat(row.querySelector('input[name*="[quantity]"]').value) || 0;
            const unitCost = parseFloat(row.querySelector('input[name*="[unit_cost]"]').value) || 0;
            const total = quantity * unitCost;
            row.querySelector('.total-field').value = total.toFixed(2);
        }
        
        function updateStatus(purchaseId, status) {
            if (confirm(`Mark this purchase order as ${status}?`)) {
                document.getElementById('status_purchase_id').value = purchaseId;
                document.getElementById('status_value').value = status;
                document.getElementById('statusForm').submit();
            }
        }
        
        function showReceiveModal(purchaseId) {
            // This would open a modal to receive items - simplified for now
            if (confirm('Mark all items as received and update inventory?')) {
                // Create a form to receive items
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="receive_items">
                    <input type="hidden" name="purchase_order_id" value="${purchaseId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>
