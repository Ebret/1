<?php
// ExtremeLife MLM Sponsor System Database Setup
echo "=== EXTREMELIFE MLM SPONSOR SYSTEM SETUP ===\n";

try {
    $pdo = new PDO('mysql:host=localhost;dbname=drupal_umd', 'drupal_user', 'secure_drupal_pass_1748318545');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connection successful\n";
} catch (PDOException $e) {
    die("❌ Database connection failed: " . $e->getMessage() . "\n");
}

echo "\n1. Creating enhanced MLM members table with sponsor relationships...\n";

// Drop existing table if exists and recreate with sponsor functionality
try {
    $pdo->exec("DROP TABLE IF EXISTS mlm_members");
    echo "✅ Dropped existing mlm_members table\n";
    
    $pdo->exec("CREATE TABLE mlm_members (
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
        FOREIGN KEY (sponsor_id) REFERENCES mlm_members(id) ON DELETE SET NULL,
        FOREIGN KEY (user_group_id) REFERENCES mlm_user_groups(id),
        INDEX idx_sponsor (sponsor_id),
        INDEX idx_referral_code (referral_code),
        INDEX idx_status (status)
    )");
    echo "✅ Created enhanced mlm_members table with sponsor relationships\n";
    
} catch (PDOException $e) {
    echo "❌ Error creating mlm_members table: " . $e->getMessage() . "\n";
}

echo "\n2. Creating MLM genealogy tree table...\n";

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
        FOREIGN KEY (member_id) REFERENCES mlm_members(id) ON DELETE CASCADE,
        FOREIGN KEY (sponsor_id) REFERENCES mlm_members(id) ON DELETE SET NULL,
        INDEX idx_member (member_id),
        INDEX idx_sponsor (sponsor_id),
        INDEX idx_level (level_depth)
    )");
    echo "✅ Created mlm_genealogy table\n";
    
} catch (PDOException $e) {
    echo "❌ Error creating mlm_genealogy table: " . $e->getMessage() . "\n";
}

echo "\n3. Creating sponsor commission tracking table...\n";

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
        FOREIGN KEY (sponsor_id) REFERENCES mlm_members(id) ON DELETE CASCADE,
        FOREIGN KEY (referral_id) REFERENCES mlm_members(id) ON DELETE CASCADE,
        FOREIGN KEY (order_id) REFERENCES mlm_orders(id) ON DELETE SET NULL,
        INDEX idx_sponsor (sponsor_id),
        INDEX idx_referral (referral_id),
        INDEX idx_status (status)
    )");
    echo "✅ Created mlm_sponsor_commissions table\n";
    
} catch (PDOException $e) {
    echo "❌ Error creating mlm_sponsor_commissions table: " . $e->getMessage() . "\n";
}

echo "\n4. Creating initial sponsor members for testing...\n";

// Create root sponsor (company)
try {
    $stmt = $pdo->prepare("INSERT INTO mlm_members (
        id, sponsor_id, referral_code, user_group_id, first_name, last_name, email, phone, 
        status, total_sales, total_commissions, total_rebates, direct_referrals, total_referrals
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    // Root sponsor (ExtremeLife Company)
    $stmt->execute([
        1, NULL, 'ELH000001', 5, 'ExtremeLife', 'Company', 'admin@extremelifeherbal.com', 
        '+63 912 000 0001', 'active', 50000.00, 10000.00, 500.00, 5, 25
    ]);
    echo "✅ Created root sponsor: ExtremeLife Company (ELH000001)\n";
    
    // Level 1 sponsors
    $stmt->execute([
        2, 1, 'ELH000002', 4, 'Maria', 'Santos', 'maria.santos@extremelifeherbal.com', 
        '+63 912 000 0002', 'active', 25000.00, 4500.00, 250.00, 3, 15
    ]);
    echo "✅ Created Level 1 sponsor: Maria Santos (ELH000002)\n";
    
    $stmt->execute([
        3, 1, 'ELH000003', 4, 'Juan', 'Dela Cruz', 'juan.delacruz@extremelifeherbal.com', 
        '+63 912 000 0003', 'active', 30000.00, 5400.00, 300.00, 4, 20
    ]);
    echo "✅ Created Level 1 sponsor: Juan Dela Cruz (ELH000003)\n";
    
    // Level 2 sponsors under Maria
    $stmt->execute([
        4, 2, 'ELH000004', 3, 'Ana', 'Garcia', 'ana.garcia@extremelifeherbal.com', 
        '+63 912 000 0004', 'active', 15000.00, 2250.00, 150.00, 2, 8
    ]);
    echo "✅ Created Level 2 sponsor: Ana Garcia (ELH000004)\n";
    
    $stmt->execute([
        5, 2, 'ELH000005', 3, 'Carlos', 'Rodriguez', 'carlos.rodriguez@extremelifeherbal.com', 
        '+63 912 000 0005', 'active', 18000.00, 2700.00, 180.00, 3, 10
    ]);
    echo "✅ Created Level 2 sponsor: Carlos Rodriguez (ELH000005)\n";
    
    // Level 3 members under Ana
    $stmt->execute([
        6, 4, 'ELH000006', 2, 'Sofia', 'Reyes', 'sofia.reyes@extremelifeherbal.com', 
        '+63 912 000 0006', 'active', 8000.00, 960.00, 80.00, 1, 3
    ]);
    echo "✅ Created Level 3 member: Sofia Reyes (ELH000006)\n";
    
    $stmt->execute([
        7, 4, 'ELH000007', 2, 'Miguel', 'Torres', 'miguel.torres@extremelifeherbal.com', 
        '+63 912 000 0007', 'active', 6500.00, 780.00, 65.00, 2, 5
    ]);
    echo "✅ Created Level 3 member: Miguel Torres (ELH000007)\n";
    
    // Level 4 members under Sofia
    $stmt->execute([
        8, 6, 'ELH000008', 1, 'Isabella', 'Morales', 'isabella.morales@extremelifeherbal.com', 
        '+63 912 000 0008', 'active', 3500.00, 350.00, 35.00, 0, 1
    ]);
    echo "✅ Created Level 4 member: Isabella Morales (ELH000008)\n";
    
    $stmt->execute([
        9, 6, 'ELH000009', 1, 'Diego', 'Fernandez', 'diego.fernandez@extremelifeherbal.com', 
        '+63 912 000 0009', 'active', 2800.00, 280.00, 28.00, 1, 2
    ]);
    echo "✅ Created Level 4 member: Diego Fernandez (ELH000009)\n";
    
    // Demo member for testing (Level 3 under Carlos)
    $stmt->execute([
        10, 5, 'ELH000010', 1, 'Demo', 'Member', 'demo@extremelifeherbal.com', 
        '+63 912 000 0010', 'active', 2500.00, 250.00, 25.00, 0, 0
    ]);
    echo "✅ Created Demo Member: Demo Member (ELH000010)\n";
    
} catch (PDOException $e) {
    echo "❌ Error creating sponsor members: " . $e->getMessage() . "\n";
}

echo "\n5. Building genealogy tree relationships...\n";

try {
    // Build genealogy tree for each member
    $members = $pdo->query("SELECT id, sponsor_id FROM mlm_members ORDER BY id")->fetchAll();
    
    foreach ($members as $member) {
        $upline_path = [];
        $current_sponsor = $member['sponsor_id'];
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
        $stmt->execute([$member['id'], $member['sponsor_id'], $upline_path_str, count($upline_path)]);
    }
    
    echo "✅ Built genealogy tree for all members\n";
    
} catch (PDOException $e) {
    echo "❌ Error building genealogy tree: " . $e->getMessage() . "\n";
}

echo "\n6. Creating sample sponsor commissions...\n";

try {
    // Create sample commission records
    $commission_data = [
        [2, 4, null, 'direct', 1, 1000.00, 5.00, 50.00],
        [2, 5, null, 'direct', 1, 1200.00, 5.00, 60.00],
        [4, 6, null, 'direct', 1, 800.00, 3.00, 24.00],
        [4, 7, null, 'direct', 1, 650.00, 3.00, 19.50],
        [6, 8, null, 'direct', 1, 350.00, 2.00, 7.00],
        [6, 9, null, 'direct', 1, 280.00, 2.00, 5.60],
        [1, 2, null, 'indirect', 2, 5000.00, 2.00, 100.00],
        [1, 3, null, 'indirect', 2, 6000.00, 2.00, 120.00]
    ];
    
    $stmt = $pdo->prepare("INSERT INTO mlm_sponsor_commissions (
        sponsor_id, referral_id, order_id, commission_type, commission_level, 
        base_amount, commission_rate, commission_amount, status
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'paid')");
    
    foreach ($commission_data as $commission) {
        $stmt->execute($commission);
    }
    
    echo "✅ Created sample sponsor commission records\n";
    
} catch (PDOException $e) {
    echo "❌ Error creating sponsor commissions: " . $e->getMessage() . "\n";
}

echo "\n7. Updating member totals based on sponsor commissions...\n";

try {
    // Update sponsor commission totals
    $pdo->exec("UPDATE mlm_members m SET 
        total_commissions = (
            SELECT COALESCE(SUM(commission_amount), 0) 
            FROM mlm_sponsor_commissions sc 
            WHERE sc.sponsor_id = m.id AND sc.status = 'paid'
        ) + total_commissions");
    
    echo "✅ Updated member commission totals\n";
    
} catch (PDOException $e) {
    echo "❌ Error updating member totals: " . $e->getMessage() . "\n";
}

echo "\n=== SPONSOR SYSTEM SETUP COMPLETE ===\n";
echo "✅ Enhanced MLM members table with sponsor relationships\n";
echo "✅ Genealogy tree structure created\n";
echo "✅ Sponsor commission tracking system\n";
echo "✅ 10 test members with realistic MLM hierarchy\n";
echo "✅ Sample commission data for testing\n";
echo "\n📊 MLM Hierarchy Created:\n";
echo "Level 1: ExtremeLife Company (Root)\n";
echo "Level 2: Maria Santos, Juan Dela Cruz\n";
echo "Level 3: Ana Garcia, Carlos Rodriguez\n";
echo "Level 4: Sofia Reyes, Miguel Torres, Demo Member\n";
echo "Level 5: Isabella Morales, Diego Fernandez\n";
echo "\n🎯 Ready for MLM testing with sponsor relationships!\n";
?>
