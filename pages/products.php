<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$productManager = new ProductManager();
$categories = $productManager->getCategories();

// Get filter parameters
$selectedCategory = $_GET['category'] ?? '';
$searchQuery = $_GET['search'] ?? '';
$featured = isset($_GET['featured']) ? (int)$_GET['featured'] : null;
$sortBy = $_GET['sort'] ?? 'newest';

// Get products based on filters
$products = $productManager->getAllProducts($selectedCategory, $featured, $searchQuery);

// Sort products
switch ($sortBy) {
    case 'price-low':
        usort($products, function($a, $b) { return $a['price'] <=> $b['price']; });
        break;
    case 'price-high':
        usort($products, function($a, $b) { return $b['price'] <=> $a['price']; });
        break;
    case 'rating':
        usort($products, function($a, $b) { return $b['rating'] <=> $a['rating']; });
        break;
    case 'name':
        usort($products, function($a, $b) { return strcasecmp($a['name'], $b['name']); });
        break;
    default: // newest
        usort($products, function($a, $b) { return strtotime($b['created_at']) <=> strtotime($a['created_at']); });
}

$totalProducts = count($products);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $searchQuery ? 'Search Results for "' . htmlspecialchars($searchQuery) . '"' : 'Products' ?> - ModernShop</title>
    <meta name="description" content="Browse our extensive collection of premium technology products and gadgets at ModernShop.">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="<?= getBaseUrl() ?>/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../components/header.php'; ?>
    
    <main id="main-content" class="products-main">
        <div class="container">
            <!-- Page Header -->
            <div class="page-header">
                <div class="breadcrumb">
                    <a href="<?= getBaseUrl() ?>/index.php">Home</a>
                    <span class="breadcrumb-separator">/</span>
                    <span>Products</span>
                    <?php if ($selectedCategory): ?>
                        <span class="breadcrumb-separator">/</span>
                        <span><?= htmlspecialchars($categories[array_search($selectedCategory, array_column($categories, 'id'))]['name'] ?? $selectedCategory) ?></span>
                    <?php endif; ?>
                </div>
                
                <h1 class="page-title gradient-text font-display">
                    <?php if ($searchQuery): ?>
                        Search Results
                    <?php elseif ($featured): ?>
                        Featured Products
                    <?php elseif ($selectedCategory): ?>
                        <?= htmlspecialchars($categories[array_search($selectedCategory, array_column($categories, 'id'))]['name'] ?? $selectedCategory) ?>
                    <?php else: ?>
                        All Products
                    <?php endif; ?>
                </h1>
                
                <?php if ($searchQuery): ?>
                    <p class="page-subtitle">
                        Showing results for "<strong><?= htmlspecialchars($searchQuery) ?></strong>"
                    </p>
                <?php endif; ?>
            </div>

            <div class="products-layout">
                <!-- Filters Sidebar -->
                <aside class="filters-sidebar">
                    <div class="filters-header">
                        <h2>Filters</h2>
                        <?php if ($selectedCategory || $searchQuery || $featured): ?>
                            <a href="<?= getBaseUrl() ?>/pages/products.php" class="clear-filters">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                                Clear All
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Categories Filter -->
                    <div class="filter-section">
                        <h3 class="filter-title">Categories</h3>
                        <div class="filter-options">
                            <a href="<?= getBaseUrl() ?>/pages/products.php<?= $searchQuery ? '?search=' . urlencode($searchQuery) : '' ?>" 
                               class="filter-option <?= !$selectedCategory ? 'active' : '' ?>">
                                All Categories
                            </a>
                            <?php foreach ($categories as $category): ?>
                                <a href="<?= getBaseUrl() ?>/pages/products.php?category=<?= $category['id'] ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : '' ?>" 
                                   class="filter-option <?= $selectedCategory === $category['id'] ? 'active' : '' ?>">
                                    <?= htmlspecialchars($category['name']) ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Featured Filter -->
                    <div class="filter-section">
                        <h3 class="filter-title">Special</h3>
                        <div class="filter-options">
                            <a href="<?= getBaseUrl() ?>/pages/products.php?featured=1<?= $selectedCategory ? '&category=' . $selectedCategory : '' ?><?= $searchQuery ? '&search=' . urlencode($searchQuery) : '' ?>" 
                               class="filter-option <?= $featured ? 'active' : '' ?>">
                                Featured Products
                            </a>
                        </div>
                    </div>
                </aside>

                <!-- Products Content -->
                <div class="products-content">
                    <!-- Results Header -->
                    <div class="results-header">
                        <p class="results-count" role="status" aria-live="polite">
                            <?= $totalProducts ?> <?= $totalProducts === 1 ? 'product' : 'products' ?> found
                        </p>
                        
                        <!-- Sort Options -->
                        <div class="sort-container">
                            <label for="sort" class="sort-label">Sort by:</label>
                            <select id="sort" name="sort" class="sort-select" aria-label="Sort products">
                                <option value="newest" <?= $sortBy === 'newest' ? 'selected' : '' ?>>Newest</option>
                                <option value="name" <?= $sortBy === 'name' ? 'selected' : '' ?>>Name A-Z</option>
                                <option value="price-low" <?= $sortBy === 'price-low' ? 'selected' : '' ?>>Price: Low to High</option>
                                <option value="price-high" <?= $sortBy === 'price-high' ? 'selected' : '' ?>>Price: High to Low</option>
                                <option value="rating" <?= $sortBy === 'rating' ? 'selected' : '' ?>>Highest Rated</option>
                            </select>
                        </div>
                    </div>

                    <!-- Products Grid -->
                    <?php if (empty($products)): ?>
                        <div class="no-results">
                            <div class="no-results-icon">
                                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                                    <circle cx="11" cy="11" r="8"></circle>
                                    <path d="m21 21-4.35-4.35"></path>
                                </svg>
                            </div>
                            <h3>No products found</h3>
                            <p>Try adjusting your search or filter criteria</p>
                            <a href="<?= getBaseUrl() ?>/pages/products.php" class="btn btn-primary">
                                View All Products
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="product-grid">
                            <?php foreach ($products as $product): ?>
                                <div class="product-card animate-fade-in">
                                    <div class="product-image-container">
                                        <img 
                                            src="<?= htmlspecialchars($product['image']) ?>" 
                                            alt="<?= htmlspecialchars($product['name']) ?>"
                                            class="product-image"
                                            loading="lazy"
                                        >
                                        
                                        <!-- Product Actions -->
                                        <div class="product-actions">
                                            <a href="<?= getBaseUrl() ?>/pages/product-detail.php?id=<?= $product['id'] ?>" 
                                               class="product-action-btn"
                                               aria-label="View details for <?= htmlspecialchars($product['name']) ?>">
                                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                    <circle cx="12" cy="12" r="3"></circle>
                                                </svg>
                                            </a>
                                            
                                            <?php if ($product['in_stock']): ?>
                                                <button class="product-action-btn add-to-cart-btn" 
                                                        data-product-id="<?= $product['id'] ?>"
                                                        type="button"
                                                        aria-label="Add <?= htmlspecialchars($product['name']) ?> to cart">
                                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <circle cx="9" cy="21" r="1"></circle>
                                                        <circle cx="20" cy="21" r="1"></circle>
                                                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                                                    </svg>
                                                </button>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Stock Status -->
                                        <?php if (!$product['in_stock']): ?>
                                            <div class="stock-overlay">
                                                <span class="stock-status">Out of Stock</span>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Featured Badge -->
                                        <?php if ($product['featured']): ?>
                                            <div class="featured-badge">Featured</div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="product-info">
                                        <div class="product-category"><?= htmlspecialchars($product['category_name']) ?></div>
                                        <h3 class="product-title">
                                            <a href="<?= getBaseUrl() ?>/pages/product-detail.php?id=<?= $product['id'] ?>">
                                                <?= htmlspecialchars($product['name']) ?>
                                            </a>
                                        </h3>
                                        
                                        <div class="product-rating">
                                            <div class="rating-stars">
                                                <?php
                                                $rating = floatval($product['rating']);
                                                for ($i = 1; $i <= 5; $i++) {
                                                    if ($i <= $rating) {
                                                        echo '★';
                                                    } elseif ($i - 0.5 <= $rating) {
                                                        echo '☆';
                                                    } else {
                                                        echo '☆';
                                                    }
                                                }
                                                ?>
                                            </div>
                                            <span class="rating-text">(<?= $product['reviews'] ?>)</span>
                                        </div>
                                        
                                        <div class="product-price">
                                            <span class="price-current"><?= formatPrice($product['price']) ?></span>
                                            <?php if ($product['original_price']): ?>
                                                <span class="price-original"><?= formatPrice($product['original_price']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/../components/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Sort functionality
            const sortSelect = document.getElementById('sort');
            if (sortSelect) {
                sortSelect.addEventListener('change', function() {
                    const url = new URL(window.location);
                    url.searchParams.set('sort', this.value);
                    window.location.href = url.toString();
                });
            }

            // Add to cart functionality
            const addToCartButtons = document.querySelectorAll('.add-to-cart-btn');
            addToCartButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.dataset.productId;
                    
                    // Check if user is logged in
                    <?php if (!$auth->isLoggedIn()): ?>
                        alert('Please log in to add items to your cart');
                        window.location.href = '<?= getBaseUrl() ?>/pages/login.php';
                        return;
                    <?php endif; ?>
                    
                    // Add to cart via AJAX
                    fetch('<?= getBaseUrl() ?>/api/cart.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'add',
                            product_id: productId,
                            quantity: 1
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update cart count in header
                            const cartCount = document.querySelector('.cart-count');
                            if (cartCount) {
                                cartCount.textContent = data.cart_count;
                            } else if (data.cart_count > 0) {
                                // Create cart count element if it doesn't exist
                                const cartLink = document.querySelector('.cart-link');
                                if (cartLink) {
                                    const countElement = document.createElement('span');
                                    countElement.className = 'cart-count';
                                    countElement.textContent = data.cart_count;
                                    cartLink.appendChild(countElement);
                                }
                            }
                            
                            // Show success message
                            showNotification('Product added to cart!', 'success');
                        } else {
                            showNotification(data.error || 'Failed to add product to cart', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('Failed to add product to cart', 'error');
                    });
                });
            });

            // Animation on scroll
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };
            
            const observer = new IntersectionObserver(function(entries) {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);
            
            document.querySelectorAll('.animate-fade-in').forEach(el => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(20px)';
                el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                observer.observe(el);
            });
        });

        // Notification system
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.add('show');
            }, 100);
            
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 3000);
        }
    </script>
</body>
</html>

<style>
/* Products page styles */
.products-main {
    padding-top: 6rem;
    padding-bottom: 2rem;
    min-height: 100vh;
}

.page-header {
    margin-bottom: 3rem;
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: var(--text-secondary);
    margin-bottom: 1rem;
}

.breadcrumb a {
    color: var(--text-secondary);
    text-decoration: none;
    transition: color 0.3s ease;
}

.breadcrumb a:hover {
    color: var(--text-primary);
}

.breadcrumb-separator {
    color: var(--text-muted);
}

.page-title {
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.page-subtitle {
    color: var(--text-secondary);
    font-size: 1.125rem;
}

.products-layout {
    display: grid;
    grid-template-columns: 250px 1fr;
    gap: 3rem;
}

.filters-sidebar {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    border: 1px solid var(--glass-border);
    border-radius: 1rem;
    padding: 1.5rem;
    height: fit-content;
    position: sticky;
    top: 6rem;
}

.filters-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--glass-border);
}

.filters-header h2 {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary);
}

.clear-filters {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    color: var(--text-muted);
    text-decoration: none;
    font-size: 0.875rem;
    transition: color 0.3s ease;
}

.clear-filters:hover {
    color: var(--text-primary);
}

.filter-section {
    margin-bottom: 2rem;
}

.filter-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 1rem;
}

.filter-options {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.filter-option {
    padding: 0.5rem 0.75rem;
    color: var(--text-secondary);
    text-decoration: none;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    transition: all 0.3s ease;
}

.filter-option:hover {
    background: rgba(255, 255, 255, 0.05);
    color: var(--text-primary);
}

.filter-option.active {
    background: var(--primary-gradient);
    color: white;
}

.results-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.results-count {
    color: var(--text-secondary);
    font-size: 0.875rem;
}

.sort-container {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.sort-label {
    font-size: 0.875rem;
    color: var(--text-secondary);
}

.sort-select {
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    border-radius: 0.5rem;
    padding: 0.5rem 0.75rem;
    color: var(--text-primary);
    font-size: 0.875rem;
    cursor: pointer;
}

.product-actions {
    position: absolute;
    top: 1rem;
    right: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.product-card:hover .product-actions {
    opacity: 1;
}

.product-action-btn {
    width: 2.25rem;
    height: 2.25rem;
    background: var(--glass-bg);
    backdrop-filter: blur(10px);
    border: 1px solid var(--glass-border);
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-primary);
    text-decoration: none;
    transition: all 0.3s ease;
    cursor: pointer;
}

.product-action-btn:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-2px);
}

.stock-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.75rem 0.75rem 0 0;
}

.stock-status {
    color: white;
    font-weight: 600;
    font-size: 0.875rem;
}

.featured-badge {
    position: absolute;
    top: 1rem;
    left: 1rem;
    background: var(--secondary-gradient);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: 600;
}

.no-results {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--text-secondary);
}

.no-results-icon {
    margin-bottom: 1.5rem;
    color: var(--text-muted);
}

.no-results h3 {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

.no-results p {
    margin-bottom: 2rem;
}

/* Notification styles */
.notification {
    position: fixed;
    top: 6rem;
    right: 1rem;
    padding: 1rem 1.5rem;
    border-radius: 0.75rem;
    color: white;
    font-weight: 500;
    z-index: 1000;
    transform: translateX(100%);
    transition: transform 0.3s ease;
}

.notification.show {
    transform: translateX(0);
}

.notification-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.notification-error {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

@media (max-width: 768px) {
    .products-layout {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .filters-sidebar {
        position: static;
        order: 2;
    }
    
    .page-title {
        font-size: 2rem;
    }
    
    .results-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .sort-container {
        justify-content: space-between;
    }
}
</style>
