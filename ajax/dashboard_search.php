<?php
// ExtremeLife MLM Dashboard Search Endpoint
// Provides global search functionality across dashboard sections

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

// Check request method and content type
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['query']) || empty(trim($input['query']))) {
    echo json_encode([
        'success' => false,
        'message' => 'Search query is required'
    ]);
    exit();
}

$member_id = $_SESSION['member_id'];
$query = trim($input['query']);
$searchQuery = '%' . $query . '%';

try {
    require_once '../system_config.php';
    
    $config = new SystemConfig();
    $pdo = $config->getDBConnection();
    
    $results = performGlobalSearch($pdo, $member_id, $searchQuery, $query);
    
    echo json_encode([
        'success' => true,
        'results' => $results,
        'query' => $query,
        'total_results' => array_sum(array_map('count', $results))
    ]);
    
} catch (Exception $e) {
    error_log("Search error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Search error occurred'
    ]);
}

function performGlobalSearch($pdo, $member_id, $searchQuery, $originalQuery) {
    $results = [
        'products' => [],
        'transactions' => [],
        'team_members' => [],
        'commissions' => [],
        'orders' => []
    ];
    
    try {
        // Search products
        $stmt = $pdo->prepare("
            SELECT id, name, description, price, image_url, category
            FROM mlm_products 
            WHERE (name LIKE ? OR description LIKE ? OR category LIKE ?) 
            AND status = 'active'
            LIMIT 10
        ");
        $stmt->execute([$searchQuery, $searchQuery, $searchQuery]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($products as $product) {
            $results['products'][] = [
                'id' => $product['id'],
                'title' => $product['name'],
                'description' => substr($product['description'], 0, 100) . '...',
                'price' => '₱' . number_format($product['price'], 2),
                'category' => $product['category'],
                'image' => $product['image_url'] ?: 'images/product-placeholder.jpg',
                'type' => 'product',
                'url' => '#sales',
                'relevance' => calculateRelevance($originalQuery, $product['name'] . ' ' . $product['description'])
            ];
        }
        
        // Search transactions/sales
        $stmt = $pdo->prepare("
            SELECT s.id, s.amount, s.product_name, s.created_at, s.status,
                   p.name as product_name_full, p.image_url
            FROM mlm_sales s
            LEFT JOIN mlm_products p ON s.product_id = p.id
            WHERE s.member_id = ? 
            AND (s.product_name LIKE ? OR p.name LIKE ?)
            ORDER BY s.created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$member_id, $searchQuery, $searchQuery]);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($transactions as $transaction) {
            $results['transactions'][] = [
                'id' => $transaction['id'],
                'title' => $transaction['product_name_full'] ?: $transaction['product_name'],
                'description' => 'Sale on ' . date('M j, Y', strtotime($transaction['created_at'])),
                'amount' => '₱' . number_format($transaction['amount'], 2),
                'status' => $transaction['status'],
                'date' => $transaction['created_at'],
                'type' => 'transaction',
                'url' => '#overview',
                'relevance' => calculateRelevance($originalQuery, $transaction['product_name'])
            ];
        }
        
        // Search team members
        $stmt = $pdo->prepare("
            SELECT id, first_name, last_name, email, phone, status, created_at
            FROM mlm_members 
            WHERE sponsor_id = ? 
            AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$member_id, $searchQuery, $searchQuery, $searchQuery]);
        $teamMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($teamMembers as $member) {
            $fullName = $member['first_name'] . ' ' . $member['last_name'];
            $results['team_members'][] = [
                'id' => $member['id'],
                'title' => $fullName,
                'description' => $member['email'],
                'status' => $member['status'],
                'joined' => date('M j, Y', strtotime($member['created_at'])),
                'type' => 'team_member',
                'url' => '#team',
                'relevance' => calculateRelevance($originalQuery, $fullName . ' ' . $member['email'])
            ];
        }
        
        // Search commissions
        $stmt = $pdo->prepare("
            SELECT id, amount, description, type, status, created_at
            FROM mlm_commissions 
            WHERE member_id = ? 
            AND (description LIKE ? OR type LIKE ?)
            ORDER BY created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$member_id, $searchQuery, $searchQuery]);
        $commissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($commissions as $commission) {
            $results['commissions'][] = [
                'id' => $commission['id'],
                'title' => $commission['description'],
                'description' => ucfirst($commission['type']) . ' commission',
                'amount' => '₱' . number_format($commission['amount'], 2),
                'status' => $commission['status'],
                'date' => $commission['created_at'],
                'type' => 'commission',
                'url' => '#analytics',
                'relevance' => calculateRelevance($originalQuery, $commission['description'])
            ];
        }
        
        // Search orders
        $stmt = $pdo->prepare("
            SELECT o.id, o.total_amount, o.status, o.created_at,
                   GROUP_CONCAT(oi.product_name SEPARATOR ', ') as products
            FROM mlm_orders o
            LEFT JOIN mlm_order_items oi ON o.id = oi.order_id
            WHERE o.member_id = ? 
            AND (o.status LIKE ? OR oi.product_name LIKE ?)
            GROUP BY o.id
            ORDER BY o.created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$member_id, $searchQuery, $searchQuery]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($orders as $order) {
            $results['orders'][] = [
                'id' => $order['id'],
                'title' => 'Order #' . $order['id'],
                'description' => $order['products'] ?: 'Order items',
                'amount' => '₱' . number_format($order['total_amount'], 2),
                'status' => $order['status'],
                'date' => $order['created_at'],
                'type' => 'order',
                'url' => '#sales',
                'relevance' => calculateRelevance($originalQuery, $order['products'] ?: '')
            ];
        }
        
        // Sort all results by relevance
        foreach ($results as &$categoryResults) {
            usort($categoryResults, function($a, $b) {
                return $b['relevance'] <=> $a['relevance'];
            });
        }
        
    } catch (Exception $e) {
        error_log("Error in global search: " . $e->getMessage());
        throw $e;
    }
    
    return $results;
}

function calculateRelevance($query, $text) {
    $query = strtolower($query);
    $text = strtolower($text);
    
    // Exact match gets highest score
    if (strpos($text, $query) !== false) {
        $position = strpos($text, $query);
        // Earlier matches get higher scores
        $positionScore = max(0, 100 - $position);
        return 100 + $positionScore;
    }
    
    // Word matches
    $queryWords = explode(' ', $query);
    $textWords = explode(' ', $text);
    $matches = 0;
    
    foreach ($queryWords as $queryWord) {
        foreach ($textWords as $textWord) {
            if (strpos($textWord, $queryWord) !== false) {
                $matches++;
                break;
            }
        }
    }
    
    // Calculate relevance based on word matches
    $wordRelevance = ($matches / count($queryWords)) * 50;
    
    // Partial matches
    $partialMatches = 0;
    foreach ($queryWords as $queryWord) {
        if (strpos($text, $queryWord) !== false) {
            $partialMatches++;
        }
    }
    
    $partialRelevance = ($partialMatches / count($queryWords)) * 25;
    
    return $wordRelevance + $partialRelevance;
}
?>
