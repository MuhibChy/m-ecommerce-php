<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$auth = getAuth();
$auth->requireAdmin();

$productManager = new ProductManager();
$categories = $productManager->getCategories();
$products = $productManager->getAllProducts();

$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add':
        case 'edit':
            handleProductSave($productManager, $action);
            break;
        case 'delete':
            handleProductDelete($productManager);
            break;
    }
    
    // Refresh products list
    $products = $productManager->getAllProducts();
}

function handleProductSave($productManager, $action) {
    global $error, $success;
    
    $data = [
        'name' => sanitizeInput($_POST['name'] ?? ''),
        'category_id' => sanitizeInput($_POST['category_id'] ?? ''),
        'price' => floatval($_POST['price'] ?? 0),
        'original_price' => $_POST['original_price'] ? floatval($_POST['original_price']) : null,
        'image' => sanitizeInput($_POST['image'] ?? ''),
        'rating' => floatval($_POST['rating'] ?? 4.5),
        'reviews' => intval($_POST['reviews'] ?? 0),
        'in_stock' => isset($_POST['in_stock']),
        'featured' => isset($_POST['featured']),
        'description' => sanitizeInput($_POST['description'] ?? ''),
        'specs' => array_filter(array_map('trim', explode("\n", $_POST['specs'] ?? ''))),
        'tags' => array_filter(array_map('trim', explode(',', $_POST['tags'] ?? '')))
    ];
    
    // Validation
    if (empty($data['name']) || empty($data['category_id']) || $data['price'] <= 0 || empty($data['image'])) {
        $error = 'Please fill in all required fields';
        return;
    }
    
    if ($action === 'add') {
        if ($productManager->addProduct($data)) {
            $success = 'Product added successfully!';
        } else {
            $error = 'Failed to add product';
        }
    } else {
        $productId = intval($_POST['product_id'] ?? 0);
        if ($productManager->updateProduct($productId, $data)) {
            $success = 'Product updated successfully!';
        } else {
            $error = 'Failed to update product';
        }
    }
}

function handleProductDelete($productManager) {
    global $error, $success;
    
    $productId = intval($_POST['product_id'] ?? 0);
    if ($productManager->deleteProduct($productId)) {
        $success = 'Product deleted successfully!';
    } else {
        $error = 'Failed to delete product';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Product Management - ModernShop</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="<?= getBaseUrl() ?>/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../components/header.php'; ?>
    
    <main id="main-content" class="admin-main">
        <div class="container">
            <!-- Header -->
            <div class="admin-header">
                <div class="admin-title-section">
                    <h1 class="admin-title gradient-text font-display">Product Management</h1>
                    <p class="admin-subtitle">Add, edit, or remove products from your catalog</p>
                </div>
                
                <button class="btn btn-primary" onclick="openProductModal()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Add New Product
                </button>
            </div>

            <!-- Admin Info -->
            <div class="admin-info">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <span>Logged in as: <strong><?= htmlspecialchars($auth->getCurrentUser()['email']) ?></strong></span>
            </div>

            <!-- Messages -->
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="15" y1="9" x2="9" y2="15"></line>
                        <line x1="9" y1="9" x2="15" y2="15"></line>
                    </svg>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="20,6 9,17 4,12"></polyline>
                    </svg>
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <!-- Products List -->
            <div class="products-list">
                <?php if (empty($products)): ?>
                    <div class="no-products">
                        <h3>No products found</h3>
                        <p>Add your first product to get started!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <div class="product-item">
                            <div class="product-image">
                                <img src="<?= htmlspecialchars($product['image']) ?>" 
                                     alt="<?= htmlspecialchars($product['name']) ?>"
                                     onerror="this.src='https://via.placeholder.com/150x150?text=No+Image'">
                            </div>
                            
                            <div class="product-details">
                                <div class="product-main-info">
                                    <h3 class="product-name"><?= htmlspecialchars($product['name']) ?></h3>
                                    <p class="product-category"><?= htmlspecialchars($product['category_name']) ?></p>
                                </div>
                                
                                <div class="product-meta">
                                    <div class="meta-item">
                                        <span class="meta-label">Price:</span>
                                        <span class="meta-value"><?= formatPrice($product['price']) ?></span>
                                        <?php if ($product['original_price']): ?>
                                            <span class="original-price"><?= formatPrice($product['original_price']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="meta-item">
                                        <span class="meta-label">Rating:</span>
                                        <span class="meta-value"><?= $product['rating'] ?> ★ (<?= $product['reviews'] ?> reviews)</span>
                                    </div>
                                    
                                    <div class="meta-item">
                                        <span class="meta-label">Status:</span>
                                        <span class="meta-value status-<?= $product['in_stock'] ? 'in-stock' : 'out-of-stock' ?>">
                                            <?= $product['in_stock'] ? 'In Stock' : 'Out of Stock' ?>
                                        </span>
                                    </div>
                                    
                                    <div class="meta-item">
                                        <span class="meta-label">Featured:</span>
                                        <span class="meta-value"><?= $product['featured'] ? 'Yes' : 'No' ?></span>
                                    </div>
                                </div>
                                
                                <?php 
                                $tags = json_decode($product['tags'], true);
                                if (!empty($tags)): 
                                ?>
                                    <div class="product-tags">
                                        <?php foreach ($tags as $tag): ?>
                                            <span class="tag"><?= htmlspecialchars($tag) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-actions">
                                <button class="btn-icon" onclick="editProduct(<?= htmlspecialchars(json_encode($product)) ?>)" title="Edit Product">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                    </svg>
                                </button>
                                
                                <button class="btn-icon btn-danger" onclick="deleteProduct(<?= $product['id'] ?>, '<?= htmlspecialchars($product['name']) ?>')" title="Delete Product">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3,6 5,6 21,6"></polyline>
                                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Product Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add New Product</h2>
                <button class="modal-close" onclick="closeProductModal()">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
            
            <form id="productForm" method="POST" class="modal-form">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="product_id" id="productId">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="name" class="form-label">Product Name *</label>
                        <input type="text" id="name" name="name" class="input" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="category_id" class="form-label">Category *</label>
                        <select id="category_id" name="category_id" class="input" required>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="price" class="form-label">Price ($) *</label>
                        <input type="number" id="price" name="price" class="input" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="original_price" class="form-label">Original Price ($)</label>
                        <input type="number" id="original_price" name="original_price" class="input" step="0.01" min="0">
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="image" class="form-label">Image URL *</label>
                        <input type="url" id="image" name="image" class="input" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="rating" class="form-label">Rating (0-5)</label>
                        <input type="number" id="rating" name="rating" class="input" step="0.1" min="0" max="5" value="4.5">
                    </div>
                    
                    <div class="form-group">
                        <label for="reviews" class="form-label">Reviews Count</label>
                        <input type="number" id="reviews" name="reviews" class="input" min="0" value="0">
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="in_stock" id="in_stock" class="checkbox" checked>
                                <span>In Stock</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="checkbox-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="featured" id="featured" class="checkbox">
                                <span>Featured Product</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="description" class="form-label">Description</label>
                        <textarea id="description" name="description" class="input" rows="4"></textarea>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="specs" class="form-label">Specifications (one per line)</label>
                        <textarea id="specs" name="specs" class="input" rows="4" placeholder="Intel i9-13900H&#10;32GB RAM&#10;1TB SSD"></textarea>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="tags" class="form-label">Tags (comma separated)</label>
                        <input type="text" id="tags" name="tags" class="input" placeholder="gaming, high-performance, new">
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeProductModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                            <polyline points="17,21 17,13 7,13 7,21"></polyline>
                            <polyline points="7,3 7,8 15,8"></polyline>
                        </svg>
                        <span id="submitText">Save Product</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Form (hidden) -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="product_id" id="deleteProductId">
    </form>
    
    <?php include __DIR__ . '/../components/footer.php'; ?>

    <script>
        function openProductModal(product = null) {
            const modal = document.getElementById('productModal');
            const form = document.getElementById('productForm');
            const modalTitle = document.getElementById('modalTitle');
            const formAction = document.getElementById('formAction');
            const submitText = document.getElementById('submitText');
            
            if (product) {
                // Edit mode
                modalTitle.textContent = 'Edit Product';
                formAction.value = 'edit';
                submitText.textContent = 'Update Product';
                
                // Fill form with product data
                document.getElementById('productId').value = product.id;
                document.getElementById('name').value = product.name;
                document.getElementById('category_id').value = product.category_id;
                document.getElementById('price').value = product.price;
                document.getElementById('original_price').value = product.original_price || '';
                document.getElementById('image').value = product.image;
                document.getElementById('rating').value = product.rating;
                document.getElementById('reviews').value = product.reviews;
                document.getElementById('in_stock').checked = product.in_stock == 1;
                document.getElementById('featured').checked = product.featured == 1;
                document.getElementById('description').value = product.description || '';
                
                // Handle specs and tags
                const specs = JSON.parse(product.specs || '[]');
                document.getElementById('specs').value = specs.join('\n');
                
                const tags = JSON.parse(product.tags || '[]');
                document.getElementById('tags').value = tags.join(', ');
            } else {
                // Add mode
                modalTitle.textContent = 'Add New Product';
                formAction.value = 'add';
                submitText.textContent = 'Save Product';
                form.reset();
                document.getElementById('rating').value = '4.5';
                document.getElementById('reviews').value = '0';
                document.getElementById('in_stock').checked = true;
            }
            
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        
        function closeProductModal() {
            const modal = document.getElementById('productModal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        
        function editProduct(product) {
            openProductModal(product);
        }
        
        function deleteProduct(productId, productName) {
            if (confirm(`Are you sure you want to delete "${productName}"? This action cannot be undone.`)) {
                document.getElementById('deleteProductId').value = productId;
                document.getElementById('deleteForm').submit();
            }
        }
        
        // Close modal when clicking outside
        document.getElementById('productModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeProductModal();
            }
        });
        
        // Form validation
        document.getElementById('productForm').addEventListener('submit', function(e) {
            const name = document.getElementById('name').value.trim();
            const price = parseFloat(document.getElementById('price').value);
            const image = document.getElementById('image').value.trim();
            
            if (!name || price <= 0 || !image) {
                e.preventDefault();
                alert('Please fill in all required fields with valid values.');
                return;
            }
        });
    </script>
</body>
</html>

<style>
.admin-main {
    padding-top: 6rem;
    padding-bottom: 2rem;
    min-height: 100vh;
}

.admin-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.admin-title {
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.admin-subtitle {
    color: var(--text-secondary);
    font-size: 1rem;
}

.admin-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background: rgba(147, 51, 234, 0.1);
    border: 1px solid rgba(147, 51, 234, 0.2);
    border-radius: 0.75rem;
    padding: 1rem;
    margin-bottom: 2rem;
    color: #c084fc;
    font-size: 0.875rem;
}

.products-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.product-item {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    border: 1px solid var(--glass-border);
    border-radius: 1rem;
    padding: 1.5rem;
    display: grid;
    grid-template-columns: 150px 1fr auto;
    gap: 1.5rem;
    align-items: start;
}

.product-image img {
    width: 150px;
    height: 150px;
    object-fit: cover;
    border-radius: 0.75rem;
    background: var(--glass-bg);
}

.product-details {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.product-name {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
}

.product-category {
    color: var(--text-muted);
    font-size: 0.875rem;
}

.product-meta {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 0.75rem;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
}

.meta-label {
    color: var(--text-muted);
    font-weight: 500;
}

.meta-value {
    color: var(--text-primary);
}

.original-price {
    color: var(--text-muted);
    text-decoration: line-through;
    font-size: 0.75rem;
}

.status-in-stock {
    color: #10b981;
}

.status-out-of-stock {
    color: #ef4444;
}

.product-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.product-actions {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.btn-icon {
    width: 2.5rem;
    height: 2.5rem;
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    border-radius: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-secondary);
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-icon:hover {
    background: rgba(255, 255, 255, 0.1);
    color: var(--text-primary);
}

.btn-danger:hover {
    background: rgba(239, 68, 68, 0.1);
    color: #ef4444;
    border-color: rgba(239, 68, 68, 0.3);
}

.modal {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.8);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 2rem;
}

.modal-content {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    border: 1px solid var(--glass-border);
    border-radius: 1.5rem;
    width: 100%;
    max-width: 800px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 2rem 2rem 1rem;
    border-bottom: 1px solid var(--glass-border);
}

.modal-header h2 {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-primary);
}

.modal-close {
    background: none;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 0.5rem;
    transition: all 0.3s ease;
}

.modal-close:hover {
    background: rgba(255, 255, 255, 0.1);
    color: var(--text-primary);
}

.modal-form {
    padding: 2rem;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.checkbox-group {
    display: flex;
    align-items: center;
    height: 100%;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--glass-border);
}

.no-products {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--text-secondary);
}

.no-products h3 {
    font-size: 1.5rem;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

@media (max-width: 768px) {
    .product-item {
        grid-template-columns: 1fr;
        text-align: center;
    }
    
    .product-actions {
        flex-direction: row;
        justify-content: center;
    }
    
    .form-grid {
        grid-template-columns: 1fr;
    }
    
    .admin-header {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>
