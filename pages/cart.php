<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$auth = getAuth();
$auth->requireLogin();

$user = $auth->getCurrentUser();
$cartManager = new CartManager();
$cartItems = $cartManager->getCartItems($user['id']);

// Calculate totals
$subtotal = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$shipping = $subtotal > 99 ? 0 : 9.99;
$tax = $subtotal * 0.08; // 8% tax
$total = $subtotal + $shipping + $tax;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - ModernShop</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="<?= getBaseUrl() ?>/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../components/header.php'; ?>
    
    <main id="main-content" class="cart-main">
        <div class="container">
            <!-- Breadcrumb -->
            <div class="breadcrumb">
                <a href="<?= getBaseUrl() ?>/index.php">Home</a>
                <span class="breadcrumb-separator">/</span>
                <span>Shopping Cart</span>
            </div>

            <h1 class="page-title gradient-text font-display">Shopping Cart</h1>

            <?php if (empty($cartItems)): ?>
                <!-- Empty Cart -->
                <div class="empty-cart">
                    <div class="empty-cart-icon">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>
                    </div>
                    <h3>Your cart is empty</h3>
                    <p>Add some products to get started!</p>
                    <a href="<?= getBaseUrl() ?>/pages/products.php" class="btn btn-primary">
                        Continue Shopping
                    </a>
                </div>
            <?php else: ?>
                <!-- Cart Content -->
                <div class="cart-layout">
                    <!-- Cart Items -->
                    <div class="cart-items">
                        <div class="cart-header">
                            <h2>Items in your cart (<?= count($cartItems) ?>)</h2>
                        </div>

                        <?php foreach ($cartItems as $item): ?>
                            <div class="cart-item" data-product-id="<?= $item['product_id'] ?>">
                                <div class="item-image">
                                    <img src="<?= htmlspecialchars($item['image']) ?>" 
                                         alt="<?= htmlspecialchars($item['name']) ?>">
                                </div>
                                
                                <div class="item-details">
                                    <h3 class="item-name">
                                        <a href="<?= getBaseUrl() ?>/pages/product-detail.php?id=<?= $item['product_id'] ?>">
                                            <?= htmlspecialchars($item['name']) ?>
                                        </a>
                                    </h3>
                                    <p class="item-price"><?= formatPrice($item['price']) ?></p>
                                </div>
                                
                                <div class="item-quantity">
                                    <label for="qty-<?= $item['product_id'] ?>" class="quantity-label">Quantity:</label>
                                    <div class="quantity-controls">
                                        <button type="button" class="quantity-btn decrease-btn" 
                                                data-product-id="<?= $item['product_id'] ?>"
                                                aria-label="Decrease quantity">-</button>
                                        <input type="number" 
                                               id="qty-<?= $item['product_id'] ?>"
                                               class="quantity-input" 
                                               value="<?= $item['quantity'] ?>" 
                                               min="1" 
                                               max="10"
                                               data-product-id="<?= $item['product_id'] ?>"
                                               aria-live="polite">
                                        <button type="button" class="quantity-btn increase-btn" 
                                                data-product-id="<?= $item['product_id'] ?>"
                                                aria-label="Increase quantity">+</button>
                                    </div>
                                </div>
                                
                                <div class="item-total">
                                    <span class="total-price"><?= formatPrice($item['price'] * $item['quantity']) ?></span>
                                </div>
                                
                                <div class="item-actions">
                                    <button type="button" class="remove-btn" 
                                            data-product-id="<?= $item['product_id'] ?>"
                                            aria-label="Remove <?= htmlspecialchars($item['name']) ?> from cart">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="3,6 5,6 21,6"></polyline>
                                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Order Summary -->
                    <div class="order-summary">
                        <div class="summary-card">
                            <h3>Order Summary</h3>
                            
                            <div class="summary-line">
                                <span>Subtotal:</span>
                                <span id="subtotal"><?= formatPrice($subtotal) ?></span>
                            </div>
                            
                            <div class="summary-line">
                                <span>Shipping:</span>
                                <span id="shipping">
                                    <?php if ($shipping > 0): ?>
                                        <?= formatPrice($shipping) ?>
                                    <?php else: ?>
                                        <span class="free-shipping">FREE</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            
                            <div class="summary-line">
                                <span>Tax:</span>
                                <span id="tax"><?= formatPrice($tax) ?></span>
                            </div>
                            
                            <div class="summary-line total-line">
                                <span>Total:</span>
                                <span id="total"><?= formatPrice($total) ?></span>
                            </div>
                            
                            <?php if ($subtotal < 99): ?>
                                <div class="shipping-notice">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="12" y1="8" x2="12" y2="12"></line>
                                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                    </svg>
                                    <span>Add <?= formatPrice(99 - $subtotal) ?> more for free shipping!</span>
                                </div>
                            <?php endif; ?>
                            
                            <button class="btn btn-primary btn-full checkout-btn">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="1" y="3" width="15" height="13"></rect>
                                    <path d="M16 8h4l-4-4v4z"></path>
                                </svg>
                                Proceed to Checkout
                            </button>
                            
                            <a href="<?= getBaseUrl() ?>/pages/products.php" class="continue-shopping">
                                Continue Shopping
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <?php include __DIR__ . '/../components/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Quantity controls
            document.querySelectorAll('.decrease-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const productId = this.dataset.productId;
                    const input = document.querySelector(`#qty-${productId}`);
                    const currentValue = parseInt(input.value);
                    
                    if (currentValue > 1) {
                        updateQuantity(productId, currentValue - 1);
                    }
                });
            });
            
            document.querySelectorAll('.increase-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const productId = this.dataset.productId;
                    const input = document.querySelector(`#qty-${productId}`);
                    const currentValue = parseInt(input.value);
                    
                    if (currentValue < 10) {
                        updateQuantity(productId, currentValue + 1);
                    }
                });
            });
            
            // Quantity input change
            document.querySelectorAll('.quantity-input').forEach(input => {
                input.addEventListener('change', function() {
                    const productId = this.dataset.productId;
                    const quantity = parseInt(this.value);
                    
                    if (quantity >= 1 && quantity <= 10) {
                        updateQuantity(productId, quantity);
                    } else {
                        this.value = quantity < 1 ? 1 : 10;
                    }
                });
            });
            
            // Remove buttons
            document.querySelectorAll('.remove-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const productId = this.dataset.productId;
                    removeFromCart(productId);
                });
            });
            
            // Checkout button
            const checkoutBtn = document.querySelector('.checkout-btn');
            if (checkoutBtn) {
                checkoutBtn.addEventListener('click', function() {
                    alert('Checkout functionality would be implemented here!');
                });
            }
        });
        
        function updateQuantity(productId, quantity) {
            fetch('<?= getBaseUrl() ?>/api/cart.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: quantity
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload(); // Refresh to update totals
                } else {
                    showNotification(data.error || 'Failed to update quantity', 'error');
                }
            })
            .catch(error => {
                showNotification('Failed to update quantity', 'error');
            });
        }
        
        function removeFromCart(productId) {
            if (!confirm('Are you sure you want to remove this item from your cart?')) {
                return;
            }
            
            fetch('<?= getBaseUrl() ?>/api/cart.php', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    product_id: productId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    showNotification(data.error || 'Failed to remove item', 'error');
                }
            })
            .catch(error => {
                showNotification('Failed to remove item', 'error');
            });
        }
        
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
.cart-main {
    padding-top: 6rem;
    padding-bottom: 2rem;
    min-height: 100vh;
}

.page-title {
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 2rem;
}

.empty-cart {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--text-secondary);
}

.empty-cart-icon {
    margin-bottom: 1.5rem;
    color: var(--text-muted);
}

.empty-cart h3 {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

.empty-cart p {
    margin-bottom: 2rem;
}

.cart-layout {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 3rem;
}

.cart-items {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.cart-header h2 {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 1.5rem;
}

.cart-item {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    border: 1px solid var(--glass-border);
    border-radius: 1rem;
    padding: 1.5rem;
    display: grid;
    grid-template-columns: 100px 1fr auto auto auto;
    gap: 1.5rem;
    align-items: center;
}

.item-image img {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: 0.75rem;
}

.item-details {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.item-name a {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--text-primary);
    text-decoration: none;
    transition: color 0.3s ease;
}

.item-name a:hover {
    color: #60a5fa;
}

.item-price {
    font-size: 1rem;
    color: var(--text-secondary);
}

.item-quantity {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    align-items: center;
}

.quantity-label {
    font-size: 0.875rem;
    color: var(--text-secondary);
}

.quantity-controls {
    display: flex;
    align-items: center;
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    border-radius: 0.5rem;
}

.quantity-btn {
    width: 2rem;
    height: 2rem;
    background: none;
    border: none;
    color: var(--text-primary);
    cursor: pointer;
    font-size: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.3s ease;
}

.quantity-btn:hover {
    background: rgba(255, 255, 255, 0.1);
}

.quantity-input {
    width: 2.5rem;
    height: 2rem;
    text-align: center;
    background: none;
    border: none;
    color: var(--text-primary);
    font-size: 0.875rem;
}

.item-total {
    text-align: right;
}

.total-price {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--text-primary);
}

.remove-btn {
    width: 2rem;
    height: 2rem;
    background: none;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    border-radius: 0.25rem;
    transition: all 0.3s ease;
}

.remove-btn:hover {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
}

.order-summary {
    position: sticky;
    top: 6rem;
    height: fit-content;
}

.summary-card {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    border: 1px solid var(--glass-border);
    border-radius: 1rem;
    padding: 2rem;
}

.summary-card h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 1.5rem;
}

.summary-line {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--glass-border);
    font-size: 0.875rem;
}

.summary-line:last-of-type {
    border-bottom: none;
}

.total-line {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-top: 0.5rem;
    padding-top: 1rem;
    border-top: 2px solid var(--glass-border);
}

.free-shipping {
    color: #10b981;
    font-weight: 600;
}

.shipping-notice {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(59, 130, 246, 0.1);
    border: 1px solid rgba(59, 130, 246, 0.2);
    border-radius: 0.5rem;
    padding: 0.75rem;
    margin: 1rem 0;
    color: #60a5fa;
    font-size: 0.875rem;
}

.checkout-btn {
    width: 100%;
    margin: 1.5rem 0 1rem;
    padding: 1rem;
    font-size: 1rem;
    font-weight: 600;
}

.continue-shopping {
    display: block;
    text-align: center;
    color: var(--text-secondary);
    text-decoration: none;
    font-size: 0.875rem;
    transition: color 0.3s ease;
}

.continue-shopping:hover {
    color: var(--text-primary);
}

@media (max-width: 768px) {
    .cart-layout {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .cart-item {
        grid-template-columns: 80px 1fr;
        grid-template-rows: auto auto auto;
        gap: 1rem;
    }
    
    .item-image {
        grid-row: 1 / 3;
    }
    
    .item-details {
        grid-column: 2;
        grid-row: 1;
    }
    
    .item-quantity,
    .item-total,
    .item-actions {
        grid-column: 1 / -1;
        grid-row: 3;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .order-summary {
        position: static;
        order: -1;
    }
}
</style>
