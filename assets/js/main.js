/**
 * Bake & Take - Main JavaScript
 */

document.addEventListener('DOMContentLoaded', function () {

    // Initialize cart from localStorage
    initCart();

    // Navbar scroll effect
    initNavbarScroll();

    // Initialize product interactions
    initProductCards();

    // Initialize quantity controls
    initQuantityControls();

    // Initialize animations
    initScrollAnimations();

});

/**
 * Cart Management - Database-backed only (Requires Login)
 */
let cart = [];
let isLoggedIn = false;

async function initCart() {
    try {
        const response = await fetch('includes/cart_api.php?action=get');
        const data = await response.json();

        if (data.success) {
            isLoggedIn = data.loggedIn;
            if (data.loggedIn && data.items) {
                cart = data.items;
            } else {
                cart = [];
            }
        }
    } catch (error) {
        console.error('Error loading cart:', error);
        cart = [];
    }
    updateCartUI();
}

async function addToCart(productId, productName, productPrice, productImage) {
    if (!isLoggedIn) {
        showNotification('Please login to add items to cart', 'info');
        setTimeout(() => {
            window.location.href = 'index.php?page=login';
        }, 1000);
        return;
    }

    try {
        const formData = new FormData();
        formData.append('action', 'add');
        formData.append('product_id', productId);
        formData.append('quantity', 1);

        const response = await fetch('includes/cart_api.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.requireLogin) {
            showNotification('Please login to add items to cart', 'info');
            window.location.href = 'index.php?page=login';
            return;
        }

        if (data.success) {
            // Refresh cart from server
            await initCart();
            showNotification(`${productName} added to cart!`, 'success');
            animateCartIcon();
        } else {
            showNotification(data.message || 'Error adding to cart', 'error');
        }
    } catch (error) {
        console.error('Error adding to cart:', error);
        showNotification('Error adding to cart', 'error');
    }
}

async function removeFromCart(productId) {
    if (!isLoggedIn) return;

    try {
        const formData = new FormData();
        formData.append('action', 'remove');
        formData.append('product_id', productId);

        const response = await fetch('includes/cart_api.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.success) {
            await initCart();
        }
    } catch (error) {
        console.error('Error removing from cart:', error);
    }

    if (typeof renderCartPage === 'function') {
        renderCartPage();
    }
}

async function updateQuantity(productId, newQuantity) {
    if (!isLoggedIn) return;

    if (newQuantity <= 0) {
        await removeFromCart(productId);
        return;
    }

    try {
        const formData = new FormData();
        formData.append('action', 'update');
        formData.append('product_id', productId);
        formData.append('quantity', newQuantity);

        const response = await fetch('includes/cart_api.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.success) {
            await initCart();
        }
    } catch (error) {
        console.error('Error updating quantity:', error);
    }
}

// Helper to remove local cart data from previous guest sessions
function clearLocalCart() {
    localStorage.removeItem('bakeAndTakeCart');
}

function getCart() {
    return cart;
}

function getCartTotal() {
    return cart.reduce((total, item) => total + (item.price * item.quantity), 0);
}

function getCartItemCount() {
    return cart.reduce((count, item) => count + item.quantity, 0);
}

async function clearCart() {
    if (isLoggedIn) {
        try {
            const formData = new FormData();
            formData.append('action', 'clear');

            await fetch('includes/cart_api.php', {
                method: 'POST',
                body: formData
            });
        } catch (error) {
            console.error('Error clearing cart:', error);
        }
    }

    cart = [];
    updateCartUI();
}

function updateCartUI() {
    const cartCountElements = document.querySelectorAll('#cartCount, .cart-count');
    const count = getCartItemCount();

    cartCountElements.forEach(el => {
        el.textContent = count;
        el.style.display = count > 0 ? 'flex' : 'none';
    });

    // Clear legacy localStorage data if it exists
    if (localStorage.getItem('bakeAndTakeCart')) {
        clearLocalCart();
    }
}

function animateCartIcon() {
    const cartBtn = document.querySelector('#cartBtn');
    if (cartBtn) {
        cartBtn.classList.add('cart-bounce');
        setTimeout(() => cartBtn.classList.remove('cart-bounce'), 500);
    }
}

/**
 * Navbar Scroll Effect
 */
function initNavbarScroll() {
    const navbar = document.getElementById('mainNav');

    function handleScroll() {
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    }

    window.addEventListener('scroll', handleScroll);
    handleScroll();
}

/**
 * Product Card Interactions
 */
function initProductCards() {
    document.querySelectorAll('.btn-add-cart').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();

            const card = this.closest('.product-card');
            const id = parseInt(card.dataset.productId);
            const name = card.dataset.productName;
            const price = parseFloat(card.dataset.productPrice);
            const image = card.dataset.productImage;

            addToCart(id, name, price, image);
        });
    });
}

/**
 * Quantity Controls
 */
function initQuantityControls() {
    document.querySelectorAll('.quantity-control').forEach(control => {
        const minusBtn = control.querySelector('.quantity-minus');
        const plusBtn = control.querySelector('.quantity-plus');
        const input = control.querySelector('.quantity-input');

        if (minusBtn) {
            minusBtn.addEventListener('click', () => {
                const currentVal = parseInt(input.value) || 1;
                if (currentVal > 1) {
                    input.value = currentVal - 1;
                    input.dispatchEvent(new Event('change'));
                }
            });
        }

        if (plusBtn) {
            plusBtn.addEventListener('click', () => {
                const currentVal = parseInt(input.value) || 1;
                input.value = currentVal + 1;
                input.dispatchEvent(new Event('change'));
            });
        }
    });
}

/**
 * Scroll Animations
 */
function initScrollAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);

    document.querySelectorAll('.product-card, .category-card, .feature-card, .testimonial-card').forEach(el => {
        el.classList.add('animate-on-scroll');
        observer.observe(el);
    });
}

/**
 * Notification System
 */
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="bi ${type === 'success' ? 'bi-check-circle' : 'bi-info-circle'}"></i>
        <span>${message}</span>
    `;

    // Add styles dynamically if not present
    if (!document.querySelector('#notification-styles')) {
        const styles = document.createElement('style');
        styles.id = 'notification-styles';
        styles.textContent = `
            .notification {
                position: fixed;
                bottom: 2rem;
                right: 2rem;
                background: #2C1810;
                color: white;
                padding: 1rem 1.5rem;
                border-radius: 12px;
                display: flex;
                align-items: center;
                gap: 0.75rem;
                box-shadow: 0 8px 30px rgba(0,0,0,0.2);
                z-index: 9999;
                animation: slideIn 0.3s ease-out;
            }
            .notification-success { background: linear-gradient(135deg, #D4A574, #B8896A); }
            .notification i { font-size: 1.25rem; }
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
            .cart-bounce { animation: bounce 0.5s ease; }
            @keyframes bounce {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.2); }
            }
            .animate-on-scroll {
                opacity: 0;
                transform: translateY(30px);
                transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
            }
            .animate-in {
                opacity: 1;
                transform: translateY(0);
            }
        `;
        document.head.appendChild(styles);
    }

    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease-out forwards';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

/**
 * Form Validation
 */
function validateForm(formId) {
    const form = document.getElementById(formId);
    let isValid = true;

    form.querySelectorAll('[required]').forEach(input => {
        if (!input.value.trim()) {
            isValid = false;
            input.classList.add('is-invalid');
        } else {
            input.classList.remove('is-invalid');
        }
    });

    const emailInputs = form.querySelectorAll('input[type="email"]');
    emailInputs.forEach(input => {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (input.value && !emailRegex.test(input.value)) {
            isValid = false;
            input.classList.add('is-invalid');
        }
    });

    return isValid;
}

/**
 * Smooth Scroll
 */
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const targetId = this.getAttribute('href');
        if (targetId !== '#') {
            e.preventDefault();
            document.querySelector(targetId)?.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});
