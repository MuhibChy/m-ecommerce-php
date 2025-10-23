<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $subject = sanitizeInput($_POST['subject'] ?? '');
    $message = sanitizeInput($_POST['message'] ?? '');
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'Please fill in all fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        // In a real application, you would send an email here
        $success = 'Thank you! Your message has been sent successfully.';
        // Clear form data
        $_POST = [];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - ModernShop</title>
    <meta name="description" content="Get in touch with ModernShop. We're here to help with any questions or concerns.">
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="<?= getBaseUrl() ?>/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../components/header.php'; ?>
    
    <main id="main-content" class="contact-main">
        <div class="container">
            <!-- Page Header -->
            <div class="page-header">
                <div class="breadcrumb">
                    <a href="<?= getBaseUrl() ?>/index.php">Home</a>
                    <span class="breadcrumb-separator">/</span>
                    <span>Contact</span>
                </div>
                
                <h1 class="page-title gradient-text font-display">Get in Touch</h1>
                <p class="page-subtitle">
                    We'd love to hear from you. Send us a message and we'll respond as soon as possible.
                </p>
            </div>

            <div class="contact-layout">
                <!-- Contact Form -->
                <div class="contact-form-section">
                    <div class="form-card">
                        <h2>Send us a Message</h2>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success" role="status" aria-live="polite">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="20,6 9,17 4,12"></polyline>
                                </svg>
                                <?= htmlspecialchars($success) ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($error): ?>
                            <div class="alert alert-error" role="alert" aria-live="assertive">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="15" y1="9" x2="9" y2="15"></line>
                                    <line x1="9" y1="9" x2="15" y2="15"></line>
                                </svg>
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="contact-form" aria-label="Contact form">
                            <div class="form-group">
                                <label for="name" class="form-label">Your Name</label>
                                <input type="text" id="name" name="name" class="input" 
                                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" id="email" name="email" class="input" 
                                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="subject" class="form-label">Subject</label>
                                <input type="text" id="subject" name="subject" class="input" 
                                       value="<?= htmlspecialchars($_POST['subject'] ?? '') ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="message" class="form-label">Message</label>
                                <textarea id="message" name="message" class="input" rows="6" 
                                          required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary btn-full">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="22" y1="2" x2="11" y2="13"></line>
                                    <polygon points="22,2 15,22 11,13 2,9 22,2"></polygon>
                                </svg>
                                Send Message
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Contact Info -->
                <div class="contact-info-section">
                    <div class="info-card">
                        <h3>Contact Information</h3>
                        <p>Get in touch with us through any of these channels:</p>
                        
                        <div class="contact-methods">
                            <div class="contact-method">
                                <div class="method-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                        <polyline points="22,6 12,13 2,6"></polyline>
                                    </svg>
                                </div>
                                <div class="method-details">
                                    <h4>Email</h4>
                                    <p>support@modernshop.com</p>
                                </div>
                            </div>

                            <div class="contact-method">
                                <div class="method-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                                    </svg>
                                </div>
                                <div class="method-details">
                                    <h4>Phone</h4>
                                    <p>+1 (555) 123-4567</p>
                                </div>
                            </div>

                            <div class="contact-method">
                                <div class="method-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                        <circle cx="12" cy="10" r="3"></circle>
                                    </svg>
                                </div>
                                <div class="method-details">
                                    <h4>Address</h4>
                                    <p>123 Tech Street<br>San Francisco, CA 94105</p>
                                </div>
                            </div>

                            <div class="contact-method">
                                <div class="method-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <polyline points="12,6 12,12 16,14"></polyline>
                                    </svg>
                                </div>
                                <div class="method-details">
                                    <h4>Business Hours</h4>
                                    <p>Monday - Friday: 9:00 AM - 6:00 PM<br>
                                       Saturday: 10:00 AM - 4:00 PM<br>
                                       Sunday: Closed</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- FAQ Section -->
                    <div class="faq-card">
                        <h3>Frequently Asked Questions</h3>
                        
                        <div class="faq-item">
                            <h4>How long does shipping take?</h4>
                            <p>Standard shipping takes 3-5 business days. Express shipping is available for 1-2 day delivery.</p>
                        </div>
                        
                        <div class="faq-item">
                            <h4>What is your return policy?</h4>
                            <p>We offer a 30-day return policy for all items in original condition with original packaging.</p>
                        </div>
                        
                        <div class="faq-item">
                            <h4>Do you offer technical support?</h4>
                            <p>Yes! Our technical support team is available 24/7 to help with any product-related questions.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/../components/footer.php'; ?>
</body>
</html>

<style>
.contact-main {
    padding-top: 6rem;
    padding-bottom: 2rem;
    min-height: 100vh;
}

.contact-layout {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 3rem;
    margin-top: 2rem;
}

.form-card,
.info-card,
.faq-card {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    border: 1px solid var(--glass-border);
    border-radius: 1rem;
    padding: 2rem;
}

.form-card h2,
.info-card h3,
.faq-card h3 {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 1.5rem;
}

.contact-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.contact-methods {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    margin-top: 2rem;
}

.contact-method {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}

.method-icon {
    width: 3rem;
    height: 3rem;
    background: var(--primary-gradient);
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    flex-shrink: 0;
}

.method-details h4 {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
}

.method-details p {
    color: var(--text-secondary);
    font-size: 0.875rem;
    line-height: 1.5;
}

.faq-card {
    margin-top: 2rem;
}

.faq-item {
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid var(--glass-border);
}

.faq-item:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: none;
}

.faq-item h4 {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

.faq-item p {
    color: var(--text-secondary);
    font-size: 0.875rem;
    line-height: 1.6;
}

@media (max-width: 768px) {
    .contact-layout {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .contact-info-section {
        order: -1;
    }
}
</style>
