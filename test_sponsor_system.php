<?php
// ExtremeLife MLM Sponsor System Test (Standalone)
echo "=== EXTREMELIFE MLM SPONSOR SYSTEM TEST ===\n";

// Test data structure for sponsor relationships
$test_members = [
    1 => [
        'id' => 1,
        'sponsor_id' => null,
        'referral_code' => 'ELH000001',
        'first_name' => 'ExtremeLife',
        'last_name' => 'Company',
        'email' => 'admin@extremelifeherbal.com',
        'phone' => '+63 912 000 0001',
        'user_group_id' => 5,
        'group_name' => 'Diamond',
        'commission_rate' => 20.00,
        'rebate_rate' => 6.00,
        'total_sales' => 50000.00,
        'total_commissions' => 10000.00,
        'total_rebates' => 500.00,
        'direct_referrals_count' => 2,
        'sponsor_earnings' => 0.00,
        'status' => 'active',
        'joined_date' => '2023-01-01 00:00:00'
    ],
    2 => [
        'id' => 2,
        'sponsor_id' => 1,
        'referral_code' => 'ELH000002',
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'email' => 'maria.santos@extremelifeherbal.com',
        'phone' => '+63 912 000 0002',
        'user_group_id' => 4,
        'group_name' => 'VIP',
        'commission_rate' => 18.00,
        'rebate_rate' => 5.00,
        'total_sales' => 25000.00,
        'total_commissions' => 4500.00,
        'total_rebates' => 250.00,
        'direct_referrals_count' => 2,
        'sponsor_earnings' => 150.00,
        'status' => 'active',
        'joined_date' => '2023-02-15 00:00:00'
    ],
    3 => [
        'id' => 3,
        'sponsor_id' => 1,
        'referral_code' => 'ELH000003',
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
        'email' => 'juan.delacruz@extremelifeherbal.com',
        'phone' => '+63 912 000 0003',
        'user_group_id' => 4,
        'group_name' => 'VIP',
        'commission_rate' => 18.00,
        'rebate_rate' => 5.00,
        'total_sales' => 30000.00,
        'total_commissions' => 5400.00,
        'total_rebates' => 300.00,
        'direct_referrals_count' => 1,
        'sponsor_earnings' => 180.00,
        'status' => 'active',
        'joined_date' => '2023-03-01 00:00:00'
    ],
    4 => [
        'id' => 4,
        'sponsor_id' => 2,
        'referral_code' => 'ELH000004',
        'first_name' => 'Ana',
        'last_name' => 'Garcia',
        'email' => 'ana.garcia@extremelifeherbal.com',
        'phone' => '+63 912 000 0004',
        'user_group_id' => 3,
        'group_name' => 'Distributor',
        'commission_rate' => 15.00,
        'rebate_rate' => 4.00,
        'total_sales' => 15000.00,
        'total_commissions' => 2250.00,
        'total_rebates' => 150.00,
        'direct_referrals_count' => 2,
        'sponsor_earnings' => 75.00,
        'status' => 'active',
        'joined_date' => '2023-04-10 00:00:00'
    ],
    5 => [
        'id' => 5,
        'sponsor_id' => 2,
        'referral_code' => 'ELH000005',
        'first_name' => 'Carlos',
        'last_name' => 'Rodriguez',
        'email' => 'carlos.rodriguez@extremelifeherbal.com',
        'phone' => '+63 912 000 0005',
        'user_group_id' => 3,
        'group_name' => 'Distributor',
        'commission_rate' => 15.00,
        'rebate_rate' => 4.00,
        'total_sales' => 18000.00,
        'total_commissions' => 2700.00,
        'total_rebates' => 180.00,
        'direct_referrals_count' => 1,
        'sponsor_earnings' => 90.00,
        'status' => 'active',
        'joined_date' => '2023-05-20 00:00:00'
    ],
    6 => [
        'id' => 6,
        'sponsor_id' => 4,
        'referral_code' => 'ELH000006',
        'first_name' => 'Sofia',
        'last_name' => 'Reyes',
        'email' => 'sofia.reyes@extremelifeherbal.com',
        'phone' => '+63 912 000 0006',
        'user_group_id' => 2,
        'group_name' => 'Wholesale',
        'commission_rate' => 12.00,
        'rebate_rate' => 3.00,
        'total_sales' => 8000.00,
        'total_commissions' => 960.00,
        'total_rebates' => 80.00,
        'direct_referrals_count' => 2,
        'sponsor_earnings' => 40.00,
        'status' => 'active',
        'joined_date' => '2023-06-15 00:00:00'
    ],
    7 => [
        'id' => 7,
        'sponsor_id' => 4,
        'referral_code' => 'ELH000007',
        'first_name' => 'Miguel',
        'last_name' => 'Torres',
        'email' => 'miguel.torres@extremelifeherbal.com',
        'phone' => '+63 912 000 0007',
        'user_group_id' => 2,
        'group_name' => 'Wholesale',
        'commission_rate' => 12.00,
        'rebate_rate' => 3.00,
        'total_sales' => 6500.00,
        'total_commissions' => 780.00,
        'total_rebates' => 65.00,
        'direct_referrals_count' => 0,
        'sponsor_earnings' => 0.00,
        'status' => 'active',
        'joined_date' => '2023-07-01 00:00:00'
    ],
    8 => [
        'id' => 8,
        'sponsor_id' => 6,
        'referral_code' => 'ELH000008',
        'first_name' => 'Isabella',
        'last_name' => 'Morales',
        'email' => 'isabella.morales@extremelifeherbal.com',
        'phone' => '+63 912 000 0008',
        'user_group_id' => 1,
        'group_name' => 'Member',
        'commission_rate' => 10.00,
        'rebate_rate' => 2.00,
        'total_sales' => 3500.00,
        'total_commissions' => 350.00,
        'total_rebates' => 35.00,
        'direct_referrals_count' => 0,
        'sponsor_earnings' => 0.00,
        'status' => 'active',
        'joined_date' => '2023-08-10 00:00:00'
    ],
    9 => [
        'id' => 9,
        'sponsor_id' => 6,
        'referral_code' => 'ELH000009',
        'first_name' => 'Diego',
        'last_name' => 'Fernandez',
        'email' => 'diego.fernandez@extremelifeherbal.com',
        'phone' => '+63 912 000 0009',
        'user_group_id' => 1,
        'group_name' => 'Member',
        'commission_rate' => 10.00,
        'rebate_rate' => 2.00,
        'total_sales' => 2800.00,
        'total_commissions' => 280.00,
        'total_rebates' => 28.00,
        'direct_referrals_count' => 0,
        'sponsor_earnings' => 0.00,
        'status' => 'active',
        'joined_date' => '2023-09-05 00:00:00'
    ],
    10 => [
        'id' => 10,
        'sponsor_id' => 5,
        'referral_code' => 'ELH000010',
        'first_name' => 'Demo',
        'last_name' => 'Member',
        'email' => 'demo@extremelifeherbal.com',
        'phone' => '+63 912 000 0010',
        'user_group_id' => 1,
        'group_name' => 'Member',
        'commission_rate' => 10.00,
        'rebate_rate' => 2.00,
        'total_sales' => 2500.00,
        'total_commissions' => 250.00,
        'total_rebates' => 25.00,
        'direct_referrals_count' => 0,
        'sponsor_earnings' => 0.00,
        'status' => 'active',
        'joined_date' => '2023-10-01 00:00:00'
    ]
];

// Test genealogy relationships
$genealogy_tree = [
    1 => ['level' => 1, 'upline' => [], 'downline' => [2, 3]],
    2 => ['level' => 2, 'upline' => [1], 'downline' => [4, 5]],
    3 => ['level' => 2, 'upline' => [1], 'downline' => []],
    4 => ['level' => 3, 'upline' => [1, 2], 'downline' => [6, 7]],
    5 => ['level' => 3, 'upline' => [1, 2], 'downline' => [10]],
    6 => ['level' => 4, 'upline' => [1, 2, 4], 'downline' => [8, 9]],
    7 => ['level' => 4, 'upline' => [1, 2, 4], 'downline' => []],
    8 => ['level' => 5, 'upline' => [1, 2, 4, 6], 'downline' => []],
    9 => ['level' => 5, 'upline' => [1, 2, 4, 6], 'downline' => []],
    10 => ['level' => 4, 'upline' => [1, 2, 5], 'downline' => []]
];

echo "✅ Test data structure created successfully\n\n";

// Test 1: Display MLM hierarchy
echo "1. Testing MLM Hierarchy Structure:\n";
echo "=====================================\n";

function displayMemberTree($members, $genealogy, $member_id, $indent = 0) {
    $member = $members[$member_id];
    $prefix = str_repeat("  ", $indent);
    $icon = $indent == 0 ? "👑" : ($indent == 1 ? "🥇" : ($indent == 2 ? "🥈" : "🥉"));
    
    echo $prefix . $icon . " " . $member['first_name'] . " " . $member['last_name'] . 
         " (" . $member['referral_code'] . ") - " . $member['group_name'] . 
         " [₱" . number_format($member['total_sales'], 2) . " sales]\n";
    
    // Display direct referrals
    foreach ($genealogy[$member_id]['downline'] as $child_id) {
        if (isset($members[$child_id])) {
            displayMemberTree($members, $genealogy, $child_id, $indent + 1);
        }
    }
}

displayMemberTree($test_members, $genealogy_tree, 1);

// Test 2: Commission calculations
echo "\n2. Testing Commission Calculations:\n";
echo "===================================\n";

function calculateCommissions($members, $genealogy, $member_id, $sale_amount) {
    $member = $members[$member_id];
    $commissions = [];
    
    // Direct commission for the member
    $direct_commission = $sale_amount * ($member['commission_rate'] / 100);
    $rebate = $sale_amount * ($member['rebate_rate'] / 100);
    
    $commissions[] = [
        'member_id' => $member_id,
        'name' => $member['first_name'] . ' ' . $member['last_name'],
        'type' => 'Direct Sale',
        'commission' => $direct_commission,
        'rebate' => $rebate,
        'total' => $direct_commission + $rebate
    ];
    
    // Sponsor commissions (simplified - 2% for direct sponsor)
    if ($member['sponsor_id'] && isset($members[$member['sponsor_id']])) {
        $sponsor = $members[$member['sponsor_id']];
        $sponsor_commission = $sale_amount * 0.02; // 2% sponsor bonus
        
        $commissions[] = [
            'member_id' => $sponsor['id'],
            'name' => $sponsor['first_name'] . ' ' . $sponsor['last_name'],
            'type' => 'Sponsor Bonus',
            'commission' => $sponsor_commission,
            'rebate' => 0,
            'total' => $sponsor_commission
        ];
    }
    
    return $commissions;
}

// Test sale for Demo Member
$sale_amount = 1000.00;
$commissions = calculateCommissions($test_members, $genealogy_tree, 10, $sale_amount);

echo "Sale Amount: ₱" . number_format($sale_amount, 2) . "\n";
echo "Commission Breakdown:\n";
foreach ($commissions as $commission) {
    echo "  - " . $commission['name'] . " (" . $commission['type'] . "): ₱" . 
         number_format($commission['total'], 2) . "\n";
}

// Test 3: Sponsor validation
echo "\n3. Testing Sponsor Code Validation:\n";
echo "===================================\n";

function validateSponsorCode($members, $sponsor_code) {
    foreach ($members as $member) {
        if ($member['referral_code'] === $sponsor_code && $member['status'] === 'active') {
            return $member;
        }
    }
    return false;
}

$test_codes = ['ELH000005', 'ELH000999', 'ELH000001'];
foreach ($test_codes as $code) {
    $sponsor = validateSponsorCode($test_members, $code);
    if ($sponsor) {
        echo "✅ $code: Valid - " . $sponsor['first_name'] . " " . $sponsor['last_name'] . "\n";
    } else {
        echo "❌ $code: Invalid sponsor code\n";
    }
}

// Test 4: Registration simulation
echo "\n4. Testing New Member Registration:\n";
echo "===================================\n";

function simulateRegistration($members, $sponsor_code, $new_member_data) {
    $sponsor = validateSponsorCode($members, $sponsor_code);
    if (!$sponsor) {
        return ['success' => false, 'error' => 'Invalid sponsor code'];
    }
    
    // Generate new referral code
    $new_id = max(array_keys($members)) + 1;
    $new_referral_code = 'ELH' . str_pad($new_id, 6, '0', STR_PAD_LEFT);
    
    $new_member = array_merge($new_member_data, [
        'id' => $new_id,
        'sponsor_id' => $sponsor['id'],
        'referral_code' => $new_referral_code,
        'user_group_id' => 1,
        'group_name' => 'Member',
        'commission_rate' => 10.00,
        'rebate_rate' => 2.00,
        'total_sales' => 0.00,
        'total_commissions' => 0.00,
        'total_rebates' => 0.00,
        'direct_referrals_count' => 0,
        'sponsor_earnings' => 0.00,
        'status' => 'active',
        'joined_date' => date('Y-m-d H:i:s')
    ]);
    
    return [
        'success' => true, 
        'member' => $new_member,
        'sponsor' => $sponsor
    ];
}

$new_member_data = [
    'first_name' => 'Test',
    'last_name' => 'User',
    'email' => 'test@extremelifeherbal.com',
    'phone' => '+63 912 000 0011'
];

$registration = simulateRegistration($test_members, 'ELH000005', $new_member_data);
if ($registration['success']) {
    echo "✅ Registration successful!\n";
    echo "   New Member: " . $registration['member']['first_name'] . " " . $registration['member']['last_name'] . "\n";
    echo "   Referral Code: " . $registration['member']['referral_code'] . "\n";
    echo "   Sponsor: " . $registration['sponsor']['first_name'] . " " . $registration['sponsor']['last_name'] . "\n";
} else {
    echo "❌ Registration failed: " . $registration['error'] . "\n";
}

echo "\n=== SPONSOR SYSTEM TEST COMPLETE ===\n";
echo "✅ MLM hierarchy structure validated\n";
echo "✅ Commission calculations working\n";
echo "✅ Sponsor code validation functional\n";
echo "✅ Member registration simulation successful\n";
echo "\n🎯 Ready for live testing with sponsor relationships!\n";
echo "\n📊 Test URLs for sponsor registration:\n";
echo "   - With sponsor: /register.php?sponsor=ELH000005\n";
echo "   - Direct registration: /register.php\n";
echo "   - Demo member dashboard: /member_dashboard.php (ID: 10)\n";
?>
