<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$categoryId = $_GET['category'] ?? 0;
$search = $_GET['search'] ?? '';

// Get menu items based on category or search
if ($categoryId) {
    $menuItems = getMenuItemsByCategory($categoryId);
    $category = $pdo->prepare("SELECT * FROM menu_categories WHERE id = ?");
    $category->execute([$categoryId]);
    $category = $category->fetch();
} elseif ($search) {
    $menuItems = getMenuItemsBySearch($search);
} else {
    $menuItems = getAllMenuItems();
}

$categories = getMenuCategories();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu - DAL TOKKI CAFE</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="assets/css/website.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="assets/images/logo.png" alt="DAL TOKKI CAFE" class="logo">
                <span>DAL TOKKI CAFE</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#about">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="menu.php">Menu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#testimonials">Testimonials</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#contact">Contact</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-light ms-2" href="index.php#app">Get Our App</a>
                    </li>
                    <li class="nav-item">
                        <button class="btn btn-outline-light ms-2" id="themeToggle">
                            <i class="fas fa-moon"></i>
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Menu Header -->
    <section class="py-5 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h1 class="display-4 fw-bold mb-4">Our Menu</h1>
                    <p class="lead">Discover our authentic Korean dishes and premium beverages</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Search and Filters -->
    <section class="py-3 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="Search menu items..." id="menuSearch" value="<?php echo htmlspecialchars($search); ?>">
                                <button class="btn btn-outline-primary" onclick="searchMenu()">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <select class="form-select" id="categoryFilter" onchange="filterByCategory()">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $categoryId == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Menu Items -->
    <section class="py-5">
        <div class="container">
            <?php if ($categoryId && isset($category)): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <h2 class="text-center"><?php echo htmlspecialchars($category['name']); ?></h2>
                    <p class="text-center text-muted"><?php echo htmlspecialchars($category['description']); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <?php if (empty($menuItems)): ?>
            <div class="row">
                <div class="col-12 text-center">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No menu items found.
                        <?php if ($search): ?>
                        Try a different search term.
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="row">
                <?php foreach ($menuItems as $item): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="menu-item-card">
                        <div class="menu-item-image">
                            <?php if ($item['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="img-fluid">
                            <?php else: ?>
                                <div class="no-image">
                                    <i class="fas fa-utensils"></i>
                                </div>
                            <?php endif; ?>
                            <?php if ($item['is_out_of_stock']): ?>
                            <div class="out-of-stock-overlay">
                                <span class="badge bg-danger">Out of Stock</span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="menu-item-content">
                            <h5 class="menu-item-name"><?php echo htmlspecialchars($item['name']); ?></h5>
                            <p class="menu-item-description"><?php echo htmlspecialchars($item['description']); ?></p>
                            <div class="menu-item-footer">
                                <span class="menu-item-price">₱<?php echo number_format($item['price'], 2); ?></span>
                                <button class="btn btn-primary btn-sm" 
                                        onclick="addToCart(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name']); ?>', <?php echo $item['price']; ?>)"
                                        <?php echo $item['is_out_of_stock'] ? 'disabled' : ''; ?>>
                                    <i class="fas fa-plus"></i> Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Cart Sidebar -->
    <div class="cart-sidebar" id="cartSidebar">
        <div class="cart-header">
            <h5>Shopping Cart</h5>
            <button class="btn-close" onclick="closeCart()"></button>
        </div>
        <div class="cart-body" id="cartBody">
            <!-- Cart items will be loaded here -->
        </div>
        <div class="cart-footer">
            <div class="cart-total">
                <strong>Total: ₱<span id="cartTotal">0.00</span></strong>
            </div>
            <button class="btn btn-primary w-100" onclick="checkout()">
                <i class="fas fa-shopping-cart"></i> Checkout
            </button>
        </div>
    </div>

    <!-- Cart Toggle Button -->
    <button class="cart-toggle" id="cartToggle" onclick="toggleCart()">
        <i class="fas fa-shopping-cart"></i>
        <span class="cart-count" id="cartCount">0</span>
    </button>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5>DAL TOKKI CAFE</h5>
                    <p>Experience authentic Korean culture with our premium blends and traditional recipes</p>
                </div>
                <div class="col-lg-4 mb-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white-50">Home</a></li>
                        <li><a href="index.php#about" class="text-white-50">About</a></li>
                        <li><a href="menu.php" class="text-white-50">Menu</a></li>
                        <li><a href="index.php#contact" class="text-white-50">Contact</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 mb-4">
                    <h5>Contact Info</h5>
                    <p><i class="fas fa-map-marker-alt me-2"></i>123 Korean Street, Manila, Philippines</p>
                    <p><i class="fas fa-phone me-2"></i>+63 123 456 7890</p>
                    <p><i class="fas fa-envelope me-2"></i>info@daltokki.com</p>
                </div>
            </div>
            <hr class="my-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0">&copy; 2024 DAL TOKKI CAFE. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-end">
                    <p class="mb-0">Made with <i class="fas fa-heart text-danger"></i> in the Philippines</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/website.js"></script>
    <script src="assets/js/menu.js"></script>
</body>
</html>

<style>
.menu-item-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    height: 100%;
}

.menu-item-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 15px rgba(0,0,0,0.15);
}

.menu-item-image {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.menu-item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.no-image {
    width: 100%;
    height: 100%;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
    font-size: 3rem;
}

.out-of-stock-overlay {
    position: absolute;
    top: 10px;
    right: 10px;
}

.menu-item-content {
    padding: 1.5rem;
}

.menu-item-name {
    font-weight: 600;
    color: #1a1a1a;
    margin-bottom: 0.5rem;
}

.menu-item-description {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 1rem;
}

.menu-item-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.menu-item-price {
    font-weight: 700;
    color: #1a1a1a;
    font-size: 1.2rem;
}

.cart-sidebar {
    position: fixed;
    top: 0;
    right: -400px;
    width: 400px;
    height: 100vh;
    background: white;
    box-shadow: -5px 0 15px rgba(0,0,0,0.1);
    z-index: 1050;
    transition: right 0.3s ease;
    display: flex;
    flex-direction: column;
}

.cart-sidebar.show {
    right: 0;
}

.cart-header {
    padding: 1.5rem;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.cart-body {
    flex: 1;
    padding: 1rem;
    overflow-y: auto;
}

.cart-footer {
    padding: 1.5rem;
    border-top: 1px solid #e9ecef;
}

.cart-toggle {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: #1a1a1a;
    color: white;
    border: none;
    font-size: 1.5rem;
    box-shadow: 0 4px 15px rgba(0,0,0,0.3);
    z-index: 1040;
    transition: all 0.3s ease;
}

.cart-toggle:hover {
    transform: scale(1.1);
    background: #333;
}

.cart-count {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #dc3545;
    color: white;
    border-radius: 50%;
    width: 25px;
    height: 25px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    font-weight: 600;
}

@media (max-width: 768px) {
    .cart-sidebar {
        width: 100%;
        right: -100%;
    }
    
    .cart-toggle {
        bottom: 20px;
        right: 20px;
        width: 50px;
        height: 50px;
        font-size: 1.2rem;
    }
}
</style>
