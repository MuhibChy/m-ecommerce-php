<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$auth = getAuth();
$error = '';
$success = '';

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    redirect('/index.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = 'Please fill in all fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        $result = $auth->register($name, $email, $password);
        
        if ($result['success']) {
            // Auto-login after registration
            $loginResult = $auth->login($email, $password);
            if ($loginResult['success']) {
                redirect('/index.php');
            } else {
                $success = 'Registration successful! Please log in.';
            }
        } else {
            $error = $result['error'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - ModernShop</title>
    <meta name="description" content="Create your ModernShop account to access exclusive features and start shopping.">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="<?= getBaseUrl() ?>/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../components/header.php'; ?>
    
    <main id="main-content" class="auth-main">
        <div class="container">
            <div class="auth-container">
                <div class="auth-card">
                    <!-- Header -->
                    <div class="auth-header">
                        <h1 class="auth-title gradient-text font-display">Create Account</h1>
                        <p class="auth-subtitle">Join us and start shopping</p>
                    </div>

                    <!-- Error/Success Messages -->
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

                    <?php if ($success): ?>
                        <div class="alert alert-success" role="status" aria-live="polite">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="20,6 9,17 4,12"></polyline>
                            </svg>
                            <?= htmlspecialchars($success) ?>
                        </div>
                    <?php endif; ?>

                    <!-- Register Form -->
                    <form method="POST" class="auth-form" aria-label="Registration form">
                        <!-- Name -->
                        <div class="form-group">
                            <label for="name" class="form-label">Full Name</label>
                            <div class="input-group">
                                <div class="input-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                        <circle cx="12" cy="7" r="4"></circle>
                                    </svg>
                                </div>
                                <input
                                    type="text"
                                    id="name"
                                    name="name"
                                    class="input input-with-icon"
                                    placeholder="Enter your full name"
                                    value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                                    required
                                    autocomplete="name"
                                >
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="form-group">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <div class="input-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                        <polyline points="22,6 12,13 2,6"></polyline>
                                    </svg>
                                </div>
                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    class="input input-with-icon"
                                    placeholder="Enter your email"
                                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                                    required
                                    autocomplete="email"
                                >
                            </div>
                        </div>

                        <!-- Password -->
                        <div class="form-group">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <div class="input-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                        <circle cx="12" cy="16" r="1"></circle>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                    </svg>
                                </div>
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    class="input input-with-icon password-input"
                                    placeholder="Create a password"
                                    required
                                    autocomplete="new-password"
                                    minlength="6"
                                >
                                <button
                                    type="button"
                                    class="password-toggle"
                                    aria-label="Show password"
                                    aria-pressed="false"
                                >
                                    <svg class="eye-open" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                    <svg class="eye-closed hidden" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                        <line x1="1" y1="1" x2="23" y2="23"></line>
                                    </svg>
                                </button>
                            </div>
                            <p class="form-help">Must be at least 6 characters</p>
                        </div>

                        <!-- Confirm Password -->
                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <div class="input-group">
                                <div class="input-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                        <circle cx="12" cy="16" r="1"></circle>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                    </svg>
                                </div>
                                <input
                                    type="password"
                                    id="confirm_password"
                                    name="confirm_password"
                                    class="input input-with-icon confirm-password-input"
                                    placeholder="Confirm your password"
                                    required
                                    autocomplete="new-password"
                                >
                                <button
                                    type="button"
                                    class="password-toggle confirm-password-toggle"
                                    aria-label="Show confirm password"
                                    aria-pressed="false"
                                >
                                    <svg class="eye-open" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                    <svg class="eye-closed hidden" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                        <line x1="1" y1="1" x2="23" y2="23"></line>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Terms and Conditions -->
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="terms" class="checkbox" required>
                                <span class="checkbox-text">
                                    I agree to the 
                                    <a href="<?= getBaseUrl() ?>/pages/terms.php" class="auth-link" target="_blank">
                                        Terms of Service
                                    </a> 
                                    and 
                                    <a href="<?= getBaseUrl() ?>/pages/privacy.php" class="auth-link" target="_blank">
                                        Privacy Policy
                                    </a>
                                </span>
                            </label>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-primary btn-full auth-submit">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="8.5" cy="7" r="4"></circle>
                                <line x1="20" y1="8" x2="20" y2="14"></line>
                                <line x1="23" y1="11" x2="17" y2="11"></line>
                            </svg>
                            Create Account
                        </button>
                    </form>

                    <!-- Login Link -->
                    <div class="auth-footer">
                        <p>Already have an account? 
                            <a href="<?= getBaseUrl() ?>/pages/login.php" class="auth-link">
                                Sign in here
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/../components/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Password toggle functionality
            function setupPasswordToggle(inputSelector, toggleSelector) {
                const passwordInput = document.querySelector(inputSelector);
                const passwordToggle = document.querySelector(toggleSelector);
                
                if (passwordToggle && passwordInput) {
                    const eyeOpen = passwordToggle.querySelector('.eye-open');
                    const eyeClosed = passwordToggle.querySelector('.eye-closed');
                    
                    passwordToggle.addEventListener('click', function() {
                        const isPassword = passwordInput.type === 'password';
                        
                        passwordInput.type = isPassword ? 'text' : 'password';
                        eyeOpen.classList.toggle('hidden', isPassword);
                        eyeClosed.classList.toggle('hidden', !isPassword);
                        
                        this.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
                        this.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
                    });
                }
            }
            
            setupPasswordToggle('.password-input', '.password-toggle');
            setupPasswordToggle('.confirm-password-input', '.confirm-password-toggle');

            // Form validation
            const form = document.querySelector('.auth-form');
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            
            if (form) {
                // Real-time password confirmation validation
                function validatePasswordMatch() {
                    const password = passwordInput.value;
                    const confirmPassword = confirmPasswordInput.value;
                    
                    if (confirmPassword && password !== confirmPassword) {
                        confirmPasswordInput.setCustomValidity('Passwords do not match');
                    } else {
                        confirmPasswordInput.setCustomValidity('');
                    }
                }
                
                passwordInput.addEventListener('input', validatePasswordMatch);
                confirmPasswordInput.addEventListener('input', validatePasswordMatch);
                
                // Form submission validation
                form.addEventListener('submit', function(e) {
                    const name = form.querySelector('#name').value;
                    const email = form.querySelector('#email').value;
                    const password = passwordInput.value;
                    const confirmPassword = confirmPasswordInput.value;
                    const terms = form.querySelector('input[name="terms"]').checked;
                    
                    if (!name || !email || !password || !confirmPassword) {
                        e.preventDefault();
                        alert('Please fill in all fields');
                        return;
                    }
                    
                    if (password.length < 6) {
                        e.preventDefault();
                        alert('Password must be at least 6 characters long');
                        return;
                    }
                    
                    if (password !== confirmPassword) {
                        e.preventDefault();
                        alert('Passwords do not match');
                        return;
                    }
                    
                    if (!terms) {
                        e.preventDefault();
                        alert('Please accept the Terms of Service and Privacy Policy');
                        return;
                    }
                });
            }
        });
    </script>
</body>
</html>

<style>
/* Additional styles for register page */
.alert-success {
    background: rgba(34, 197, 94, 0.1);
    border: 1px solid rgba(34, 197, 94, 0.2);
    color: #86efac;
}

.form-help {
    font-size: 0.75rem;
    color: var(--text-muted);
    margin-top: 0.25rem;
}

.checkbox-text {
    font-size: 0.875rem;
    line-height: 1.4;
}

.checkbox-text a {
    color: #60a5fa;
    text-decoration: none;
}

.checkbox-text a:hover {
    color: #3b82f6;
    text-decoration: underline;
}
</style>
