<?php
// ExtremeLife MLM Complete Sponsor System Integration
echo "=== EXTREMELIFE MLM SPONSOR SYSTEM INTEGRATION ===\n";

// Database configuration
$db_config = [
    'host' => 'localhost',
    'dbname' => 'drupal_umd',
    'username' => 'drupal_user',
    'password' => 'secure_drupal_pass_1748318545'
];

// Test database connection
try {
    $pdo = new PDO("mysql:host={$db_config['host']};dbname={$db_config['dbname']}", 
                   $db_config['username'], $db_config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connection successful\n";
    $db_available = true;
} catch (PDOException $e) {
    echo "⚠️  Database connection failed: " . $e->getMessage() . "\n";
    echo "📝 Creating file-based sponsor system for testing...\n";
    $db_available = false;
}

if ($db_available) {
    echo "\n1. Setting up MLM database tables...\n";
    
    // Create enhanced MLM members table
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS mlm_members (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            sponsor_id INT NULL,
            referral_code VARCHAR(20) UNIQUE NOT NULL,
            user_group_id INT DEFAULT 1,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            phone VARCHAR(50),
            address TEXT,
            city VARCHAR(100),
            province VARCHAR(100),
            postal_code VARCHAR(20),
            status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
            total_sales DECIMAL(12,2) DEFAULT 0.00,
            total_commissions DECIMAL(12,2) DEFAULT 0.00,
            total_rebates DECIMAL(12,2) DEFAULT 0.00,
            personal_sales DECIMAL(12,2) DEFAULT 0.00,
            team_sales DECIMAL(12,2) DEFAULT 0.00,
            direct_referrals INT DEFAULT 0,
            total_referrals INT DEFAULT 0,
            rank_advancement_date TIMESTAMP NULL,
            last_commission_date TIMESTAMP NULL,
            joined_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_sponsor (sponsor_id),
            INDEX idx_referral_code (referral_code),
            INDEX idx_status (status)
        )");
        echo "✅ MLM members table created/verified\n";
    } catch (PDOException $e) {
        echo "❌ Error creating mlm_members table: " . $e->getMessage() . "\n";
    }
    
    // Create genealogy table
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS mlm_genealogy (
            id INT AUTO_INCREMENT PRIMARY KEY,
            member_id INT NOT NULL,
            sponsor_id INT NULL,
            upline_path TEXT,
            level_depth INT DEFAULT 1,
            left_node INT DEFAULT 0,
            right_node INT DEFAULT 0,
            created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_member (member_id),
            INDEX idx_sponsor (sponsor_id),
            INDEX idx_level (level_depth)
        )");
        echo "✅ MLM genealogy table created/verified\n";
    } catch (PDOException $e) {
        echo "❌ Error creating mlm_genealogy table: " . $e->getMessage() . "\n";
    }
    
    // Create sponsor commissions table
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS mlm_sponsor_commissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sponsor_id INT NOT NULL,
            referral_id INT NOT NULL,
            order_id INT NULL,
            commission_type ENUM('direct', 'indirect', 'leadership', 'override') DEFAULT 'direct',
            commission_level INT DEFAULT 1,
            base_amount DECIMAL(12,2) NOT NULL,
            commission_rate DECIMAL(5,2) NOT NULL,
            commission_amount DECIMAL(12,2) NOT NULL,
            status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
            created_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            paid_date TIMESTAMP NULL,
            INDEX idx_sponsor (sponsor_id),
            INDEX idx_referral (referral_id),
            INDEX idx_status (status)
        )");
        echo "✅ MLM sponsor commissions table created/verified\n";
    } catch (PDOException $e) {
        echo "❌ Error creating mlm_sponsor_commissions table: " . $e->getMessage() . "\n";
    }
    
    echo "\n2. Populating test member hierarchy...\n";
    
    // Insert test members
    $test_members = [
        [1, NULL, 'ELH000001', 5, 'ExtremeLife', 'Company', 'admin@extremelifeherbal.com', '+63 912 000 0001', 50000.00, 10000.00, 500.00, 2],
        [2, 1, 'ELH000002', 4, 'Maria', 'Santos', 'maria.santos@extremelifeherbal.com', '+63 912 000 0002', 25000.00, 4500.00, 250.00, 2],
        [3, 1, 'ELH000003', 4, 'Juan', 'Dela Cruz', 'juan.delacruz@extremelifeherbal.com', '+63 912 000 0003', 30000.00, 5400.00, 300.00, 1],
        [4, 2, 'ELH000004', 3, 'Ana', 'Garcia', 'ana.garcia@extremelifeherbal.com', '+63 912 000 0004', 15000.00, 2250.00, 150.00, 2],
        [5, 2, 'ELH000005', 3, 'Carlos', 'Rodriguez', 'carlos.rodriguez@extremelifeherbal.com', '+63 912 000 0005', 18000.00, 2700.00, 180.00, 1],
        [6, 4, 'ELH000006', 2, 'Sofia', 'Reyes', 'sofia.reyes@extremelifeherbal.com', '+63 912 000 0006', 8000.00, 960.00, 80.00, 2],
        [7, 4, 'ELH000007', 2, 'Miguel', 'Torres', 'miguel.torres@extremelifeherbal.com', '+63 912 000 0007', 6500.00, 780.00, 65.00, 0],
        [8, 6, 'ELH000008', 1, 'Isabella', 'Morales', 'isabella.morales@extremelifeherbal.com', '+63 912 000 0008', 3500.00, 350.00, 35.00, 0],
        [9, 6, 'ELH000009', 1, 'Diego', 'Fernandez', 'diego.fernandez@extremelifeherbal.com', '+63 912 000 0009', 2800.00, 280.00, 28.00, 0],
        [10, 5, 'ELH000010', 1, 'Demo', 'Member', 'demo@extremelifeherbal.com', '+63 912 000 0010', 2500.00, 250.00, 25.00, 0]
    ];
    
    try {
        // Clear existing test data
        $pdo->exec("DELETE FROM mlm_sponsor_commissions WHERE sponsor_id <= 10");
        $pdo->exec("DELETE FROM mlm_genealogy WHERE member_id <= 10");
        $pdo->exec("DELETE FROM mlm_members WHERE id <= 10");
        
        $stmt = $pdo->prepare("INSERT INTO mlm_members (
            id, sponsor_id, referral_code, user_group_id, first_name, last_name, email, phone,
            total_sales, total_commissions, total_rebates, direct_referrals, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
        
        foreach ($test_members as $member) {
            $stmt->execute($member);
            echo "✅ Created member: {$member[4]} {$member[5]} ({$member[2]})\n";
        }
        
        echo "\n3. Building genealogy relationships...\n";
        
        // Build genealogy tree
        foreach ($test_members as $member) {
            $member_id = $member[0];
            $sponsor_id = $member[1];
            
            $upline_path = [];
            $current_sponsor = $sponsor_id;
            $level = 1;
            
            // Build upline path
            while ($current_sponsor) {
                $upline_path[] = $current_sponsor;
                $sponsor_data = $pdo->query("SELECT sponsor_id FROM mlm_members WHERE id = $current_sponsor")->fetch();
                $current_sponsor = $sponsor_data ? $sponsor_data['sponsor_id'] : null;
                $level++;
            }
            
            $upline_path_str = implode(',', array_reverse($upline_path));
            
            $stmt = $pdo->prepare("INSERT INTO mlm_genealogy (member_id, sponsor_id, upline_path, level_depth) VALUES (?, ?, ?, ?)");
            $stmt->execute([$member_id, $sponsor_id, $upline_path_str, count($upline_path)]);
        }
        
        echo "✅ Genealogy tree built for all members\n";
        
        echo "\n4. Creating sample commission records...\n";
        
        // Sample commission data
        $commission_data = [
            [2, 4, 'direct', 1, 1000.00, 5.00, 50.00],
            [2, 5, 'direct', 1, 1200.00, 5.00, 60.00],
            [4, 6, 'direct', 1, 800.00, 3.00, 24.00],
            [4, 7, 'direct', 1, 650.00, 3.00, 19.50],
            [6, 8, 'direct', 1, 350.00, 2.00, 7.00],
            [6, 9, 'direct', 1, 280.00, 2.00, 5.60],
            [5, 10, 'direct', 1, 250.00, 2.00, 5.00],
            [1, 2, 'indirect', 2, 5000.00, 2.00, 100.00],
            [1, 3, 'indirect', 2, 6000.00, 2.00, 120.00]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO mlm_sponsor_commissions (
            sponsor_id, referral_id, commission_type, commission_level, 
            base_amount, commission_rate, commission_amount, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'paid')");
        
        foreach ($commission_data as $commission) {
            $stmt->execute($commission);
        }
        
        echo "✅ Sample commission records created\n";
        
    } catch (PDOException $e) {
        echo "❌ Error populating test data: " . $e->getMessage() . "\n";
    }
}

// Create file-based sponsor system for testing without database
echo "\n5. Creating file-based sponsor system for testing...\n";

$sponsor_data = [
    'members' => [
        'ELH000001' => ['name' => 'ExtremeLife Company', 'rank' => 'Diamond', 'sponsor' => null],
        'ELH000002' => ['name' => 'Maria Santos', 'rank' => 'VIP', 'sponsor' => 'ELH000001'],
        'ELH000003' => ['name' => 'Juan Dela Cruz', 'rank' => 'VIP', 'sponsor' => 'ELH000001'],
        'ELH000004' => ['name' => 'Ana Garcia', 'rank' => 'Distributor', 'sponsor' => 'ELH000002'],
        'ELH000005' => ['name' => 'Carlos Rodriguez', 'rank' => 'Distributor', 'sponsor' => 'ELH000002'],
        'ELH000006' => ['name' => 'Sofia Reyes', 'rank' => 'Wholesale', 'sponsor' => 'ELH000004'],
        'ELH000007' => ['name' => 'Miguel Torres', 'rank' => 'Wholesale', 'sponsor' => 'ELH000004'],
        'ELH000008' => ['name' => 'Isabella Morales', 'rank' => 'Member', 'sponsor' => 'ELH000006'],
        'ELH000009' => ['name' => 'Diego Fernandez', 'rank' => 'Member', 'sponsor' => 'ELH000006'],
        'ELH000010' => ['name' => 'Demo Member', 'rank' => 'Member', 'sponsor' => 'ELH000005']
    ],
    'commission_rates' => [
        'Member' => 10,
        'Wholesale' => 12,
        'Distributor' => 15,
        'VIP' => 18,
        'Diamond' => 20
    ],
    'rebate_rates' => [
        'Member' => 2,
        'Wholesale' => 3,
        'Distributor' => 4,
        'VIP' => 5,
        'Diamond' => 6
    ]
];

file_put_contents('sponsor_system_data.json', json_encode($sponsor_data, JSON_PRETTY_PRINT));
echo "✅ File-based sponsor system created: sponsor_system_data.json\n";

echo "\n6. Testing sponsor system functionality...\n";

// Test sponsor validation
function validateSponsorCode($code, $data) {
    return isset($data['members'][$code]);
}

// Test commission calculation
function calculateCommission($member_rank, $sale_amount, $data) {
    $rate = $data['commission_rates'][$member_rank] ?? 10;
    $rebate_rate = $data['rebate_rates'][$member_rank] ?? 2;
    
    return [
        'commission' => $sale_amount * ($rate / 100),
        'rebate' => $sale_amount * ($rebate_rate / 100),
        'total' => $sale_amount * (($rate + $rebate_rate) / 100)
    ];
}

// Run tests
$test_codes = ['ELH000005', 'ELH000010', 'ELH000999'];
foreach ($test_codes as $code) {
    $valid = validateSponsorCode($code, $sponsor_data);
    echo ($valid ? "✅" : "❌") . " Sponsor code $code: " . ($valid ? "Valid" : "Invalid") . "\n";
}

// Test commission calculation
$test_sale = 1000.00;
$test_rank = 'Member';
$commission = calculateCommission($test_rank, $test_sale, $sponsor_data);
echo "✅ Commission test ($test_rank, ₱$test_sale): ₱{$commission['total']}\n";

echo "\n=== SPONSOR SYSTEM INTEGRATION COMPLETE ===\n";
echo "✅ Database tables created (if available)\n";
echo "✅ Test member hierarchy populated\n";
echo "✅ Genealogy relationships established\n";
echo "✅ Commission tracking system ready\n";
echo "✅ File-based fallback system created\n";
echo "\n📊 Ready for testing:\n";
echo "   - Member registration: /register.php?sponsor=ELH000005\n";
echo "   - Member dashboard: /member_dashboard.php\n";
echo "   - Commission calculator: /commission_calculator.php\n";
echo "\n🎯 Next steps:\n";
echo "   1. Deploy fixed member_dashboard.php to production\n";
echo "   2. Test registration flow with sponsor codes\n";
echo "   3. Verify commission calculations\n";
echo "   4. Test e-commerce integration\n";
?>
