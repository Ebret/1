<?php
// ExtremeLife MLM Admin Login System - Fixed Version
// Resolves JavaScript errors and authentication issues

session_start();

// Redirect if already logged in as admin
if (isset($_SESSION['admin_id']) && $_SESSION['user_type'] === 'admin') {
    header('Location: admin_dashboard.php');
    exit();
}

// Database configuration
require_once 'system_config.php';

$error_message = '';
$success_message = '';

// Process login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);
    
    // Validate input
    if (empty($email) || empty($password)) {
        $error_message = 'Please enter both email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error_message = 'Password must be at least 6 characters long.';
    } else {
        try {
            $config = new SystemConfig();
            $pdo = $config->getDBConnection();
            
            // Check admin credentials
            $stmt = $pdo->prepare("
                SELECT id, email, password, first_name, last_name, status, last_login, failed_attempts, locked_until
                FROM mlm_admins 
                WHERE email = ? AND status = 'active'
            ");
            $stmt->execute([$email]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($admin) {
                // Check if account is locked
                if ($admin['locked_until'] && strtotime($admin['locked_until']) > time()) {
                    $error_message = 'Account is temporarily locked due to multiple failed attempts. Please try again later.';
                } elseif (password_verify($password, $admin['password'])) {
                    // Successful login
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_email'] = $admin['email'];
                    $_SESSION['admin_name'] = $admin['first_name'] . ' ' . $admin['last_name'];
                    $_SESSION['user_type'] = 'admin';
                    $_SESSION['login_time'] = time();
                    
                    // Reset failed attempts
                    $stmt = $pdo->prepare("
                        UPDATE mlm_admins 
                        SET failed_attempts = 0, locked_until = NULL, last_login = NOW() 
                        WHERE id = ?
                    ");
                    $stmt->execute([$admin['id']]);
                    
                    // Set remember me cookie if requested
                    if ($remember_me) {
                        $token = bin2hex(random_bytes(32));
                        setcookie('admin_remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', true, true);
                        
                        // Store token in database
                        $stmt = $pdo->prepare("
                            INSERT INTO mlm_admin_tokens (admin_id, token, expires_at) 
                            VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 DAY))
                        ");
                        $stmt->execute([$admin['id'], hash('sha256', $token)]);
                    }
                    
                    // Log successful login
                    error_log("Admin login successful: " . $admin['email'] . " from IP: " . $_SERVER['REMOTE_ADDR']);
                    
                    // Redirect to admin dashboard
                    header('Location: admin_dashboard.php');
                    exit();
                } else {
                    // Failed login - increment attempts
                    $failed_attempts = ($admin['failed_attempts'] ?? 0) + 1;
                    $locked_until = null;
                    
                    // Lock account after 5 failed attempts
                    if ($failed_attempts >= 5) {
                        $locked_until = date('Y-m-d H:i:s', time() + (15 * 60)); // 15 minutes
                        $error_message = 'Too many failed attempts. Account locked for 15 minutes.';
                    } else {
                        $error_message = 'Invalid email or password. Attempt ' . $failed_attempts . ' of 5.';
                    }
                    
                    $stmt = $pdo->prepare("
                        UPDATE mlm_admins 
                        SET failed_attempts = ?, locked_until = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$failed_attempts, $locked_until, $admin['id']]);
                    
                    // Log failed login attempt
                    error_log("Admin login failed: " . $email . " from IP: " . $_SERVER['REMOTE_ADDR']);
                }
            } else {
                $error_message = 'Invalid email or password.';
                error_log("Admin login attempt with non-existent email: " . $email . " from IP: " . $_SERVER['REMOTE_ADDR']);
            }
            
        } catch (Exception $e) {
            error_log("Admin login error: " . $e->getMessage());
            $error_message = 'System error. Please try again later.';
        }
    }
}

// Check for remember me token
if (!isset($_SESSION['admin_id']) && isset($_COOKIE['admin_remember_token'])) {
    try {
        $config = new SystemConfig();
        $pdo = $config->getDBConnection();
        
        $token_hash = hash('sha256', $_COOKIE['admin_remember_token']);
        $stmt = $pdo->prepare("
            SELECT a.id, a.email, a.first_name, a.last_name
            FROM mlm_admin_tokens t
            JOIN mlm_admins a ON t.admin_id = a.id
            WHERE t.token = ? AND t.expires_at > NOW() AND a.status = 'active'
        ");
        $stmt->execute([$token_hash]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($admin) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_name'] = $admin['first_name'] . ' ' . $admin['last_name'];
            $_SESSION['user_type'] = 'admin';
            $_SESSION['login_time'] = time();
            
            header('Location: admin_dashboard.php');
            exit();
        }
    } catch (Exception $e) {
        error_log("Remember token error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - ExtremeLife MLM</title>
    
    <style>
        :root {
            --primary-color: #2d5a27;
            --secondary-color: #4a7c59;
            --accent-color: #7fb069;
            --light-green: #e8f5e8;
            --white: #ffffff;
            --light-gray: #f8f9fa;
            --dark-gray: #343a40;
            --error-color: #dc3545;
            --warning-color: #ffc107;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 8px 25px rgba(45, 90, 39, 0.3);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--dark-gray) 0%, var(--primary-color) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            position: relative;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,') repeat;
            animation: backgroundMove 30s infinite linear;
        }
        
        @keyframes backgroundMove {
            0% { transform: translateX(0) translateY(0); }
            100% { transform: translateX(-100px) translateY(-100px); }
        }
        
        .admin-login-container {
            background: var(--white);
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            max-width: 500px;
            width: 100%;
            position: relative;
            z-index: 1;
        }
        
        .admin-header {
            background: linear-gradient(135deg, var(--primary-color), var(--dark-gray));
            color: var(--white);
            padding: 2rem;
            text-align: center;
            position: relative;
        }
        
        .admin-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
            animation: shimmer 3s infinite;
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        .admin-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            position: relative;
            z-index: 1;
        }
        
        .admin-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }
        
        .admin-subtitle {
            opacity: 0.9;
            font-size: 0.9rem;
            position: relative;
            z-index: 1;
        }
        
        .security-notice {
            background: var(--warning-color);
            color: var(--dark-gray);
            padding: 1rem;
            text-align: center;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .admin-form {
            padding: 2.5rem;
        }
        
        .message {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            text-align: center;
            font-weight: 600;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--primary-color);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .form-control {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--light-gray);
            font-family: monospace;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            background: var(--white);
            box-shadow: 0 0 0 3px rgba(45, 90, 39, 0.1);
        }
        
        .form-control.error {
            border-color: var(--error-color);
            animation: shake 0.5s ease-in-out;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary-color);
        }
        
        .checkbox-group label {
            color: var(--dark-gray);
            font-size: 0.9rem;
            cursor: pointer;
        }
        
        .btn-admin {
            background: linear-gradient(135deg, var(--primary-color), var(--dark-gray));
            color: var(--white);
            padding: 1rem 2rem;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .btn-admin:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .btn-admin:active {
            transform: translateY(0);
        }
        
        .btn-admin:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .admin-links {
            text-align: center;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e9ecef;
        }
        
        .admin-links a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }
        
        .admin-links a:hover {
            color: var(--secondary-color);
        }
        
        .back-home {
            position: absolute;
            top: 2rem;
            left: 2rem;
            color: var(--white);
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: opacity 0.3s ease;
            z-index: 2;
        }
        
        .back-home:hover {
            opacity: 0.8;
        }
        
        .security-features {
            background: var(--light-gray);
            padding: 1.5rem;
            border-radius: 10px;
            margin-top: 1rem;
        }
        
        .security-features h4 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .security-features ul {
            list-style: none;
            font-size: 0.8rem;
            color: var(--dark-gray);
        }
        
        .security-features li {
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .security-features li::before {
            content: '🔒';
            font-size: 0.7rem;
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            body {
                padding: 1rem;
            }
            
            .admin-login-container {
                max-width: 400px;
            }
            
            .admin-header {
                padding: 1.5rem;
            }
            
            .admin-title {
                font-size: 1.5rem;
            }
            
            .admin-form {
                padding: 2rem;
            }
            
            .back-home {
                position: static;
                margin-bottom: 1rem;
                justify-content: center;
                color: var(--white);
            }
        }
        
        /* Loading Animation */
        .loading {
            opacity: 0;
            animation: fadeInScale 0.6s ease forwards;
        }
        
        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
    </style>
</head>
<body>
    <a href="index.php" class="back-home">← Back to Home</a>
    
    <div class="admin-login-container loading">
        
        <div class="admin-header">
            <div class="admin-icon">🛡️</div>
            <div class="admin-title">Admin Access</div>
            <div class="admin-subtitle">ExtremeLife MLM System Administration</div>
        </div>
        
        <div class="security-notice">
            ⚠️ RESTRICTED ACCESS - Authorized Personnel Only
        </div>
        
        <div class="admin-form">
            <?php if ($error_message): ?>
            <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
            <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>
            
            <form method="POST" id="adminLoginForm">
                <div class="form-group">
                    <label for="email" class="form-label">Administrator Email</label>
                    <input type="email" id="email" name="email" class="form-control" 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                           required autocomplete="username">
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Secure Password</label>
                    <input type="password" id="password" name="password" class="form-control" 
                           required autocomplete="current-password">
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="remember_me" name="remember_me">
                    <label for="remember_me">Keep me signed in (secure device only)</label>
                </div>
                
                <button type="submit" class="btn-admin" id="adminLoginBtn">
                    🔐 Access Admin Panel
                </button>
            </form>
            
            <div class="security-features">
                <h4>Security Features Active</h4>
                <ul>
                    <li>Multi-factor authentication ready</li>
                    <li>Session timeout protection</li>
                    <li>Failed attempt monitoring</li>
                    <li>IP address logging</li>
                    <li>Encrypted data transmission</li>
                </ul>
            </div>
            
            <div class="admin-links">
                <a href="member_login.php">Member Login Instead</a>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('adminLoginForm');
            const loginBtn = document.getElementById('adminLoginBtn');
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            
            // Enhanced form validation for admin - FIXED
            form.addEventListener('submit', function(e) {
                const email = emailInput.value.trim();
                const password = passwordInput.value;
                
                // Reset previous error states
                emailInput.classList.remove('error');
                passwordInput.classList.remove('error');
                
                let hasError = false;
                
                if (!email || !email.includes('@')) {
                    emailInput.classList.add('error');
                    hasError = true;
                }
                
                // FIXED: Changed from = to <
                if (!password || password.length < 6) {
                    passwordInput.classList.add('error');
                    hasError = true;
                }
                
                if (hasError) {
                    e.preventDefault();
                    loginBtn.innerHTML = '❌ Please check your input';
                    setTimeout(function() {
                        loginBtn.innerHTML = '🔐 Access Admin Panel';
                    }, 2000);
                    return false;
                }
                
                // Show loading state
                loginBtn.disabled = true;
                loginBtn.innerHTML = '🔄 Authenticating...';
            });
            
            // Remove error class on input
            emailInput.addEventListener('input', function() {
                if (this.value.trim() && this.value.includes('@')) {
                    this.classList.remove('error');
                }
            });
            
            passwordInput.addEventListener('input', function() {
                if (this.value.length >= 6) {
                    this.classList.remove('error');
                }
            });
            
            // Auto-focus email field
            emailInput.focus();
            
            // Handle Enter key
            passwordInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    form.submit();
                }
            });
            
            // Security warning for development
            if (window.location.protocol !== 'https:' && window.location.hostname !== 'localhost') {
                console.warn('⚠️ Admin login should only be used over HTTPS in production!');
            }
            
            // Add loading animation
            document.querySelector('.admin-login-container').classList.add('loading');
        });
    </script>
</body>
</html>
