<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'place':
        placeOrder();
        break;
    case 'status':
        getOrderStatus();
        break;
    case 'track':
        trackOrder();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function placeOrder() {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Get form data
        $firstName = sanitizeInput($_POST['first_name'] ?? '');
        $lastName = sanitizeInput($_POST['last_name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $deliveryAddress = sanitizeInput($_POST['delivery_address'] ?? '');
        $paymentMethod = sanitizeInput($_POST['payment_method'] ?? 'cash');
        $items = json_decode($_POST['items'] ?? '[]', true);
        
        // Validate required fields
        if (empty($firstName) || empty($lastName) || empty($email) || empty($items)) {
            throw new Exception('Missing required fields');
        }
        
        if (!validateEmail($email)) {
            throw new Exception('Invalid email address');
        }
        
        // Check if customer exists, create if not
        $customer = getCustomerByEmail($email);
        if (!$customer) {
            $customerData = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => $phone,
                'address' => $deliveryAddress
            ];
            
            if (!createCustomer($customerData)) {
                throw new Exception('Failed to create customer');
            }
            
            $customer = getCustomerByEmail($email);
        }
        
        // Calculate total amount
        $totalAmount = 0;
        $orderItems = [];
        
        foreach ($items as $item) {
            $menuItem = $pdo->prepare("SELECT * FROM menu_items WHERE id = ? AND is_available = 1");
            $menuItem->execute([$item['id']]);
            $menuItem = $menuItem->fetch();
            
            if (!$menuItem) {
                throw new Exception('Menu item not found or unavailable: ' . $item['name']);
            }
            
            $itemTotal = $menuItem['price'] * $item['quantity'];
            $totalAmount += $itemTotal;
            
            $orderItems[] = [
                'menu_item_id' => $menuItem['id'],
                'quantity' => $item['quantity'],
                'unit_price' => $menuItem['price'],
                'total_price' => $itemTotal
            ];
        }
        
        // Create order
        $orderId = createOrder($customer['id'], $orderItems, $totalAmount, $deliveryAddress);
        
        if (!$orderId) {
            throw new Exception('Failed to create order');
        }
        
        // Update order with payment method
        $stmt = $pdo->prepare("UPDATE orders SET payment_method = ? WHERE id = ?");
        $stmt->execute([$paymentMethod, $orderId]);
        
        // Calculate loyalty points (1 point per peso)
        $pointsEarned = floor($totalAmount);
        updateCustomerLoyaltyPoints($customer['id'], $pointsEarned);
        updateCustomerLoyaltyLevel($customer['id']);
        
        // Update order with loyalty points
        $stmt = $pdo->prepare("UPDATE orders SET loyalty_points_earned = ? WHERE id = ?");
        $stmt->execute([$pointsEarned, $orderId]);
        
        // Create notification for admin
        sendNotification('new_order', 'New Order', "New order #{$orderId} has been placed", $orderId, $customer['id']);
        
        $pdo->commit();
        
        // Get order number
        $stmt = $pdo->prepare("SELECT order_number FROM orders WHERE id = ?");
        $stmt->execute([$orderId]);
        $orderNumber = $stmt->fetchColumn();
        
        echo json_encode([
            'success' => true,
            'message' => 'Order placed successfully',
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'total_amount' => $totalAmount,
            'points_earned' => $pointsEarned
        ]);
        
    } catch (Exception $e) {
        $pdo->rollback();
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

function getOrderStatus() {
    global $pdo;
    
    $orderNumber = $_GET['order_number'] ?? '';
    
    if (empty($orderNumber)) {
        echo json_encode(['success' => false, 'message' => 'Order number required']);
        return;
    }
    
    $stmt = $pdo->prepare("
        SELECT o.*, c.first_name, c.last_name, c.email
        FROM orders o
        LEFT JOIN customers c ON o.customer_id = c.id
        WHERE o.order_number = ?
    ");
    $stmt->execute([$orderNumber]);
    $order = $stmt->fetch();
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        return;
    }
    
    // Get order items
    $stmt = $pdo->prepare("
        SELECT oi.*, mi.name, mi.description
        FROM order_items oi
        JOIN menu_items mi ON oi.menu_item_id = mi.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order['id']]);
    $order['items'] = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'order' => $order]);
}

function trackOrder() {
    global $pdo;
    
    $orderNumber = $_GET['order_number'] ?? '';
    $email = $_GET['email'] ?? '';
    
    if (empty($orderNumber) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Order number and email required']);
        return;
    }
    
    $stmt = $pdo->prepare("
        SELECT o.*, c.first_name, c.last_name, c.email
        FROM orders o
        LEFT JOIN customers c ON o.customer_id = c.id
        WHERE o.order_number = ? AND c.email = ?
    ");
    $stmt->execute([$orderNumber, $email]);
    $order = $stmt->fetch();
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found or email does not match']);
        return;
    }
    
    // Get order items
    $stmt = $pdo->prepare("
        SELECT oi.*, mi.name, mi.description
        FROM order_items oi
        JOIN menu_items mi ON oi.menu_item_id = mi.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order['id']]);
    $order['items'] = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'order' => $order]);
}
?>
