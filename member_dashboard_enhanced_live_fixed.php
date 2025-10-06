<?php
// Fixed Member Dashboard Enhanced Live
// Eliminates template literals and fixes JavaScript syntax errors

// Start session and check authentication
session_start();

// Database configuration
require_once 'system_config.php';

// Check if user is logged in
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
    
    // Fetch dashboard statistics
    $stats = getDashboardStats($pdo, $member_id);
    
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $error_message = "Database connection error. Please try again later.";
}

function getDashboardStats($pdo, $member_id) {
    $stats = [
        'commission_balance' => 0,
        'total_earnings' => 0,
        'team_size' => 0,
        'active_referrals' => 0,
        'recent_commissions' => []
    ];
    
    try {
        // Get commission balance
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as balance FROM mlm_commissions WHERE member_id = ? AND status = 'approved'");
        $stmt->execute([$member_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['commission_balance'] = $result['balance'];
        
        // Get total earnings
        $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM mlm_commissions WHERE member_id = ?");
        $stmt->execute([$member_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_earnings'] = $result['total'];
        
        // Get team size (direct referrals)
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM mlm_members WHERE sponsor_id = ?");
        $stmt->execute([$member_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['team_size'] = $result['count'];
        
        // Get active referrals
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM mlm_members WHERE sponsor_id = ? AND status = 'active'");
        $stmt->execute([$member_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['active_referrals'] = $result['count'];
        
        // Get recent commissions
        $stmt = $pdo->prepare("SELECT * FROM mlm_commissions WHERE member_id = ? ORDER BY created_at DESC LIMIT 5");
        $stmt->execute([$member_id]);
        $stats['recent_commissions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Error fetching dashboard stats: " . $e->getMessage());
    }
    
    return $stats;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Dashboard - ExtremeLife MLM</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .dashboard-container {
            padding: 2rem 0;
        }
        
        .stats-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .stats-value {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
        }
        
        .stats-label {
            color: #718096;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn-refresh {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            color: white;
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
        }
        
        .btn-refresh:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .recent-activity {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .activity-item {
            padding: 0.75rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .amount-positive {
            color: #38a169;
            font-weight: 600;
        }
        
        .navbar-custom {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-custom">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">
                <i class="fas fa-chart-line text-primary me-2"></i>
                ExtremeLife MLM
            </a>
            <div class="navbar-nav ms-auto">
                <span class="navbar-text me-3">
                    Welcome, <?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?>
                </span>
                <button class="btn btn-refresh" data-action="refresh">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
                <a href="member_logout.php" class="btn btn-outline-danger ms-2">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container dashboard-container">
        <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo htmlspecialchars($error_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Dashboard Statistics -->
        <div class="row">
            <div class="col-md-3 col-sm-6">
                <div class="stats-card text-center">
                    <div class="stats-value" id="commission-balance">
                        ₱<?php echo number_format($stats['commission_balance'], 2); ?>
                    </div>
                    <div class="stats-label">Commission Balance</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stats-card text-center">
                    <div class="stats-value" id="total-earnings">
                        ₱<?php echo number_format($stats['total_earnings'], 2); ?>
                    </div>
                    <div class="stats-label">Total Earnings</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stats-card text-center">
                    <div class="stats-value" id="team-size">
                        <?php echo $stats['team_size']; ?>
                    </div>
                    <div class="stats-label">Team Size</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stats-card text-center">
                    <div class="stats-value" id="active-referrals">
                        <?php echo $stats['active_referrals']; ?>
                    </div>
                    <div class="stats-label">Active Referrals</div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="row">
            <div class="col-12">
                <div class="stats-card">
                    <h5 class="mb-3">
                        <i class="fas fa-history me-2"></i>
                        Recent Commission Activity
                    </h5>
                    <div class="recent-activity">
                        <?php if (empty($stats['recent_commissions'])): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-2x mb-2"></i>
                            <p>No recent commission activity</p>
                        </div>
                        <?php else: ?>
                        <?php foreach ($stats['recent_commissions'] as $commission): ?>
                        <div class="activity-item">
                            <div>
                                <div class="fw-semibold">
                                    <?php echo htmlspecialchars($commission['description'] ?? 'Commission'); ?>
                                </div>
                                <small class="text-muted">
                                    <?php echo date('M j, Y g:i A', strtotime($commission['created_at'])); ?>
                                </small>
                            </div>
                            <div class="amount-positive">
                                +₱<?php echo number_format($commission['amount'], 2); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row">
            <div class="col-md-4">
                <div class="stats-card text-center">
                    <h6 class="mb-3">Quick Actions</h6>
                    <div class="d-grid gap-2">
                        <a href="member_genealogy.php" class="btn btn-outline-primary">
                            <i class="fas fa-sitemap me-2"></i>View Genealogy
                        </a>
                        <a href="member_referrals.php" class="btn btn-outline-success">
                            <i class="fas fa-users me-2"></i>Manage Referrals
                        </a>
                        <a href="member_commissions.php" class="btn btn-outline-info">
                            <i class="fas fa-chart-bar me-2"></i>Commission Reports
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="stats-card">
                    <h6 class="mb-3">
                        <i class="fas fa-bullhorn me-2"></i>
                        Announcements
                    </h6>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Welcome to your ExtremeLife MLM dashboard! Start building your network today.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Fixed Dashboard JavaScript (no template literals) -->
    <script src="member_dashboard_enhanced_live_fixed.js"></script>
</body>
</html>
