<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$auth = getAuth();
$user = $auth->getCurrentUser();
$isLoggedIn = $auth->isLoggedIn();

// Get cart count if user is logged in
$cartCount = 0;
if ($isLoggedIn) {
    $cartManager = new CartManager();
    $cartCount = $cartManager->getCartCount($user['id']);
}

// Get categories for navigation
$productManager = new ProductManager();
$categories = $productManager->getCategories();
?>

<header class="header">
    <div class="container">
        <div class="header-content">
            <!-- Skip Link -->
            <a href="#main-content" class="skip-link">Skip to main content</a>
            
            <!-- Logo -->
            <a href="<?= getBaseUrl() ?>/index.php" class="logo">
                <div class="logo-icon">M</div>
                <span class="gradient-text font-display">ModernShop</span>
            </a>
            
            <!-- Search Bar -->
            <form class="search-form" method="GET" action="<?= getBaseUrl() ?>/pages/products.php" role="search" aria-label="Site search">
                <input 
                    type="text" 
                    name="search" 
                    class="input search-input" 
                    placeholder="Search products..."
                    value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>"
                    aria-label="Search products"
                >
                <button type="submit" class="search-btn" aria-label="Submit search">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.35-4.35"></path>
                    </svg>
                </button>
            </form>
            
            <!-- Navigation -->
            <nav class="nav" role="navigation" aria-label="Primary">
                <a href="<?= getBaseUrl() ?>/index.php" class="nav-link">Home</a>
                
                <!-- Categories Dropdown -->
                <div class="nav-dropdown">
                    <button class="nav-link dropdown-toggle" aria-haspopup="menu" aria-expanded="false">
                        Categories
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6,9 12,15 18,9"></polyline>
                        </svg>
                    </button>
                    <div class="dropdown-menu" role="menu" aria-label="Product categories">
                        <a href="<?= getBaseUrl() ?>/pages/products.php" class="dropdown-item">All Products</a>
                        <?php foreach ($categories as $category): ?>
                            <a href="<?= getBaseUrl() ?>/pages/products.php?category=<?= $category['id'] ?>" class="dropdown-item">
                                <?= htmlspecialchars($category['name']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <a href="<?= getBaseUrl() ?>/pages/contact.php" class="nav-link">Contact</a>
                <a href="<?= getBaseUrl() ?>/pages/support.php" class="nav-link">Support</a>
            </nav>
            
            <!-- Actions -->
            <div class="nav-actions">
                <?php if ($isLoggedIn): ?>
                    <!-- User Account -->
                    <a href="<?= getBaseUrl() ?>/pages/account.php" class="btn-ghost" aria-label="Go to account">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <span><?= htmlspecialchars($user['name']) ?></span>
                    </a>
                    
                    <!-- Cart -->
                    <a href="<?= getBaseUrl() ?>/pages/cart.php" class="btn-ghost cart-link" aria-label="View cart">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>
                        <?php if ($cartCount > 0): ?>
                            <span class="cart-count"><?= $cartCount ?></span>
                        <?php endif; ?>
                    </a>
                    
                    <!-- Admin Link -->
                    <?php if ($auth->isAdmin()): ?>
                        <a href="<?= getBaseUrl() ?>/admin/dashboard.php" class="btn-ghost">Admin</a>
                    <?php endif; ?>
                    
                    <!-- Logout -->
                    <a href="<?= getBaseUrl() ?>/pages/logout.php" class="btn-secondary">Logout</a>
                <?php else: ?>
                    <!-- Login/Register -->
                    <a href="<?= getBaseUrl() ?>/pages/login.php" class="btn-ghost">Login</a>
                    <a href="<?= getBaseUrl() ?>/pages/register.php" class="btn-primary">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<style>
/* Additional header styles */
.nav-dropdown {
    position: relative;
}

.dropdown-toggle {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    background: none;
    border: none;
    cursor: pointer;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    left: 0;
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    border: 1px solid var(--glass-border);
    border-radius: 0.75rem;
    padding: 0.5rem 0;
    min-width: 200px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    z-index: 1000;
}

.nav-dropdown:hover .dropdown-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.dropdown-item {
    display: block;
    padding: 0.5rem 1rem;
    color: var(--text-secondary);
    text-decoration: none;
    transition: all 0.3s ease;
}

.dropdown-item:hover {
    background: rgba(255, 255, 255, 0.1);
    color: var(--text-primary);
}

.cart-link {
    position: relative;
}

.cart-count {
    position: absolute;
    top: -8px;
    right: -8px;
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    font-size: 0.75rem;
    font-weight: bold;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: pulse 2s infinite;
}

@media (max-width: 768px) {
    .search-form {
        display: none;
    }
    
    .nav {
        display: none;
    }
    
    .nav-actions {
        gap: 0.5rem;
    }
    
    .nav-actions .btn-ghost span {
        display: none;
    }
}
</style>

<script>
// Simple dropdown functionality
document.addEventListener('DOMContentLoaded', function() {
    const dropdownToggle = document.querySelector('.dropdown-toggle');
    const dropdownMenu = document.querySelector('.dropdown-menu');
    
    if (dropdownToggle && dropdownMenu) {
        dropdownToggle.addEventListener('click', function(e) {
            e.preventDefault();
            const isOpen = dropdownMenu.style.opacity === '1';
            
            if (isOpen) {
                dropdownMenu.style.opacity = '0';
                dropdownMenu.style.visibility = 'hidden';
                dropdownMenu.style.transform = 'translateY(-10px)';
                dropdownToggle.setAttribute('aria-expanded', 'false');
            } else {
                dropdownMenu.style.opacity = '1';
                dropdownMenu.style.visibility = 'visible';
                dropdownMenu.style.transform = 'translateY(0)';
                dropdownToggle.setAttribute('aria-expanded', 'true');
            }
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!dropdownToggle.contains(e.target) && !dropdownMenu.contains(e.target)) {
                dropdownMenu.style.opacity = '0';
                dropdownMenu.style.visibility = 'hidden';
                dropdownMenu.style.transform = 'translateY(-10px)';
                dropdownToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }
});
</script>
