<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once __DIR__ . '/../includes/auth.php';
    require_once __DIR__ . '/../includes/functions.php';
    
    $auth = getAuth();
    $auth->requireAdmin();
    
    // Try to include business functions, but handle gracefully if they fail
    $businessFunctionsLoaded = false;
    try {
        require_once __DIR__ . '/../includes/business_functions.php';
        $businessFunctionsLoaded = true;
    } catch (Exception $e) {
        // Business functions not available, we'll show a simplified dashboard
        error_log("Business functions not loaded: " . $e->getMessage());
    }
    
    // Initialize managers only if business functions are loaded
    if ($businessFunctionsLoaded) {
        try {
            $salesManager = new SalesManager();
            $purchaseManager = new PurchaseManager();
            $inventoryManager = new InventoryManager();
            $financialManager = new FinancialManager();
            $supportManager = new SupportManager();
        } catch (Exception $e) {
            $businessFunctionsLoaded = false;
            error_log("Manager initialization failed: " . $e->getMessage());
        }
    }
} catch (Exception $e) {
    // Redirect to simple dashboard if there are issues
    header('Location: dashboard_simple.php');
    exit;
}

// Initialize default values
$monthlySales = ['total_sales' => 0, 'total_revenue' => 0, 'average_sale' => 0, 'pending_amount' => 0];
$recentSales = [];
$recentPurchases = [];
$lowStockItems = [];
$totalInventoryValue = 0;
$allInventory = [];
$monthlyFinances = ['income' => 0, 'expense' => 0, 'profit' => 0];
$supportStatsArray = ['open' => 0, 'in_progress' => 0, 'resolved' => 0, 'closed' => 0];
$recentTickets = [];
$recentStockMovements = [];

// Get current month data only if business functions are loaded
if ($businessFunctionsLoaded) {
    $currentMonth = date('Y-m-01');
    $currentMonthEnd = date('Y-m-t');

    try {
        // Sales data
        $monthlySales = $salesManager->getSalesReport($currentMonth, $currentMonthEnd);
        $recentSales = $salesManager->getAllSales(null, null, 5);

        // Purchase data
        $recentPurchases = $purchaseManager->getAllPurchaseOrders(null, 5);

        // Inventory data
        $lowStockItems = $inventoryManager->getAllInventory(true);
        $allInventory = $inventoryManager->getAllInventory();
        foreach ($allInventory as $item) {
            $totalInventoryValue += $item['quantity_in_stock'] * $item['price'];
        }

        // Financial data
        $monthlyFinances = $financialManager->getFinancialSummary($currentMonth, $currentMonthEnd);

        // Support data
        $supportStats = $supportManager->getSupportStats();
        $recentTickets = $supportManager->getAllTickets(null, null, null, 5);

        // Convert support stats to associative array
        foreach ($supportStats as $stat) {
            $supportStatsArray[$stat['status']] = $stat['count'];
        }

        // Get recent stock movements
        $recentStockMovements = $inventoryManager->getStockMovements(null, 10);
        
    } catch (Exception $e) {
        // If any business function fails, log the error but continue with default values
        error_log("Dashboard data fetching failed: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - ModernShop</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                        <h1 class="text-xl font-semibold text-gray-900">Admin Dashboard</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <a href="products.php" class="text-gray-600 hover:text-gray-900">Products</a>
                        <a href="sales.php" class="text-gray-600 hover:text-gray-900">Sales</a>
                        <a href="sales-receipt.php" class="text-gray-600 hover:text-gray-900">Sales Receipt</a>
                        <a href="purchases.php" class="text-gray-600 hover:text-gray-900">Purchases</a>
                        <a href="inventory.php" class="text-gray-600 hover:text-gray-900">Inventory</a>
                        <a href="finances.php" class="text-gray-600 hover:text-gray-900">Finances</a>
                        <a href="support.php" class="text-gray-600 hover:text-gray-900">Support</a>
                        <a href="../pages/logout.php" class="text-red-600 hover:text-red-700">Logout</a>
                    </div>
                </div>
            </div>
        </nav>

        <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <!-- Welcome Section -->
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-900">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
                <p class="text-gray-600">Here's what's happening with your business today.</p>
            </div>

            <!-- Key Metrics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Sales Card -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Monthly Sales</dt>
                                    <dd class="text-lg font-medium text-gray-900"><?php echo formatCurrency($monthlySales['total_revenue'] ?? 0); ?></dd>
                                </dl>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="text-sm text-gray-500">
                                <?php echo ($monthlySales['total_sales'] ?? 0); ?> orders this month
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Inventory Value Card -->
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
                                    <dt class="text-sm font-medium text-gray-500 truncate">Inventory Value</dt>
                                    <dd class="text-lg font-medium text-gray-900"><?php echo formatCurrency($totalInventoryValue); ?></dd>
                                </dl>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="text-sm text-red-600">
                                <?php echo count($lowStockItems); ?> items low in stock
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Monthly Profit Card -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 <?php echo $monthlyFinances['profit'] >= 0 ? 'bg-green-500' : 'bg-red-500'; ?> rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Monthly Profit</dt>
                                    <dd class="text-lg font-medium <?php echo $monthlyFinances['profit'] >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                        <?php echo formatCurrency($monthlyFinances['profit']); ?>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="text-sm text-gray-500">
                                Income: <?php echo formatCurrency($monthlyFinances['income']); ?> | 
                                Expenses: <?php echo formatCurrency($monthlyFinances['expense']); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Support Tickets Card -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Open Tickets</dt>
                                    <dd class="text-lg font-medium text-gray-900"><?php echo ($supportStatsArray['open'] ?? 0) + ($supportStatsArray['in_progress'] ?? 0); ?></dd>
                                </dl>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="text-sm text-gray-500">
                                <?php echo $supportStatsArray['resolved'] ?? 0; ?> resolved this month
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="bg-white shadow rounded-lg mb-8">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Quick Actions</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        <a href="products.php" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <svg class="w-8 h-8 text-blue-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                            <span class="text-sm font-medium text-gray-900">Add Product</span>
                        </a>
                        
                        <a href="sales.php" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <svg class="w-8 h-8 text-green-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            <span class="text-sm font-medium text-gray-900">View Sales</span>
                        </a>
                        
                        <a href="sales-receipt.php" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <svg class="w-8 h-8 text-indigo-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span class="text-sm font-medium text-gray-900">Sales Receipt</span>
                        </a>
                        
                        <a href="purchases.php" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <svg class="w-8 h-8 text-orange-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                            <span class="text-sm font-medium text-gray-900">New Purchase</span>
                        </a>
                        
                        <a href="inventory.php" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <svg class="w-8 h-8 text-purple-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            <span class="text-sm font-medium text-gray-900">Check Inventory</span>
                        </a>
                        
                        <a href="finances.php" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <svg class="w-8 h-8 text-yellow-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                            <span class="text-sm font-medium text-gray-900">Add Transaction</span>
                        </a>
                        
                        <a href="support.php" class="flex flex-col items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <svg class="w-8 h-8 text-red-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                            </svg>
                            <span class="text-sm font-medium text-gray-900">Support Tickets</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- Recent Sales -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Sales</h3>
                            <a href="sales.php" class="text-sm text-blue-600 hover:text-blue-800">View all</a>
                        </div>
                        <div class="space-y-3">
                            <?php foreach ($recentSales as $sale): ?>
                            <div class="flex justify-between items-center">
                                <div>
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($sale['sale_number']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($sale['customer_name']); ?></div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-medium text-gray-900"><?php echo formatCurrency($sale['total_amount']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo formatDate($sale['sale_date']); ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Low Stock Alerts -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Low Stock Alerts</h3>
                            <a href="inventory.php?low_stock=1" class="text-sm text-red-600 hover:text-red-800">View all</a>
                        </div>
                        <div class="space-y-3">
                            <?php foreach (array_slice($lowStockItems, 0, 5) as $item): ?>
                            <div class="flex justify-between items-center">
                                <div>
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($item['category_name']); ?></div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-medium text-red-600"><?php echo $item['quantity_in_stock']; ?> left</div>
                                    <div class="text-sm text-gray-500">Reorder: <?php echo $item['reorder_level']; ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Grid 2 -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Recent Support Tickets -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Support Tickets</h3>
                            <a href="support.php" class="text-sm text-purple-600 hover:text-purple-800">View all</a>
                        </div>
                        <div class="space-y-3">
                            <?php foreach ($recentTickets as $ticket): ?>
                            <div class="flex justify-between items-center">
                                <div>
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($ticket['ticket_number']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($ticket['subject']); ?></div>
                                </div>
                                <div class="text-right">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo getStatusBadgeClass($ticket['status']); ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                    </span>
                                    <div class="text-sm text-gray-500 mt-1"><?php echo formatDate($ticket['created_at']); ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Recent Stock Movements -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Recent Stock Movements</h3>
                            <a href="inventory.php" class="text-sm text-blue-600 hover:text-blue-800">View all</a>
                        </div>
                        <div class="space-y-3">
                            <?php foreach (array_slice($recentStockMovements, 0, 5) as $movement): ?>
                            <div class="flex justify-between items-center">
                                <div>
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($movement['product_name']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo ucfirst($movement['reference_type']); ?></div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-medium <?php echo $movement['movement_type'] === 'in' ? 'text-green-600' : 'text-red-600'; ?>">
                                        <?php echo $movement['movement_type'] === 'in' ? '+' : '-'; ?><?php echo $movement['quantity']; ?>
                                    </div>
                                    <div class="text-sm text-gray-500"><?php echo formatDate($movement['created_at'], 'M d'); ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
