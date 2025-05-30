<?php
// ExtremeLife MLM Checkout Confirmation with Commission Processing
session_start();

// Check if there's a pending order
if (!isset($_SESSION['pending_order'])) {
    header('Location: enhanced_cart.php');
    exit;
}

$order = $_SESSION['pending_order'];

// Database connection with fallback
try {
    $pdo = new PDO('mysql:host=localhost;dbname=drupal_umd', 'drupal_user', 'secure_drupal_pass_1748318545');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db_available = true;
} catch (PDOException $e) {
    $db_available = false;
}

$message = '';
$error = '';

// Handle order confirmation
if (isset($_POST['action']) && $_POST['action'] == 'confirm_order') {
    try {
        $pickup_date = $_POST['pickup_date'];
        $pickup_time = $_POST['pickup_time'];
        $customer_notes = $_POST['customer_notes'] ?? '';
        
        // Validate pickup date (must be future date)
        if (strtotime($pickup_date) <= time()) {
            throw new Exception("Pickup date must be in the future.");
        }
        
        // Process order
        if ($db_available) {
            // Save to database
            $pdo->beginTransaction();
            
            // Insert order
            $stmt = $pdo->prepare("INSERT INTO mlm_orders (
                order_number, member_id, customer_name, customer_email, customer_phone,
                total_amount, pickup_date, pickup_time, payment_method, status, 
                customer_notes, created_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'cash_pickup', 'pending', ?, NOW())");
            
            $stmt->execute([
                $order['order_number'],
                $order['member_id'],
                $order['member_name'],
                $_SESSION['member_email'] ?? 'demo@extremelifeherbal.com',
                $_SESSION['member_phone'] ?? '+63 912 000 0010',
                $order['total'],
                $pickup_date,
                $pickup_time,
                $customer_notes
            ]);
            
            $order_id = $pdo->lastInsertId();
            
            // Insert order items
            foreach ($order['items'] as $item) {
                $stmt = $pdo->prepare("INSERT INTO mlm_order_items (
                    order_id, product_id, product_name, quantity, unit_price, subtotal
                ) VALUES (?, ?, ?, ?, ?, ?)");
                
                $stmt->execute([
                    $order_id,
                    $item['product_id'],
                    $item['name'],
                    $item['quantity'],
                    $item['price'],
                    $item['subtotal']
                ]);
            }
            
            // Process commission
            $stmt = $pdo->prepare("INSERT INTO mlm_commissions (
                member_id, order_id, commission_type, base_amount, commission_rate, 
                commission_amount, rebate_rate, rebate_amount, total_amount, status
            ) VALUES (?, ?, 'direct', ?, ?, ?, ?, ?, ?, 'pending')");
            
            $stmt->execute([
                $order['member_id'],
                $order_id,
                $order['total'],
                $_SESSION['member_commission_rate'] ?? 10.00,
                $order['commission'],
                $_SESSION['member_rebate_rate'] ?? 2.00,
                $order['rebate'],
                $order['total_earnings']
            ]);
            
            $pdo->commit();
            
        } else {
            // Save to file for testing
            $orders_file = 'orders_' . date('Y-m') . '.json';
            $orders = [];
            
            if (file_exists($orders_file)) {
                $orders = json_decode(file_get_contents($orders_file), true) ?? [];
            }
            
            $orders[] = [
                'order_number' => $order['order_number'],
                'member_id' => $order['member_id'],
                'member_name' => $order['member_name'],
                'items' => $order['items'],
                'total' => $order['total'],
                'commission' => $order['commission'],
                'rebate' => $order['rebate'],
                'total_earnings' => $order['total_earnings'],
                'pickup_date' => $pickup_date,
                'pickup_time' => $pickup_time,
                'customer_notes' => $customer_notes,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            file_put_contents($orders_file, json_encode($orders, JSON_PRETTY_PRINT));
        }
        
        // Clear cart and pending order
        $_SESSION['cart'] = [];
        $_SESSION['completed_order'] = $order;
        $_SESSION['completed_order']['pickup_date'] = $pickup_date;
        $_SESSION['completed_order']['pickup_time'] = $pickup_time;
        $_SESSION['completed_order']['customer_notes'] = $customer_notes;
        unset($_SESSION['pending_order']);
        
        // Redirect to success page
        header('Location: order_success.php');
        exit;
        
    } catch (Exception $e) {
        if ($db_available && $pdo->inTransaction()) {
            $pdo->rollback();
        }
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Confirmation - ExtremeLife Herbal MLM</title>
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
        .container { max-width: 800px; margin: 0 auto; padding: 2rem; }
        .confirmation-card {
            background: white; padding: 2rem; border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1); margin-bottom: 2rem;
        }
        .order-summary {
            background: #f8f9fa; padding: 1.5rem; border-radius: 10px;
            border-left: 4px solid #2d5a27; margin-bottom: 2rem;
        }
        .order-item {
            display: flex; justify-content: space-between; padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }
        .order-item:last-child { border-bottom: none; }
        .earnings-highlight {
            background: linear-gradient(135deg, #e8f5e8, #d4edda);
            padding: 1.5rem; border-radius: 10px; margin: 1.5rem 0;
            border: 2px solid #28a745;
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
            font-weight: 600; text-decoration: none; display: inline-block;
            margin: 0.25rem; transition: all 0.3s ease;
        }
        .btn:hover { background: #4a7c59; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        .btn-secondary { background: #6c757d; }
        .btn-secondary:hover { background: #5a6268; }
        .alert {
            padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 600;
        }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .pickup-info {
            background: #fff3cd; padding: 1rem; border-radius: 8px;
            border: 1px solid #ffeaa7; margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <header class="header">
        <h1>✅ Checkout Confirmation</h1>
        <p>Review your order and schedule pickup</p>
    </header>

    <div class="container">
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="confirmation-card">
            <h2 style="color: #2d5a27; margin-bottom: 1.5rem;">📋 Order Review</h2>
            
            <!-- Order Summary -->
            <div class="order-summary">
                <h3 style="color: #2d5a27; margin-bottom: 1rem;">Order #<?= $order['order_number'] ?></h3>
                
                <?php foreach ($order['items'] as $item): ?>
                    <div class="order-item">
                        <div>
                            <strong><?= htmlspecialchars($item['name']) ?></strong><br>
                            <small>Qty: <?= $item['quantity'] ?> × ₱<?= number_format($item['price'], 2) ?></small>
                        </div>
                        <div><strong>₱<?= number_format($item['subtotal'], 2) ?></strong></div>
                    </div>
                <?php endforeach; ?>
                
                <div class="order-item" style="font-size: 1.1rem; font-weight: bold; margin-top: 1rem; padding-top: 1rem; border-top: 2px solid #2d5a27;">
                    <div>Order Total:</div>
                    <div style="color: #2d5a27;">₱<?= number_format($order['total'], 2) ?></div>
                </div>
            </div>

            <!-- MLM Earnings Highlight -->
            <div class="earnings-highlight">
                <h3 style="color: #2d5a27; margin-bottom: 1rem;">🎯 Your MLM Earnings from this Order</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
                    <div style="text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: bold; color: #28a745;">₱<?= number_format($order['commission'], 2) ?></div>
                        <div>Commission</div>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: bold; color: #28a745;">₱<?= number_format($order['rebate'], 2) ?></div>
                        <div>Rebate</div>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 1.8rem; font-weight: bold; color: #2d5a27;">₱<?= number_format($order['total_earnings'], 2) ?></div>
                        <div><strong>Total Earnings</strong></div>
                    </div>
                </div>
            </div>

            <!-- Pickup Information -->
            <div class="pickup-info">
                <h4 style="color: #856404; margin-bottom: 0.5rem;">📍 Store Pickup Information</h4>
                <p><strong>Location:</strong> ExtremeLife Herbal Store</p>
                <p><strong>Address:</strong> 123 Herbal Street, Wellness City</p>
                <p><strong>Hours:</strong> Monday-Saturday 9:00 AM - 6:00 PM</p>
                <p><strong>Payment:</strong> Cash payment upon pickup</p>
            </div>

            <!-- Pickup Scheduling Form -->
            <form method="post">
                <input type="hidden" name="action" value="confirm_order">
                
                <h3 style="color: #2d5a27; margin-bottom: 1rem;">📅 Schedule Your Pickup</h3>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label>Pickup Date</label>
                        <input type="date" name="pickup_date" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Pickup Time</label>
                        <select name="pickup_time" required>
                            <option value="">Select time</option>
                            <option value="09:00">9:00 AM</option>
                            <option value="10:00">10:00 AM</option>
                            <option value="11:00">11:00 AM</option>
                            <option value="12:00">12:00 PM</option>
                            <option value="13:00">1:00 PM</option>
                            <option value="14:00">2:00 PM</option>
                            <option value="15:00">3:00 PM</option>
                            <option value="16:00">4:00 PM</option>
                            <option value="17:00">5:00 PM</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Special Instructions (Optional)</label>
                    <textarea name="customer_notes" rows="3" placeholder="Any special requests or notes for your pickup..."></textarea>
                </div>
                
                <div style="text-align: center; margin-top: 2rem;">
                    <a href="enhanced_cart.php" class="btn btn-secondary">← Back to Cart</a>
                    <button type="submit" class="btn btn-success" style="font-size: 1.1rem; padding: 1rem 2rem;">
                        ✅ Confirm Order & Schedule Pickup
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Set minimum date to tomorrow
        document.querySelector('input[name="pickup_date"]').min = new Date(Date.now() + 86400000).toISOString().split('T')[0];
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const pickupDate = document.querySelector('input[name="pickup_date"]').value;
            const pickupTime = document.querySelector('select[name="pickup_time"]').value;
            
            if (!pickupDate || !pickupTime) {
                e.preventDefault();
                alert('Please select both pickup date and time.');
                return;
            }
            
            const selectedDate = new Date(pickupDate);
            const today = new Date();
            
            if (selectedDate <= today) {
                e.preventDefault();
                alert('Pickup date must be in the future.');
                return;
            }
            
            // Confirm order
            if (!confirm('Are you sure you want to confirm this order?')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
