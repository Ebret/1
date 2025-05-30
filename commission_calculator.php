<?php
// ExtremeLife MLM Commission Calculator for E-commerce Orders
function calculateMLMCommissions($order_id, $pdo) {
    try {
        // Get order details
        $order_stmt = $pdo->prepare("SELECT * FROM mlm_orders WHERE id = ?");
        $order_stmt->execute([$order_id]);
        $order = $order_stmt->fetch();
        
        if (!$order) {
            throw new Exception("Order not found");
        }
        
        // Get order items
        $items_stmt = $pdo->prepare("SELECT oi.*, p.commission_value, p.rebate_percentage 
                                    FROM mlm_order_items oi 
                                    LEFT JOIN mlm_products p ON oi.product_id = p.id 
                                    WHERE oi.order_id = ?");
        $items_stmt->execute([$order_id]);
        $order_items = $items_stmt->fetchAll();
        
        // Determine member (for demo, use member ID 1)
        $member_id = 1; // In real system, get from session or order
        
        // Get member's user group
        $member_stmt = $pdo->prepare("SELECT m.*, g.commission_rate, g.rebate_rate 
                                     FROM mlm_members m 
                                     LEFT JOIN mlm_user_groups g ON m.user_group_id = g.id 
                                     WHERE m.id = ?");
        $member_stmt->execute([$member_id]);
        $member = $member_stmt->fetch();
        
        if (!$member) {
            // Create default member if not exists
            $pdo->exec("INSERT IGNORE INTO mlm_members (id, user_group_id, status) VALUES (1, 1, 'active')");
            $member = ['id' => 1, 'commission_rate' => 10.00, 'rebate_rate' => 2.00, 'user_group_id' => 1];
        }
        
        $total_commission = 0;
        $total_rebate = 0;
        
        // Calculate commissions for each item
        foreach ($order_items as $item) {
            // Calculate commission based on member's group rate
            $commission_amount = ($item['subtotal'] * $member['commission_rate']) / 100;
            
            // Calculate rebate based on product rebate percentage
            $rebate_amount = ($item['subtotal'] * $member['rebate_rate']) / 100;
            
            // Insert commission record
            $comm_stmt = $pdo->prepare("INSERT INTO mlm_commissions (member_id, order_id, product_id, amount, commission_type, status) VALUES (?, ?, ?, ?, 'direct', 'pending')");
            $comm_stmt->execute([$member_id, $order_id, $item['product_id'], $commission_amount]);
            
            // Insert rebate record if table exists
            try {
                $rebate_stmt = $pdo->prepare("INSERT INTO mlm_rebates (member_id, product_id, order_id, rebate_amount, rebate_percentage, status) VALUES (?, ?, ?, ?, ?, 'pending')");
                $rebate_stmt->execute([$member_id, $item['product_id'], $order_id, $rebate_amount, $member['rebate_rate']]);
            } catch (PDOException $e) {
                // Table might not exist, skip rebate insertion
                error_log("Rebate table not found: " . $e->getMessage());
            }
            
            // Update order item with commission and rebate earned
            $update_item_stmt = $pdo->prepare("UPDATE mlm_order_items SET commission_earned = ?, rebate_earned = ? WHERE id = ?");
            $update_item_stmt->execute([$commission_amount, $rebate_amount, $item['id']]);
            
            $total_commission += $commission_amount;
            $total_rebate += $rebate_amount;
        }
        
        // Update member totals
        $update_member_stmt = $pdo->prepare("UPDATE mlm_members SET 
            total_sales = COALESCE(total_sales, 0) + ?, 
            total_commissions = COALESCE(total_commissions, 0) + ?, 
            total_rebates = COALESCE(total_rebates, 0) + ?,
            updated_date = NOW() 
            WHERE id = ?");
        $update_member_stmt->execute([$order['total_amount'], $total_commission, $total_rebate, $member_id]);
        
        // Check for rank advancement
        $new_rank = checkRankAdvancement($member_id, $pdo);
        
        return [
            'commission' => $total_commission,
            'rebate' => $total_rebate,
            'member_id' => $member_id,
            'new_rank' => $new_rank
        ];
        
    } catch (Exception $e) {
        error_log("Commission calculation error: " . $e->getMessage());
        return false;
    }
}

function checkRankAdvancement($member_id, $pdo) {
    try {
        // Get member's current sales and group
        $stmt = $pdo->prepare("SELECT m.*, g.group_level, g.minimum_sales_requirement 
                              FROM mlm_members m 
                              LEFT JOIN mlm_user_groups g ON m.user_group_id = g.id 
                              WHERE m.id = ?");
        $stmt->execute([$member_id]);
        $member = $stmt->fetch();
        
        if (!$member) return false;
        
        // Get next rank requirements
        $next_rank_stmt = $pdo->prepare("SELECT * FROM mlm_user_groups 
                                        WHERE group_level > ? 
                                        AND minimum_sales_requirement <= ? 
                                        ORDER BY group_level ASC LIMIT 1");
        $next_rank_stmt->execute([$member['group_level'] ?? 1, $member['total_sales'] ?? 0]);
        $next_rank = $next_rank_stmt->fetch();
        
        if ($next_rank) {
            // Promote member to next rank
            $promote_stmt = $pdo->prepare("UPDATE mlm_members SET 
                user_group_id = ?, 
                rank_advancement_date = NOW(),
                updated_date = NOW() 
                WHERE id = ?");
            $promote_stmt->execute([$next_rank['id'], $member_id]);
            
            // Log rank history if table exists
            try {
                $history_stmt = $pdo->prepare("INSERT INTO mlm_rank_history 
                    (member_id, old_group_id, new_group_id, advancement_type, sales_volume, notes) 
                    VALUES (?, ?, ?, 'automatic', ?, 'Automatic advancement based on sales volume')");
                $history_stmt->execute([$member_id, $member['user_group_id'], $next_rank['id'], $member['total_sales']]);
            } catch (PDOException $e) {
                // Table might not exist, skip history logging
                error_log("Rank history table not found: " . $e->getMessage());
            }
            
            return $next_rank['group_name'];
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("Rank advancement error: " . $e->getMessage());
        return false;
    }
}

// Function to create sample order for testing
function createSampleOrder($pdo) {
    try {
        // Generate order number
        $order_number = 'ELH' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Insert sample order
        $stmt = $pdo->prepare("INSERT INTO mlm_orders (order_number, customer_name, customer_email, customer_phone, total_amount, pickup_date, pickup_time, payment_method, status, created_date) VALUES (?, ?, ?, ?, ?, ?, ?, 'cash_pickup', 'pending', NOW())");
        $stmt->execute([
            $order_number,
            'John Doe',
            'john@example.com',
            '+63 912 345 6789',
            599.98,
            date('Y-m-d', strtotime('+1 day')),
            '14:00'
        ]);
        
        $order_id = $pdo->lastInsertId();
        
        // Insert sample order items
        $sample_items = [
            ['product_id' => 1, 'product_name' => 'Premium Turmeric Capsules', 'quantity' => 1, 'unit_price' => 299.99, 'subtotal' => 299.99],
            ['product_id' => 2, 'product_name' => 'Organic Green Tea', 'quantity' => 1, 'unit_price' => 199.99, 'subtotal' => 199.99],
            ['product_id' => 3, 'product_name' => 'Essential Oil Blend', 'quantity' => 1, 'unit_price' => 99.99, 'subtotal' => 99.99]
        ];
        
        foreach ($sample_items as $item) {
            $stmt = $pdo->prepare("INSERT INTO mlm_order_items (order_id, product_id, product_name, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $order_id,
                $item['product_id'],
                $item['product_name'],
                $item['quantity'],
                $item['unit_price'],
                $item['subtotal']
            ]);
        }
        
        return $order_id;
        
    } catch (Exception $e) {
        error_log("Sample order creation error: " . $e->getMessage());
        return false;
    }
}

// Function to get commission summary for a member
function getCommissionSummary($member_id, $pdo) {
    try {
        $summary = [
            'total_commissions' => 0,
            'pending_commissions' => 0,
            'paid_commissions' => 0,
            'total_rebates' => 0,
            'recent_orders' => []
        ];
        
        // Get commission totals
        $comm_stmt = $pdo->prepare("SELECT 
            COALESCE(SUM(amount), 0) as total,
            COALESCE(SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END), 0) as pending,
            COALESCE(SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END), 0) as paid
            FROM mlm_commissions WHERE member_id = ?");
        $comm_stmt->execute([$member_id]);
        $comm_data = $comm_stmt->fetch();
        
        if ($comm_data) {
            $summary['total_commissions'] = $comm_data['total'];
            $summary['pending_commissions'] = $comm_data['pending'];
            $summary['paid_commissions'] = $comm_data['paid'];
        }
        
        // Get recent orders with commissions
        $orders_stmt = $pdo->prepare("SELECT o.*, 
            COALESCE(SUM(c.amount), 0) as commission_earned
            FROM mlm_orders o
            LEFT JOIN mlm_commissions c ON o.id = c.order_id AND c.member_id = ?
            WHERE o.status IN ('completed', 'ready')
            GROUP BY o.id
            ORDER BY o.created_date DESC
            LIMIT 10");
        $orders_stmt->execute([$member_id]);
        $summary['recent_orders'] = $orders_stmt->fetchAll();
        
        return $summary;
        
    } catch (Exception $e) {
        error_log("Commission summary error: " . $e->getMessage());
        return [
            'total_commissions' => 0,
            'pending_commissions' => 0,
            'paid_commissions' => 0,
            'total_rebates' => 0,
            'recent_orders' => []
        ];
    }
}
?>
