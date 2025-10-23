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
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $result = $auth->login($email, $password);
        
        if ($result['success']) {
            // Redirect to intended page or home
            $redirect = $_GET['redirect'] ?? '/index.php';
            redirect($redirect);
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
    <title>Login - ModernShop</title>
    <meta name="description" content="Sign in to your ModernShop account to access exclusive features and manage your orders.">
    
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
                        <h1 class="auth-title gradient-text font-display">Welcome Back</h1>
                        <p class="auth-subtitle">Sign in to your account</p>
                    </div>

                    <!-- Error Message -->
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

                    <!-- Login Form -->
                    <form method="POST" class="auth-form" aria-label="Login form">
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
                                    placeholder="Enter your password"
                                    required
                                    autocomplete="current-password"
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
                        </div>

                        <!-- Remember Me & Forgot Password -->
                        <div class="form-options">
                            <label class="checkbox-label">
                                <input type="checkbox" name="remember" class="checkbox">
                                <span class="checkbox-text">Remember me</span>
                            </label>
                            <a href="<?= getBaseUrl() ?>/pages/forgot-password.php" class="forgot-link">
                                Forgot password?
                            </a>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-primary btn-full auth-submit">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                                <polyline points="10,17 15,12 10,7"></polyline>
                                <line x1="15" y1="12" x2="3" y2="12"></line>
                            </svg>
                            Sign In
                        </button>
                    </form>

                    <!-- Register Link -->
                    <div class="auth-footer">
                        <p>Don't have an account? 
                            <a href="<?= getBaseUrl() ?>/pages/register.php" class="auth-link">
                                Create one here
                            </a>
                        </p>
                    </div>
                </div>

                <!-- Demo Credentials -->
                <div class="demo-info">
                    <h3>Demo Credentials</h3>
                    <div class="demo-accounts">
                        <div class="demo-account">
                            <strong>Admin Account:</strong><br>
                            Email: admin@modernshop.com<br>
                            Password: admin123
                        </div>
                        <div class="demo-account">
                            <strong>Regular User:</strong><br>
                            Email: user@example.com<br>
                            Password: user123
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <?php include __DIR__ . '/../components/footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Password toggle functionality
            const passwordInput = document.querySelector('.password-input');
            const passwordToggle = document.querySelector('.password-toggle');
            const eyeOpen = passwordToggle.querySelector('.eye-open');
            const eyeClosed = passwordToggle.querySelector('.eye-closed');
            
            if (passwordToggle) {
                passwordToggle.addEventListener('click', function() {
                    const isPassword = passwordInput.type === 'password';
                    
                    passwordInput.type = isPassword ? 'text' : 'password';
                    eyeOpen.classList.toggle('hidden', isPassword);
                    eyeClosed.classList.toggle('hidden', !isPassword);
                    
                    this.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
                    this.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
                });
            }

            // Form validation
            const form = document.querySelector('.auth-form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const email = form.querySelector('#email').value;
                    const password = form.querySelector('#password').value;
                    
                    if (!email || !password) {
                        e.preventDefault();
                        alert('Please fill in all fields');
                    }
                });
            }

            // Demo account quick fill
            document.querySelectorAll('.demo-account').forEach(account => {
                account.addEventListener('click', function() {
                    const text = this.textContent;
                    const emailMatch = text.match(/Email: (.+)/);
                    const passwordMatch = text.match(/Password: (.+)/);
                    
                    if (emailMatch && passwordMatch) {
                        document.getElementById('email').value = emailMatch[1].trim();
                        document.getElementById('password').value = passwordMatch[1].trim();
                    }
                });
            });
        });
    </script>
</body>
</html>

<style>
/* Authentication page styles */
.auth-main {
    min-height: 100vh;
    display: flex;
    align-items: center;
    padding: 6rem 0 2rem;
}

.auth-container {
    max-width: 500px;
    margin: 0 auto;
    width: 100%;
}

.auth-card {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    border: 1px solid var(--glass-border);
    border-radius: 1.5rem;
    padding: 3rem 2rem;
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
}

.auth-header {
    text-align: center;
    margin-bottom: 2rem;
}

.auth-title {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.auth-subtitle {
    color: var(--text-secondary);
    font-size: 1rem;
}

.auth-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-label {
    font-weight: 500;
    color: var(--text-primary);
    font-size: 0.875rem;
}

.input-group {
    position: relative;
}

.input-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
    z-index: 2;
}

.input-with-icon {
    padding-left: 3rem;
    padding-right: 3rem;
}

.password-toggle {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--text-muted);
    cursor: pointer;
    transition: color 0.3s ease;
    z-index: 2;
}

.password-toggle:hover {
    color: var(--text-primary);
}

.form-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    font-size: 0.875rem;
}

.checkbox {
    width: 1rem;
    height: 1rem;
    border-radius: 0.25rem;
}

.forgot-link {
    color: #60a5fa;
    text-decoration: none;
    font-size: 0.875rem;
    transition: color 0.3s ease;
}

.forgot-link:hover {
    color: #3b82f6;
}

.btn-full {
    width: 100%;
}

.auth-submit {
    padding: 1rem;
    font-size: 1rem;
    font-weight: 600;
}

.auth-footer {
    text-align: center;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid var(--glass-border);
}

.auth-link {
    color: #60a5fa;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s ease;
}

.auth-link:hover {
    color: #3b82f6;
}

.alert {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    border-radius: 0.75rem;
    margin-bottom: 1.5rem;
    font-size: 0.875rem;
}

.alert-error {
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.2);
    color: #fca5a5;
}

.demo-info {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    border: 1px solid var(--glass-border);
    border-radius: 1rem;
    padding: 1.5rem;
    margin-top: 2rem;
}

.demo-info h3 {
    color: var(--text-primary);
    margin-bottom: 1rem;
    font-size: 1.125rem;
}

.demo-accounts {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.demo-account {
    background: rgba(255, 255, 255, 0.05);
    padding: 1rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    color: var(--text-secondary);
    cursor: pointer;
    transition: all 0.3s ease;
}

.demo-account:hover {
    background: rgba(255, 255, 255, 0.1);
    color: var(--text-primary);
}

.hidden {
    display: none;
}

@media (max-width: 768px) {
    .auth-card {
        padding: 2rem 1.5rem;
        margin: 0 1rem;
    }
    
    .form-options {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>
