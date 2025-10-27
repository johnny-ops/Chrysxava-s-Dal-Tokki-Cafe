<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'categories':
        getCategories();
        break;
    case 'items':
        getMenuItems();
        break;
    case 'search':
        searchMenuItems();
        break;
    default:
        getAllMenuItems();
}

function getCategories() {
    global $pdo;
    
    $stmt = $pdo->query("SELECT * FROM menu_categories WHERE is_active = 1 ORDER BY display_order");
    $categories = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'categories' => $categories]);
}

function getMenuItems() {
    global $pdo;
    
    $categoryId = $_GET['category_id'] ?? 0;
    
    if ($categoryId) {
        $stmt = $pdo->prepare("
            SELECT mi.*, mc.name as category_name
            FROM menu_items mi
            JOIN menu_categories mc ON mi.category_id = mc.id
            WHERE mi.category_id = ? AND mi.is_available = 1
            ORDER BY mi.display_order
        ");
        $stmt->execute([$categoryId]);
    } else {
        $stmt = $pdo->query("
            SELECT mi.*, mc.name as category_name
            FROM menu_items mi
            JOIN menu_categories mc ON mi.category_id = mc.id
            WHERE mi.is_available = 1
            ORDER BY mc.display_order, mi.display_order
        ");
    }
    
    $items = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'items' => $items]);
}

function searchMenuItems() {
    global $pdo;
    
    $searchTerm = $_GET['search'] ?? '';
    
    if (empty($searchTerm)) {
        echo json_encode(['success' => false, 'message' => 'Search term required']);
        return;
    }
    
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
    $items = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'items' => $items]);
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
    $items = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'items' => $items]);
}
?>
