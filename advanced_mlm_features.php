<?php
// ExtremeLife MLM Advanced Features - Rank Advancement & Analytics
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

// Get member information
$member_id = $_SESSION['member_id'] ?? 10;
$member = null;

if ($db_available) {
    try {
        $stmt = $pdo->prepare("SELECT m.*, CONCAT(m.first_name, ' ', m.last_name) as user_name, g.group_name, g.commission_rate, g.rebate_rate 
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
        'group_name' => 'Member',
        'commission_rate' => 10.00,
        'rebate_rate' => 2.00,
        'total_sales' => 2500.00,
        'total_commissions' => 250.00,
        'total_rebates' => 25.00,
        'direct_referrals' => 0,
        'joined_date' => date('Y-m-d H:i:s', strtotime('-3 months'))
    ];
}

// Rank advancement thresholds
$rank_thresholds = [
    'Member' => ['sales' => 0, 'commission' => 10, 'rebate' => 2],
    'Wholesale' => ['sales' => 1000, 'commission' => 12, 'rebate' => 3],
    'Distributor' => ['sales' => 5000, 'commission' => 15, 'rebate' => 4],
    'VIP' => ['sales' => 15000, 'commission' => 18, 'rebate' => 5],
    'Diamond' => ['sales' => 50000, 'commission' => 20, 'rebate' => 6]
];

// Calculate rank advancement eligibility
function calculateRankAdvancement($member, $thresholds) {
    $current_rank = $member['group_name'];
    $current_sales = $member['total_sales'];
    
    $eligible_ranks = [];
    $next_rank = null;
    $progress = [];
    
    foreach ($thresholds as $rank => $requirements) {
        if ($current_sales >= $requirements['sales']) {
            $eligible_ranks[] = $rank;
        } else {
            if (!$next_rank) {
                $next_rank = $rank;
                $remaining = $requirements['sales'] - $current_sales;
                $progress = [
                    'rank' => $rank,
                    'required' => $requirements['sales'],
                    'current' => $current_sales,
                    'remaining' => $remaining,
                    'percentage' => ($current_sales / $requirements['sales']) * 100
                ];
            }
        }
    }
    
    return [
        'eligible_ranks' => $eligible_ranks,
        'highest_eligible' => end($eligible_ranks),
        'next_rank' => $next_rank,
        'progress' => $progress
    ];
}

// Generate team performance analytics
function generateTeamAnalytics($member_id, $db_available, $pdo) {
    $analytics = [
        'team_size' => 0,
        'team_sales' => 0,
        'team_commissions' => 0,
        'active_members' => 0,
        'monthly_growth' => 0,
        'top_performers' => []
    ];
    
    if ($db_available) {
        try {
            // Get team statistics
            $stmt = $pdo->prepare("SELECT COUNT(*) as team_size, 
                                  COALESCE(SUM(total_sales), 0) as team_sales,
                                  COALESCE(SUM(total_commissions), 0) as team_commissions
                                  FROM mlm_genealogy g 
                                  JOIN mlm_members m ON g.member_id = m.id 
                                  WHERE g.upline_path LIKE CONCAT('%', ?, '%') AND m.status = 'active'");
            $stmt->execute([$member_id]);
            $team_stats = $stmt->fetch();
            
            if ($team_stats) {
                $analytics['team_size'] = $team_stats['team_size'];
                $analytics['team_sales'] = $team_stats['team_sales'];
                $analytics['team_commissions'] = $team_stats['team_commissions'];
            }
            
        } catch (PDOException $e) {
            // Use fallback data
        }
    }
    
    // Fallback analytics for demo
    if ($analytics['team_size'] == 0) {
        $analytics = [
            'team_size' => 5,
            'team_sales' => 12500.00,
            'team_commissions' => 1875.00,
            'active_members' => 5,
            'monthly_growth' => 15.5,
            'top_performers' => [
                ['name' => 'Sofia Reyes', 'sales' => 8000.00, 'rank' => 'Wholesale'],
                ['name' => 'Miguel Torres', 'sales' => 6500.00, 'rank' => 'Wholesale'],
                ['name' => 'Isabella Morales', 'sales' => 3500.00, 'rank' => 'Member']
            ]
        ];
    }
    
    return $analytics;
}

// Handle rank advancement request
$message = '';
$error = '';

if (isset($_POST['action']) && $_POST['action'] == 'request_advancement') {
    $requested_rank = $_POST['requested_rank'];
    
    $advancement = calculateRankAdvancement($member, $rank_thresholds);
    
    if (in_array($requested_rank, $advancement['eligible_ranks'])) {
        // Process rank advancement
        if ($db_available) {
            try {
                $new_group_id = array_search($requested_rank, ['Member', 'Wholesale', 'Distributor', 'VIP', 'Diamond']) + 1;
                
                $stmt = $pdo->prepare("UPDATE mlm_members SET user_group_id = ?, rank_advancement_date = NOW() WHERE id = ?");
                $stmt->execute([$new_group_id, $member['id']]);
                
                $message = "Congratulations! You have been advanced to $requested_rank rank.";
                
                // Update member data
                $member['group_name'] = $requested_rank;
                $member['commission_rate'] = $rank_thresholds[$requested_rank]['commission'];
                $member['rebate_rate'] = $rank_thresholds[$requested_rank]['rebate'];
                
            } catch (PDOException $e) {
                $error = "Error processing rank advancement: " . $e->getMessage();
            }
        } else {
            $message = "Rank advancement request submitted for $requested_rank. (Demo mode - would be processed in live system)";
        }
    } else {
        $error = "You are not eligible for $requested_rank rank yet.";
    }
}

$advancement = calculateRankAdvancement($member, $rank_thresholds);
$analytics = generateTeamAnalytics($member['id'], $db_available, $pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced MLM Features - ExtremeLife Herbal</title>
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
        .features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 2rem; }
        .feature-card {
            background: white; padding: 2rem; border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .rank-progress {
            background: linear-gradient(135deg, #e8f5e8, #d4edda);
            padding: 1.5rem; border-radius: 10px; margin-bottom: 1.5rem;
            border: 2px solid #28a745;
        }
        .progress-bar {
            background: #e9ecef; height: 20px; border-radius: 10px;
            overflow: hidden; margin: 1rem 0;
        }
        .progress-fill {
            background: linear-gradient(135deg, #28a745, #20c997);
            height: 100%; transition: width 0.3s ease;
        }
        .analytics-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; }
        .metric-card {
            background: #f8f9fa; padding: 1.5rem; border-radius: 10px;
            text-align: center; border-left: 4px solid #2d5a27;
        }
        .metric-value { font-size: 2rem; font-weight: bold; color: #2d5a27; }
        .metric-label { color: #666; font-size: 0.9rem; }
        .btn {
            background: #2d5a27; color: white; padding: 0.75rem 1.5rem;
            border: none; border-radius: 25px; cursor: pointer;
            font-weight: 600; text-decoration: none; display: inline-block;
            margin: 0.25rem; transition: all 0.3s ease;
        }
        .btn:hover { background: #4a7c59; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-warning:hover { background: #e0a800; }
        .alert {
            padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 600;
        }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .rank-badge {
            display: inline-block; padding: 0.5rem 1rem; border-radius: 20px;
            font-weight: bold; margin: 0.25rem;
        }
        .rank-member { background: #ffc107; color: #212529; }
        .rank-wholesale { background: #17a2b8; color: white; }
        .rank-distributor { background: #28a745; color: white; }
        .rank-vip { background: #6f42c1; color: white; }
        .rank-diamond { background: #343a40; color: white; }
        .referral-tools {
            background: #fff3cd; padding: 1.5rem; border-radius: 10px;
            border: 1px solid #ffeaa7; margin-top: 1.5rem;
        }
    </style>
</head>
<body>
    <header class="header">
        <h1>🚀 Advanced MLM Features</h1>
        <p>Rank Advancement, Analytics & Team Management</p>
    </header>

    <nav class="nav">
        <a href="/">🏠 Home</a>
        <a href="/member_dashboard.php">👤 Dashboard</a>
        <a href="/database_catalog.php">📦 Products</a>
        <a href="/enhanced_cart.php">🛒 Cart</a>
        <a href="/register.php">👥 Refer</a>
    </nav>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="features-grid">
            <!-- Rank Advancement -->
            <div class="feature-card">
                <h2 style="color: #2d5a27; margin-bottom: 1.5rem;">🏆 Rank Advancement</h2>
                
                <div class="rank-progress">
                    <h3 style="color: #2d5a27; margin-bottom: 1rem;">Current Rank: 
                        <span class="rank-badge rank-<?= strtolower($member['group_name']) ?>">
                            <?= $member['group_name'] ?>
                        </span>
                    </h3>
                    
                    <p><strong>Commission Rate:</strong> <?= $member['commission_rate'] ?>%</p>
                    <p><strong>Rebate Rate:</strong> <?= $member['rebate_rate'] ?>%</p>
                    <p><strong>Total Sales:</strong> ₱<?= number_format($member['total_sales'], 2) ?></p>
                </div>
                
                <?php if ($advancement['next_rank']): ?>
                    <h4 style="color: #2d5a27; margin-bottom: 1rem;">Progress to <?= $advancement['next_rank'] ?>:</h4>
                    
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= min($advancement['progress']['percentage'], 100) ?>%;"></div>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                        <span>₱<?= number_format($advancement['progress']['current'], 2) ?></span>
                        <span>₱<?= number_format($advancement['progress']['required'], 2) ?></span>
                    </div>
                    
                    <p style="color: #666; margin-bottom: 1.5rem;">
                        You need ₱<?= number_format($advancement['progress']['remaining'], 2) ?> more in sales to reach <?= $advancement['next_rank'] ?> rank.
                    </p>
                <?php endif; ?>
                
                <?php if ($advancement['highest_eligible'] != $member['group_name']): ?>
                    <form method="post" style="margin-top: 1rem;">
                        <input type="hidden" name="action" value="request_advancement">
                        <input type="hidden" name="requested_rank" value="<?= $advancement['highest_eligible'] ?>">
                        <button type="submit" class="btn btn-success">
                            🎉 Advance to <?= $advancement['highest_eligible'] ?> Rank
                        </button>
                    </form>
                <?php endif; ?>
                
                <!-- Rank Benefits -->
                <div style="margin-top: 1.5rem;">
                    <h4 style="color: #2d5a27; margin-bottom: 1rem;">Rank Benefits:</h4>
                    <?php foreach ($rank_thresholds as $rank => $benefits): ?>
                        <div style="margin-bottom: 0.5rem;">
                            <span class="rank-badge rank-<?= strtolower($rank) ?>"><?= $rank ?></span>
                            <small>₱<?= number_format($benefits['sales']) ?> sales - <?= $benefits['commission'] ?>% commission</small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Team Analytics -->
            <div class="feature-card">
                <h2 style="color: #2d5a27; margin-bottom: 1.5rem;">📊 Team Performance Analytics</h2>
                
                <div class="analytics-grid">
                    <div class="metric-card">
                        <div class="metric-value"><?= $analytics['team_size'] ?></div>
                        <div class="metric-label">Team Members</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value">₱<?= number_format($analytics['team_sales'], 0) ?></div>
                        <div class="metric-label">Team Sales</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value">₱<?= number_format($analytics['team_commissions'], 0) ?></div>
                        <div class="metric-label">Team Commissions</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value"><?= $analytics['active_members'] ?></div>
                        <div class="metric-label">Active Members</div>
                    </div>
                    <div class="metric-card">
                        <div class="metric-value">+<?= $analytics['monthly_growth'] ?>%</div>
                        <div class="metric-label">Monthly Growth</div>
                    </div>
                </div>
                
                <?php if (!empty($analytics['top_performers'])): ?>
                    <h4 style="color: #2d5a27; margin: 1.5rem 0 1rem;">🌟 Top Performers</h4>
                    <?php foreach ($analytics['top_performers'] as $performer): ?>
                        <div style="display: flex; justify-content: space-between; padding: 0.5rem; background: #f8f9fa; border-radius: 5px; margin-bottom: 0.5rem;">
                            <div>
                                <strong><?= htmlspecialchars($performer['name']) ?></strong>
                                <span class="rank-badge rank-<?= strtolower($performer['rank']) ?>"><?= $performer['rank'] ?></span>
                            </div>
                            <div><strong>₱<?= number_format($performer['sales'], 2) ?></strong></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Referral Tools -->
            <div class="feature-card">
                <h2 style="color: #2d5a27; margin-bottom: 1.5rem;">🔗 Referral & Recruitment Tools</h2>
                
                <div style="margin-bottom: 1.5rem;">
                    <h4 style="color: #2d5a27; margin-bottom: 0.5rem;">Your Referral Code:</h4>
                    <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; font-family: monospace; font-size: 1.2rem; font-weight: bold; text-align: center; border: 2px solid #2d5a27;">
                        <?= $member['referral_code'] ?? 'ELH000010' ?>
                    </div>
                </div>
                
                <div style="margin-bottom: 1.5rem;">
                    <h4 style="color: #2d5a27; margin-bottom: 0.5rem;">Referral Link:</h4>
                    <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; font-family: monospace; font-size: 0.9rem; word-break: break-all;">
                        http://extremelifeherbal.com/register.php?sponsor=<?= $member['referral_code'] ?? 'ELH000010' ?>
                    </div>
                    <button onclick="copyReferralLink()" class="btn" style="margin-top: 0.5rem;">📋 Copy Link</button>
                </div>
                
                <div class="referral-tools">
                    <h4 style="color: #856404; margin-bottom: 1rem;">📱 Share on Social Media</h4>
                    <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                        <a href="#" onclick="shareOnFacebook()" class="btn" style="background: #3b5998;">📘 Facebook</a>
                        <a href="#" onclick="shareOnWhatsApp()" class="btn" style="background: #25d366;">📱 WhatsApp</a>
                        <a href="#" onclick="shareOnMessenger()" class="btn" style="background: #0084ff;">💬 Messenger</a>
                    </div>
                </div>
                
                <div style="margin-top: 1.5rem;">
                    <h4 style="color: #2d5a27; margin-bottom: 1rem;">📈 Referral Statistics</h4>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div style="text-align: center; padding: 1rem; background: #e8f5e8; border-radius: 8px;">
                            <div style="font-size: 1.5rem; font-weight: bold; color: #28a745;"><?= $member['direct_referrals'] ?? 0 ?></div>
                            <div>Direct Referrals</div>
                        </div>
                        <div style="text-align: center; padding: 1rem; background: #fff3e0; border-radius: 8px;">
                            <div style="font-size: 1.5rem; font-weight: bold; color: #f57c00;">₱<?= number_format(($member['direct_referrals'] ?? 0) * 50, 2) ?></div>
                            <div>Referral Bonuses</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function copyReferralLink() {
            const link = "http://extremelifeherbal.com/register.php?sponsor=<?= $member['referral_code'] ?? 'ELH000010' ?>";
            navigator.clipboard.writeText(link).then(() => {
                alert('Referral link copied to clipboard!');
            });
        }
        
        function shareOnFacebook() {
            const url = encodeURIComponent("http://extremelifeherbal.com/register.php?sponsor=<?= $member['referral_code'] ?? 'ELH000010' ?>");
            const text = encodeURIComponent("Join me in ExtremeLife Herbal MLM and start earning with natural health products!");
            window.open(`https://www.facebook.com/sharer/sharer.php?u=${url}&quote=${text}`, '_blank');
        }
        
        function shareOnWhatsApp() {
            const text = encodeURIComponent("Join me in ExtremeLife Herbal MLM! Use my referral code: <?= $member['referral_code'] ?? 'ELH000010' ?> or click: http://extremelifeherbal.com/register.php?sponsor=<?= $member['referral_code'] ?? 'ELH000010' ?>");
            window.open(`https://wa.me/?text=${text}`, '_blank');
        }
        
        function shareOnMessenger() {
            const url = encodeURIComponent("http://extremelifeherbal.com/register.php?sponsor=<?= $member['referral_code'] ?? 'ELH000010' ?>");
            window.open(`https://www.facebook.com/dialog/send?link=${url}&app_id=YOUR_APP_ID`, '_blank');
        }
        
        // Animate progress bars
        document.addEventListener("DOMContentLoaded", function() {
            const progressBars = document.querySelectorAll(".progress-fill");
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = "0%";
                setTimeout(() => {
                    bar.style.width = width;
                }, 500);
            });
        });
    </script>
</body>
</html>
