<?php
// ExtremeLife MLM Order Management System
session_start();

try {
    $pdo = new PDO('mysql:host=localhost;dbname=drupal_umd', 'drupal_user', 'secure_drupal_pass_1748318545');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

$message = '';
$error = '';

// Handle order status updates
if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'update_status':
            try {
                $stmt = $pdo->prepare("UPDATE mlm_orders SET status = ?, updated_date = NOW() WHERE id = ?");
                $stmt->execute([$_POST['status'], $_POST['order_id']]);
                $message = "Order status updated successfully!";
            } catch (PDOException $e) {
                $error = "Error updating order status: " . $e->getMessage();
            }
            break;
    }
}

// Get orders with pagination
$page = $_GET['page'] ?? 1;
$limit = 20;
$offset = ($page - 1) * $limit;

try {
    $orders = $pdo->query("SELECT * FROM mlm_orders ORDER BY created_date DESC LIMIT $limit OFFSET $offset")->fetchAll();
    $total_orders = $pdo->query("SELECT COUNT(*) FROM mlm_orders")->fetchColumn();
    $total_pages = ceil($total_orders / $limit);
} catch (PDOException $e) {
    $orders = [];
    $total_orders = 0;
    $total_pages = 1;
}

// Get order statistics
try {
    $stats = [
        'pending' => $pdo->query("SELECT COUNT(*) FROM mlm_orders WHERE status = 'pending'")->fetchColumn(),
        'confirmed' => $pdo->query("SELECT COUNT(*) FROM mlm_orders WHERE status = 'confirmed'")->fetchColumn(),
        'ready' => $pdo->query("SELECT COUNT(*) FROM mlm_orders WHERE status = 'ready'")->fetchColumn(),
        'completed' => $pdo->query("SELECT COUNT(*) FROM mlm_orders WHERE status = 'completed'")->fetchColumn(),
        'total_revenue' => $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM mlm_orders WHERE status IN ('completed', 'ready')")->fetchColumn()
    ];
} catch (PDOException $e) {
    $stats = ['pending' => 0, 'confirmed' => 0, 'ready' => 0, 'completed' => 0, 'total_revenue' => 0];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management - ExtremeLife Herbal</title>
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
        .container { max-width: 1400px; margin: 0 auto; padding: 2rem; }
        .alert {
            padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 600;
        }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .stats-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem; margin-bottom: 2rem;
        }
        .stat-card {
            background: white; padding: 2rem; border-radius: 10px;
            text-align: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .stat-number { font-size: 2.5rem; font-weight: bold; color: #2d5a27; }
        .stat-label { color: #666; font-weight: 600; margin-top: 0.5rem; }
        .orders-card {
            background: white; padding: 2rem; border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .orders-table {
            width: 100%; border-collapse: collapse; margin-top: 1rem;
        }
        .orders-table th, .orders-table td {
            padding: 0.75rem; text-align: left; border-bottom: 1px solid #ddd;
        }
        .orders-table th { background: #f8f9fa; font-weight: 600; }
        .orders-table tr:hover { background: #f8f9fa; }
        .status-badge {
            padding: 0.25rem 0.75rem; border-radius: 15px; font-size: 0.8rem;
            font-weight: 600; text-transform: uppercase;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #cce5ff; color: #004085; }
        .status-ready { background: #d4edda; color: #155724; }
        .status-completed { background: #e2e3e5; color: #383d41; }
        .btn {
            background: #2d5a27; color: white; padding: 0.5rem 1rem;
            border: none; border-radius: 20px; cursor: pointer;
            font-weight: 600; font-size: 0.9rem; text-decoration: none;
            display: inline-block; margin: 0.25rem;
        }
        .btn:hover { background: #4a7c59; }
        .btn-sm { padding: 0.25rem 0.75rem; font-size: 0.8rem; }
        .pagination {
            display: flex; justify-content: center; gap: 0.5rem; margin-top: 2rem;
        }
        .pagination a {
            padding: 0.5rem 1rem; background: white; color: #2d5a27;
            text-decoration: none; border-radius: 5px; border: 1px solid #ddd;
        }
        .pagination a:hover { background: #e8f5e8; }
        .pagination .active { background: #2d5a27; color: white; }
    </style>
</head>
<body>
    <header class="header">
        <h1>📦 ExtremeLife Order Management</h1>
        <p>Manage customer orders and store pickup operations</p>
    </header>

    <nav class="nav">
        <a href="/">🏠 Home</a>
        <a href="/member_dashboard.php">👤 Dashboard</a>
        <a href="/database_catalog.php">📦 Products</a>
        <a href="/cart.php">🛒 Cart</a>
        <a href="/checkout.php">💳 Checkout</a>
        <a href="/mlm_tools.php">🔧 MLM Tools</a>
        <a href="/income_management.php">💰 Income</a>
    </nav>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Order Statistics Dashboard -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $stats['pending'] ?></div>
                <div class="stat-label">Pending Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['confirmed'] ?></div>
                <div class="stat-label">Confirmed Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['ready'] ?></div>
                <div class="stat-label">Ready for Pickup</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['completed'] ?></div>
                <div class="stat-label">Completed Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">₱<?= number_format($stats['total_revenue'], 2) ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
        </div>

        <!-- Order Management Table -->
        <div class="orders-card">
            <h2 style="color: #2d5a27; margin-bottom: 1.5rem;">📋 Order Management</h2>

            <!-- Order Filters -->
            <div style="margin-bottom: 1.5rem; display: flex; gap: 1rem; flex-wrap: wrap;">
                <select onchange="filterOrders(this.value)" style="padding: 0.5rem; border: 2px solid #ddd; border-radius: 5px;">
                    <option value="">All Orders</option>
                    <option value="pending">Pending</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="ready">Ready for Pickup</option>
                    <option value="completed">Completed</option>
                </select>

                <input type="date" onchange="filterByDate(this.value)" style="padding: 0.5rem; border: 2px solid #ddd; border-radius: 5px;">

                <input type="text" placeholder="Search customer..." onkeyup="searchOrders(this.value)" style="padding: 0.5rem; border: 2px solid #ddd; border-radius: 5px; flex: 1; min-width: 200px;">
            </div>

            <table class="orders-table" id="ordersTable">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Pickup Date</th>
                        <th>Status</th>
                        <th>Commission</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr data-status="<?= $order['status'] ?>" data-customer="<?= strtolower($order['customer_name']) ?>" data-date="<?= $order['pickup_date'] ?>">
                        <td><strong><?= htmlspecialchars($order['order_number']) ?></strong></td>
                        <td>
                            <div>
                                <strong><?= htmlspecialchars($order['customer_name']) ?></strong><br>
                                <small><?= htmlspecialchars($order['customer_email']) ?></small><br>
                                <small><?= htmlspecialchars($order['customer_phone']) ?></small>
                            </div>
                        </td>
                        <td><strong>₱<?= number_format($order['total_amount'], 2) ?></strong></td>
                        <td>
                            <?= date('M d, Y', strtotime($order['pickup_date'])) ?><br>
                            <small><?= date('g:i A', strtotime($order['pickup_time'])) ?></small>
                        </td>
                        <td>
                            <span class="status-badge status-<?= $order['status'] ?>">
                                <?= ucfirst($order['status']) ?>
                            </span>
                        </td>
                        <td>
                            <?php
                            $commission = $order['total_amount'] * 0.10; // 10% default commission
                            echo '₱' . number_format($commission, 2);
                            ?>
                        </td>
                        <td>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                <select name="status" onchange="this.form.submit()" class="btn btn-sm">
                                    <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="confirmed" <?= $order['status'] == 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                                    <option value="ready" <?= $order['status'] == 'ready' ? 'selected' : '' ?>>Ready</option>
                                    <option value="completed" <?= $order['status'] == 'completed' ? 'selected' : '' ?>>Completed</option>
                                </select>
                            </form>
                            <button class="btn btn-sm" onclick="viewOrderDetails(<?= $order['id'] ?>)">👁️ View</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="pagination">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>" class="<?= $page == $i ? 'active' : '' ?>"><?= $i ?></a>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Daily Operations Summary -->
        <div class="orders-card" style="margin-top: 2rem;">
            <h2 style="color: #2d5a27; margin-bottom: 1.5rem;">📊 Daily Operations Summary</h2>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
                <div style="background: #e8f5e8; padding: 1.5rem; border-radius: 10px; border-left: 4px solid #28a745;">
                    <h4 style="color: #28a745;">Today's Orders</h4>
                    <p style="font-size: 2rem; font-weight: bold; color: #2d5a27;">
                        <?php
                        try {
                            $today_orders = $pdo->query("SELECT COUNT(*) FROM mlm_orders WHERE DATE(created_date) = CURDATE()")->fetchColumn();
                            echo $today_orders;
                        } catch (PDOException $e) {
                            echo "0";
                        }
                        ?>
                    </p>
                    <small>New orders today</small>
                </div>

                <div style="background: #fff3e0; padding: 1.5rem; border-radius: 10px; border-left: 4px solid #f57c00;">
                    <h4 style="color: #f57c00;">Today's Revenue</h4>
                    <p style="font-size: 2rem; font-weight: bold; color: #2d5a27;">
                        ₱<?php
                        try {
                            $today_revenue = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM mlm_orders WHERE DATE(created_date) = CURDATE()")->fetchColumn();
                            echo number_format($today_revenue, 2);
                        } catch (PDOException $e) {
                            echo "0.00";
                        }
                        ?>
                    </p>
                    <small>Sales today</small>
                </div>

                <div style="background: #f3e5f5; padding: 1.5rem; border-radius: 10px; border-left: 4px solid #7b1fa2;">
                    <h4 style="color: #7b1fa2;">Pickups Today</h4>
                    <p style="font-size: 2rem; font-weight: bold; color: #2d5a27;">
                        <?php
                        try {
                            $today_pickups = $pdo->query("SELECT COUNT(*) FROM mlm_orders WHERE pickup_date = CURDATE()")->fetchColumn();
                            echo $today_pickups;
                        } catch (PDOException $e) {
                            echo "0";
                        }
                        ?>
                    </p>
                    <small>Scheduled pickups</small>
                </div>

                <div style="background: #e3f2fd; padding: 1.5rem; border-radius: 10px; border-left: 4px solid #1976d2;">
                    <h4 style="color: #1976d2;">Commission Earned</h4>
                    <p style="font-size: 2rem; font-weight: bold; color: #2d5a27;">
                        ₱<?php
                        try {
                            $today_commission = $pdo->query("SELECT COALESCE(SUM(total_amount * 0.10), 0) FROM mlm_orders WHERE DATE(created_date) = CURDATE()")->fetchColumn();
                            echo number_format($today_commission, 2);
                        } catch (PDOException $e) {
                            echo "0.00";
                        }
                        ?>
                    </p>
                    <small>MLM commissions</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div id="orderModal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
        <div style="background-color: white; margin: 5% auto; padding: 2rem; border-radius: 15px; width: 90%; max-width: 800px;">
            <span onclick="closeModal()" style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
            <h2 style="color: #2d5a27; margin-bottom: 1.5rem;">📋 Order Details</h2>
            <div id="orderDetailsContent">
                <!-- Order details will be loaded here via JavaScript -->
            </div>
        </div>
    </div>

    <script>
        function filterOrders(status) {
            const rows = document.querySelectorAll("#ordersTable tbody tr");
            rows.forEach(row => {
                if (status === "" || row.dataset.status === status) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        }

        function filterByDate(date) {
            const rows = document.querySelectorAll("#ordersTable tbody tr");
            rows.forEach(row => {
                if (date === "" || row.dataset.date === date) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        }

        function searchOrders(query) {
            const rows = document.querySelectorAll("#ordersTable tbody tr");
            query = query.toLowerCase();
            rows.forEach(row => {
                const customer = row.dataset.customer;
                if (query === "" || customer.includes(query)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        }

        function viewOrderDetails(orderId) {
            // In a real application, this would fetch order details via AJAX
            document.getElementById("orderDetailsContent").innerHTML = `
                <div style="text-align: center; padding: 2rem;">
                    <h3>Order #ELH${orderId.toString().padStart(6, "0")}</h3>
                    <p>Order details would be loaded here...</p>
                    <div style="margin-top: 2rem;">
                        <button class="btn" onclick="closeModal()">Close</button>
                    </div>
                </div>
            `;
            document.getElementById("orderModal").style.display = "block";
        }

        function closeModal() {
            document.getElementById("orderModal").style.display = "none";
        }

        // Auto-refresh order status every 30 seconds
        setInterval(() => {
            // In a real application, this would refresh order data via AJAX
            console.log("Auto-refresh order data (placeholder)");
        }, 30000);

        // Add loading states for status updates
        document.querySelectorAll("select[name='status']").forEach(select => {
            select.addEventListener("change", function() {
                this.disabled = true;
                this.style.opacity = "0.6";

                // Re-enable after form submission
                setTimeout(() => {
                    this.disabled = false;
                    this.style.opacity = "1";
                }, 2000);
            });
        });
    </script>
</body>
</html>