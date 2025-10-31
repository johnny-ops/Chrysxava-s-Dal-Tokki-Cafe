<?php
// Database functions for DAL TOKKI CAFE

// Database connection
function getDatabaseConnection() {
    $host = 'localhost';
    $dbname = 'daltokki_cafe';
    $username = 'root';
    $password = '';
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch(PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Get all menu categories
function getMenuCategories() {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare("SELECT * FROM menu_categories ORDER BY name ASC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get all menu items
function getAllMenuItems() {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare("
        SELECT mi.*, mc.name as category_name 
        FROM menu_items mi 
        LEFT JOIN menu_categories mc ON mi.category_id = mc.id 
        WHERE mi.is_active = 1 
        ORDER BY mc.name ASC, mi.name ASC
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get menu items by category
function getMenuItemsByCategory($categoryId) {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare("
        SELECT mi.*, mc.name as category_name 
        FROM menu_items mi 
        LEFT JOIN menu_categories mc ON mi.category_id = mc.id 
        WHERE mi.category_id = ? AND mi.is_active = 1 
        ORDER BY mi.name ASC
    ");
    $stmt->execute([$categoryId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get menu items by search term
function getMenuItemsBySearch($searchTerm) {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare("
        SELECT mi.*, mc.name as category_name 
        FROM menu_items mi 
        LEFT JOIN menu_categories mc ON mi.category_id = mc.id 
        WHERE (mi.name LIKE ? OR mi.description LIKE ?) AND mi.is_active = 1 
        ORDER BY mc.name ASC, mi.name ASC
    ");
    $searchPattern = "%$searchTerm%";
    $stmt->execute([$searchPattern, $searchPattern]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get menu item by ID
function getMenuItemById($itemId) {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare("
        SELECT mi.*, mc.name as category_name 
        FROM menu_items mi 
        LEFT JOIN menu_categories mc ON mi.category_id = mc.id 
        WHERE mi.id = ? AND mi.is_active = 1
    ");
    $stmt->execute([$itemId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Add menu item
function addMenuItem($data) {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare("
        INSERT INTO menu_items (name, description, price, category_id, image_url, is_active, created_at) 
        VALUES (?, ?, ?, ?, ?, 1, NOW())
    ");
    return $stmt->execute([
        $data['name'],
        $data['description'],
        $data['price'],
        $data['category_id'],
        $data['image_url'] ?? null
    ]);
}

// Update menu item
function updateMenuItem($itemId, $data) {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare("
        UPDATE menu_items 
        SET name = ?, description = ?, price = ?, category_id = ?, image_url = ?, updated_at = NOW()
        WHERE id = ?
    ");
    return $stmt->execute([
        $data['name'],
        $data['description'],
        $data['price'],
        $data['category_id'],
        $data['image_url'] ?? null,
        $itemId
    ]);
}

// Delete menu item (soft delete)
function deleteMenuItem($itemId) {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare("UPDATE menu_items SET is_active = 0, updated_at = NOW() WHERE id = ?");
    return $stmt->execute([$itemId]);
}

// Get category by ID
function getCategoryById($categoryId) {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare("SELECT * FROM menu_categories WHERE id = ?");
    $stmt->execute([$categoryId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Add category
function addCategory($name, $description = '') {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare("INSERT INTO menu_categories (name, description, created_at) VALUES (?, ?, NOW())");
    return $stmt->execute([$name, $description]);
}

// Update category
function updateCategory($categoryId, $name, $description = '') {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare("UPDATE menu_categories SET name = ?, description = ?, updated_at = NOW() WHERE id = ?");
    return $stmt->execute([$name, $description, $categoryId]);
}

// Delete category
function deleteCategory($categoryId) {
    $pdo = getDatabaseConnection();
    $stmt = $pdo->prepare("DELETE FROM menu_categories WHERE id = ?");
    return $stmt->execute([$categoryId]);
}

// Create database tables if they don't exist
function createTables() {
    $pdo = getDatabaseConnection();
    
    // Create menu_categories table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS menu_categories (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    
    // Create menu_items table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS menu_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(200) NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL,
            category_id INT,
            image_url VARCHAR(500),
            is_active BOOLEAN DEFAULT TRUE,
            is_out_of_stock BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES menu_categories(id) ON DELETE SET NULL
        )
    ");
    
    // Insert sample categories if they don't exist
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM menu_categories");
    $stmt->execute();
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        $sampleCategories = [
            ['Korean Rice Meals', 'Authentic Korean rice-based dishes'],
            ['Sandwich & Burger', 'Korean-style sandwiches and burgers'],
            ['Korean Party Trays', 'Perfect for sharing with friends and family'],
            ['Coffee', 'Premium coffee blends and specialty drinks'],
            ['Non-Coffee', 'Refreshing non-coffee beverages'],
            ['Pinoy Rice Meal', 'Classic Filipino rice meals'],
            ['Salad Rolls', 'Fresh and healthy salad rolls']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO menu_categories (name, description) VALUES (?, ?)");
        foreach ($sampleCategories as $category) {
            $stmt->execute($category);
        }
    }
    
    // Insert sample menu items if they don't exist
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM menu_items");
    $stmt->execute();
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        $sampleItems = [
            ['KM2 Chicken Pops w/ Rice', 'Crispy chicken pops served with rice', 159.00, 1, ''],
            ['KM3 Bulgogi Double Burger Steak', 'Double bulgogi burger steak with rice', 169.00, 1, ''],
            ['KM4 Bilbao', 'Traditional Korean bilbao dish', 189.00, 1, ''],
            ['SB4 Bulgogi Burger', 'Korean-style bulgogi burger', 139.00, 2, ''],
            ['SB5 Crispy Chicken Sandwich', 'Crispy fried chicken sandwich', 159.00, 2, ''],
            ['SB6 Spicy Crispy Chicken Sandwich', 'Spicy fried chicken sandwich', 169.00, 2, ''],
            ['KF1 Samgyeop Rolls', 'Perfect for sharing', 629.00, 3, ''],
            ['KF2 Kimbap', 'Korean rice rolls', 489.00, 3, ''],
            ['KF4 Chicken Pops', 'Party-sized chicken pops', 589.00, 3, ''],
            ['C4 Spanish Latte 12oz', 'Premium Spanish latte', 105.00, 4, ''],
            ['C7 Café Mocha HOT 12oz', 'Hot café mocha', 105.00, 4, ''],
            ['C13 Biscoff Latté ICED 22oz', 'Iced biscoff latte', 135.00, 4, ''],
            ['NC3 Milky Strawberry 12oz ICED', 'Refreshing strawberry drink', 110.00, 5, ''],
            ['NC4 Milky Blueberry 12oz ICED', 'Refreshing blueberry drink', 110.00, 5, ''],
            ['NC8 Nutella Ala Mode 12oz ICED', 'Iced nutella ala mode', 135.00, 5, ''],
            ['PR2 Cheesy Hungarian Sausage w/ Rice', 'Cheesy Hungarian with rice', 89.00, 6, ''],
            ['PR5 Lechon Kawali Sinigang', 'Crispy pork belly in sinigang', 179.00, 6, ''],
            ['PR6 Lechon Kawali Kare-Kare', 'Crispy pork belly in kare-kare', 179.00, 6, ''],
            ['R1 Samgyeop Roll', 'Korean pork belly roll', 129.00, 7, ''],
            ['R3 Bulgogi Kimbap', 'Bulgogi rice roll', 169.00, 7, ''],
            ['S1 Kani Salad', 'Fresh kani salad', 169.00, 7, '']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO menu_items (name, description, price, category_id, image_url) VALUES (?, ?, ?, ?, ?)");
        foreach ($sampleItems as $item) {
            $stmt->execute($item);
        }
    }
}

// Initialize database
createTables();
?>
