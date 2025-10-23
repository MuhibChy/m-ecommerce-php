<?php
/**
 * Admin Access Page - Entry point for admin area
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once __DIR__ . '/../includes/auth.php';
    $auth = getAuth();
    
    // If user is already logged in and is admin, redirect to dashboard
    if ($auth->isLoggedIn() && $auth->isAdmin()) {
        header('Location: dashboard_simple.php');
        exit;
    }
    
    // If user is logged in but not admin, show access denied
    if ($auth->isLoggedIn() && !$auth->isAdmin()) {
        $accessDenied = true;
    }
    
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Access - ModernShop</title>
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
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <div class="mx-auto h-12 w-12 flex items-center justify-center rounded-full bg-blue-100">
                    <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Admin Access
                </h2>
                <p class="mt-2 text-center text-sm text-gray-600">
                    ModernShop Administration Panel
                </p>
            </div>

            <?php if (isset($error)): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                    <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($accessDenied)): ?>
                <div class="bg-red-50 border border-red-200 rounded-lg p-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Access Denied</h3>
                            <div class="mt-2 text-sm text-red-700">
                                <p>You need administrator privileges to access this area.</p>
                            </div>
                            <div class="mt-4">
                                <div class="-mx-2 -my-1.5 flex">
                                    <a href="../pages/logout.php" class="bg-red-100 px-2 py-1.5 rounded-md text-sm font-medium text-red-800 hover:bg-red-200 mr-2">
                                        Logout
                                    </a>
                                    <a href="../index.php" class="bg-red-100 px-2 py-1.5 rounded-md text-sm font-medium text-red-800 hover:bg-red-200">
                                        Go Home
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
                    <div class="space-y-6">
                        <div class="text-center">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Admin Login Required</h3>
                            <p class="text-sm text-gray-600 mb-6">Please log in with your administrator account to access the admin panel.</p>
                        </div>

                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-blue-800 mb-2">Default Admin Credentials:</h4>
                            <div class="text-sm text-blue-700">
                                <p><strong>Email:</strong> admin@modernshop.com</p>
                                <p><strong>Password:</strong> admin123</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <a href="../pages/login.php" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Login to Admin Panel
                            </a>
                            
                            <div class="text-center">
                                <span class="text-sm text-gray-500">or</span>
                            </div>
                            
                            <a href="../index.php" class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Visit Main Website
                            </a>
                        </div>

                        <div class="mt-6 border-t border-gray-200 pt-6">
                            <h4 class="text-sm font-medium text-gray-900 mb-3">Quick Setup Links:</h4>
                            <div class="space-y-2">
                                <a href="../setup_database.php" class="block text-sm text-blue-600 hover:text-blue-800">
                                    🔧 Database Setup
                                </a>
                                <a href="../diagnostic.php" class="block text-sm text-blue-600 hover:text-blue-800">
                                    🔍 System Diagnostic
                                </a>
                                <a href="dashboard_simple.php" class="block text-sm text-blue-600 hover:text-blue-800">
                                    📊 Simple Dashboard (if logged in)
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="text-center">
                <p class="text-xs text-gray-500">
                    ModernShop Admin Panel v2.0
                </p>
            </div>
        </div>
    </div>
</body>
</html>
