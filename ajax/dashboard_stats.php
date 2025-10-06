<?php
// AJAX endpoint for dashboard statistics
// Returns JSON data for dashboard updates

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Start session and check authentication
session_start();

// Check if user is logged in
if (!isset($_SESSION['member_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ]);
    exit();
}

// Check if request is AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit();
}

$member_id = $_SESSION['member_id'];

try {
    // Include database configuration
    require_once '../system_config.php';
    
    $config = new SystemConfig();
    $pdo = $config->getDBConnection();
    
    // Fetch updated dashboard statistics
    $stats = getDashboardStats($pdo, $member_id);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log("Dashboard AJAX error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error occurred'
    ]);
}

function getDashboardStats($pdo, $member_id) {
    $stats = [
        'commission_balance' => 0,
        'total_earnings' => 0,
        'team_size' => 0,
        'active_referrals' => 0,
        'recent_commissions' => [],
        'pending_withdrawals' => 0,
        'this_month_earnings' => 0
    ];
    
    try {
        // Get commission balance (approved commissions minus withdrawals)
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0) as balance 
            FROM mlm_commissions 
            WHERE member_id = ? AND status = 'approved'
        ");
        $stmt->execute([$member_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $approved_commissions = $result['balance'];
        
        // Get total withdrawals
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0) as total 
            FROM mlm_withdrawals 
            WHERE member_id = ? AND status = 'completed'
        ");
        $stmt->execute([$member_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_withdrawals = $result['total'];
        
        $stats['commission_balance'] = $approved_commissions - $total_withdrawals;
        
        // Get total earnings (all commissions)
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0) as total 
            FROM mlm_commissions 
            WHERE member_id = ?
        ");
        $stmt->execute([$member_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['total_earnings'] = $result['total'];
        
        // Get team size (direct referrals)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM mlm_members 
            WHERE sponsor_id = ?
        ");
        $stmt->execute([$member_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['team_size'] = $result['count'];
        
        // Get active referrals
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM mlm_members 
            WHERE sponsor_id = ? AND status = 'active'
        ");
        $stmt->execute([$member_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['active_referrals'] = $result['count'];
        
        // Get recent commissions (last 5)
        $stmt = $pdo->prepare("
            SELECT 
                amount,
                description,
                created_at,
                status
            FROM mlm_commissions 
            WHERE member_id = ? 
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        $stmt->execute([$member_id]);
        $stats['recent_commissions'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get pending withdrawals
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0) as total 
            FROM mlm_withdrawals 
            WHERE member_id = ? AND status = 'pending'
        ");
        $stmt->execute([$member_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['pending_withdrawals'] = $result['total'];
        
        // Get this month's earnings
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(amount), 0) as total 
            FROM mlm_commissions 
            WHERE member_id = ? 
            AND YEAR(created_at) = YEAR(CURDATE()) 
            AND MONTH(created_at) = MONTH(CURDATE())
        ");
        $stmt->execute([$member_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['this_month_earnings'] = $result['total'];
        
        // Format numbers to 2 decimal places
        $stats['commission_balance'] = number_format($stats['commission_balance'], 2, '.', '');
        $stats['total_earnings'] = number_format($stats['total_earnings'], 2, '.', '');
        $stats['pending_withdrawals'] = number_format($stats['pending_withdrawals'], 2, '.', '');
        $stats['this_month_earnings'] = number_format($stats['this_month_earnings'], 2, '.', '');
        
    } catch (Exception $e) {
        error_log("Error fetching dashboard stats: " . $e->getMessage());
        throw $e;
    }
    
    return $stats;
}
?>
