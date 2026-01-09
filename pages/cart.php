<!-- Page Header -->
<header class="page-header">
    <div class="container">
        <h1>Shopping Cart</h1>
        <div class="breadcrumb-custom">
            <a href="index.php">Home</a>
            <span>/</span>
            <span>Cart</span>
        </div>
    </div>
</header>

<?php if (!isset($_SESSION['user_id'])): ?>
<!-- Login Required Section -->
<section class="section">
    <div class="container text-center py-5">
        <i class="bi bi-person-lock" style="font-size: 5rem; color: var(--text-light);"></i>
        <h3 class="mt-4">Login Required</h3>
        <p class="text-muted mb-4">Please login to view your shopping cart and complete your order.</p>
        <div class="d-flex justify-content-center gap-3">
            <a href="index.php?page=login" class="btn btn-hero btn-hero-primary px-5">
                <i class="bi bi-box-arrow-in-right me-2"></i>Login
            </a>
            <a href="index.php?page=register" class="btn btn-hero btn-hero-secondary px-5">
                <i class="bi bi-person-plus me-2"></i>Register
            </a>
        </div>
    </div>
</section>
<?php else: ?>
<!-- Cart Section -->
<section class="section cart-section">

    <div class="container">
        <!-- Empty Cart Message - Centered -->
        <div class="empty-cart text-center py-5" id="emptyCart" style="display: none;">
            <i class="bi bi-cart-x" style="font-size: 5rem; color: var(--text-light);"></i>
            <h3 class="mt-3">Your cart is empty</h3>
            <p class="text-muted mb-4">Looks like you haven't added any items yet</p>
            <a href="index.php?page=menu" class="btn btn-hero btn-hero-primary">
                <i class="bi bi-basket me-2"></i> Browse Menu
            </a>
        </div>
        
        <div class="row g-4" id="cartContentRow">
            <!-- Cart Items -->
            <div class="col-lg-8">
                <div class="cart-items-container" id="cartItemsContainer">
                    <!-- Cart items will be rendered by JavaScript -->
                </div>
            </div>
            
            <!-- Cart Summary -->
            <div class="col-lg-4">
                <div class="cart-summary" id="cartSummary">
                    <h4><i class="bi bi-receipt me-2"></i>Order Summary</h4>
                    <div class="summary-content">
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span id="subtotal">$0.00</span>
                        </div>
                        <div class="summary-row">
                            <span>Tax (8%)</span>
                            <span id="tax">$0.00</span>
                        </div>
                        <div class="summary-row total">
                            <span>Total</span>
                            <span id="total">$0.00</span>
                        </div>
                    </div>
                    
                    <div class="promo-code mt-4">
                        <label class="form-label">Promo Code</label>
                        <div class="input-group">
                            <input type="text" class="form-control form-control-custom" placeholder="Enter code">
                            <button class="btn btn-outline-secondary" type="button">Apply</button>
                        </div>
                    </div>
                    
                    <a href="index.php?page=checkout" class="btn btn-hero btn-hero-primary w-100 mt-4" id="checkoutBtn">
                        <i class="bi bi-lock me-2"></i> Proceed to Checkout
                    </a>
                    
                    <div class="secure-badge text-center mt-3">
                        <i class="bi bi-shield-check"></i>
                        <small>Secure checkout with SSL encryption</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.cart-section { background: var(--cream); min-height: 60vh; }

.cart-item {
    display: flex;
    gap: 1.5rem;
    padding: 1.5rem;
    background: var(--white);
    border-radius: var(--radius-lg);
    margin-bottom: 1rem;
    align-items: center;
    transition: var(--transition);
}

.cart-item:hover { box-shadow: var(--shadow-sm); }

.cart-item-image {
    width: 100px;
    height: 100px;
    border-radius: var(--radius-md);
    object-fit: cover;
    background: var(--cream-dark);
}

.cart-item-details { flex: 1; }

.cart-item-title {
    font-family: var(--font-display);
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 0.25rem;
    font-size: 1.1rem;
}

.cart-item-price {
    color: var(--primary-dark);
    font-weight: 600;
    font-size: 1.1rem;
}

.cart-item-total {
    font-family: var(--font-display);
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--secondary);
    min-width: 80px;
    text-align: right;
}

.quantity-control {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.quantity-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: 2px solid var(--cream-dark);
    background: var(--white);
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    justify-content: center;
}

.quantity-btn:hover {
    border-color: var(--primary);
    color: var(--primary);
}

.quantity-input {
    width: 50px;
    text-align: center;
    border: 2px solid var(--cream-dark);
    border-radius: var(--radius-sm);
    padding: 0.4rem;
    font-weight: 600;
}

.quantity-input:focus {
    outline: none;
    border-color: var(--primary);
}

.remove-item {
    background: none;
    border: none;
    color: var(--text-light);
    cursor: pointer;
    padding: 0.5rem;
    transition: var(--transition);
}

.remove-item:hover { color: #dc3545; }

.cart-summary {
    background: var(--white);
    padding: 2rem;
    border-radius: var(--radius-xl);
    position: sticky;
    top: 100px;
    box-shadow: var(--shadow-md);
}

.cart-summary h4 {
    margin-bottom: 1.5rem;
    color: var(--dark);
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--accent);
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 1rem;
    color: var(--text-secondary);
}

.summary-row.total {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--dark);
    padding-top: 1rem;
    margin-top: 1rem;
    border-top: 2px solid var(--accent);
}

.secure-badge {
    color: var(--text-light);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.secure-badge i { color: #28a745; }

@media (max-width: 991px) {
    .cart-summary { position: static; margin-top: 2rem; }
}

@media (max-width: 767px) {
    .cart-item {
        flex-wrap: wrap;
        gap: 1rem;
    }
    .cart-item-image { width: 80px; height: 80px; }
    .cart-item-total { width: 100%; text-align: left; margin-top: 0.5rem; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', async function() {
    // Wait for main.js initCart to complete, then render
    // Use a small delay to ensure the async initCart has finished
    setTimeout(async function() {
        await initCart();
        renderCartPage();
    }, 100);
});

function renderCartPage() {
    const cartItems = getCart();
    const container = document.getElementById('cartItemsContainer');
    const emptyCart = document.getElementById('emptyCart');
    const cartContentRow = document.getElementById('cartContentRow');
    
    if (cartItems.length === 0) {
        container.innerHTML = '';
        emptyCart.style.display = 'block';
        cartContentRow.style.display = 'none';
        return;
    }
    
    emptyCart.style.display = 'none';
    cartContentRow.style.display = 'flex';
    
    container.innerHTML = cartItems.map(item => `
        <div class="cart-item" data-id="${item.id}">
            <img src="${item.image}" alt="${item.name}" class="cart-item-image" 
                 onerror="this.src='assets/images/placeholder.jpg'">
            <div class="cart-item-details">
                <h5 class="cart-item-title">${item.name}</h5>
                <span class="cart-item-price">$${item.price.toFixed(2)}</span>
            </div>
            <div class="quantity-control">
                <button class="quantity-btn quantity-minus" onclick="updateCartItem(${item.id}, ${item.quantity - 1})">
                    <i class="bi bi-dash"></i>
                </button>
                <input type="number" class="quantity-input" value="${item.quantity}" min="1" 
                       onchange="updateCartItem(${item.id}, parseInt(this.value))">
                <button class="quantity-btn quantity-plus" onclick="updateCartItem(${item.id}, ${item.quantity + 1})">
                    <i class="bi bi-plus"></i>
                </button>
            </div>
            <span class="cart-item-total">$${(item.price * item.quantity).toFixed(2)}</span>
            <button class="remove-item" onclick="removeCartItem(${item.id})" title="Remove">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
    `).join('');
    
    updateSummary();
}

async function updateCartItem(id, quantity) {
    await updateQuantity(id, quantity);
    renderCartPage();
}

async function removeCartItem(id) {
    await removeFromCart(id);
    renderCartPage();
    showNotification('Item removed from cart', 'info');
}

function updateSummary() {
    const subtotal = getCartTotal();
    const tax = subtotal * 0.08;
    const total = subtotal + tax;
    
    document.getElementById('subtotal').textContent = '$' + subtotal.toFixed(2);
    document.getElementById('tax').textContent = '$' + tax.toFixed(2);
    document.getElementById('total').textContent = '$' + total.toFixed(2);
}
</script>

<?php endif; ?>

