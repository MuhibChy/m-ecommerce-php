<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$productManager = new ProductManager();
$featuredProducts = $productManager->getFeaturedProducts();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ModernShop - Premium E-commerce Experience</title>
    <meta name="description" content="Discover cutting-edge technology and premium products at ModernShop. Your trusted destination for the latest gadgets and electronics.">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="<?= getBaseUrl() ?>/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/components/header.php'; ?>
    
    <main id="main-content">
        <!-- Hero Section -->
        <section class="hero" role="region" aria-roledescription="carousel" aria-label="Featured promotions">
            <div class="hero-content animate-fade-in">
                <div class="hero-badge">
                    <span class="gradient-text">✨ New Arrivals</span>
                </div>
                <h1 class="hero-title gradient-text font-display">
                    Experience the Future of Shopping
                </h1>
                <p class="hero-subtitle">
                    Discover cutting-edge technology and premium products with our curated collection of the latest innovations.
                </p>
                <div class="hero-actions">
                    <a href="<?= getBaseUrl() ?>/pages/products.php" class="btn btn-primary">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>
                        Shop Now
                    </a>
                    <a href="<?= getBaseUrl() ?>/pages/products.php?featured=1" class="btn btn-secondary">
                        View Featured
                    </a>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="features-section">
            <div class="container">
                <div class="features-grid">
                    <div class="feature-card animate-fade-in">
                        <div class="feature-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                <polyline points="3.27,6.96 12,12.01 20.73,6.96"></polyline>
                                <line x1="12" y1="22.08" x2="12" y2="12"></line>
                            </svg>
                        </div>
                        <h3>Free Shipping</h3>
                        <p>Free delivery on orders over $99</p>
                    </div>
                    
                    <div class="feature-card animate-fade-in">
                        <div class="feature-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 12l2 2 4-4"></path>
                                <path d="M21 12c.552 0 1-.448 1-1V5c0-.552-.448-1-1-1H3c-.552 0-1 .448-1 1v6c0 .552.448 1 1 1h18z"></path>
                                <path d="M3 12v7c0 .552.448 1 1 1h16c.552 0 1-.448 1-1v-7"></path>
                            </svg>
                        </div>
                        <h3>Secure Payment</h3>
                        <p>100% secure payment processing</p>
                    </div>
                    
                    <div class="feature-card animate-fade-in">
                        <div class="feature-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14.828 14.828a4 4 0 0 1-5.656 0M9 10h1m4 0h1m-6 4h8m-4-8V4a2 2 0 1 1 4 0v2M6 18h12a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2z"></path>
                            </svg>
                        </div>
                        <h3>Quality Guarantee</h3>
                        <p>Premium quality products only</p>
                    </div>
                    
                    <div class="feature-card animate-fade-in">
                        <div class="feature-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                            </svg>
                        </div>
                        <h3>24/7 Support</h3>
                        <p>Always here to help you</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Featured Products -->
        <?php if (!empty($featuredProducts)): ?>
        <section class="featured-products">
            <div class="container">
                <div class="section-header">
                    <h2 class="section-title gradient-text font-display">Featured Products</h2>
                    <p class="section-subtitle">Discover our handpicked selection of premium products</p>
                </div>
                
                <div class="product-grid">
                    <?php foreach (array_slice($featuredProducts, 0, 6) as $product): ?>
                        <div class="product-card animate-fade-in">
                            <div class="product-image-container">
                                <img 
                                    src="<?= htmlspecialchars($product['image']) ?>" 
                                    alt="<?= htmlspecialchars($product['name']) ?>"
                                    class="product-image"
                                    loading="lazy"
                                >
                                <div class="product-overlay">
                                    <a href="<?= getBaseUrl() ?>/pages/product-detail.php?id=<?= $product['id'] ?>" 
                                       class="btn btn-primary"
                                       aria-label="View details for <?= htmlspecialchars($product['name']) ?>">
                                        View Details
                                    </a>
                                </div>
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
                                    <span class="rating-text">(<?= $product['reviews'] ?> reviews)</span>
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
                
                <div class="text-center mt-8">
                    <a href="<?= getBaseUrl() ?>/pages/products.php" class="btn btn-secondary">
                        View All Products
                    </a>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Newsletter Section -->
        <section class="newsletter-section">
            <div class="container">
                <div class="newsletter-card">
                    <div class="newsletter-content">
                        <h2 class="newsletter-title gradient-text font-display">Stay in the Loop</h2>
                        <p class="newsletter-subtitle">
                            Get the latest updates on new products, exclusive offers, and tech insights delivered to your inbox.
                        </p>
                        <form class="newsletter-form-large" aria-label="Newsletter subscription">
                            <input 
                                type="email" 
                                class="input newsletter-input-large" 
                                placeholder="Enter your email address"
                                aria-label="Email address"
                                required
                            >
                            <button type="submit" class="btn btn-primary newsletter-btn-large">
                                Subscribe Now
                            </button>
                        </form>
                        <p class="newsletter-disclaimer">
                            We respect your privacy. Unsubscribe at any time.
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </main>
    
    <?php include __DIR__ . '/components/footer.php'; ?>

    <script>
        // Add some interactive animations
        document.addEventListener('DOMContentLoaded', function() {
            // Animate elements on scroll
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
            
            // Observe all animated elements
            document.querySelectorAll('.animate-fade-in').forEach(el => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(20px)';
                el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                observer.observe(el);
            });
            
            // Newsletter form submission
            const newsletterForm = document.querySelector('.newsletter-form-large');
            if (newsletterForm) {
                newsletterForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const email = this.querySelector('.newsletter-input-large').value;
                    
                    if (email) {
                        alert('Thank you for subscribing! We\'ll keep you updated with the latest news.');
                        this.querySelector('.newsletter-input-large').value = '';
                    }
                });
            }
        });
    </script>
</body>
</html>

<style>
/* Additional styles for home page */
.hero-badge {
    display: inline-block;
    padding: 0.5rem 1rem;
    background: var(--glass-bg);
    border: 1px solid var(--glass-border);
    border-radius: 2rem;
    font-size: 0.875rem;
    margin-bottom: 2rem;
    backdrop-filter: blur(10px);
}

.hero-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
    margin-top: 2rem;
}

.features-section {
    padding: 5rem 0;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 2rem;
}

.feature-card {
    text-align: center;
    padding: 2rem;
}

.feature-icon {
    width: 4rem;
    height: 4rem;
    background: var(--primary-gradient);
    border-radius: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    color: white;
}

.feature-card h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--text-primary);
}

.feature-card p {
    color: var(--text-secondary);
}

.featured-products {
    padding: 5rem 0;
}

.section-header {
    text-align: center;
    margin-bottom: 3rem;
}

.section-title {
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 1rem;
}

.section-subtitle {
    font-size: 1.125rem;
    color: var(--text-secondary);
}

.product-image-container {
    position: relative;
    overflow: hidden;
    border-radius: 0.75rem 0.75rem 0 0;
}

.product-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.product-card:hover .product-overlay {
    opacity: 1;
}

.newsletter-section {
    padding: 5rem 0;
}

.newsletter-card {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    border: 1px solid var(--glass-border);
    border-radius: 2rem;
    padding: 4rem 2rem;
    text-align: center;
}

.newsletter-title {
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 1rem;
}

.newsletter-subtitle {
    font-size: 1.125rem;
    color: var(--text-secondary);
    margin-bottom: 2rem;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.newsletter-form-large {
    display: flex;
    gap: 1rem;
    max-width: 500px;
    margin: 0 auto 1rem;
}

.newsletter-input-large {
    flex: 1;
}

.newsletter-btn-large {
    white-space: nowrap;
}

.newsletter-disclaimer {
    font-size: 0.875rem;
    color: var(--text-muted);
}

@media (max-width: 768px) {
    .hero-title {
        font-size: 2.5rem;
    }
    
    .newsletter-form-large {
        flex-direction: column;
    }
    
    .newsletter-card {
        padding: 3rem 1.5rem;
    }
    
    .section-title {
        font-size: 2rem;
    }
}
</style>
