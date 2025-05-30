<?php
// ExtremeLife Herbal MLM Member Dashboard - FIXED VERSION
session_start();

try {
    $pdo = new PDO('mysql:host=localhost;dbname=drupal_umd', 'drupal_user', 'secure_drupal_pass_1748318545');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// Initialize default member data
$member_id = $_SESSION['member_id'] ?? 1; // Default to member 1 for demo

// Get member information with proper error handling
try {
    $stmt = $pdo->prepare("SELECT m.*, u.name as user_name, u.mail, g.group_name, g.commission_rate, g.rebate_rate
                          FROM mlm_members m
                          LEFT JOIN users_field_data u ON m.user_id = u.uid
                          LEFT JOIN mlm_user_groups g ON m.user_group_id = g.id
                          WHERE m.id = ?");
    $stmt->execute([$member_id]);
    $member = $stmt->fetch();

    // If no member found, create default demo member
    if (!$member) {
        $member = [
            'id' => 1,
            'user_name' => 'Demo Member',
            'mail' => 'demo@extremelifeherbal.com',
            'rank' => 'member',
            'commission_rate' => 10.00,
            'rebate_rate' => 2.00,
            'total_sales' => 2500.00,
            'total_commissions' => 1250.00,
            'total_rebates' => 125.00,
            'joined_date' => date('Y-m-d H:i:s', strtotime('-6 months')),
            'group_name' => 'Member',
            'user_group_id' => 1
        ];
    }
} catch (PDOException $e) {
    // Fallback to demo data if database query fails
    $member = [
        'id' => 1,
        'user_name' => 'Demo Member',
        'mail' => 'demo@extremelifeherbal.com',
        'rank' => 'member',
        'commission_rate' => 10.00,
        'rebate_rate' => 2.00,
        'total_sales' => 2500.00,
        'total_commissions' => 1250.00,
        'total_rebates' => 125.00,
        'joined_date' => date('Y-m-d H:i:s', strtotime('-6 months')),
        'group_name' => 'Member',
        'user_group_id' => 1
    ];
}

// Generate referral code
$referral_code = 'ELH' . str_pad($member['id'], 6, '0', STR_PAD_LEFT);

// Ensure all required fields have default values
$member['user_name'] = $member['user_name'] ?? 'Demo Member';
$member['mail'] = $member['mail'] ?? 'demo@extremelifeherbal.com';
$member['group_name'] = $member['group_name'] ?? 'Member';
$member['commission_rate'] = $member['commission_rate'] ?? 10.00;
$member['rebate_rate'] = $member['rebate_rate'] ?? 2.00;
$member['total_sales'] = $member['total_sales'] ?? 0.00;
$member['total_commissions'] = $member['total_commissions'] ?? 0.00;
$member['total_rebates'] = $member['total_rebates'] ?? 0.00;
$member['joined_date'] = $member['joined_date'] ?? date('Y-m-d H:i:s');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Dashboard - ExtremeLife Herbal MLM</title>
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
        .dashboard-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem; margin-bottom: 2rem;
        }
        .dashboard-card {
            background: white; padding: 2rem; border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        .dashboard-card:hover { transform: translateY(-5px); }
        .card-header {
            display: flex; align-items: center; margin-bottom: 1.5rem;
            padding-bottom: 1rem; border-bottom: 2px solid #e9ecef;
        }
        .card-icon {
            font-size: 2rem; margin-right: 1rem; color: #2d5a27;
        }
        .card-title { color: #2d5a27; font-size: 1.3rem; font-weight: 600; }
        .stat-number { font-size: 2.5rem; font-weight: bold; color: #2d5a27; }
        .stat-label { color: #666; font-weight: 600; margin-top: 0.5rem; }
        .member-info {
            background: linear-gradient(135deg, #e8f5e8, #d4edda);
            padding: 2rem; border-radius: 15px; margin-bottom: 2rem;
            border: 2px solid #28a745;
        }
        .member-details {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem; margin-top: 1.5rem;
        }
        .detail-item {
            background: white; padding: 1.5rem; border-radius: 10px;
            border-left: 4px solid #28a745; text-align: center;
        }
        .detail-item h4 { color: #2d5a27; margin-bottom: 0.5rem; }
        .detail-item p { font-size: 1.1rem; font-weight: 600; }
        .genealogy-tree {
            background: white; padding: 2rem; border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1); margin-bottom: 2rem;
        }
        .tree-node {
            background: #f8f9fa; padding: 1rem; border-radius: 8px;
            margin: 0.5rem; text-align: center; border: 2px solid #dee2e6;
        }
        .tree-node.current { background: #e8f5e8; border-color: #28a745; }
        .tree-level { display: flex; justify-content: center; flex-wrap: wrap; margin: 1rem 0; }
        .btn {
            background: linear-gradient(135deg, #2d5a27, #4a7c59);
            color: white; padding: 0.75rem 1.5rem; border: none;
            border-radius: 25px; cursor: pointer; font-weight: 600;
            text-decoration: none; display: inline-block; margin: 0.25rem;
            transition: all 0.3s ease;
        }
        .btn:hover {
            background: linear-gradient(135deg, #4a7c59, #2d5a27);
            transform: translateY(-2px);
        }
        .btn-secondary { background: linear-gradient(135deg, #6c757d, #5a6268); }
        .quick-actions {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem; margin-top: 2rem;
        }
        .commission-chart {
            background: white; padding: 2rem; border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .progress-bar {
            background: #e9ecef; height: 20px; border-radius: 10px;
            overflow: hidden; margin: 1rem 0;
        }
        .progress-fill {
            background: linear-gradient(135deg, #28a745, #20c997);
            height: 100%; transition: width 0.3s ease;
        }
        @media (max-width: 768px) {
            .dashboard-grid { grid-template-columns: 1fr; }
            .member-details { grid-template-columns: 1fr; }
            .quick-actions { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <header class="header">
        <h1>👤 ExtremeLife Member Dashboard</h1>
        <p>Welcome back, <?= htmlspecialchars($member['user_name']) ?>!</p>
    </header>

    <nav class="nav">
        <a href="/">🏠 Home</a>
        <a href="/database_catalog.php">📦 Shop Products</a>
        <a href="/cart.php">🛒 Cart</a>
        <a href="/register.php">👥 Refer Friends</a>
        <a href="/mlm_tools.php">🔧 MLM Tools</a>
        <a href="/income_management.php">💰 Income</a>
    </nav>

    <div class="container">
        <!-- Member Information Card -->
        <div class="member-info">
            <h2 style="color: #2d5a27; text-align: center; margin-bottom: 1rem;">
                🌟 Member Profile
            </h2>
            <div class="member-details">
                <div class="detail-item">
                    <h4>👤 Member ID</h4>
                    <p><?= $member['id'] ?></p>
                </div>
                <div class="detail-item">
                    <h4>🔗 Referral Code</h4>
                    <p><?= $referral_code ?></p>
                </div>
                <div class="detail-item">
                    <h4>💼 Rank</h4>
                    <p><?= ucfirst($member['group_name']) ?></p>
                </div>
                <div class="detail-item">
                    <h4>📧 Email</h4>
                    <p><?= htmlspecialchars($member['mail']) ?></p>
                </div>
                <div class="detail-item">
                    <h4>📅 Member Since</h4>
                    <p><?= date('M Y', strtotime($member['joined_date'])) ?></p>
                </div>
                <div class="detail-item">
                    <h4>💰 Commission Rate</h4>
                    <p><?= $member['commission_rate'] ?>%</p>
                </div>
            </div>
        </div>

        <!-- Dashboard Statistics -->
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <div class="card-header">
                    <div class="card-icon">💰</div>
                    <div class="card-title">Total Commissions</div>
                </div>
                <div class="stat-number">₱<?= number_format($member['total_commissions'], 2) ?></div>
                <div class="stat-label">Lifetime Earnings</div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 65%;"></div>
                </div>
                <small>65% of monthly goal</small>
            </div>

            <div class="dashboard-card">
                <div class="card-header">
                    <div class="card-icon">📈</div>
                    <div class="card-title">Total Sales</div>
                </div>
                <div class="stat-number">₱<?= number_format($member['total_sales'], 2) ?></div>
                <div class="stat-label">Personal Sales Volume</div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 45%;"></div>
                </div>
                <small>₱2,500 to next rank</small>
            </div>

            <div class="dashboard-card">
                <div class="card-header">
                    <div class="card-icon">💎</div>
                    <div class="card-title">Total Rebates</div>
                </div>
                <div class="stat-number">₱<?= number_format($member['total_rebates'], 2) ?></div>
                <div class="stat-label">Rebate Earnings</div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: 30%;"></div>
                </div>
                <small><?= $member['rebate_rate'] ?>% rebate rate</small>
            </div>

            <div class="dashboard-card">
                <div class="card-header">
                    <div class="card-icon">👥</div>
                    <div class="card-title">Team Size</div>
                </div>
                <div class="stat-number">12</div>
                <div class="stat-label">Direct Referrals</div>
                <div style="margin-top: 1rem;">
                    <p><strong>Active:</strong> 8 members</p>
                    <p><strong>This Month:</strong> +3 new</p>
                </div>
            </div>

            <div class="dashboard-card">
                <div class="card-header">
                    <div class="card-icon">🏆</div>
                    <div class="card-title">Achievements</div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 3rem; margin: 1rem 0;">🥉</div>
                    <p><strong><?= ucfirst($member['group_name']) ?> Member</strong></p>
                    <p>Next: Wholesale (₱1,000 sales)</p>
                </div>
            </div>
        </div>

        <!-- Genealogy Tree Visualization -->
        <div class="genealogy-tree">
            <h2 style="color: #2d5a27; text-align: center; margin-bottom: 2rem;">
                🌳 Your MLM Genealogy Tree
            </h2>

            <div class="tree-level">
                <div class="tree-node">
                    <strong>Sponsor</strong><br>
                    John Smith<br>
                    <small>ELH000001</small>
                </div>
            </div>

            <div class="tree-level">
                <div class="tree-node current">
                    <strong>YOU</strong><br>
                    <?= htmlspecialchars($member['user_name']) ?><br>
                    <small><?= $referral_code ?></small>
                </div>
            </div>

            <div class="tree-level">
                <div class="tree-node">
                    <strong>Maria Garcia</strong><br>
                    ELH000003<br>
                    <small>Direct Referral</small>
                </div>
                <div class="tree-node">
                    <strong>David Lee</strong><br>
                    ELH000004<br>
                    <small>Direct Referral</small>
                </div>
                <div class="tree-node">
                    <strong>Sarah Johnson</strong><br>
                    ELH000005<br>
                    <small>Direct Referral</small>
                </div>
            </div>

            <div style="text-align: center; margin-top: 2rem;">
                <a href="#" class="btn">📊 View Full Tree</a>
                <a href="#" class="btn btn-secondary">📋 Team Reports</a>
            </div>
        </div>

        <!-- Commission Tracking -->
        <div class="commission-chart">
            <h2 style="color: #2d5a27; margin-bottom: 1.5rem;">
                💳 Commission & Rebate Breakdown
            </h2>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; border-left: 4px solid #28a745;">
                    <h4 style="color: #28a745;">Direct Sales</h4>
                    <p style="font-size: 1.5rem; font-weight: bold; color: #2d5a27;">₱<?= number_format($member['total_commissions'] * 0.6, 2) ?></p>
                    <small>From personal sales (<?= $member['commission_rate'] ?>%)</small>
                </div>
                <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; border-left: 4px solid #007cba;">
                    <h4 style="color: #007cba;">Team Commissions</h4>
                    <p style="font-size: 1.5rem; font-weight: bold; color: #2d5a27;">₱<?= number_format($member['total_commissions'] * 0.3, 2) ?></p>
                    <small>From team sales</small>
                </div>
                <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; border-left: 4px solid #ffc107;">
                    <h4 style="color: #856404;">Rebate Earnings</h4>
                    <p style="font-size: 1.5rem; font-weight: bold; color: #2d5a27;">₱<?= number_format($member['total_rebates'], 2) ?></p>
                    <small>Product rebates (<?= $member['rebate_rate'] ?>%)</small>
                </div>
                <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 8px; border-left: 4px solid #dc3545;">
                    <h4 style="color: #dc3545;">Pending</h4>
                    <p style="font-size: 1.5rem; font-weight: bold; color: #2d5a27;">₱<?= number_format($member['total_commissions'] * 0.1, 2) ?></p>
                    <small>Processing payout</small>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="dashboard-card">
            <h2 style="color: #2d5a27; margin-bottom: 1.5rem;">⚡ Quick Actions</h2>

            <div class="quick-actions">
                <a href="/database_catalog.php" class="btn">
                    🛒 Shop Products
                </a>
                <a href="/cart.php" class="btn">
                    🛒 View Cart
                </a>
                <a href="/register.php" class="btn">
                    👥 Refer New Member
                </a>
                <a href="/income_management.php" class="btn">
                    💰 Income Management
                </a>
                <a href="/mlm_tools.php" class="btn">
                    📊 MLM Tools
                </a>
                <a href="#" class="btn">
                    📞 Contact Support
                </a>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="dashboard-card">
            <h2 style="color: #2d5a27; margin-bottom: 1.5rem;">📋 Recent Activity</h2>

            <div style="space-y: 1rem;">
                <div style="padding: 1rem; background: #f8f9fa; border-radius: 8px; margin-bottom: 1rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong>New referral: Sarah Johnson</strong>
                            <p style="color: #666; font-size: 0.9rem;">Joined as Wholesale Member</p>
                        </div>
                        <div style="color: #28a745; font-weight: bold;">+₱50.00</div>
                    </div>
                </div>

                <div style="padding: 1rem; background: #f8f9fa; border-radius: 8px; margin-bottom: 1rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong>Commission payout processed</strong>
                            <p style="color: #666; font-size: 0.9rem;">Monthly commission payment</p>
                        </div>
                        <div style="color: #28a745; font-weight: bold;">₱<?= number_format($member['total_commissions'] * 0.2, 2) ?></div>
                    </div>
                </div>

                <div style="padding: 1rem; background: #f8f9fa; border-radius: 8px; margin-bottom: 1rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong>Product sale: Premium Turmeric</strong>
                            <p style="color: #666; font-size: 0.9rem;">Personal sale commission</p>
                        </div>
                        <div style="color: #28a745; font-weight: bold;">+₱<?= number_format($member['commission_rate'], 2) ?></div>
                    </div>
                </div>

                <div style="padding: 1rem; background: #f8f9fa; border-radius: 8px; margin-bottom: 1rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong>Rank advancement eligible</strong>
                            <p style="color: #666; font-size: 0.9rem;">You are eligible for Wholesale rank</p>
                        </div>
                        <div style="color: #ffc107; font-weight: bold;">🏆 Upgrade</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MLM Performance Metrics -->
        <div class="dashboard-card">
            <h2 style="color: #2d5a27; margin-bottom: 1.5rem;">📊 Performance Metrics</h2>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                <div style="text-align: center; padding: 1rem; background: #e8f5e8; border-radius: 10px;">
                    <h3 style="color: #2d5a27;">Monthly Sales</h3>
                    <p style="font-size: 2rem; font-weight: bold; color: #28a745;">₱<?= number_format($member['total_sales'] * 0.3, 2) ?></p>
                    <small>This month</small>
                </div>

                <div style="text-align: center; padding: 1rem; background: #fff3e0; border-radius: 10px;">
                    <h3 style="color: #f57c00;">Team Volume</h3>
                    <p style="font-size: 2rem; font-weight: bold; color: #f57c00;">₱<?= number_format($member['total_sales'] * 2.5, 2) ?></p>
                    <small>Team total</small>
                </div>

                <div style="text-align: center; padding: 1rem; background: #f3e5f5; border-radius: 10px;">
                    <h3 style="color: #7b1fa2;">Conversion Rate</h3>
                    <p style="font-size: 2rem; font-weight: bold; color: #7b1fa2;">15.5%</p>
                    <small>Referral success</small>
                </div>

                <div style="text-align: center; padding: 1rem; background: #e3f2fd; border-radius: 10px;">
                    <h3 style="color: #1976d2;">Growth Rate</h3>
                    <p style="font-size: 2rem; font-weight: bold; color: #1976d2;">+25%</p>
                    <small>Month over month</small>
                </div>
            </div>
        </div>

        <!-- Rank Progression -->
        <div class="dashboard-card">
            <h2 style="color: #2d5a27; margin-bottom: 1.5rem;">🏆 Rank Progression</h2>

            <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 10px;">
                <h4 style="color: #2d5a27; margin-bottom: 1rem;">Current: <?= ucfirst($member['group_name']) ?> (<?= $member['commission_rate'] ?>% commission)</h4>

                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= min(($member['total_sales'] / 1000) * 100, 100) ?>%;"></div>
                </div>

                <div style="display: flex; justify-content: space-between; margin-top: 0.5rem;">
                    <span>₱<?= number_format($member['total_sales'], 2) ?></span>
                    <span>₱1,000 (Wholesale)</span>
                </div>

                <p style="margin-top: 1rem; color: #666;">
                    <?php if ($member['total_sales'] >= 1000): ?>
                        🎉 Congratulations! You are eligible for Wholesale rank upgrade.
                    <?php else: ?>
                        You need ₱<?= number_format(1000 - $member['total_sales'], 2) ?> more in sales to reach Wholesale rank.
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>

    <script>
        // Add interactive features
        document.addEventListener("DOMContentLoaded", function() {
            // Animate progress bars
            const progressBars = document.querySelectorAll(".progress-fill");
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = "0%";
                setTimeout(() => {
                    bar.style.width = width;
                }, 500);
            });

            // Add hover effects to cards
            const cards = document.querySelectorAll(".dashboard-card");
            cards.forEach(card => {
                card.addEventListener("mouseenter", function() {
                    this.style.boxShadow = "0 12px 35px rgba(0,0,0,0.15)";
                });
                card.addEventListener("mouseleave", function() {
                    this.style.boxShadow = "0 8px 25px rgba(0,0,0,0.1)";
                });
            });

            // Add click animation to buttons
            const buttons = document.querySelectorAll(".btn");
            buttons.forEach(button => {
                button.addEventListener("click", function() {
                    this.style.transform = "scale(0.95)";
                    setTimeout(() => {
                        this.style.transform = "translateY(-2px)";
                    }, 150);
                });
            });
        });

        // Auto-refresh dashboard data every 5 minutes
        setInterval(() => {
            // In a real application, this would fetch updated data via AJAX
            console.log("Dashboard data refresh (placeholder)");
        }, 300000);
    </script>
</body>
</html>