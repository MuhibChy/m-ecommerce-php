<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once __DIR__ . '/../includes/auth.php';
    require_once __DIR__ . '/../includes/functions.php';
    require_once __DIR__ . '/../includes/business_functions.php';

    $auth = getAuth();
    $auth->requireAdmin();

    $financialManager = new FinancialManager();
} catch (Exception $e) {
    echo "<div style='background: #fef2f2; border: 1px solid #ef4444; border-radius: 8px; padding: 20px; margin: 20px; font-family: Arial, sans-serif;'>";
    echo "<h2 style='color: #dc2626;'>Financial Management Error</h2>";
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
        case 'add_transaction':
            handleAddTransaction($financialManager);
            break;
        case 'add_category':
            handleAddCategory($financialManager);
            break;
    }
}

function handleAddTransaction($financialManager) {
    global $error, $success;
    
    try {
        $data = [
            'category_id' => intval($_POST['category_id']),
            'type' => sanitizeInput($_POST['type']),
            'amount' => floatval($_POST['amount']),
            'description' => sanitizeInput($_POST['description']),
            'transaction_date' => $_POST['transaction_date'],
            'payment_method' => sanitizeInput($_POST['payment_method']),
            'reference_number' => sanitizeInput($_POST['reference_number'])
        ];
        
        $userId = $_SESSION['user_id'];
        $financialManager->createTransaction($data, $userId);
        $success = "Transaction added successfully!";
    } catch (Exception $e) {
        $error = "Error adding transaction: " . $e->getMessage();
    }
}

function handleAddCategory($financialManager) {
    global $error, $success;
    
    try {
        $name = sanitizeInput($_POST['category_name']);
        $type = sanitizeInput($_POST['category_type']);
        $description = sanitizeInput($_POST['category_description']);
        
        $stmt = getDB()->prepare("INSERT INTO financial_categories (name, type, description) VALUES (?, ?, ?)");
        $stmt->execute([$name, $type, $description]);
        
        $success = "Category added successfully!";
    } catch (Exception $e) {
        $error = "Error adding category: " . $e->getMessage();
    }
}

// Get data for display
$dateFrom = $_GET['date_from'] ?? date('Y-m-01');
$dateTo = $_GET['date_to'] ?? date('Y-m-d');
$type = $_GET['type'] ?? '';

$transactions = $financialManager->getAllTransactions($type, $dateFrom, $dateTo, 100);
$incomeCategories = $financialManager->getAllCategories('income');
$expenseCategories = $financialManager->getAllCategories('expense');
$financialSummary = $financialManager->getFinancialSummary($dateFrom, $dateTo);

// Get monthly summary for chart
$monthlyData = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthStart = $month . '-01';
    $monthEnd = date('Y-m-t', strtotime($monthStart));
    
    $monthlySummary = $financialManager->getFinancialSummary($monthStart, $monthEnd);
    $monthlyData[] = [
        'month' => date('M Y', strtotime($monthStart)),
        'income' => $monthlySummary['income'],
        'expense' => $monthlySummary['expense'],
        'profit' => $monthlySummary['profit']
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Management - ModernShop Admin</title>
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
                        <h1 class="text-xl font-semibold text-gray-900">Financial Management</h1>
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

            <!-- Financial Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Income</dt>
                                    <dd class="text-lg font-medium text-gray-900"><?php echo formatCurrency($financialSummary['income']); ?></dd>
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Expenses</dt>
                                    <dd class="text-lg font-medium text-gray-900"><?php echo formatCurrency($financialSummary['expense']); ?></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 <?php echo $financialSummary['profit'] >= 0 ? 'bg-blue-500' : 'bg-orange-500'; ?> rounded-md flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Net Profit</dt>
                                    <dd class="text-lg font-medium <?php echo $financialSummary['profit'] >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                        <?php echo formatCurrency($financialSummary['profit']); ?>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chart -->
            <div class="bg-white shadow rounded-lg mb-8">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Financial Trend (Last 12 Months)</h3>
                    <canvas id="financialChart" width="400" height="100"></canvas>
                </div>
            </div>

            <!-- Filter and Actions -->
            <div class="mb-6 flex flex-wrap justify-between items-center gap-4">
                <div class="flex flex-wrap gap-4">
                    <form method="GET" class="flex flex-wrap gap-2">
                        <input type="date" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" 
                               class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <input type="date" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" 
                               class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <select name="type" class="border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Transactions</option>
                            <option value="income" <?php echo $type === 'income' ? 'selected' : ''; ?>>Income Only</option>
                            <option value="expense" <?php echo $type === 'expense' ? 'selected' : ''; ?>>Expenses Only</option>
                        </select>
                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                            Filter
                        </button>
                    </form>
                </div>
                <div class="flex gap-3">
                    <button onclick="showTransactionModal()" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                        Add Transaction
                    </button>
                    <button onclick="showCategoryModal()" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">
                        Add Category
                    </button>
                </div>
            </div>

            <!-- Transactions Table -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Financial Transactions</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Method</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($transactions as $transaction): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo formatDate($transaction['transaction_date']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $transaction['type'] === 'income' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo ucfirst($transaction['type']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo htmlspecialchars($transaction['category_name']); ?>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <?php echo htmlspecialchars($transaction['description']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium <?php echo $transaction['type'] === 'income' ? 'text-green-600' : 'text-red-600'; ?>">
                                        <?php echo ($transaction['type'] === 'income' ? '+' : '-') . formatCurrency($transaction['amount']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo ucfirst(str_replace('_', ' ', $transaction['payment_method'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($transaction['reference_number']); ?>
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

    <!-- Add Transaction Modal -->
    <div id="transactionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg p-6 w-full max-w-md">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Add Financial Transaction</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="add_transaction">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Type</label>
                        <select name="type" id="transaction_type" required onchange="updateCategories()" 
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select Type</option>
                            <option value="income">Income</option>
                            <option value="expense">Expense</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Category</label>
                        <select name="category_id" id="transaction_category" required 
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select Category</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Amount</label>
                        <input type="number" name="amount" step="0.01" min="0" required 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" rows="3" required 
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Transaction Date</label>
                        <input type="date" name="transaction_date" value="<?php echo date('Y-m-d'); ?>" required 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Payment Method</label>
                        <select name="payment_method" required 
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="cash">Cash</option>
                            <option value="bank">Bank Transfer</option>
                            <option value="card">Card</option>
                            <option value="mobile_payment">Mobile Payment</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Reference Number</label>
                        <input type="text" name="reference_number" 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeTransactionModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                            Add Transaction
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div id="categoryModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg p-6 w-full max-w-md">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Add Financial Category</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="add_category">
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Category Name</label>
                        <input type="text" name="category_name" required 
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Type</label>
                        <select name="category_type" required 
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Select Type</option>
                            <option value="income">Income</option>
                            <option value="expense">Expense</option>
                        </select>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="category_description" rows="3" 
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeCategoryModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700">
                            Add Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Categories data
        const incomeCategories = <?php echo json_encode($incomeCategories); ?>;
        const expenseCategories = <?php echo json_encode($expenseCategories); ?>;
        
        function showTransactionModal() {
            document.getElementById('transactionModal').classList.remove('hidden');
        }
        
        function closeTransactionModal() {
            document.getElementById('transactionModal').classList.add('hidden');
        }
        
        function showCategoryModal() {
            document.getElementById('categoryModal').classList.remove('hidden');
        }
        
        function closeCategoryModal() {
            document.getElementById('categoryModal').classList.add('hidden');
        }
        
        function updateCategories() {
            const type = document.getElementById('transaction_type').value;
            const categorySelect = document.getElementById('transaction_category');
            
            categorySelect.innerHTML = '<option value="">Select Category</option>';
            
            const categories = type === 'income' ? incomeCategories : expenseCategories;
            categories.forEach(category => {
                const option = document.createElement('option');
                option.value = category.id;
                option.textContent = category.name;
                categorySelect.appendChild(option);
            });
        }
        
        // Financial Chart
        const ctx = document.getElementById('financialChart').getContext('2d');
        const monthlyData = <?php echo json_encode($monthlyData); ?>;
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: monthlyData.map(d => d.month),
                datasets: [{
                    label: 'Income',
                    data: monthlyData.map(d => d.income),
                    borderColor: 'rgb(34, 197, 94)',
                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                    tension: 0.1
                }, {
                    label: 'Expenses',
                    data: monthlyData.map(d => d.expense),
                    borderColor: 'rgb(239, 68, 68)',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    tension: 0.1
                }, {
                    label: 'Profit',
                    data: monthlyData.map(d => d.profit),
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'BDT ' + value.toLocaleString();
                            }
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': BDT ' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
