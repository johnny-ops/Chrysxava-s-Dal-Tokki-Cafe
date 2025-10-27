<?php
// Website functions

function getSettings() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $settings = [];
    
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    return $settings;
}

function getMenuCategories() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT * FROM menu_categories WHERE is_active = 1 ORDER BY display_order");
    return $stmt->fetchAll();
}

function getMenuItemsByCategory($categoryId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT * FROM menu_items 
        WHERE category_id = ? AND is_available = 1 
        ORDER BY display_order
    ");
    $stmt->execute([$categoryId]);
    return $stmt->fetchAll();
}

function getFeaturedMenuItems() {
    global $pdo;
    
    $stmt = $pdo->query("
        SELECT mi.*, mc.name as category_name
        FROM menu_items mi
        JOIN menu_categories mc ON mi.category_id = mc.id
        WHERE mi.is_available = 1
        ORDER BY mi.display_order
        LIMIT 6
    ");
    return $stmt->fetchAll();
}

function getAllMenuItems() {
    global $pdo;
    
    $stmt = $pdo->query("
        SELECT mi.*, mc.name as category_name
        FROM menu_items mi
        JOIN menu_categories mc ON mi.category_id = mc.id
        WHERE mi.is_available = 1
        ORDER BY mc.display_order, mi.display_order
    ");
    return $stmt->fetchAll();
}

function getMenuItemsBySearch($searchTerm) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT mi.*, mc.name as category_name
        FROM menu_items mi
        JOIN menu_categories mc ON mi.category_id = mc.id
        WHERE mi.is_available = 1 
        AND (mi.name LIKE ? OR mi.description LIKE ?)
        ORDER BY mc.display_order, mi.display_order
    ");
    $searchPattern = "%$searchTerm%";
    $stmt->execute([$searchPattern, $searchPattern]);
    return $stmt->fetchAll();
}

function createCustomer($data) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        INSERT INTO customers (first_name, last_name, email, phone, address, loyalty_points, loyalty_level)
        VALUES (?, ?, ?, ?, ?, 0, 'bronze')
    ");
    
    return $stmt->execute([
        $data['first_name'],
        $data['last_name'],
        $data['email'],
        $data['phone'],
        $data['address']
    ]);
}

function getCustomerByEmail($email) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch();
}

function createOrder($customerId, $items, $totalAmount, $deliveryAddress = null) {
    global $pdo;
    
    try {
        $pdo->beginTransaction();
        
        // Generate order number
        $orderNumber = 'DT' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        // Create order
        $stmt = $pdo->prepare("
            INSERT INTO orders (customer_id, order_number, total_amount, delivery_address, status)
            VALUES (?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([$customerId, $orderNumber, $totalAmount, $deliveryAddress]);
        $orderId = $pdo->lastInsertId();
        
        // Add order items
        foreach ($items as $item) {
            $stmt = $pdo->prepare("
                INSERT INTO order_items (order_id, menu_item_id, quantity, unit_price, total_price)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $orderId,
                $item['menu_item_id'],
                $item['quantity'],
                $item['unit_price'],
                $item['total_price']
            ]);
        }
        
        $pdo->commit();
        return $orderId;
        
    } catch (Exception $e) {
        $pdo->rollback();
        throw $e;
    }
}

function getOrderById($orderId) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT o.*, c.first_name, c.last_name, c.email, c.phone
        FROM orders o
        LEFT JOIN customers c ON o.customer_id = c.id
        WHERE o.id = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    
    if ($order) {
        // Get order items
        $stmt = $pdo->prepare("
            SELECT oi.*, mi.name, mi.description
            FROM order_items oi
            JOIN menu_items mi ON oi.menu_item_id = mi.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$orderId]);
        $order['items'] = $stmt->fetchAll();
    }
    
    return $order;
}

function updateOrderStatus($orderId, $status) {
    global $pdo;
    
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    return $stmt->execute([$status, $orderId]);
}

function getLoyaltyBenefits($level) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        SELECT * FROM loyalty_benefits 
        WHERE level = ? AND is_active = 1 
        ORDER BY points_required
    ");
    $stmt->execute([$level]);
    return $stmt->fetchAll();
}

function updateCustomerLoyaltyPoints($customerId, $points) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        UPDATE customers 
        SET loyalty_points = loyalty_points + ?, 
            total_earned_points = total_earned_points + ?
        WHERE id = ?
    ");
    return $stmt->execute([$points, $points, $customerId]);
}

function getCustomerLoyaltyLevel($points) {
    if ($points >= 5000) return 'gold';
    if ($points >= 2000) return 'silver';
    return 'bronze';
}

function updateCustomerLoyaltyLevel($customerId) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT loyalty_points FROM customers WHERE id = ?");
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch();
    
    if ($customer) {
        $newLevel = getCustomerLoyaltyLevel($customer['loyalty_points']);
        
        $stmt = $pdo->prepare("UPDATE customers SET loyalty_level = ? WHERE id = ?");
        $stmt->execute([$newLevel, $customerId]);
    }
}

function sendNotification($type, $title, $message, $orderId = null, $customerId = null) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        INSERT INTO notifications (type, title, message, order_id, customer_id)
        VALUES (?, ?, ?, ?, ?)
    ");
    return $stmt->execute([$type, $title, $message, $orderId, $customerId]);
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function generateOrderNumber() {
    return 'DT' . date('Ymd') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}
?>
