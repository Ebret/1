<?php
// ExtremeLife MLM Notifications Check Endpoint
// Returns new notifications for real-time updates

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

session_start();

// Check authentication
if (!isset($_SESSION['member_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Authentication required'
    ]);
    exit();
}

$member_id = $_SESSION['member_id'];

try {
    require_once '../system_config.php';
    
    $config = new SystemConfig();
    $pdo = $config->getDBConnection();
    
    $notifications = getNotifications($pdo, $member_id);
    $unreadCount = getUnreadNotificationCount($pdo, $member_id);
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'count' => $unreadCount,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    error_log("Notifications check error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error checking notifications'
    ]);
}

function getNotifications($pdo, $member_id) {
    try {
        // Get recent notifications (last 24 hours)
        $stmt = $pdo->prepare("
            SELECT 
                id,
                type,
                title,
                message,
                data,
                is_read,
                created_at,
                CASE 
                    WHEN created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE) THEN 1 
                    ELSE 0 
                END as is_new
            FROM mlm_notifications 
            WHERE member_id = ? 
            AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY created_at DESC 
            LIMIT 20
        ");
        $stmt->execute([$member_id]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If no notifications exist, create some sample ones for demo
        if (empty($notifications)) {
            $notifications = createSampleNotifications($pdo, $member_id);
        }
        
        // Format notifications
        foreach ($notifications as &$notification) {
            $notification['formatted_time'] = formatTimeAgo($notification['created_at']);
            $notification['icon'] = getNotificationIcon($notification['type']);
            $notification['color'] = getNotificationColor($notification['type']);
            
            // Parse JSON data if exists
            if ($notification['data']) {
                $notification['data'] = json_decode($notification['data'], true);
            }
        }
        
        return $notifications;
        
    } catch (Exception $e) {
        error_log("Error fetching notifications: " . $e->getMessage());
        return [];
    }
}

function getUnreadNotificationCount($pdo, $member_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM mlm_notifications 
            WHERE member_id = ? AND is_read = 0
        ");
        $stmt->execute([$member_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return (int) $result['count'];
        
    } catch (Exception $e) {
        error_log("Error getting unread count: " . $e->getMessage());
        return 0;
    }
}

function createSampleNotifications($pdo, $member_id) {
    // Create sample notifications for demo purposes
    $sampleNotifications = [
        [
            'type' => 'commission',
            'title' => 'New Commission Earned',
            'message' => 'You earned ₱250.00 commission from John Doe\'s purchase',
            'data' => json_encode(['amount' => 250.00, 'from_member' => 'John Doe']),
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 minutes'))
        ],
        [
            'type' => 'sale',
            'title' => 'Product Sale',
            'message' => 'Your Vitamin C Complex was purchased by Maria Santos',
            'data' => json_encode(['product' => 'Vitamin C Complex', 'customer' => 'Maria Santos']),
            'created_at' => date('Y-m-d H:i:s', strtotime('-15 minutes'))
        ],
        [
            'type' => 'team',
            'title' => 'New Team Member',
            'message' => 'Sarah Johnson joined your team',
            'data' => json_encode(['member_name' => 'Sarah Johnson']),
            'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))
        ],
        [
            'type' => 'system',
            'title' => 'Monthly Report Available',
            'message' => 'Your monthly performance report is now available',
            'data' => json_encode(['report_month' => date('F Y')]),
            'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))
        ]
    ];
    
    try {
        // Check if notifications table exists, if not create it
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS mlm_notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                member_id INT NOT NULL,
                type VARCHAR(50) NOT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                data JSON NULL,
                is_read BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_member_created (member_id, created_at),
                INDEX idx_member_read (member_id, is_read)
            )
        ");
        
        // Insert sample notifications
        $stmt = $pdo->prepare("
            INSERT INTO mlm_notifications (member_id, type, title, message, data, created_at)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($sampleNotifications as $notification) {
            $stmt->execute([
                $member_id,
                $notification['type'],
                $notification['title'],
                $notification['message'],
                $notification['data'],
                $notification['created_at']
            ]);
        }
        
        // Return the created notifications
        return getNotifications($pdo, $member_id);
        
    } catch (Exception $e) {
        error_log("Error creating sample notifications: " . $e->getMessage());
        return [];
    }
}

function formatTimeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) {
        return 'Just now';
    } elseif ($time < 3600) {
        $minutes = floor($time / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($time < 86400) {
        $hours = floor($time / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($time < 2592000) {
        $days = floor($time / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', strtotime($datetime));
    }
}

function getNotificationIcon($type) {
    $icons = [
        'commission' => 'fas fa-coins',
        'sale' => 'fas fa-shopping-cart',
        'team' => 'fas fa-users',
        'system' => 'fas fa-cog',
        'promotion' => 'fas fa-percentage',
        'achievement' => 'fas fa-trophy',
        'warning' => 'fas fa-exclamation-triangle',
        'info' => 'fas fa-info-circle'
    ];
    
    return $icons[$type] ?? 'fas fa-bell';
}

function getNotificationColor($type) {
    $colors = [
        'commission' => 'success',
        'sale' => 'primary',
        'team' => 'info',
        'system' => 'secondary',
        'promotion' => 'warning',
        'achievement' => 'success',
        'warning' => 'warning',
        'info' => 'info'
    ];
    
    return $colors[$type] ?? 'primary';
}
?>
