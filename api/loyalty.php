<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'check_status':
        checkLoyaltyStatus();
        break;
    case 'benefits':
        getLoyaltyBenefits();
        break;
    case 'redeem':
        redeemPoints();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function checkLoyaltyStatus() {
    global $pdo;
    
    $email = $_POST['email'] ?? $_GET['email'] ?? '';
    
    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Email required']);
        return;
    }
    
    $customer = getCustomerByEmail($email);
    
    if (!$customer) {
        echo json_encode(['success' => false, 'message' => 'Customer not found']);
        return;
    }
    
    // Get loyalty benefits for customer's level
    $benefits = getLoyaltyBenefits($customer['loyalty_level']);
    
    echo json_encode([
        'success' => true,
        'customer' => $customer,
        'benefits' => $benefits
    ]);
}

function getLoyaltyBenefits() {
    global $pdo;
    
    $level = $_GET['level'] ?? '';
    
    if (empty($level)) {
        echo json_encode(['success' => false, 'message' => 'Level required']);
        return;
    }
    
    $benefits = getLoyaltyBenefits($level);
    
    echo json_encode(['success' => true, 'benefits' => $benefits]);
}

function redeemPoints() {
    global $pdo;
    
    $customerId = $_POST['customer_id'] ?? 0;
    $points = $_POST['points'] ?? 0;
    $reason = $_POST['reason'] ?? '';
    
    if (!$customerId || !$points) {
        echo json_encode(['success' => false, 'message' => 'Customer ID and points required']);
        return;
    }
    
    try {
        $pdo->beginTransaction();
        
        // Check if customer has enough points
        $stmt = $pdo->prepare("SELECT loyalty_points FROM customers WHERE id = ?");
        $stmt->execute([$customerId]);
        $currentPoints = $stmt->fetchColumn();
        
        if ($currentPoints < $points) {
            throw new Exception('Insufficient points');
        }
        
        // Update customer points
        $stmt = $pdo->prepare("
            UPDATE customers 
            SET loyalty_points = loyalty_points - ?, 
                total_redeemed_points = total_redeemed_points + ?
            WHERE id = ?
        ");
        $stmt->execute([$points, $points, $customerId]);
        
        // Create redemption record
        $stmt = $pdo->prepare("
            INSERT INTO loyalty_redemptions (customer_id, points_redeemed, reason, created_at)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$customerId, $points, $reason]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Points redeemed successfully',
            'remaining_points' => $currentPoints - $points
        ]);
        
    } catch (Exception $e) {
        $pdo->rollback();
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
?>
