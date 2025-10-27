// DAL TOKKI CAFE Website JavaScript

let isDarkMode = false;

// Initialize website
document.addEventListener('DOMContentLoaded', function() {
    initializeWebsite();
    setupEventListeners();
    loadTheme();
});

function initializeWebsite() {
    // Smooth scrolling for navigation links
    const navLinks = document.querySelectorAll('a[href^="#"]');
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);
            
            if (targetElement) {
                const offsetTop = targetElement.offsetTop - 80; // Account for fixed navbar
                window.scrollTo({
                    top: offsetTop,
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Add fade-in animation to elements
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
            entry.target.classList.add('fade-in-up');
        }
        });
    }, observerOptions);
    
    // Observe all sections
    document.querySelectorAll('section').forEach(section => {
        observer.observe(section);
    });
}

function setupEventListeners() {
    // Theme toggle
    const themeToggle = document.getElementById('themeToggle');
    if (themeToggle) {
        themeToggle.addEventListener('click', toggleTheme);
    }
    
    // Contact form
    const contactForm = document.getElementById('contactForm');
    if (contactForm) {
        contactForm.addEventListener('submit', handleContactForm);
    }
    
    // Navbar scroll effect
    window.addEventListener('scroll', handleNavbarScroll);
    
    // Mobile menu close on link click
    const navbarToggler = document.querySelector('.navbar-toggler');
    const navbarCollapse = document.querySelector('.navbar-collapse');
    const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
    
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (navbarCollapse.classList.contains('show')) {
                navbarToggler.click();
            }
        });
    });
}

function handleNavbarScroll() {
    const navbar = document.querySelector('.navbar');
    const scrollTop = window.pageYOffset;
    
    if (scrollTop > 100) {
        navbar.style.background = 'rgba(26, 26, 26, 0.98)';
        navbar.style.boxShadow = '0 2px 20px rgba(0,0,0,0.1)';
    } else {
        navbar.style.background = 'rgba(26, 26, 26, 0.95)';
        navbar.style.boxShadow = 'none';
    }
}

function toggleTheme() {
    isDarkMode = !isDarkMode;
    document.body.classList.toggle('dark-mode', isDarkMode);
    
    const themeIcon = document.querySelector('#themeToggle i');
    if (isDarkMode) {
        themeIcon.className = 'fas fa-sun';
    } else {
        themeIcon.className = 'fas fa-moon';
    }
    
    // Save theme preference
    localStorage.setItem('darkMode', isDarkMode);
}

function loadTheme() {
    const savedTheme = localStorage.getItem('darkMode');
    if (savedTheme === 'true') {
        toggleTheme();
    }
}

function handleContactForm(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);
    
    // Show loading state
    const submitBtn = e.target.querySelector('button[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Sending...';
    submitBtn.disabled = true;
    
    // Simulate form submission
    setTimeout(() => {
        showAlert('Thank you for your message! We will get back to you soon.', 'success');
        e.target.reset();
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
    }, 2000);
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

// Search functionality
function initializeSearch() {
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase();
            const menuItems = document.querySelectorAll('.menu-item');
            
            menuItems.forEach(item => {
                const itemName = item.querySelector('h6').textContent.toLowerCase();
                const itemDescription = item.querySelector('.item-description').textContent.toLowerCase();
                
                if (itemName.includes(query) || itemDescription.includes(query)) {
                    item.closest('.menu-category-card').style.display = 'block';
                } else {
                    item.closest('.menu-category-card').style.display = 'none';
                }
            });
        });
    }
}

// Order functionality
function addToCart(itemId, itemName, itemPrice) {
    let cart = JSON.parse(localStorage.getItem('cart') || '[]');
    
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

function updateCartDisplay() {
    const cart = JSON.parse(localStorage.getItem('cart') || '[]');
    const cartCount = document.getElementById('cartCount');
    const cartTotal = document.getElementById('cartTotal');
    
    if (cartCount) {
        const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
        cartCount.textContent = totalItems;
        cartCount.style.display = totalItems > 0 ? 'inline' : 'none';
    }
    
    if (cartTotal) {
        const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        cartTotal.textContent = `₱${total.toFixed(2)}`;
    }
}

function removeFromCart(itemId) {
    let cart = JSON.parse(localStorage.getItem('cart') || '[]');
    cart = cart.filter(item => item.id !== itemId);
    localStorage.setItem('cart', JSON.stringify(cart));
    updateCartDisplay();
}

function clearCart() {
    localStorage.removeItem('cart');
    updateCartDisplay();
}

// Loyalty program
function checkLoyaltyStatus(customerEmail) {
    fetch('api/loyalty.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'check_status',
            email: customerEmail
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showLoyaltyModal(data.customer);
        } else {
            showAlert('Customer not found in loyalty program', 'warning');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error checking loyalty status', 'danger');
    });
}

function showLoyaltyModal(customer) {
    const modal = createModal('Loyalty Status', `
        <div class="row">
            <div class="col-md-6">
                <h6>Customer Information</h6>
                <p><strong>Name:</strong> ${customer.first_name} ${customer.last_name}</p>
                <p><strong>Email:</strong> ${customer.email}</p>
                <p><strong>Level:</strong> <span class="badge bg-${getLevelColor(customer.loyalty_level)}">${customer.loyalty_level.toUpperCase()}</span></p>
            </div>
            <div class="col-md-6">
                <h6>Loyalty Points</h6>
                <p><strong>Current Points:</strong> ${customer.loyalty_points}</p>
                <p><strong>Total Earned:</strong> ${customer.total_earned_points}</p>
                <p><strong>Total Redeemed:</strong> ${customer.total_redeemed_points}</p>
            </div>
        </div>
        <div class="mt-3">
            <h6>Available Benefits</h6>
            <div id="loyaltyBenefits">
                <!-- Benefits will be loaded here -->
            </div>
        </div>
    `);
    
    document.body.appendChild(modal);
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
}

function getLevelColor(level) {
    switch(level) {
        case 'gold': return 'warning';
        case 'silver': return 'secondary';
        case 'bronze': return 'danger';
        default: return 'primary';
    }
}

// Utility functions
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

// API functions
function apiRequest(url, data = null, method = 'GET') {
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
        }
    };
    
    if (data) {
        options.body = JSON.stringify(data);
    }
    
    return fetch(url, options)
        .then(response => response.json())
        .catch(error => {
            console.error('API Error:', error);
            return { success: false, message: 'Network error' };
        });
}

// Initialize cart display on page load
document.addEventListener('DOMContentLoaded', function() {
    updateCartDisplay();
});

// Smooth reveal animation
function reveal() {
    const reveals = document.querySelectorAll('.reveal');
    
    reveals.forEach(element => {
        const windowHeight = window.innerHeight;
        const elementTop = element.getBoundingClientRect().top;
        const elementVisible = 150;
        
        if (elementTop < windowHeight - elementVisible) {
            element.classList.add('active');
        }
    });
}

window.addEventListener('scroll', reveal);

// Initialize reveal on load
reveal();
