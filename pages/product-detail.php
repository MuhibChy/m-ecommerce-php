<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$auth = getAuth();
$productManager = new ProductManager();

// Get product ID
$productId = $_GET['id'] ?? null;
if (!$productId) {
    redirect('/pages/products.php');
}

// Get product details
$product = $productManager->getProductById($productId);
if (!$product) {
    redirect('/pages/products.php');
}

// Decode JSON fields
$specs = json_decode($product['specs'], true) ?: [];
$tags = json_decode($product['tags'], true) ?: [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?> - ModernShop</title>
    <meta name="description" content="<?= htmlspecialchars($product['description']) ?>">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="<?= getBaseUrl() ?>/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../components/header.php'; ?>
    
    <main id="main-content" class="product-detail-main">
        <div class="container">
            <!-- Breadcrumb -->
            <div class="breadcrumb">
                <a href="<?= getBaseUrl() ?>/index.php">Home</a>
                <span class="breadcrumb-separator">/</span>
                <a href="<?= getBaseUrl() ?>/pages/products.php">Products</a>
                <span class="breadcrumb-separator">/</span>
                <a href="<?= getBaseUrl() ?>/pages/products.php?category=<?= $product['category_id'] ?>">
                    <?= htmlspecialchars($product['category_name']) ?>
                </a>
                <span class="breadcrumb-separator">/</span>
                <span><?= htmlspecialchars($product['name']) ?></span>
            </div>

            <!-- Product Details -->
            <div class="product-detail-grid">
                <!-- Product Image -->
                <div class="product-image-section">
                    <div class="main-image-container">
                        <img 
                            src="<?= htmlspecialchars($product['image']) ?>" 
                            alt="<?= htmlspecialchars($product['name']) ?>"
                            class="main-image"
                        >
                        <?php if (!$product['in_stock']): ?>
                            <div class="stock-overlay">
                                <span class="stock-status">Out of Stock</span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Product Info -->
                <div class="product-info-section">
                    <div class="product-header">
                        <div class="product-category"><?= htmlspecialchars($product['category_name']) ?></div>
                        <h1 class="product-title"><?= htmlspecialchars($product['name']) ?></h1>
                        
                        <!-- Rating -->
                        <div class="product-rating">
                            <div class="rating-stars">
                                <?php
                                $rating = floatval($product['rating']);
                                for ($i = 1; $i <= 5; $i++) {
                                    echo $i <= $rating ? '★' : '☆';
                                }
                                ?>
                            </div>
                            <span class="rating-text"><?= $product['rating'] ?> (<?= $product['reviews'] ?> reviews)</span>
                        </div>

                        <!-- Price -->
                        <div class="product-price">
                            <span class="price-current"><?= formatPrice($product['price']) ?></span>
                            <?php if ($product['original_price']): ?>
                                <span class="price-original"><?= formatPrice($product['original_price']) ?></span>
                                <span class="price-discount">
                                    <?= round((($product['original_price'] - $product['price']) / $product['original_price']) * 100) ?>% OFF
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Stock Status -->
                        <div class="stock-status-info">
                            <?php if ($product['in_stock']): ?>
                                <div class="in-stock">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="20,6 9,17 4,12"></polyline>
                                    </svg>
                                    <span>In Stock</span>
                                </div>
                            <?php else: ?>
                                <div class="out-of-stock">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="15" y1="9" x2="9" y2="15"></line>
                                        <line x1="9" y1="9" x2="15" y2="15"></line>
                                    </svg>
                                    <span>Out of Stock</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Quantity and Actions -->
                    <?php if ($product['in_stock']): ?>
                        <div class="product-actions">
                            <div class="quantity-selector" aria-label="Quantity selector">
                                <label for="quantity" class="quantity-label">Quantity:</label>
                                <div class="quantity-controls">
                                    <button type="button" class="quantity-btn" id="decrease-qty" aria-label="Decrease quantity">-</button>
                                    <input type="number" id="quantity" value="1" min="1" max="10" class="quantity-input" aria-live="polite">
                                    <button type="button" class="quantity-btn" id="increase-qty" aria-label="Increase quantity">+</button>
                                </div>
                            </div>

                            <div class="action-buttons">
                                <button class="btn btn-primary add-to-cart-btn" data-product-id="<?= $product['id'] ?>">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="9" cy="21" r="1"></circle>
                                        <circle cx="20" cy="21" r="1"></circle>
                                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                                    </svg>
                                    Add to Cart
                                </button>
                                
                                <button class="btn btn-secondary buy-now-btn">
                                    Buy Now
                                </button>
                                
                                <button class="favorite-btn" aria-label="Add to favorites" aria-pressed="false">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Description -->
                    <?php if ($product['description']): ?>
                        <div class="product-description">
                            <h3>Description</h3>
                            <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                        </div>
                    <?php endif; ?>

                    <!-- Specifications -->
                    <?php if (!empty($specs)): ?>
                        <div class="product-specs">
                            <h3>Specifications</h3>
                            <ul>
                                <?php foreach ($specs as $spec): ?>
                                    <li><?= htmlspecialchars($spec) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <!-- Tags -->
                    <?php if (!empty($tags)): ?>
                        <div class="product-tags">
                            <h3>Tags</h3>
                            <div class="tags-list">
                                <?php foreach ($tags as $tag): ?>
                                    <span class="tag"><?= htmlspecialchars($tag) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/../components/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Quantity controls
            const quantityInput = document.getElementById('quantity');
            const decreaseBtn = document.getElementById('decrease-qty');
            const increaseBtn = document.getElementById('increase-qty');
            
            decreaseBtn.addEventListener('click', function() {
                const current = parseInt(quantityInput.value);
                if (current > 1) {
                    quantityInput.value = current - 1;
                }
            });
            
            increaseBtn.addEventListener('click', function() {
                const current = parseInt(quantityInput.value);
                if (current < 10) {
                    quantityInput.value = current + 1;
                }
            });

            // Add to cart
            const addToCartBtn = document.querySelector('.add-to-cart-btn');
            if (addToCartBtn) {
                addToCartBtn.addEventListener('click', function() {
                    const productId = this.dataset.productId;
                    const quantity = parseInt(quantityInput.value);
                    
                    <?php if (!$auth->isLoggedIn()): ?>
                        alert('Please log in to add items to your cart');
                        window.location.href = '<?= getBaseUrl() ?>/pages/login.php';
                        return;
                    <?php endif; ?>
                    
                    fetch('<?= getBaseUrl() ?>/api/cart.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'add',
                            product_id: productId,
                            quantity: quantity
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification('Product added to cart!', 'success');
                            // Update cart count
                            const cartCount = document.querySelector('.cart-count');
                            if (cartCount) {
                                cartCount.textContent = data.cart_count;
                            }
                        } else {
                            showNotification(data.error || 'Failed to add to cart', 'error');
                        }
                    })
                    .catch(error => {
                        showNotification('Failed to add to cart', 'error');
                    });
                });
            }

            // Favorite toggle
            const favoriteBtn = document.querySelector('.favorite-btn');
            if (favoriteBtn) {
                favoriteBtn.addEventListener('click', function() {
                    const isPressed = this.getAttribute('aria-pressed') === 'true';
                    this.setAttribute('aria-pressed', !isPressed);
                    this.classList.toggle('active');
                    
                    showNotification(
                        isPressed ? 'Removed from favorites' : 'Added to favorites',
                        'success'
                    );
                });
            }
        });

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => notification.classList.add('show'), 100);
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => document.body.removeChild(notification), 300);
            }, 3000);
        }
    </script>
</body>
</html>

<style>
.product-detail-main {
    padding-top: 6rem;
    padding-bottom: 2rem;
    min-height: 100vh;
}

.product-detail-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4rem;
    margin-top: 2rem;
}

.main-image-container {
    position: relative;
    background: var(--glass-bg);
    border-radius: 1rem;
    overflow: hidden;
    aspect-ratio: 1;
}

.main-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.product-info-section {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.product-title {
    font-size: 2rem;
    font-weight: bold;
    color: var(--text-primary);
    margin: 0.5rem 0;
}

.product-price {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin: 1rem 0;
}

.price-current {
    font-size: 2rem;
    font-weight: bold;
    color: var(--text-primary);
}

.price-discount {
    background: var(--secondary-gradient);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.875rem;
    font-weight: 600;
}

.stock-status-info .in-stock {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #10b981;
}

.stock-status-info .out-of-stock {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #ef4444;
}

.quantity-selector {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.quantity-controls {
    display: flex;
    align-items: center;
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    border-radius: 0.5rem;
}

.quantity-btn {
    width: 2.5rem;
    height: 2.5rem;
    background: none;
    border: none;
    color: var(--text-primary);
    cursor: pointer;
    font-size: 1.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.quantity-input {
    width: 3rem;
    height: 2.5rem;
    text-align: center;
    background: none;
    border: none;
    color: var(--text-primary);
}

.action-buttons {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.favorite-btn {
    width: 3rem;
    height: 3rem;
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-secondary);
    cursor: pointer;
    transition: all 0.3s ease;
}

.favorite-btn:hover,
.favorite-btn.active {
    color: #f43f5e;
    background: rgba(244, 63, 94, 0.1);
}

.product-description,
.product-specs,
.product-tags {
    background: var(--glass-bg);
    padding: 1.5rem;
    border-radius: 1rem;
    border: 1px solid var(--glass-border);
}

.product-specs ul {
    list-style: none;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.product-specs li {
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--glass-border);
}

.tags-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.tag {
    background: var(--primary-gradient);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.875rem;
}

@media (max-width: 768px) {
    .product-detail-grid {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .product-title {
        font-size: 1.5rem;
    }
    
    .action-buttons {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>
