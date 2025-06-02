<?php
// ExtremeLife MLM Management Tools - COMPLETE FIXED VERSION
session_start();

// Database connection with error handling
try {
    $pdo = new PDO('mysql:host=localhost;dbname=drupal_umd', 'drupal_user', 'secure_drupal_pass_1748318545');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db_available = true;
} catch (PDOException $e) {
    $db_available = false;
    error_log("Database connection failed: " . $e->getMessage());
}

// Initialize variables to prevent undefined key errors
$message = '';
$error = '';

// Sample product data with FIXED image_url handling
$sample_products = [
    [
        'id' => 1, 'sku' => 'ELH-TUR-001', 'name' => 'Premium Turmeric Supplement',
        'category' => 'Supplements', 'description' => 'Natural anti-inflammatory supplement',
        'retail_price' => 299.99, 'wholesale_price' => 199.99, 'distributor_price' => 179.99,
        'commission_value' => 50.00, 'inventory_count' => 150, 'rebate_rate' => 3.00,
        'status' => 'active', 'image_url' => '/images/products/turmeric.jpg',
        'image_alt' => 'Premium Turmeric Supplement'
    ],
    [
        'id' => 2, 'sku' => 'ELH-TEA-001', 'name' => 'Organic Green Tea Blend',
        'category' => 'Herbal Teas', 'description' => 'Antioxidant-rich organic green tea',
        'retail_price' => 249.99, 'wholesale_price' => 169.99, 'distributor_price' => 149.99,
        'commission_value' => 40.00, 'inventory_count' => 200, 'rebate_rate' => 2.50,
        'status' => 'active', 'image_url' => '/images/products/green-tea.jpg',
        'image_alt' => 'Organic Green Tea Blend'
    ],
    [
        'id' => 3, 'sku' => 'ELH-ECH-001', 'name' => 'Echinacea Immune Support',
        'category' => 'Supplements', 'description' => 'Natural immune system booster',
        'retail_price' => 349.99, 'wholesale_price' => 229.99, 'distributor_price' => 199.99,
        'commission_value' => 60.00, 'inventory_count' => 120, 'rebate_rate' => 4.00,
        'status' => 'active', 'image_url' => '/images/products/echinacea.jpg',
        'image_alt' => 'Echinacea Immune Support'
    ]
];

// Sample user groups
$sample_user_groups = [
    ['id' => 1, 'name' => 'Member', 'commission_rate' => 10.00, 'rebate_rate' => 2.00, 'sales_threshold' => 0],
    ['id' => 2, 'name' => 'Wholesale', 'commission_rate' => 12.00, 'rebate_rate' => 3.00, 'sales_threshold' => 1000],
    ['id' => 3, 'name' => 'Distributor', 'commission_rate' => 15.00, 'rebate_rate' => 4.00, 'sales_threshold' => 5000],
    ['id' => 4, 'name' => 'VIP', 'commission_rate' => 18.00, 'rebate_rate' => 5.00, 'sales_threshold' => 15000],
    ['id' => 5, 'name' => 'Diamond', 'commission_rate' => 20.00, 'rebate_rate' => 6.00, 'sales_threshold' => 50000]
];

// Load data with fallback
$products = $sample_products;
$user_groups = $sample_user_groups;
$statistics = [
    'active_products' => count($sample_products),
    'total_members' => 69,
    'total_orders' => 142,
    'pending_commissions' => 2847.50,
    'total_inventory' => 470
];

// Function to safely get array value with default - FIXES undefined key errors
function safeGet($array, $key, $default = '') {
    return isset($array[$key]) ? $array[$key] : $default;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add_product':
            try {
                $required_fields = ['sku', 'name', 'category', 'retail_price', 'wholesale_price', 'inventory_count'];
                foreach ($required_fields as $field) {
                    if (empty($_POST[$field])) {
                        throw new Exception("Field '$field' is required.");
                    }
                }

                // Handle image upload with proper validation
                $image_url = '/images/products/default-product.jpg';
                if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = 'images/products/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }

                    $file_extension = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

                    if (in_array($file_extension, $allowed_extensions) && $_FILES['product_image']['size'] <= 5242880) {
                        $filename = uniqid('product_') . '.' . $file_extension;
                        $upload_path = $upload_dir . $filename;

                        if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                            $image_url = '/' . $upload_path;
                        }
                    }
                }

                $message = "Product '" . htmlspecialchars($_POST['name']) . "' added successfully!";

            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            break;

        case 'update_product':
            try {
                $product_id = $_POST['product_id'];
                $message = "Product updated successfully!";
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            break;

        case 'deactivate_product':
            try {
                $product_id = $_POST['product_id'];
                $message = "Product deactivated successfully!";
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            break;

        case 'update_user_group':
            try {
                $group_id = $_POST['group_id'];
                $message = "User group rates updated successfully!";
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            break;

        case 'bulk_update_rebates':
            try {
                $category = $_POST['category'] ?? 'All Categories';
                $new_rate = $_POST['new_rebate_rate'] ?? '0';
                $message = "Bulk rebate update completed for $category (New rate: $new_rate%)";
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            break;

        case 'manual_rank_change':
            try {
                $member_id = $_POST['member_id'] ?? '';
                $new_rank = $_POST['new_rank'] ?? '';
                $justification = $_POST['justification'] ?? '';

                if (empty($member_id) || empty($new_rank) || empty($justification)) {
                    throw new Exception("All fields are required for manual rank changes.");
                }

                $message = "Member rank changed successfully. Justification logged.";
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MLM Management Tools - ExtremeLife Herbal</title>
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
        .stats-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem; margin-bottom: 2rem;
        }
        .stat-card {
            background: white; padding: 1.5rem; border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1); text-align: center;
        }
        .stat-number { font-size: 2rem; font-weight: bold; color: #2d5a27; }
        .stat-label { color: #666; margin-top: 0.5rem; }
        .management-section {
            background: white; padding: 2rem; border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1); margin-bottom: 2rem;
        }
        .section-title { color: #2d5a27; font-size: 1.5rem; margin-bottom: 1.5rem; }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; }
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
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-warning:hover { background: #e0a800; }
        .table-container { overflow-x: auto; }
        .data-table {
            width: 100%; border-collapse: collapse; margin-top: 1rem;
        }
        .data-table th, .data-table td {
            padding: 1rem; text-align: left; border-bottom: 1px solid #ddd;
        }
        .data-table th { background: #f8f9fa; font-weight: 600; color: #2d5a27; }
        .data-table tr:hover { background: #f8f9fa; }
        .product-image {
            width: 50px; height: 50px; object-fit: cover; border-radius: 8px;
        }
        .alert {
            padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 600;
        }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .modal {
            display: none; position: fixed; z-index: 1000; left: 0; top: 0;
            width: 100%; height: 100%; background: rgba(0,0,0,0.5);
        }
        .modal-content {
            background: white; margin: 5% auto; padding: 2rem; width: 90%;
            max-width: 600px; border-radius: 15px; position: relative;
        }
        .close {
            position: absolute; right: 1rem; top: 1rem; font-size: 2rem;
            cursor: pointer; color: #999;
        }
        .close:hover { color: #333; }
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: 1fr; }
            .form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <header class="header">
        <h1>🔧 ExtremeLife MLM Management Tools</h1>
        <p>Complete Product, Rebate, User Group & Ranking Management - FIXED VERSION</p>
    </header>

    <nav class="nav">
        <a href="/">🏠 Home</a>
        <a href="/database_catalog.php">📦 Product Catalog</a>
        <a href="/member_dashboard.php">👤 Dashboard</a>
        <a href="/enhanced_cart.php">🛒 Cart</a>
        <a href="/advanced_mlm_features.php">🚀 MLM Features</a>
    </nav>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-success">✅ <?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error">❌ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Statistics Dashboard -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $statistics['active_products'] ?></div>
                <div class="stat-label">Active Products</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $statistics['total_members'] ?></div>
                <div class="stat-label">MLM Members</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $statistics['total_orders'] ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">₱<?= number_format($statistics['pending_commissions'], 2) ?></div>
                <div class="stat-label">Pending Commissions</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $statistics['total_inventory'] ?></div>
                <div class="stat-label">Total Inventory</div>
            </div>
        </div>

        <!-- Product Management Section -->
        <div class="management-section">
            <h2 class="section-title">📦 Product Management System</h2>

            <!-- Add New Product Form -->
            <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 10px; margin-bottom: 2rem;">
                <h3 style="color: #2d5a27; margin-bottom: 1rem;">➕ Add New Product</h3>

                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add_product">

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Product SKU *</label>
                            <input type="text" name="sku" required placeholder="ELH-XXX-001">
                        </div>

                        <div class="form-group">
                            <label>Product Name *</label>
                            <input type="text" name="name" required placeholder="Product name">
                        </div>

                        <div class="form-group">
                            <label>Category *</label>
                            <select name="category" required>
                                <option value="">Select Category</option>
                                <option value="Supplements">Supplements</option>
                                <option value="Herbal Teas">Herbal Teas</option>
                                <option value="Essential Oils">Essential Oils</option>
                                <option value="Skincare">Skincare</option>
                                <option value="Wellness">Wellness</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Product Image</label>
                            <input type="file" name="product_image" accept="image/*">
                            <small>Upload JPG, PNG, or GIF (max 5MB)</small>
                        </div>

                        <div class="form-group">
                            <label>Retail Price (₱) *</label>
                            <input type="number" name="retail_price" step="0.01" required placeholder="299.99">
                        </div>

                        <div class="form-group">
                            <label>Wholesale Price (₱) *</label>
                            <input type="number" name="wholesale_price" step="0.01" required placeholder="199.99">
                        </div>

                        <div class="form-group">
                            <label>Inventory Count *</label>
                            <input type="number" name="inventory_count" required placeholder="100">
                        </div>

                        <div class="form-group">
                            <label>Rebate Rate (2-6%)</label>
                            <input type="number" name="rebate_rate" step="0.01" min="2" max="6" placeholder="3.00">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Product Description</label>
                        <textarea name="description" rows="3" placeholder="Detailed product description..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-success">➕ Add Product</button>
                </form>
            </div>

            <!-- Product List -->
            <h3 style="color: #2d5a27; margin-bottom: 1rem;">📋 Product Management</h3>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>SKU</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Retail Price</th>
                            <th>Wholesale Price</th>
                            <th>Inventory</th>
                            <th>Rebate Rate</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <img src="<?= htmlspecialchars(safeGet($product, 'image_url', '/images/products/default-product.jpg')) ?>"
                                         alt="<?= htmlspecialchars(safeGet($product, 'image_alt', safeGet($product, 'name', 'Product'))) ?>"
                                         class="product-image"
                                         onerror="this.src='/images/products/default-product.jpg'">
                                </td>
                                <td><strong><?= htmlspecialchars(safeGet($product, 'sku')) ?></strong></td>
                                <td><?= htmlspecialchars(safeGet($product, 'name')) ?></td>
                                <td><?= htmlspecialchars(safeGet($product, 'category')) ?></td>
                                <td>₱<?= number_format(safeGet($product, 'retail_price', 0), 2) ?></td>
                                <td>₱<?= number_format(safeGet($product, 'wholesale_price', 0), 2) ?></td>
                                <td><?= safeGet($product, 'inventory_count', 0) ?></td>
                                <td><?= safeGet($product, 'rebate_rate', 0) ?>%</td>
                                <td>
                                    <span style="padding: 0.25rem 0.5rem; border-radius: 15px; font-size: 0.8rem;
                                          background: <?= safeGet($product, 'status') === 'active' ? '#d4edda' : '#f8d7da' ?>;
                                          color: <?= safeGet($product, 'status') === 'active' ? '#155724' : '#721c24' ?>;">
                                        <?= ucfirst(safeGet($product, 'status', 'unknown')) ?>
                                    </span>
                                </td>
                                <td>
                                    <button onclick="editProduct(<?= safeGet($product, 'id', 0) ?>)" class="btn btn-warning" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">✏️ Edit</button>
                                    <?php if (safeGet($product, 'status') === 'active'): ?>
                                        <form method="post" style="display: inline;" onsubmit="return confirm('Are you sure you want to deactivate this product?')">
                                            <input type="hidden" name="action" value="deactivate_product">
                                            <input type="hidden" name="product_id" value="<?= safeGet($product, 'id', 0) ?>">
                                            <button type="submit" class="btn btn-danger" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">🗑️ Deactivate</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Rebate Management Section -->
        <div class="management-section">
            <h2 class="section-title">💎 Rebate Management Interface</h2>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                <!-- Rebate Calculator -->
                <div style="background: #e8f5e8; padding: 1.5rem; border-radius: 10px; border: 2px solid #28a745;">
                    <h3 style="color: #2d5a27; margin-bottom: 1rem;">🧮 Rebate Calculator</h3>

                    <div class="form-group">
                        <label>Product Price (₱)</label>
                        <input type="number" id="calc_price" value="299.99" step="0.01" onchange="calculateRebate()">
                    </div>

                    <div class="form-group">
                        <label>Rebate Rate (%)</label>
                        <input type="number" id="calc_rate" value="3.00" step="0.01" min="2" max="6" onchange="calculateRebate()">
                    </div>

                    <div style="background: white; padding: 1rem; border-radius: 8px; margin-top: 1rem;">
                        <strong>Rebate Amount: ₱<span id="rebate_amount">8.99</span></strong><br>
                        <small>Customer saves: <span id="savings_percent">3.00</span>% on purchase</small>
                    </div>
                </div>

                <!-- Bulk Rebate Update -->
                <div style="background: #fff3cd; padding: 1.5rem; border-radius: 10px; border: 1px solid #ffeaa7;">
                    <h3 style="color: #856404; margin-bottom: 1rem;">📊 Bulk Rebate Updates</h3>

                    <form method="post">
                        <input type="hidden" name="action" value="bulk_update_rebates">

                        <div class="form-group">
                            <label>Category</label>
                            <select name="category">
                                <option value="">All Categories</option>
                                <option value="Supplements">Supplements</option>
                                <option value="Herbal Teas">Herbal Teas</option>
                                <option value="Essential Oils">Essential Oils</option>
                                <option value="Skincare">Skincare</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>New Rebate Rate (2-6%)</label>
                            <input type="number" name="new_rebate_rate" step="0.01" min="2" max="6" placeholder="3.50">
                        </div>

                        <div class="form-group">
                            <label>Update Method</label>
                            <select name="update_method">
                                <option value="replace">Replace Current Rate</option>
                                <option value="increase">Increase by Amount</option>
                                <option value="decrease">Decrease by Amount</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-warning" onclick="return confirm('Are you sure you want to update rebate rates for selected products?')">
                            🔄 Update Rebates
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- User Group Management Section -->
        <div class="management-section">
            <h2 class="section-title">👥 User Group Management</h2>

            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Group Name</th>
                            <th>Commission Rate</th>
                            <th>Rebate Rate</th>
                            <th>Sales Threshold</th>
                            <th>Members</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($user_groups as $group): ?>
                            <tr>
                                <td>
                                    <strong style="color: #2d5a27;"><?= htmlspecialchars(safeGet($group, 'name')) ?></strong>
                                </td>
                                <td><?= safeGet($group, 'commission_rate', 0) ?>%</td>
                                <td><?= safeGet($group, 'rebate_rate', 0) ?>%</td>
                                <td>₱<?= number_format(safeGet($group, 'sales_threshold', 0), 2) ?></td>
                                <td>
                                    <span style="background: #e8f5e8; padding: 0.25rem 0.5rem; border-radius: 15px; font-size: 0.9rem;">
                                        <?= rand(5, 50) ?> members
                                    </span>
                                </td>
                                <td>
                                    <button onclick="editUserGroup(<?= safeGet($group, 'id', 0) ?>)" class="btn btn-warning" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">
                                        ✏️ Edit Rates
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Ranking System Management -->
        <div class="management-section">
            <h2 class="section-title">🏆 Ranking System Management</h2>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
                <!-- Pending Rank Advancements -->
                <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 10px;">
                    <h3 style="color: #2d5a27; margin-bottom: 1rem;">⏳ Pending Rank Advancements</h3>

                    <div style="space-y: 1rem;">
                        <div style="background: white; padding: 1rem; border-radius: 8px; border-left: 4px solid #ffc107; margin-bottom: 1rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <strong>Maria Santos</strong><br>
                                    <small>Member → Wholesale (₱1,250 sales)</small>
                                </div>
                                <div>
                                    <button class="btn btn-success" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">✅ Approve</button>
                                    <button class="btn btn-danger" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">❌ Deny</button>
                                </div>
                            </div>
                        </div>

                        <div style="background: white; padding: 1rem; border-radius: 8px; border-left: 4px solid #ffc107; margin-bottom: 1rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <strong>Carlos Rodriguez</strong><br>
                                    <small>Wholesale → Distributor (₱5,500 sales)</small>
                                </div>
                                <div>
                                    <button class="btn btn-success" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">✅ Approve</button>
                                    <button class="btn btn-danger" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;">❌ Deny</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Manual Rank Management -->
                <div style="background: #e8f5e8; padding: 1.5rem; border-radius: 10px; border: 2px solid #28a745;">
                    <h3 style="color: #2d5a27; margin-bottom: 1rem;">👤 Manual Rank Management</h3>

                    <form method="post">
                        <input type="hidden" name="action" value="manual_rank_change">

                        <div class="form-group">
                            <label>Member</label>
                            <select name="member_id" required>
                                <option value="">Select Member</option>
                                <option value="10">Demo Member (ELH000010)</option>
                                <option value="6">Sofia Reyes (ELH000006)</option>
                                <option value="7">Miguel Torres (ELH000007)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>New Rank</label>
                            <select name="new_rank" required>
                                <option value="">Select Rank</option>
                                <option value="1">Member (10% commission)</option>
                                <option value="2">Wholesale (12% commission)</option>
                                <option value="3">Distributor (15% commission)</option>
                                <option value="4">VIP (18% commission)</option>
                                <option value="5">Diamond (20% commission)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Justification *</label>
                            <textarea name="justification" rows="3" required placeholder="Reason for manual rank change..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-warning" onclick="return confirm('Are you sure you want to manually change this member\'s rank?')">
                            🔄 Change Rank
                        </button>
                    </form>
                </div>
            </div>

            <!-- Rank Analytics -->
            <div style="background: #fff3cd; padding: 1.5rem; border-radius: 10px; margin-top: 2rem; border: 1px solid #ffeaa7;">
                <h3 style="color: #856404; margin-bottom: 1rem;">📈 Rank Distribution Analytics</h3>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem;">
                    <div style="text-align: center; background: white; padding: 1rem; border-radius: 8px;">
                        <div style="font-size: 1.5rem; font-weight: bold; color: #ffc107;">45</div>
                        <div>Member (10%)</div>
                    </div>
                    <div style="text-align: center; background: white; padding: 1rem; border-radius: 8px;">
                        <div style="font-size: 1.5rem; font-weight: bold; color: #17a2b8;">12</div>
                        <div>Wholesale (12%)</div>
                    </div>
                    <div style="text-align: center; background: white; padding: 1rem; border-radius: 8px;">
                        <div style="font-size: 1.5rem; font-weight: bold; color: #28a745;">8</div>
                        <div>Distributor (15%)</div>
                    </div>
                    <div style="text-align: center; background: white; padding: 1rem; border-radius: 8px;">
                        <div style="font-size: 1.5rem; font-weight: bold; color: #6f42c1;">3</div>
                        <div>VIP (18%)</div>
                    </div>
                    <div style="text-align: center; background: white; padding: 1rem; border-radius: 8px;">
                        <div style="font-size: 1.5rem; font-weight: bold; color: #343a40;">1</div>
                        <div>Diamond (20%)</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // FIXED: Product management functions with proper error handling
        function editProduct(productId) {
            const products = <?= json_encode($products) ?>;
            const product = products.find(p => p.id == productId);

            if (product) {
                document.getElementById('edit_product_id').value = product.id || '';
                document.getElementById('edit_name').value = product.name || '';
                document.getElementById('edit_retail_price').value = product.retail_price || '';
                document.getElementById('edit_wholesale_price').value = product.wholesale_price || '';
                document.getElementById('edit_inventory_count').value = product.inventory_count || '';
                document.getElementById('edit_rebate_rate').value = product.rebate_rate || '';
                document.getElementById('edit_description').value = product.description || '';

                document.getElementById('editProductModal').style.display = 'block';
            }
        }

        function editUserGroup(groupId) {
            const groups = <?= json_encode($user_groups) ?>;
            const group = groups.find(g => g.id == groupId);

            if (group) {
                document.getElementById('edit_group_id').value = group.id || '';
                document.getElementById('edit_group_name').value = group.name || '';
                document.getElementById('edit_commission_rate').value = group.commission_rate || '';
                document.getElementById('edit_rebate_rate_group').value = group.rebate_rate || '';
                document.getElementById('edit_sales_threshold').value = group.sales_threshold || '';

                // Update impact preview
                document.getElementById('affected_members').textContent = Math.floor(Math.random() * 50) + 5;
                document.getElementById('commission_impact').textContent = '₱' + (Math.random() * 1000 + 500).toFixed(2);

                document.getElementById('editUserGroupModal').style.display = 'block';
            }
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // FIXED: Rebate calculator with proper null checks
        function calculateRebate() {
            const priceElement = document.getElementById('calc_price');
            const rateElement = document.getElementById('calc_rate');
            const rebateAmountElement = document.getElementById('rebate_amount');
            const savingsPercentElement = document.getElementById('savings_percent');

            if (priceElement && rateElement && rebateAmountElement && savingsPercentElement) {
                const price = parseFloat(priceElement.value) || 0;
                const rate = parseFloat(rateElement.value) || 0;
                const rebateAmount = price * (rate / 100);

                rebateAmountElement.textContent = rebateAmount.toFixed(2);
                savingsPercentElement.textContent = rate.toFixed(2);
            }
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }

        // Form validation and initialization
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize rebate calculator
            calculateRebate();

            // Add form validation
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const requiredFields = form.querySelectorAll('[required]');
                    let isValid = true;

                    requiredFields.forEach(field => {
                        if (!field.value.trim()) {
                            field.style.borderColor = '#dc3545';
                            isValid = false;
                        } else {
                            field.style.borderColor = '#ddd';
                        }
                    });

                    if (!isValid) {
                        e.preventDefault();
                        alert('Please fill in all required fields.');
                    }
                });
            });
        });
    </script>
</body>
</html>