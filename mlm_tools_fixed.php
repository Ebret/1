<?php
// ExtremeLife MLM Management Tools - FIXED VERSION with Enhanced Features
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
$products = [];
$user_groups = [];
$statistics = [
    'active_products' => 0,
    'total_members' => 0,
    'total_orders' => 0,
    'pending_commissions' => 0.00,
    'total_inventory' => 0
];

// Sample product data with proper array structure
$sample_products = [
    [
        'id' => 1,
        'sku' => 'ELH-TUR-001',
        'name' => 'Premium Turmeric Supplement',
        'category' => 'Supplements',
        'description' => 'Natural anti-inflammatory supplement with curcumin',
        'retail_price' => 299.99,
        'wholesale_price' => 199.99,
        'distributor_price' => 179.99,
        'commission_value' => 50.00,
        'inventory_count' => 150,
        'rebate_rate' => 3.00,
        'status' => 'active',
        'image_url' => '/images/products/turmeric.jpg', // FIXED: Always include image_url
        'image_alt' => 'Premium Turmeric Supplement'
    ],
    [
        'id' => 2,
        'sku' => 'ELH-TEA-001',
        'name' => 'Organic Green Tea Blend',
        'category' => 'Herbal Teas',
        'description' => 'Antioxidant-rich organic green tea blend',
        'retail_price' => 249.99,
        'wholesale_price' => 169.99,
        'distributor_price' => 149.99,
        'commission_value' => 40.00,
        'inventory_count' => 200,
        'rebate_rate' => 2.50,
        'status' => 'active',
        'image_url' => '/images/products/green-tea.jpg', // FIXED: Always include image_url
        'image_alt' => 'Organic Green Tea Blend'
    ],
    [
        'id' => 3,
        'sku' => 'ELH-ECH-001',
        'name' => 'Echinacea Immune Support',
        'category' => 'Supplements',
        'description' => 'Natural immune system booster with echinacea extract',
        'retail_price' => 349.99,
        'wholesale_price' => 229.99,
        'distributor_price' => 199.99,
        'commission_value' => 60.00,
        'inventory_count' => 120,
        'rebate_rate' => 4.00,
        'status' => 'active',
        'image_url' => '/images/products/echinacea.jpg', // FIXED: Always include image_url
        'image_alt' => 'Echinacea Immune Support'
    ]
];

// Sample user groups data
$sample_user_groups = [
    ['id' => 1, 'name' => 'Member', 'commission_rate' => 10.00, 'rebate_rate' => 2.00, 'sales_threshold' => 0],
    ['id' => 2, 'name' => 'Wholesale', 'commission_rate' => 12.00, 'rebate_rate' => 3.00, 'sales_threshold' => 1000],
    ['id' => 3, 'name' => 'Distributor', 'commission_rate' => 15.00, 'rebate_rate' => 4.00, 'sales_threshold' => 5000],
    ['id' => 4, 'name' => 'VIP', 'commission_rate' => 18.00, 'rebate_rate' => 5.00, 'sales_threshold' => 15000],
    ['id' => 5, 'name' => 'Diamond', 'commission_rate' => 20.00, 'rebate_rate' => 6.00, 'sales_threshold' => 50000]
];

// Load data from database or use samples
if ($db_available) {
    try {
        // Get products
        $stmt = $pdo->query("SELECT * FROM mlm_products WHERE status != 'deleted' ORDER BY name");
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Ensure image_url is always set for each product
        foreach ($products as &$product) {
            if (!isset($product['image_url']) || empty($product['image_url'])) {
                $product['image_url'] = '/images/products/default-product.jpg';
            }
            if (!isset($product['image_alt'])) {
                $product['image_alt'] = htmlspecialchars($product['name'] ?? 'Product Image');
            }
        }
        
        // Get user groups
        $stmt = $pdo->query("SELECT * FROM mlm_user_groups ORDER BY sales_threshold");
        $user_groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get statistics
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM mlm_products WHERE status = 'active'");
        $statistics['active_products'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM mlm_members WHERE status = 'active'");
        $statistics['total_members'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM mlm_orders");
        $statistics['total_orders'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COALESCE(SUM(commission_amount), 0) as total FROM mlm_commissions WHERE status = 'pending'");
        $statistics['pending_commissions'] = $stmt->fetchColumn();
        
        $stmt = $pdo->query("SELECT COALESCE(SUM(inventory_count), 0) as total FROM mlm_products WHERE status = 'active'");
        $statistics['total_inventory'] = $stmt->fetchColumn();
        
    } catch (PDOException $e) {
        error_log("Database query error: " . $e->getMessage());
        $products = $sample_products;
        $user_groups = $sample_user_groups;
    }
} else {
    $products = $sample_products;
    $user_groups = $sample_user_groups;
    $statistics = [
        'active_products' => count($sample_products),
        'total_members' => 10,
        'total_orders' => 25,
        'pending_commissions' => 1250.00,
        'total_inventory' => 570
    ];
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_product':
            try {
                // Validate required fields
                $required_fields = ['sku', 'name', 'category', 'retail_price', 'wholesale_price', 'inventory_count'];
                foreach ($required_fields as $field) {
                    if (empty($_POST[$field])) {
                        throw new Exception("Field '$field' is required.");
                    }
                }
                
                // Handle image upload
                $image_url = '/images/products/default-product.jpg';
                if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                    $upload_dir = 'images/products/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file_extension = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
                    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
                    
                    if (in_array($file_extension, $allowed_extensions)) {
                        $filename = uniqid('product_') . '.' . $file_extension;
                        $upload_path = $upload_dir . $filename;
                        
                        if (move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_path)) {
                            $image_url = '/' . $upload_path;
                        }
                    }
                }
                
                if ($db_available) {
                    $stmt = $pdo->prepare("INSERT INTO mlm_products (
                        sku, name, category, description, retail_price, wholesale_price, 
                        distributor_price, commission_value, inventory_count, rebate_rate, 
                        image_url, status, created_date
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())");
                    
                    $stmt->execute([
                        $_POST['sku'],
                        $_POST['name'],
                        $_POST['category'],
                        $_POST['description'] ?? '',
                        $_POST['retail_price'],
                        $_POST['wholesale_price'],
                        $_POST['distributor_price'] ?? $_POST['wholesale_price'],
                        $_POST['commission_value'] ?? 0,
                        $_POST['inventory_count'],
                        $_POST['rebate_rate'] ?? 2.00,
                        $image_url
                    ]);
                    
                    $message = "Product added successfully!";
                } else {
                    $message = "Product would be added (demo mode - database not available)";
                }
                
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            break;
            
        case 'update_product':
            try {
                $product_id = $_POST['product_id'];
                
                if ($db_available) {
                    $stmt = $pdo->prepare("UPDATE mlm_products SET 
                        name = ?, retail_price = ?, wholesale_price = ?, 
                        inventory_count = ?, rebate_rate = ?, description = ?
                        WHERE id = ?");
                    
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['retail_price'],
                        $_POST['wholesale_price'],
                        $_POST['inventory_count'],
                        $_POST['rebate_rate'],
                        $_POST['description'],
                        $product_id
                    ]);
                    
                    $message = "Product updated successfully!";
                } else {
                    $message = "Product would be updated (demo mode)";
                }
                
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            break;
            
        case 'deactivate_product':
            try {
                $product_id = $_POST['product_id'];
                
                if ($db_available) {
                    $stmt = $pdo->prepare("UPDATE mlm_products SET status = 'inactive' WHERE id = ?");
                    $stmt->execute([$product_id]);
                    $message = "Product deactivated successfully!";
                } else {
                    $message = "Product would be deactivated (demo mode)";
                }
                
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            break;
            
        case 'update_user_group':
            try {
                $group_id = $_POST['group_id'];
                
                if ($db_available) {
                    $stmt = $pdo->prepare("UPDATE mlm_user_groups SET 
                        commission_rate = ?, rebate_rate = ?, sales_threshold = ?
                        WHERE id = ?");
                    
                    $stmt->execute([
                        $_POST['commission_rate'],
                        $_POST['rebate_rate'],
                        $_POST['sales_threshold'],
                        $group_id
                    ]);
                    
                    $message = "User group updated successfully!";
                } else {
                    $message = "User group would be updated (demo mode)";
                }
                
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
            break;
    }
}

// Function to safely get array value with default
function safeGet($array, $key, $default = '') {
    return isset($array[$key]) ? $array[$key] : $default;
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
        <p>Complete Product, Rebate, User Group & Ranking Management</p>
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
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
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
