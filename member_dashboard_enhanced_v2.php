<?php
// ExtremeLife MLM Enhanced Dashboard v2.0
// Implements comprehensive dashboard enhancements with ExtremeLife branding

session_start();

// Database configuration
require_once 'system_config.php';

// Check authentication
if (!isset($_SESSION['member_id'])) {
    header('Location: member_login.php');
    exit();
}

$member_id = $_SESSION['member_id'];

try {
    $config = new SystemConfig();
    $pdo = $config->getDBConnection();
    
    // Fetch member data
    $stmt = $pdo->prepare("SELECT * FROM mlm_members WHERE id = ?");
    $stmt->execute([$member_id]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$member) {
        session_destroy();
        header('Location: member_login.php');
        exit();
    }
    
    // Fetch enhanced dashboard statistics
    $stats = getEnhancedDashboardStats($pdo, $member_id);
    $products = getProductCatalog($pdo);
    $analytics = getAnalyticsData($pdo, $member_id);
    
} catch (Exception $e) {
    error_log("Enhanced Dashboard error: " . $e->getMessage());
    $error_message = "Database connection error. Please try again later.";
}

function getEnhancedDashboardStats($pdo, $member_id) {
    $stats = [
        'commission_balance' => 0,
        'total_earnings' => 0,
        'team_size' => 0,
        'active_referrals' => 0,
        'monthly_sales' => 0,
        'pending_orders' => 0,
        'recent_activities' => [],
        'top_products' => [],
        'team_performance' => []
    ];
    
    try {
        // Enhanced statistics queries
        $queries = [
            'commission_balance' => "SELECT COALESCE(SUM(amount), 0) FROM mlm_commissions WHERE member_id = ? AND status = 'approved'",
            'total_earnings' => "SELECT COALESCE(SUM(amount), 0) FROM mlm_commissions WHERE member_id = ?",
            'team_size' => "SELECT COUNT(*) FROM mlm_members WHERE sponsor_id = ?",
            'active_referrals' => "SELECT COUNT(*) FROM mlm_members WHERE sponsor_id = ? AND status = 'active'",
            'monthly_sales' => "SELECT COALESCE(SUM(amount), 0) FROM mlm_sales WHERE member_id = ? AND MONTH(created_at) = MONTH(CURDATE())",
            'pending_orders' => "SELECT COUNT(*) FROM mlm_orders WHERE member_id = ? AND status = 'pending'"
        ];
        
        foreach ($queries as $key => $query) {
            $stmt = $pdo->prepare($query);
            $stmt->execute([$member_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $stats[$key] = array_values($result)[0];
        }
        
        // Recent activities
        $stmt = $pdo->prepare("
            SELECT 'commission' as type, amount, description, created_at 
            FROM mlm_commissions WHERE member_id = ? 
            UNION ALL
            SELECT 'sale' as type, amount, product_name as description, created_at 
            FROM mlm_sales WHERE member_id = ?
            ORDER BY created_at DESC LIMIT 10
        ");
        $stmt->execute([$member_id, $member_id]);
        $stats['recent_activities'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Top products
        $stmt = $pdo->prepare("
            SELECT p.name, p.price, COUNT(s.id) as sales_count, SUM(s.amount) as total_revenue
            FROM mlm_products p
            LEFT JOIN mlm_sales s ON p.id = s.product_id AND s.member_id = ?
            GROUP BY p.id
            ORDER BY sales_count DESC, total_revenue DESC
            LIMIT 5
        ");
        $stmt->execute([$member_id]);
        $stats['top_products'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error fetching enhanced stats: " . $e->getMessage());
    }
    
    return $stats;
}

function getProductCatalog($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT id, name, description, price, image_url, category, stock_quantity, status
            FROM mlm_products 
            WHERE status = 'active' 
            ORDER BY category, name
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error fetching products: " . $e->getMessage());
        return [];
    }
}

function getAnalyticsData($pdo, $member_id) {
    $analytics = [
        'sales_trend' => [],
        'commission_trend' => [],
        'team_growth' => [],
        'product_performance' => []
    ];
    
    try {
        // Sales trend (last 12 months)
        $stmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as sales_count,
                SUM(amount) as total_amount
            FROM mlm_sales 
            WHERE member_id = ? 
            AND created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month
        ");
        $stmt->execute([$member_id]);
        $analytics['sales_trend'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Commission trend
        $stmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as commission_count,
                SUM(amount) as total_amount
            FROM mlm_commissions 
            WHERE member_id = ? 
            AND created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month
        ");
        $stmt->execute([$member_id]);
        $analytics['commission_trend'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error fetching analytics: " . $e->getMessage());
    }
    
    return $analytics;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ExtremeLife MLM Dashboard - Enhanced</title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#2d5a27">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="manifest.json">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Enhanced ExtremeLife Styles -->
    <link href="css/extremelife-dashboard-enhanced.css" rel="stylesheet">
</head>
<body>
    <!-- Enhanced Navigation -->
    <nav class="navbar navbar-expand-lg navbar-extremelife fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="images/extremelife-logo.png" alt="ExtremeLife" height="40" class="me-2">
                <span class="brand-text">ExtremeLife MLM</span>
            </a>
            
            <!-- Mobile Toggle -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Search Bar -->
                <div class="navbar-search mx-auto">
                    <div class="input-group">
                        <input type="text" class="form-control" id="globalSearch" placeholder="Search dashboard...">
                        <button class="btn btn-outline-light" type="button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
                
                <!-- User Menu -->
                <div class="navbar-nav ms-auto">
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button" data-bs-toggle="dropdown">
                            <img src="images/default-avatar.png" alt="Avatar" class="avatar-sm me-2">
                            <span><?php echo htmlspecialchars($member['first_name']); ?></span>
                            <span class="notification-badge" id="notificationCount">3</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Settings</a></li>
                            <li><a class="dropdown-item" href="#"><i class="fas fa-bell me-2"></i>Notifications</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="member_logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Dashboard Container -->
    <div class="dashboard-container">
        <div class="container-fluid">
            
            <!-- Dashboard Tabs -->
            <div class="dashboard-tabs">
                <ul class="nav nav-pills nav-fill mb-4" id="dashboardTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="overview-tab" data-bs-toggle="pill" data-bs-target="#overview" type="button" role="tab">
                            <i class="fas fa-tachometer-alt me-2"></i>Overview
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="sales-tab" data-bs-toggle="pill" data-bs-target="#sales" type="button" role="tab">
                            <i class="fas fa-shopping-cart me-2"></i>Sales Tools
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="analytics-tab" data-bs-toggle="pill" data-bs-target="#analytics" type="button" role="tab">
                            <i class="fas fa-chart-line me-2"></i>Analytics
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="team-tab" data-bs-toggle="pill" data-bs-target="#team" type="button" role="tab">
                            <i class="fas fa-users me-2"></i>Team
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="profile-tab" data-bs-toggle="pill" data-bs-target="#profile" type="button" role="tab">
                            <i class="fas fa-user-cog me-2"></i>Profile
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Tab Content -->
            <div class="tab-content" id="dashboardTabContent">
                
                <!-- Overview Tab -->
                <div class="tab-pane fade show active" id="overview" role="tabpanel">
                    <!-- Quick Stats Cards -->
                    <div class="row mb-4">
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="stats-card stats-card-primary">
                                <div class="stats-card-body">
                                    <div class="stats-icon">
                                        <i class="fas fa-wallet"></i>
                                    </div>
                                    <div class="stats-content">
                                        <div class="stats-value" id="commission-balance">
                                            ₱<?php echo number_format($stats['commission_balance'], 2); ?>
                                        </div>
                                        <div class="stats-label">Commission Balance</div>
                                        <div class="stats-trend">
                                            <i class="fas fa-arrow-up text-success"></i>
                                            <span class="text-success">+12.5%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="stats-card stats-card-success">
                                <div class="stats-card-body">
                                    <div class="stats-icon">
                                        <i class="fas fa-chart-line"></i>
                                    </div>
                                    <div class="stats-content">
                                        <div class="stats-value" id="monthly-sales">
                                            ₱<?php echo number_format($stats['monthly_sales'], 2); ?>
                                        </div>
                                        <div class="stats-label">Monthly Sales</div>
                                        <div class="stats-trend">
                                            <i class="fas fa-arrow-up text-success"></i>
                                            <span class="text-success">+8.3%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="stats-card stats-card-info">
                                <div class="stats-card-body">
                                    <div class="stats-icon">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div class="stats-content">
                                        <div class="stats-value" id="team-size">
                                            <?php echo $stats['team_size']; ?>
                                        </div>
                                        <div class="stats-label">Team Members</div>
                                        <div class="stats-trend">
                                            <i class="fas fa-arrow-up text-success"></i>
                                            <span class="text-success">+5 new</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6 mb-3">
                            <div class="stats-card stats-card-warning">
                                <div class="stats-card-body">
                                    <div class="stats-icon">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <div class="stats-content">
                                        <div class="stats-value" id="pending-orders">
                                            <?php echo $stats['pending_orders']; ?>
                                        </div>
                                        <div class="stats-label">Pending Orders</div>
                                        <div class="stats-trend">
                                            <i class="fas fa-exclamation-triangle text-warning"></i>
                                            <span class="text-warning">Needs attention</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card card-extremelife">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-bolt me-2"></i>Quick Actions
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3 col-sm-6 mb-3">
                                            <button class="btn btn-quick-action w-100" onclick="openProductCatalog()">
                                                <i class="fas fa-shopping-bag mb-2"></i>
                                                <div>Browse Products</div>
                                            </button>
                                        </div>
                                        <div class="col-md-3 col-sm-6 mb-3">
                                            <button class="btn btn-quick-action w-100" onclick="shareReferralLink()">
                                                <i class="fas fa-share-alt mb-2"></i>
                                                <div>Share Link</div>
                                            </button>
                                        </div>
                                        <div class="col-md-3 col-sm-6 mb-3">
                                            <button class="btn btn-quick-action w-100" onclick="viewGenealogy()">
                                                <i class="fas fa-sitemap mb-2"></i>
                                                <div>View Tree</div>
                                            </button>
                                        </div>
                                        <div class="col-md-3 col-sm-6 mb-3">
                                            <button class="btn btn-quick-action w-100" onclick="contactSupport()">
                                                <i class="fas fa-headset mb-2"></i>
                                                <div>Support</div>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card card-extremelife">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-history me-2"></i>Recent Activity
                                    </h5>
                                    <button class="btn btn-sm btn-outline-primary" onclick="toggleActivityDetails()">
                                        <i class="fas fa-expand-alt"></i>
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="activity-timeline" id="activityTimeline">
                                        <?php foreach ($stats['recent_activities'] as $activity): ?>
                                        <div class="activity-item">
                                            <div class="activity-icon activity-<?php echo $activity['type']; ?>">
                                                <i class="fas fa-<?php echo $activity['type'] === 'commission' ? 'coins' : 'shopping-cart'; ?>"></i>
                                            </div>
                                            <div class="activity-content">
                                                <div class="activity-title"><?php echo htmlspecialchars($activity['description']); ?></div>
                                                <div class="activity-meta">
                                                    <span class="activity-amount">₱<?php echo number_format($activity['amount'], 2); ?></span>
                                                    <span class="activity-time"><?php echo date('M j, g:i A', strtotime($activity['created_at'])); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-lg-4">
                            <div class="card card-extremelife">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-trophy me-2"></i>Top Products
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="top-products-list">
                                        <?php foreach ($stats['top_products'] as $index => $product): ?>
                                        <div class="product-item">
                                            <div class="product-rank"><?php echo $index + 1; ?></div>
                                            <div class="product-info">
                                                <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                                                <div class="product-stats">
                                                    <span class="sales-count"><?php echo $product['sales_count']; ?> sales</span>
                                                    <span class="revenue">₱<?php echo number_format($product['total_revenue'], 2); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sales Tools Tab -->
                <div class="tab-pane fade" id="sales" role="tabpanel">
                    <!-- Live Selling Tools -->
                    <div class="row mb-4">
                        <div class="col-lg-8">
                            <div class="card card-extremelife">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-shopping-bag me-2"></i>Product Catalog
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <!-- Product Search and Filters -->
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="productSearch" placeholder="Search products...">
                                                <button class="btn btn-outline-secondary" type="button">
                                                    <i class="fas fa-search"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <select class="form-select" id="categoryFilter">
                                                <option value="">All Categories</option>
                                                <option value="supplements">Supplements</option>
                                                <option value="skincare">Skincare</option>
                                                <option value="wellness">Wellness</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- Product Grid -->
                                    <div class="row" id="productGrid">
                                        <?php foreach ($products as $product): ?>
                                        <div class="col-md-6 col-lg-4 mb-3">
                                            <div class="product-card">
                                                <div class="product-image">
                                                    <img src="<?php echo htmlspecialchars($product['image_url'] ?: 'images/product-placeholder.jpg'); ?>"
                                                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                                                         class="img-fluid">
                                                    <div class="product-overlay">
                                                        <button class="btn btn-primary btn-sm" onclick="shareProduct(<?php echo $product['id']; ?>)">
                                                            <i class="fas fa-share-alt"></i>
                                                        </button>
                                                        <button class="btn btn-success btn-sm" onclick="addToCart(<?php echo $product['id']; ?>)">
                                                            <i class="fas fa-cart-plus"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="product-info">
                                                    <h6 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h6>
                                                    <p class="product-description"><?php echo htmlspecialchars(substr($product['description'], 0, 80)) . '...'; ?></p>
                                                    <div class="product-price">₱<?php echo number_format($product['price'], 2); ?></div>
                                                    <div class="product-stock">
                                                        <span class="badge bg-<?php echo $product['stock_quantity'] > 10 ? 'success' : ($product['stock_quantity'] > 0 ? 'warning' : 'danger'); ?>">
                                                            <?php echo $product['stock_quantity']; ?> in stock
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <!-- Live Sales Feed -->
                            <div class="card card-extremelife mb-3">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-broadcast-tower me-2"></i>Live Sales Feed
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="live-feed" id="liveSalesFeed">
                                        <div class="feed-item">
                                            <div class="feed-avatar">
                                                <img src="images/avatar1.jpg" alt="User" class="avatar-xs">
                                            </div>
                                            <div class="feed-content">
                                                <div class="feed-text">
                                                    <strong>Maria S.</strong> just purchased <strong>Vitamin C Complex</strong>
                                                </div>
                                                <div class="feed-time">2 minutes ago</div>
                                            </div>
                                        </div>

                                        <div class="feed-item">
                                            <div class="feed-avatar">
                                                <img src="images/avatar2.jpg" alt="User" class="avatar-xs">
                                            </div>
                                            <div class="feed-content">
                                                <div class="feed-text">
                                                    <strong>John D.</strong> earned ₱250 commission
                                                </div>
                                                <div class="feed-time">5 minutes ago</div>
                                            </div>
                                        </div>

                                        <div class="feed-item">
                                            <div class="feed-avatar">
                                                <img src="images/avatar3.jpg" alt="User" class="avatar-xs">
                                            </div>
                                            <div class="feed-content">
                                                <div class="feed-text">
                                                    <strong>Sarah L.</strong> joined your team
                                                </div>
                                                <div class="feed-time">8 minutes ago</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Sales Tools -->
                            <div class="card card-extremelife">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-tools me-2"></i>Quick Tools
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <button class="btn btn-primary" onclick="generateSalesLink()">
                                            <i class="fas fa-link me-2"></i>Generate Sales Link
                                        </button>
                                        <button class="btn btn-success" onclick="createPromotion()">
                                            <i class="fas fa-percentage me-2"></i>Create Promotion
                                        </button>
                                        <button class="btn btn-info" onclick="viewCustomers()">
                                            <i class="fas fa-users me-2"></i>View Customers
                                        </button>
                                        <button class="btn btn-warning" onclick="trackOrders()">
                                            <i class="fas fa-truck me-2"></i>Track Orders
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Analytics Tab -->
                <div class="tab-pane fade" id="analytics" role="tabpanel">
                    <!-- Analytics Dashboard -->
                    <div class="row mb-4">
                        <!-- Performance Metrics -->
                        <div class="col-lg-8">
                            <div class="card card-extremelife">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-chart-line me-2"></i>Performance Analytics
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <!-- Chart Tabs -->
                                    <ul class="nav nav-tabs mb-3" id="chartTabs" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link active" id="sales-chart-tab" data-bs-toggle="tab" data-bs-target="#salesChart" type="button" role="tab">
                                                Sales Trend
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="commission-chart-tab" data-bs-toggle="tab" data-bs-target="#commissionChart" type="button" role="tab">
                                                Commission Trend
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link" id="team-chart-tab" data-bs-toggle="tab" data-bs-target="#teamChart" type="button" role="tab">
                                                Team Growth
                                            </button>
                                        </li>
                                    </ul>

                                    <!-- Chart Content -->
                                    <div class="tab-content" id="chartTabContent">
                                        <div class="tab-pane fade show active" id="salesChart" role="tabpanel">
                                            <canvas id="salesTrendChart" height="300"></canvas>
                                        </div>
                                        <div class="tab-pane fade" id="commissionChart" role="tabpanel">
                                            <canvas id="commissionTrendChart" height="300"></canvas>
                                        </div>
                                        <div class="tab-pane fade" id="teamChart" role="tabpanel">
                                            <canvas id="teamGrowthChart" height="300"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Key Metrics -->
                        <div class="col-lg-4">
                            <div class="card card-extremelife mb-3">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-tachometer-alt me-2"></i>Key Metrics
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="metric-item">
                                        <div class="metric-label">Conversion Rate</div>
                                        <div class="metric-value">12.5%</div>
                                        <div class="metric-change text-success">
                                            <i class="fas fa-arrow-up"></i> +2.3%
                                        </div>
                                    </div>

                                    <div class="metric-item">
                                        <div class="metric-label">Avg. Order Value</div>
                                        <div class="metric-value">₱1,250</div>
                                        <div class="metric-change text-success">
                                            <i class="fas fa-arrow-up"></i> +5.8%
                                        </div>
                                    </div>

                                    <div class="metric-item">
                                        <div class="metric-label">Customer Retention</div>
                                        <div class="metric-value">78%</div>
                                        <div class="metric-change text-warning">
                                            <i class="fas fa-arrow-down"></i> -1.2%
                                        </div>
                                    </div>

                                    <div class="metric-item">
                                        <div class="metric-label">Team Activity</div>
                                        <div class="metric-value">85%</div>
                                        <div class="metric-change text-success">
                                            <i class="fas fa-arrow-up"></i> +3.5%
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Goal Progress -->
                            <div class="card card-extremelife">
                                <div class="card-header">
                                    <h6 class="card-title mb-0">
                                        <i class="fas fa-target me-2"></i>Monthly Goals
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="goal-item">
                                        <div class="goal-label">Sales Target</div>
                                        <div class="progress mb-2">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: 75%" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <div class="goal-text">₱18,750 / ₱25,000</div>
                                    </div>

                                    <div class="goal-item">
                                        <div class="goal-label">New Recruits</div>
                                        <div class="progress mb-2">
                                            <div class="progress-bar bg-info" role="progressbar" style="width: 60%" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <div class="goal-text">6 / 10 members</div>
                                    </div>

                                    <div class="goal-item">
                                        <div class="goal-label">Commission Goal</div>
                                        <div class="progress mb-2">
                                            <div class="progress-bar bg-warning" role="progressbar" style="width: 45%" aria-valuenow="45" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <div class="goal-text">₱2,250 / ₱5,000</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detailed Analytics -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card card-extremelife">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-chart-bar me-2"></i>Detailed Performance Report
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Period</th>
                                                    <th>Sales</th>
                                                    <th>Commission</th>
                                                    <th>New Members</th>
                                                    <th>Growth Rate</th>
                                                    <th>Action</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>This Month</td>
                                                    <td>₱18,750</td>
                                                    <td>₱2,250</td>
                                                    <td>6</td>
                                                    <td><span class="badge bg-success">+12.5%</span></td>
                                                    <td><button class="btn btn-sm btn-outline-primary">View Details</button></td>
                                                </tr>
                                                <tr>
                                                    <td>Last Month</td>
                                                    <td>₱16,500</td>
                                                    <td>₱1,980</td>
                                                    <td>4</td>
                                                    <td><span class="badge bg-success">+8.3%</span></td>
                                                    <td><button class="btn btn-sm btn-outline-primary">View Details</button></td>
                                                </tr>
                                                <tr>
                                                    <td>2 Months Ago</td>
                                                    <td>₱15,200</td>
                                                    <td>₱1,824</td>
                                                    <td>3</td>
                                                    <td><span class="badge bg-warning">+3.2%</span></td>
                                                    <td><button class="btn btn-sm btn-outline-primary">View Details</button></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Team Tab -->
                <div class="tab-pane fade" id="team" role="tabpanel">
                    <!-- Team management content will be added in next file -->
                </div>

                <!-- Profile Tab -->
                <div class="tab-pane fade" id="profile" role="tabpanel">
                    <!-- Profile settings content will be added in next file -->
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <div class="loading-text">Loading...</div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Enhanced Dashboard JavaScript -->
    <script src="js/extremelife-dashboard-enhanced.js"></script>
    
    <!-- PWA Service Worker -->
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw.js')
                .then(function(registration) {
                    console.log('ServiceWorker registered successfully');
                })
                .catch(function(error) {
                    console.log('ServiceWorker registration failed');
                });
        }
    </script>
</body>
</html>
