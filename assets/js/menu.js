// Menu page JavaScript

let cart = JSON.parse(localStorage.getItem('cart') || '[]');

// Initialize menu page
document.addEventListener('DOMContentLoaded', function() {
    initializeMenu();
    updateCartDisplay();
    setupEventListeners();
    initCategoryStrip();
});

function initializeMenu() {
    // Initialize search functionality
    const searchInput = document.getElementById('menuSearch');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchMenu();
            }
        });
    }
    
    // Initialize category filter
    const categoryFilter = document.getElementById('categoryFilter');
    if (categoryFilter) {
        categoryFilter.addEventListener('change', filterByCategory);
    }
}

// Build slidable category strip from existing select options
function initCategoryStrip() {
    const select = document.getElementById('categoryFilter');
    if (!select) return;

    // Create strip container
    const stripWrapper = document.createElement('div');
    stripWrapper.className = 'category-strip-wrapper';
    stripWrapper.innerHTML = `
        <button class="strip-nav left" aria-label="scroll left"><i class="fas fa-chevron-left"></i></button>
        <div class="category-strip" id="categoryStrip"></div>
        <button class="strip-nav right" aria-label="scroll right"><i class="fas fa-chevron-right"></i></button>
    `;

    // Insert before the select
    const parentRow = select.closest('.row') || select.parentElement;
    if (parentRow && parentRow.parentElement) {
        parentRow.parentElement.insertBefore(stripWrapper, parentRow);
    }

    const strip = stripWrapper.querySelector('#categoryStrip');
    // Add "All" chip
    const allChip = document.createElement('button');
    allChip.className = 'category-chip' + (select.value === '' ? ' active' : '');
    allChip.textContent = 'All';
    allChip.onclick = () => { window.location.href = 'menu.php'; };
    strip.appendChild(allChip);

    // Create chips from options
    Array.from(select.options).forEach(opt => {
        if (!opt.value) return;
        const chip = document.createElement('button');
        chip.className = 'category-chip' + (select.value === opt.value ? ' active' : '');
        chip.textContent = opt.textContent;
        chip.onclick = () => { window.location.href = `menu.php?category=${opt.value}`; };
        strip.appendChild(chip);
    });

    // Scroll handlers
    const leftBtn = stripWrapper.querySelector('.strip-nav.left');
    const rightBtn = stripWrapper.querySelector('.strip-nav.right');
    leftBtn.onclick = () => strip.scrollBy({ left: -200, behavior: 'smooth' });
    rightBtn.onclick = () => strip.scrollBy({ left: 200, behavior: 'smooth' });

    // Wheel scroll support
    strip.addEventListener('wheel', (e) => {
        if (Math.abs(e.deltaY) > Math.abs(e.deltaX)) {
            e.preventDefault();
            strip.scrollLeft += e.deltaY;
        }
    }, { passive: false });
}

function setupEventListeners() {
    // Theme toggle
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', toggleTheme);
    }
}

function searchMenu() {
    const searchTerm = document.getElementById('menuSearch').value.trim();
    if (searchTerm) {
        window.location.href = `menu.php?search=${encodeURIComponent(searchTerm)}`;
    } else {
        window.location.href = 'menu.php';
    }
}

function filterByCategory(categoryId = null) {
    if (categoryId === null) {
        categoryId = document.getElementById('categoryFilter').value;
    }
    
    if (categoryId) {
        window.location.href = `menu.php?category=${categoryId}`;
    } else {
        window.location.href = 'menu.php';
    }
}

function scrollCategories(direction) {
    const strip = document.getElementById('categoryStrip');
    if (strip) {
        const scrollAmount = 200;
        if (direction === 'left') {
            strip.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
        } else {
            strip.scrollBy({ left: scrollAmount, behavior: 'smooth' });
        }
    }
}

function addToCart(itemId, itemName, itemPrice) {
    const existingItem = cart.find(item => item.id === itemId);
    
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cart.push({
            id: itemId,
            name: itemName,
            price: parseFloat(itemPrice),
            quantity: 1
        });
    }
    
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartDisplay();
    showAlert(`${itemName} added to cart!`, 'success');
}

function removeFromCart(itemId) {
    cart = cart.filter(item => item.id !== itemId);
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartDisplay();
    showAlert('Item removed from cart', 'info');
}

function updateCartQuantity(itemId, quantity) {
    const item = cart.find(item => item.id === itemId);
    if (item) {
        if (quantity <= 0) {
            removeFromCart(itemId);
        } else {
            item.quantity = quantity;
            localStorage.setItem('cart', JSON.stringify(cart));
            updateCartDisplay();
        }
    }
}

function updateCartDisplay() {
    const cartCount = document.getElementById('cartCount');
    const cartTotal = document.getElementById('cartTotal');
    const cartBody = document.getElementById('cartBody');
    
    // Update cart count
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    if (cartCount) {
        cartCount.textContent = totalItems;
        cartCount.style.display = totalItems > 0 ? 'flex' : 'none';
    }
    
    // Update cart total
    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    if (cartTotal) {
        cartTotal.textContent = total.toFixed(2);
    }
    
    // Update cart body
    if (cartBody) {
        if (cart.length === 0) {
            cartBody.innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Your cart is empty</p>
                </div>
            `;
        } else {
            cartBody.innerHTML = cart.map(item => `
                <div class="cart-item">
                    <div class="cart-item-info">
                        <h6 class="cart-item-name">${item.name}</h6>
                        <p class="cart-item-price">₱${item.price.toFixed(2)}</p>
                    </div>
                    <div class="cart-item-controls">
                        <div class="quantity-controls">
                            <button class="btn btn-sm btn-outline-secondary" onclick="updateCartQuantity(${item.id}, ${item.quantity - 1})">
                                <i class="fas fa-minus"></i>
                            </button>
                            <span class="quantity">${item.quantity}</span>
                            <button class="btn btn-sm btn-outline-secondary" onclick="updateCartQuantity(${item.id}, ${item.quantity + 1})">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                        <button class="btn btn-sm btn-outline-danger" onclick="removeFromCart(${item.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `).join('');
        }
    }
}

function toggleCart() {
    const cartSidebar = document.getElementById('cartSidebar');
    cartSidebar.classList.toggle('show');
}

function closeCart() {
    const cartSidebar = document.getElementById('cartSidebar');
    cartSidebar.classList.remove('show');
}

function checkout() {
    if (cart.length === 0) {
        showAlert('Your cart is empty!', 'warning');
        return;
    }
    
    // Show checkout modal
    showCheckoutModal();
}

function showCheckoutModal() {
    const modal = createModal('Checkout', `
        <form id="checkoutForm">
            <div class="row">
                <div class="col-md-6">
                    <h6>Customer Information</h6>
                    <div class="mb-3">
                        <input type="text" class="form-control" name="first_name" placeholder="First Name" required>
                    </div>
                    <div class="mb-3">
                        <input type="text" class="form-control" name="last_name" placeholder="Last Name" required>
                    </div>
                    <div class="mb-3">
                        <input type="email" class="form-control" name="email" placeholder="Email" required>
                    </div>
                    <div class="mb-3">
                        <input type="tel" class="form-control" name="phone" placeholder="Phone Number">
                    </div>
                </div>
                <div class="col-md-6">
                    <h6>Order Summary</h6>
                    <div class="order-summary">
                        ${cart.map(item => `
                            <div class="d-flex justify-content-between mb-2">
                                <span>${item.name} x ${item.quantity}</span>
                                <span>₱${(item.price * item.quantity).toFixed(2)}</span>
                            </div>
                        `).join('')}
                        <hr>
                        <div class="d-flex justify-content-between">
                            <strong>Total:</strong>
                            <strong>₱${cart.reduce((sum, item) => sum + (item.price * item.quantity), 0).toFixed(2)}</strong>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label for="deliveryAddress" class="form-label">Delivery Address</label>
                <textarea class="form-control" id="deliveryAddress" name="delivery_address" rows="3" placeholder="Enter your delivery address"></textarea>
            </div>
            <div class="mb-3">
                <label for="paymentMethod" class="form-label">Payment Method</label>
                <select class="form-select" id="paymentMethod" name="payment_method" required>
                    <option value="">Select Payment Method</option>
                    <option value="cash">Cash on Delivery</option>
                    <option value="card">Credit/Debit Card</option>
                    <option value="online">Online Payment</option>
                </select>
            </div>
        </form>
    `, [
        { text: 'Cancel', class: 'btn-secondary', dismiss: true },
        { text: 'Place Order', class: 'btn-primary', action: 'placeOrder' }
    ]);
    
    document.body.appendChild(modal);
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
}

function placeOrder() {
    const form = document.getElementById('checkoutForm');
    const formData = new FormData(form);
    
    // Add cart items to form data
    formData.append('items', JSON.stringify(cart));
    
    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Processing...';
    submitBtn.disabled = true;
    
    // Submit order
    fetch('api/orders.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Order placed successfully! Order #' + data.order_number, 'success');
            // Clear cart
            cart = [];
            localStorage.removeItem('cart');
            updateCartDisplay();
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.querySelector('.modal'));
            modal.hide();
        } else {
            showAlert('Error placing order: ' + data.message, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error placing order', 'danger');
    })
    .finally(() => {
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    });
}

function showAlert(message, type = 'info') {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    alert.style.top = '20px';
    alert.style.right = '20px';
    alert.style.zIndex = '9999';
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alert);
    
    setTimeout(() => {
        alert.remove();
    }, 5000);
}

function createModal(title, body, buttons = []) {
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.innerHTML = `
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">${title}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    ${body}
                </div>
                <div class="modal-footer">
                    ${buttons.map(btn => `
                        <button type="button" class="btn ${btn.class}" 
                                ${btn.dismiss ? 'data-bs-dismiss="modal"' : ''}
                                ${btn.action ? `onclick="${btn.action}()"` : ''}>
                            ${btn.text}
                        </button>
                    `).join('')}
                </div>
            </div>
        </div>
    `;
    return modal;
}

// Load theme preference
function loadTheme() {
    const savedTheme = localStorage.getItem('darkMode');
    if (savedTheme === 'true') {
        toggleTheme();
    }
}

function toggleTheme() {
    const isDarkMode = document.body.classList.contains('dark-mode');
    document.body.classList.toggle('dark-mode', !isDarkMode);
    
    const themeIcon = document.querySelector('#themeToggle i');
    if (!isDarkMode) {
        themeIcon.className = 'fas fa-sun';
    } else {
        themeIcon.className = 'fas fa-moon';
    }
    
    localStorage.setItem('darkMode', !isDarkMode);
}

// Initialize theme on load
loadTheme();
