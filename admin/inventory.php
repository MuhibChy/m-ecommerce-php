<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once __DIR__ . '/../includes/auth.php';
    require_once __DIR__ . '/../includes/functions.php';
    require_once __DIR__ . '/../includes/business_functions.php';

    $auth = getAuth();
    $auth->requireAdmin();

    $inventoryManager = new InventoryManager();
} catch (Exception $e) {
    echo "<div style='background: #fef2f2; border: 1px solid #ef4444; border-radius: 8px; padding: 20px; margin: 20px; font-family: Arial, sans-serif;'>";
    echo "<h2 style='color: #dc2626;'>Inventory Management Error</h2>";
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
        case 'adjust_stock':
            handleStockAdjustment($inventoryManager);
            break;
        case 'update_reorder':
            handleReorderUpdate($inventoryManager);
            break;
    }
}

function handleStockAdjustment($inventoryManager) {
    global $error, $success;
    
    try {
        $productId = intval($_POST['product_id']);
        $newQuantity = intval($_POST['new_quantity']);
        $notes = sanitizeInput($_POST['notes']);
        $userId = $_SESSION['user_id'];
        
        $inventoryManager->adjustStock($productId, $newQuantity, $notes, $userId);
        $success = "Stock adjusted successfully!";
    } catch (Exception $e) {
        $error = "Error adjusting stock: " . $e->getMessage();
    }
}

function handleReorderUpdate($inventoryManager) {
    global $error, $success;
    
    try {
        $productId = intval($_POST['product_id']);
        $reorderLevel = intval($_POST['reorder_level']);
        $reorderQuantity = intval($_POST['reorder_quantity']);
        
        $stmt = getDB()->prepare("UPDATE inventory SET reorder_level = ?, reorder_quantity = ? WHERE product_id = ?");
        $stmt->execute([$reorderLevel, $reorderQuantity, $productId]);
        
        $success = "Reorder settings updated successfully!";
    } catch (Exception $e) {
        $error = "Error updating reorder settings: " . $e->getMessage();
    }
}

// Get data for display
$showLowStock = isset($_GET['low_stock']) && $_GET['low_stock'] === '1';
$inventory = $inventoryManager->getAllInventory($showLowStock);
$stockMovements = $inventoryManager->getStockMovements(null, 20);

// Calculate summary statistics
$totalProducts = count($inventory);
$lowStockCount = 0;
$totalValue = 0;

foreach ($inventory as $item) {
    if ($item['quantity_in_stock'] <= $item['reorder_level']) {
        $lowStockCount++;
    }
    $totalValue += $item['quantity_in_stock'] * $item['price'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - ModernShop Admin</title>
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
                        <h1 class="text-xl font-semibold text-gray-900">Inventory Management</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="dashboard.php" class="text-gray-600 hover:text-gray-900">Dashboard</a>
                        <a href="products.php" class="text-gray-600 hover:text-gray-900">Products</a>
                        <a href="sales.php" class="text-gray-600 hover:text-gray-900">Sales</a>
                        <a href="purchases.php" class="text-gray-600 hover:text-gray-900">Purchases</a>
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

            <!-- Inventory Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Products</dt>
                                    <dd class="text-lg font-medium text-gray-900"><?php echo $totalProducts; ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-red-500 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Low Stock Items</dt>
                                    <dd class="text-lg font-medium text-gray-900"><?php echo $lowStockCount; ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Value</dt>
                                    <dd class="text-lg font-medium text-gray-900"><?php echo formatCurrency($totalValue); ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Avg Stock Level</dt>
                                    <dd class="text-lg font-medium text-gray-900">
                                        <?php 
                                        $avgStock = $totalProducts > 0 ? array_sum(array_column($inventory, 'quantity_in_stock')) / $totalProducts : 0;
                                        echo number_format($avgStock, 0);
                                        ?>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter and Actions -->
            <div class="mb-6 flex justify-between items-center">
                <div class="flex space-x-3">
                    <a href="inventory.php" class="<?php echo !$showLowStock ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'; ?> px-4 py-2 rounded-md hover:bg-blue-700">
                        All Items
                    </a>
                    <a href="inventory.php?low_stock=1" class="<?php echo $showLowStock ? 'bg-red-600 text-white' : 'bg-gray-200 text-gray-700'; ?> px-4 py-2 rounded-md hover:bg-red-700">
                        Low Stock Only
                    </a>
                </div>
                <div class="space-x-3">
                    <button onclick="showBulkAdjustModal()" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                        Bulk Stock Adjustment
                    </button>
                </div>
            </div>

            <!-- Inventory Table -->
            <div class="bg-white shadow rounded-lg mb-8">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                        <?php echo $showLowStock ? 'Low Stock Items' : 'All Inventory Items'; ?>
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reserved</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reorder Level</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Value</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($inventory as $item): ?>
                                <tr class="<?php echo $item['quantity_in_stock'] <= $item['reorder_level'] ? 'bg-red-50' : ''; ?>">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <img class="h-10 w-10 rounded-full object-cover" src="<?php echo htmlspecialchars($item['product_image']); ?>" alt="">
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($item['product_name']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($item['category_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo $item['quantity_in_stock']; ?>
                                        </div>
                                        <?php if ($item['quantity_in_stock'] <= $item['reorder_level']): ?>
                                            <div class="text-xs text-red-600">Low Stock!</div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo $item['reserved_quantity']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo $item['reorder_level']; ?> / <?php echo $item['reorder_quantity']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo formatCurrency($item['quantity_in_stock'] * $item['price']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button onclick="adjustStock(<?php echo $item['product_id']; ?>, '<?php echo htmlspecialchars($item['product_name']); ?>', <?php echo $item['quantity_in_stock']; ?>)" 
                                                class="text-blue-600 hover:text-blue-900 mr-3">
                                            Adjust Stock
                                        </button>
                                        <button onclick="updateReorder(<?php echo $item['product_id']; ?>, <?php echo $item['reorder_level']; ?>, <?php echo $item['reorder_quantity']; ?>)" 
                                                class="text-green-600 hover:text-green-900">
                                            Reorder Settings
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recent Stock Movements -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Recent Stock Movements</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">By</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($stockMovements as $movement): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($movement['product_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $movement['movement_type'] === 'in' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo ucfirst($movement['movement_type']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo $movement['quantity']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo ucfirst($movement['reference_type']); ?>
                                        <?php if ($movement['reference_id']): ?>
                                            #<?php echo $movement['reference_id']; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo formatDate($movement['created_at'], 'M d, Y H:i'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($movement['created_by_name']); ?>
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

    <!-- Stock Adjustment Modal -->
    <div id="adjustModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
        <div class="flex items-center justify-center min-h-screen">
            <div class="bg-white rounded-lg p-6 w-full max-w-md">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Adjust Stock</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="adjust_stock">
                    <input type="hidden" name="product_id" id="adjust_product_id">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Product</label>
                        <div id="adjust_product_name" class="mt-1 text-sm text-gray-900"></div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Current Stock</label>
                        <div id="adjust_current_stock" class="mt-1 text-sm text-gray-900"></div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">New Quantity</label>
                        <input type="number" name="new_quantity" id="adjust_new_quantity" min="0" required 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Notes</label>
                        <textarea name="notes" rows="3" 
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeAdjustModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                            Adjust Stock
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reorder Settings Modal -->
    <div id="reorderModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
        <div class="flex items-center justify-center min-h-screen">
            <div class="bg-white rounded-lg p-6 w-full max-w-md">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Update Reorder Settings</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="update_reorder">
                    <input type="hidden" name="product_id" id="reorder_product_id">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Reorder Level</label>
                        <input type="number" name="reorder_level" id="reorder_level" min="0" required 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <p class="mt-1 text-xs text-gray-500">Alert when stock falls below this level</p>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Reorder Quantity</label>
                        <input type="number" name="reorder_quantity" id="reorder_quantity" min="1" required 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <p class="mt-1 text-xs text-gray-500">Suggested quantity to reorder</p>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeReorderModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                            Update Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function adjustStock(productId, productName, currentStock) {
            document.getElementById('adjust_product_id').value = productId;
            document.getElementById('adjust_product_name').textContent = productName;
            document.getElementById('adjust_current_stock').textContent = currentStock;
            document.getElementById('adjust_new_quantity').value = currentStock;
            document.getElementById('adjustModal').classList.remove('hidden');
        }
        
        function closeAdjustModal() {
            document.getElementById('adjustModal').classList.add('hidden');
        }
        
        function updateReorder(productId, reorderLevel, reorderQuantity) {
            document.getElementById('reorder_product_id').value = productId;
            document.getElementById('reorder_level').value = reorderLevel;
            document.getElementById('reorder_quantity').value = reorderQuantity;
            document.getElementById('reorderModal').classList.remove('hidden');
        }
        
        function closeReorderModal() {
            document.getElementById('reorderModal').classList.add('hidden');
        }
        
        function showBulkAdjustModal() {
            alert('Bulk adjustment feature coming soon!');
        }
    </script>
</body>
</html>
