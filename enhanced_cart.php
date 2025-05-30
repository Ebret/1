<?php
// ExtremeLife MLM Enhanced Shopping Cart with Commission Integration
session_start();

// Load sponsor system data
$sponsor_data = [];
if (file_exists('sponsor_system_data.json')) {
    $sponsor_data = json_decode(file_get_contents('sponsor_system_data.json'), true);
}

// Database connection with fallback
try {
    $pdo = new PDO('mysql:host=localhost;dbname=drupal_umd', 'drupal_user', 'secure_drupal_pass_1748318545');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db_available = true;
} catch (PDOException $e) {
    $db_available = false;
}

// Initialize cart
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Get member information
$member_id = $_SESSION['member_id'] ?? 10;
$member = null;

if ($db_available) {
    try {
        $stmt = $pdo->prepare("SELECT m.*, CONCAT(m.first_name, ' ', m.last_name) as user_name, g.commission_rate, g.rebate_rate 
                              FROM mlm_members m 
                              LEFT JOIN mlm_user_groups g ON m.user_group_id = g.id 
                              WHERE m.id = ?");
        $stmt->execute([$member_id]);
        $member = $stmt->fetch();
    } catch (PDOException $e) {
        // Fallback handled below
    }
}

// Fallback member data
if (!$member) {
    $member = [
        'id' => 10,
        'user_name' => 'Demo Member',
        'email' => 'demo@extremelifeherbal.com',
        'referral_code' => 'ELH000010',
        'commission_rate' => 10.00,
        'rebate_rate' => 2.00,
        'phone' => '+63 912 000 0010'
    ];
}

// Sample products
$products = [
    1 => ['name' => 'Premium Turmeric Capsules', 'price' => 299.99, 'image' => 'turmeric.jpg', 'description' => 'Natural anti-inflammatory supplement'],
    2 => ['name' => 'Organic Green Tea', 'price' => 199.99, 'image' => 'green-tea.jpg', 'description' => 'Antioxidant-rich herbal tea'],
    3 => ['name' => 'Essential Oil Blend', 'price' => 99.99, 'image' => 'essential-oil.jpg', 'description' => 'Therapeutic aromatherapy blend'],
    4 => ['name' => 'Vitamin C Complex', 'price' => 249.99, 'image' => 'vitamin-c.jpg', 'description' => 'Immune system booster'],
    5 => ['name' => 'Herbal Sleep Aid', 'price' => 179.99, 'image' => 'sleep-aid.jpg', 'description' => 'Natural sleep enhancement']
];

// Handle cart actions
$message = '';
$error = '';

if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add_to_cart':
            $product_id = (int)$_POST['product_id'];
            $quantity = (int)$_POST['quantity'];
            
            if (isset($products[$product_id]) && $quantity > 0) {
                if (isset($_SESSION['cart'][$product_id])) {
                    $_SESSION['cart'][$product_id] += $quantity;
                } else {
                    $_SESSION['cart'][$product_id] = $quantity;
                }
                $message = "Product added to cart successfully!";
            } else {
                $error = "Invalid product or quantity.";
            }
            break;
            
        case 'update_cart':
            foreach ($_POST['quantities'] as $product_id => $quantity) {
                $quantity = (int)$quantity;
                if ($quantity > 0) {
                    $_SESSION['cart'][$product_id] = $quantity;
                } else {
                    unset($_SESSION['cart'][$product_id]);
                }
            }
            $message = "Cart updated successfully!";
            break;
            
        case 'remove_item':
            $product_id = (int)$_POST['product_id'];
            unset($_SESSION['cart'][$product_id]);
            $message = "Item removed from cart.";
            break;
            
        case 'clear_cart':
            $_SESSION['cart'] = [];
            $message = "Cart cleared.";
            break;
            
        case 'checkout':
            if (empty($_SESSION['cart'])) {
                $error = "Your cart is empty.";
            } else {
                // Process checkout
                $order_total = 0;
                $order_items = [];
                
                foreach ($_SESSION['cart'] as $product_id => $quantity) {
                    if (isset($products[$product_id])) {
                        $product = $products[$product_id];
                        $subtotal = $product['price'] * $quantity;
                        $order_total += $subtotal;
                        
                        $order_items[] = [
                            'product_id' => $product_id,
                            'name' => $product['name'],
                            'price' => $product['price'],
                            'quantity' => $quantity,
                            'subtotal' => $subtotal
                        ];
                    }
                }
                
                // Calculate commissions
                $commission_amount = $order_total * ($member['commission_rate'] / 100);
                $rebate_amount = $order_total * ($member['rebate_rate'] / 100);
                $total_earnings = $commission_amount + $rebate_amount;
                
                // Generate order number
                $order_number = 'ELH' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                
                // Store order in session for confirmation
                $_SESSION['pending_order'] = [
                    'order_number' => $order_number,
                    'items' => $order_items,
                    'total' => $order_total,
                    'commission' => $commission_amount,
                    'rebate' => $rebate_amount,
                    'total_earnings' => $total_earnings,
                    'member_id' => $member['id'],
                    'member_name' => $member['user_name'],
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                // Redirect to checkout confirmation
                header('Location: checkout_confirmation.php');
                exit;
            }
            break;
    }
}

// Calculate cart totals
$cart_total = 0;
$cart_items = [];

foreach ($_SESSION['cart'] as $product_id => $quantity) {
    if (isset($products[$product_id])) {
        $product = $products[$product_id];
        $subtotal = $product['price'] * $quantity;
        $cart_total += $subtotal;
        
        $cart_items[] = [
            'id' => $product_id,
            'name' => $product['name'],
            'price' => $product['price'],
            'quantity' => $quantity,
            'subtotal' => $subtotal
        ];
    }
}

// Calculate potential earnings
$potential_commission = $cart_total * ($member['commission_rate'] / 100);
$potential_rebate = $cart_total * ($member['rebate_rate'] / 100);
$total_potential_earnings = $potential_commission + $potential_rebate;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - ExtremeLife Herbal MLM</title>
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
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        .alert {
            padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 600;
        }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .cart-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; }
        .cart-section {
            background: white; padding: 2rem; border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .cart-item {
            display: grid; grid-template-columns: 1fr 100px 100px 100px 50px;
            gap: 1rem; align-items: center; padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        .cart-item:last-child { border-bottom: none; }
        .product-info h4 { color: #2d5a27; margin-bottom: 0.5rem; }
        .product-info p { color: #666; font-size: 0.9rem; }
        .quantity-input { width: 60px; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; }
        .btn {
            background: #2d5a27; color: white; padding: 0.75rem 1.5rem;
            border: none; border-radius: 25px; cursor: pointer;
            font-weight: 600; text-decoration: none; display: inline-block;
            margin: 0.25rem; transition: all 0.3s ease;
        }
        .btn:hover { background: #4a7c59; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        .earnings-card {
            background: linear-gradient(135deg, #e8f5e8, #d4edda);
            padding: 1.5rem; border-radius: 10px; margin-bottom: 1.5rem;
            border: 2px solid #28a745;
        }
        .earnings-item {
            display: flex; justify-content: space-between; margin-bottom: 0.5rem;
        }
        .total-section {
            background: #f8f9fa; padding: 1.5rem; border-radius: 10px;
            border-left: 4px solid #2d5a27; margin-top: 1.5rem;
        }
        @media (max-width: 768px) {
            .cart-grid { grid-template-columns: 1fr; }
            .cart-item { grid-template-columns: 1fr; text-align: center; }
        }
    </style>
</head>
<body>
    <header class="header">
        <h1>🛒 ExtremeLife Shopping Cart</h1>
        <p>Member: <?= htmlspecialchars($member['user_name']) ?> (<?= $member['referral_code'] ?? 'ELH000010' ?>)</p>
    </header>

    <nav class="nav">
        <a href="/">🏠 Home</a>
        <a href="/database_catalog.php">📦 Shop Products</a>
        <a href="/member_dashboard.php">👤 Dashboard</a>
        <a href="/register.php">👥 Refer Friends</a>
        <a href="/mlm_tools.php">🔧 MLM Tools</a>
    </nav>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="cart-grid">
            <!-- Cart Items -->
            <div class="cart-section">
                <h2 style="color: #2d5a27; margin-bottom: 1.5rem;">🛒 Your Cart</h2>
                
                <?php if (empty($cart_items)): ?>
                    <div style="text-align: center; padding: 3rem; color: #666;">
                        <div style="font-size: 4rem; margin-bottom: 1rem;">🛒</div>
                        <h3>Your cart is empty</h3>
                        <p>Add some products to get started!</p>
                        <a href="/database_catalog.php" class="btn" style="margin-top: 1rem;">Browse Products</a>
                    </div>
                <?php else: ?>
                    <form method="post">
                        <input type="hidden" name="action" value="update_cart">
                        
                        <div class="cart-item" style="font-weight: bold; background: #f8f9fa;">
                            <div>Product</div>
                            <div>Price</div>
                            <div>Quantity</div>
                            <div>Subtotal</div>
                            <div>Action</div>
                        </div>
                        
                        <?php foreach ($cart_items as $item): ?>
                            <div class="cart-item">
                                <div class="product-info">
                                    <h4><?= htmlspecialchars($item['name']) ?></h4>
                                    <p><?= htmlspecialchars($products[$item['id']]['description']) ?></p>
                                </div>
                                <div>₱<?= number_format($item['price'], 2) ?></div>
                                <div>
                                    <input type="number" name="quantities[<?= $item['id'] ?>]" 
                                           value="<?= $item['quantity'] ?>" min="0" class="quantity-input">
                                </div>
                                <div><strong>₱<?= number_format($item['subtotal'], 2) ?></strong></div>
                                <div>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="action" value="remove_item">
                                        <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                        <button type="submit" class="btn btn-danger" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">✕</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div style="margin-top: 1.5rem; text-align: right;">
                            <button type="submit" class="btn">Update Cart</button>
                            <button type="submit" name="action" value="clear_cart" class="btn btn-danger">Clear Cart</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Order Summary & Earnings -->
            <div class="cart-section">
                <h2 style="color: #2d5a27; margin-bottom: 1.5rem;">💰 Order Summary</h2>
                
                <?php if (!empty($cart_items)): ?>
                    <!-- MLM Earnings Preview -->
                    <div class="earnings-card">
                        <h3 style="color: #2d5a27; margin-bottom: 1rem;">🎯 Your MLM Earnings</h3>
                        <div class="earnings-item">
                            <span>Commission (<?= $member['commission_rate'] ?>%):</span>
                            <strong>₱<?= number_format($potential_commission, 2) ?></strong>
                        </div>
                        <div class="earnings-item">
                            <span>Rebate (<?= $member['rebate_rate'] ?>%):</span>
                            <strong>₱<?= number_format($potential_rebate, 2) ?></strong>
                        </div>
                        <hr style="margin: 0.5rem 0;">
                        <div class="earnings-item" style="font-size: 1.1rem;">
                            <span><strong>Total Earnings:</strong></span>
                            <strong style="color: #28a745;">₱<?= number_format($total_potential_earnings, 2) ?></strong>
                        </div>
                    </div>
                    
                    <!-- Order Total -->
                    <div class="total-section">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span>Subtotal:</span>
                            <span>₱<?= number_format($cart_total, 2) ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                            <span>Shipping:</span>
                            <span>Free (Store Pickup)</span>
                        </div>
                        <hr style="margin: 1rem 0;">
                        <div style="display: flex; justify-content: space-between; font-size: 1.2rem; font-weight: bold;">
                            <span>Total:</span>
                            <span style="color: #2d5a27;">₱<?= number_format($cart_total, 2) ?></span>
                        </div>
                    </div>
                    
                    <!-- Checkout Button -->
                    <form method="post" style="margin-top: 1.5rem;">
                        <input type="hidden" name="action" value="checkout">
                        <button type="submit" class="btn btn-success" style="width: 100%; padding: 1rem; font-size: 1.1rem;">
                            🛒 Proceed to Checkout
                        </button>
                    </form>
                    
                    <div style="margin-top: 1rem; padding: 1rem; background: #fff3cd; border-radius: 8px; font-size: 0.9rem;">
                        <strong>💡 Payment Method:</strong> Cash payment on store pickup<br>
                        <strong>📍 Pickup Location:</strong> ExtremeLife Herbal Store<br>
                        <strong>⏰ Pickup Hours:</strong> Mon-Sat 9AM-6PM
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 2rem; color: #666;">
                        <p>Add products to see your potential MLM earnings!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Auto-update cart when quantity changes
        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('change', function() {
                if (this.value < 0) this.value = 0;
            });
        });
        
        // Confirm before clearing cart
        document.querySelector('button[value="clear_cart"]')?.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to clear your cart?')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
