<?php
// ExtremeLife MLM Member Registration with Sponsor System
session_start();

try {
    $pdo = new PDO('mysql:host=localhost;dbname=drupal_umd', 'drupal_user', 'secure_drupal_pass_1748318545');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

$message = '';
$error = '';
$sponsor_info = null;

// Check for sponsor code in URL
$sponsor_code = $_GET['sponsor'] ?? '';
if ($sponsor_code) {
    try {
        $stmt = $pdo->prepare("SELECT id, referral_code, CONCAT(first_name, ' ', last_name) as name, email FROM mlm_members WHERE referral_code = ? AND status = 'active'");
        $stmt->execute([$sponsor_code]);
        $sponsor_info = $stmt->fetch();
    } catch (PDOException $e) {
        $error = "Error validating sponsor code.";
    }
}

// Handle registration
if (isset($_POST['action']) && $_POST['action'] == 'register') {
    try {
        // Validate required fields
        $required_fields = ['first_name', 'last_name', 'email', 'phone', 'sponsor_code'];
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Please fill in all required fields.");
            }
        }
        
        // Validate sponsor code
        $stmt = $pdo->prepare("SELECT id FROM mlm_members WHERE referral_code = ? AND status = 'active'");
        $stmt->execute([$_POST['sponsor_code']]);
        $sponsor = $stmt->fetch();
        
        if (!$sponsor) {
            throw new Exception("Invalid sponsor code. Please check and try again.");
        }
        
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM mlm_members WHERE email = ?");
        $stmt->execute([$_POST['email']]);
        if ($stmt->fetch()) {
            throw new Exception("Email address already registered. Please use a different email.");
        }
        
        // Generate unique referral code
        do {
            $new_referral_code = 'ELH' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT);
            $stmt = $pdo->prepare("SELECT id FROM mlm_members WHERE referral_code = ?");
            $stmt->execute([$new_referral_code]);
        } while ($stmt->fetch());
        
        // Insert new member
        $stmt = $pdo->prepare("INSERT INTO mlm_members (
            sponsor_id, referral_code, first_name, last_name, email, phone, 
            address, city, province, postal_code, user_group_id, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'active')");
        
        $stmt->execute([
            $sponsor['id'],
            $new_referral_code,
            $_POST['first_name'],
            $_POST['last_name'],
            $_POST['email'],
            $_POST['phone'],
            $_POST['address'] ?? '',
            $_POST['city'] ?? '',
            $_POST['province'] ?? '',
            $_POST['postal_code'] ?? ''
        ]);
        
        $new_member_id = $pdo->lastInsertId();
        
        // Update sponsor's referral count
        $pdo->prepare("UPDATE mlm_members SET direct_referrals = direct_referrals + 1 WHERE id = ?")->execute([$sponsor['id']]);
        
        // Create genealogy entry
        $upline_stmt = $pdo->prepare("SELECT upline_path, level_depth FROM mlm_genealogy WHERE member_id = ?");
        $upline_stmt->execute([$sponsor['id']]);
        $upline_data = $upline_stmt->fetch();
        
        $new_upline_path = $upline_data ? $upline_data['upline_path'] . ',' . $sponsor['id'] : $sponsor['id'];
        $new_level = $upline_data ? $upline_data['level_depth'] + 1 : 2;
        
        $stmt = $pdo->prepare("INSERT INTO mlm_genealogy (member_id, sponsor_id, upline_path, level_depth) VALUES (?, ?, ?, ?)");
        $stmt->execute([$new_member_id, $sponsor['id'], $new_upline_path, $new_level]);
        
        $message = "Registration successful! Your referral code is: $new_referral_code";
        $_SESSION['member_id'] = $new_member_id;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join ExtremeLife MLM - Member Registration</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh; line-height: 1.6;
        }
        .header {
            background: linear-gradient(135deg, #2d5a27, #4a7c59);
            color: white; padding: 2rem; text-align: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .header h1 { font-size: 2.5rem; margin-bottom: 0.5rem; }
        .nav {
            background: white; padding: 1rem; text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 2rem;
        }
        .nav a {
            color: #2d5a27; text-decoration: none; margin: 0 15px;
            padding: 10px 20px; border-radius: 25px; font-weight: 600;
            transition: all 0.3s ease;
        }
        .nav a:hover { background: #e8f5e8; }
        .container { max-width: 800px; margin: 0 auto; padding: 2rem; }
        .alert {
            padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 600;
        }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .registration-card {
            background: white; padding: 2rem; border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1); margin-bottom: 2rem;
        }
        .sponsor-info {
            background: #e8f5e8; padding: 1.5rem; border-radius: 10px;
            border: 2px solid #28a745; margin-bottom: 2rem;
        }
        .form-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        .form-group { margin-bottom: 1rem; }
        .form-group label { 
            display: block; margin-bottom: 0.5rem; font-weight: 600; color: #333;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%; padding: 0.75rem; border: 2px solid #ddd;
            border-radius: 5px; font-size: 1rem;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: #2d5a27; outline: none;
        }
        .btn {
            background: #2d5a27; color: white; padding: 0.75rem 1.5rem;
            border: none; border-radius: 25px; cursor: pointer;
            font-weight: 600; font-size: 1rem; text-decoration: none;
            display: inline-block; margin: 0.25rem; transition: all 0.3s ease;
        }
        .btn:hover { background: #4a7c59; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        .benefits-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem; margin-top: 2rem;
        }
        .benefit-card {
            background: #f8f9fa; padding: 1.5rem; border-radius: 10px;
            text-align: center; border-left: 4px solid #2d5a27;
        }
        .benefit-icon { font-size: 3rem; margin-bottom: 1rem; }
        .required { color: #dc3545; }
    </style>
</head>
<body>
    <header class="header">
        <h1>🌟 Join ExtremeLife MLM</h1>
        <p>Start your journey to financial freedom with natural health products</p>
    </header>

    <nav class="nav">
        <a href="/">🏠 Home</a>
        <a href="/database_catalog.php">📦 Products</a>
        <a href="/member_dashboard.php">👤 Dashboard</a>
        <a href="/cart.php">🛒 Cart</a>
    </nav>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
            <div style="text-align: center;">
                <a href="/member_dashboard.php" class="btn btn-success">🎉 Go to Dashboard</a>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!$message): ?>
            <?php if ($sponsor_info): ?>
                <div class="sponsor-info">
                    <h3 style="color: #2d5a27; margin-bottom: 1rem;">👤 Your Sponsor</h3>
                    <p><strong>Name:</strong> <?= htmlspecialchars($sponsor_info['name']) ?></p>
                    <p><strong>Referral Code:</strong> <?= htmlspecialchars($sponsor_info['referral_code']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($sponsor_info['email']) ?></p>
                </div>
            <?php endif; ?>

            <div class="registration-card">
                <h2 style="color: #2d5a27; margin-bottom: 1.5rem;">📝 Member Registration</h2>
                
                <form method="post">
                    <input type="hidden" name="action" value="register">
                    
                    <div class="form-grid">
                        <div>
                            <div class="form-group">
                                <label>First Name <span class="required">*</span></label>
                                <input type="text" name="first_name" required value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Last Name <span class="required">*</span></label>
                                <input type="text" name="last_name" required value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Email Address <span class="required">*</span></label>
                                <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Phone Number <span class="required">*</span></label>
                                <input type="tel" name="phone" required value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" placeholder="+63 XXX XXX XXXX">
                            </div>
                        </div>
                        
                        <div>
                            <div class="form-group">
                                <label>Sponsor Referral Code <span class="required">*</span></label>
                                <input type="text" name="sponsor_code" required value="<?= htmlspecialchars($sponsor_code ?: ($_POST['sponsor_code'] ?? '')) ?>" placeholder="ELH000000">
                            </div>
                            
                            <div class="form-group">
                                <label>Address</label>
                                <textarea name="address" rows="2" placeholder="Street address"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label>City</label>
                                <input type="text" name="city" value="<?= htmlspecialchars($_POST['city'] ?? '') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label>Province</label>
                                <input type="text" name="province" value="<?= htmlspecialchars($_POST['province'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div style="text-align: center; margin-top: 2rem;">
                        <button type="submit" class="btn btn-success" style="font-size: 1.2rem; padding: 1rem 2rem;">
                            🌟 Join ExtremeLife MLM
                        </button>
                    </div>
                </form>
            </div>

            <!-- MLM Benefits -->
            <div class="registration-card">
                <h2 style="color: #2d5a27; margin-bottom: 1.5rem;">💰 MLM Benefits & Commission Structure</h2>
                
                <div class="benefits-grid">
                    <div class="benefit-card">
                        <div class="benefit-icon">🥉</div>
                        <h4>Member (10%)</h4>
                        <p>Start earning 10% commission + 2% rebate on all sales</p>
                    </div>
                    <div class="benefit-card">
                        <div class="benefit-icon">🥈</div>
                        <h4>Wholesale (12%)</h4>
                        <p>₱1,000 sales requirement<br>12% commission + 3% rebate</p>
                    </div>
                    <div class="benefit-card">
                        <div class="benefit-icon">🥇</div>
                        <h4>Distributor (15%)</h4>
                        <p>₱5,000 sales requirement<br>15% commission + 4% rebate</p>
                    </div>
                    <div class="benefit-card">
                        <div class="benefit-icon">💎</div>
                        <h4>VIP (18%)</h4>
                        <p>₱15,000 sales requirement<br>18% commission + 5% rebate</p>
                    </div>
                    <div class="benefit-card">
                        <div class="benefit-icon">👑</div>
                        <h4>Diamond (20%)</h4>
                        <p>₱50,000 sales requirement<br>20% commission + 6% rebate</p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto-format phone number
        document.querySelector('input[name="phone"]').addEventListener('input', function() {
            let value = this.value.replace(/\D/g, '');
            if (value.startsWith('63')) {
                value = '+' + value;
            } else if (value.startsWith('0')) {
                value = '+63' + value.substring(1);
            }
            this.value = value;
        });
        
        // Validate sponsor code format
        document.querySelector('input[name="sponsor_code"]').addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    </script>
</body>
</html>
