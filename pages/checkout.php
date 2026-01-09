<!-- Page Header -->
<header class="page-header">
    <div class="container">
        <h1>Checkout</h1>
        <div class="breadcrumb-custom">
            <a href="index.php">Home</a>
            <span>/</span>
            <a href="index.php?page=cart">Cart</a>
            <span>/</span>
            <span>Checkout</span>
        </div>
    </div>
</header>

<!-- Checkout Section -->
<section class="section checkout-section">
    <div class="container">
        <div class="row g-4">
            <!-- Checkout Form -->
            <div class="col-lg-8">
                <form id="checkoutForm" action="includes/process_order.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="cart_data" id="cartDataInput" value="">
                    <input type="hidden" name="delivery_method" value="pickup">
                    
                    <!-- Contact Information -->
                    <div class="checkout-card mb-4">
                        <h4><i class="bi bi-person me-2"></i>Contact Information</h4>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">First Name *</label>
                                <input type="text" class="form-control form-control-custom" name="first_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name *</label>
                                <input type="text" class="form-control form-control-custom" name="last_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email Address *</label>
                                <input type="email" class="form-control form-control-custom" name="email" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control form-control-custom" name="phone" required>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Pickup Information -->
                    <div class="checkout-card mb-4">
                        <h4><i class="bi bi-shop me-2"></i>Pickup Details</h4>
                        <div class="delivery-options">
                            <label class="delivery-option">
                                <input type="radio" checked disabled>
                                <div class="option-content" style="border-color: var(--primary); background: var(--accent);">
                                    <div class="option-icon" style="background: var(--primary); color: var(--white);"><i class="bi bi-shop"></i></div>
                                    <div class="option-details">
                                        <strong>Store Pickup</strong>
                                        <span>Pick up at our bakery • Free</span>
                                    </div>
                                    <div class="option-price">Free</div>
                                </div>
                            </label>
                        </div>
                        <div class="mt-3">
                            <p class="mb-1"><i class="bi bi-geo-alt-fill text-primary me-2"></i><strong>PUP Sto. Tomas Branch</strong></p>
                            <p class="text-muted ms-4 small">Sto. Tomas, Batangas</p>
                        </div>
                    </div>
                    
                    <!-- Payment Information -->
                    <div class="checkout-card">
                        <h4><i class="bi bi-credit-card me-2"></i>Payment Information</h4>
                        <div class="payment-methods mb-3">
                            <span class="payment-icon"><i class="bi bi-credit-card"></i></span>
                            <span class="payment-icon"><i class="bi bi-paypal"></i></span>
                            <span class="payment-icon"><i class="bi bi-apple"></i></span>
                        </div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Card Number *</label>
                                <input type="text" class="form-control form-control-custom" name="card_number" placeholder="1234 5678 9012 3456" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Expiry Date *</label>
                                <input type="text" class="form-control form-control-custom" name="expiry" placeholder="MM/YY" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">CVV *</label>
                                <input type="text" class="form-control form-control-custom" name="cvv" placeholder="123" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Name on Card *</label>
                                <input type="text" class="form-control form-control-custom" name="card_name" required>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Order Summary -->
            <div class="col-lg-4">
                <div class="order-summary-card">
                    <h4><i class="bi bi-bag me-2"></i>Your Order</h4>
                    <div class="order-items" id="checkoutItems">
                        <!-- Rendered by JS -->
                    </div>
                    <hr>
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span id="checkoutSubtotal">$0.00</span>
                    </div>
                    <div class="summary-row">
                        <span>Tax (8%)</span>
                        <span id="checkoutTax">$0.00</span>
                    </div>
                    <div class="summary-row total">
                        <span>Total</span>
                        <span id="checkoutTotal">$0.00</span>
                    </div>
                    
                    <button type="submit" form="checkoutForm" class="btn btn-hero btn-hero-primary w-100 mt-4">
                        <i class="bi bi-lock me-2"></i> Place Order
                    </button>
                    
                    <div class="secure-info text-center mt-3">
                        <small><i class="bi bi-shield-check me-1"></i> Your payment is secure and encrypted</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.checkout-section { background: var(--cream); }

.checkout-card {
    background: var(--white);
    padding: 2rem;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-sm);
}

.checkout-card h4 {
    color: var(--dark);
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--accent);
}

.delivery-options { display: flex; flex-direction: column; gap: 1rem; }

.delivery-option {
    cursor: default;
}

.delivery-option input { display: none; }

.option-content {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem;
    border: 2px solid var(--cream-dark);
    border-radius: var(--radius-md);
    transition: var(--transition);
}

.option-icon {
    width: 50px;
    height: 50px;
    background: var(--cream-dark);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    color: var(--secondary);
}

.option-details { flex: 1; }
.option-details strong { display: block; color: var(--dark); }
.option-details span { font-size: 0.85rem; color: var(--text-light); }
.option-price { font-weight: 600; color: var(--secondary); }

.payment-methods {
    display: flex;
    gap: 0.75rem;
}

.payment-icon {
    width: 50px;
    height: 35px;
    background: var(--cream-dark);
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--text-secondary);
}

.order-summary-card {
    background: var(--white);
    padding: 2rem;
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-md);
    position: sticky;
    top: 100px;
}

.order-summary-card h4 {
    margin-bottom: 1.5rem;
    color: var(--dark);
}

.order-item {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--cream-dark);
}

.order-item:last-child { border-bottom: none; }

.order-item-image {
    width: 60px;
    height: 60px;
    border-radius: var(--radius-sm);
    object-fit: cover;
}

.order-item-details { flex: 1; }
.order-item-name { font-weight: 500; color: var(--dark); font-size: 0.95rem; }
.order-item-qty { font-size: 0.85rem; color: var(--text-light); }
.order-item-price { font-weight: 600; color: var(--secondary); }

.secure-info { color: var(--text-light); }
.secure-info i { color: #28a745; }

@media (max-width: 991px) {
    .order-summary-card { position: static; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Wait for main.js to initialize the cart from localStorage
    setTimeout(function() {
        // Re-initialize cart from localStorage to ensure we have the latest data
        const savedCart = localStorage.getItem('bakeAndTakeCart');
        if (savedCart) {
            try {
                const parsedCart = JSON.parse(savedCart);
                if (typeof cart !== 'undefined') {
                    cart.length = 0;
                    parsedCart.forEach(item => cart.push(item));
                }
            } catch (e) {
                console.error('Error parsing cart from localStorage:', e);
            }
        }
        renderCheckoutItems();
    }, 50);
    
    // Handle form submission - add cart data
    document.getElementById('checkoutForm').addEventListener('submit', function(e) {
        const cart = getCart();
        if (cart.length === 0) {
            e.preventDefault();
            alert('Your cart is empty!');
            window.location.href = 'index.php?page=cart';
            return false;
        }
        // Populate the hidden cart_data field with cart JSON
        document.getElementById('cartDataInput').value = JSON.stringify(cart);
    });
});


function renderCheckoutItems() {
    const cart = getCart();
    const container = document.getElementById('checkoutItems');
    
    if (cart.length === 0) {
        window.location.href = 'index.php?page=cart';
        return;
    }
    
    container.innerHTML = cart.map(item => `
        <div class="order-item">
            <img src="${item.image}" alt="${item.name}" class="order-item-image" onerror="this.src='assets/images/placeholder.jpg'">
            <div class="order-item-details">
                <div class="order-item-name">${item.name}</div>
                <div class="order-item-qty">Qty: ${item.quantity}</div>
            </div>
            <div class="order-item-price">$${(item.price * item.quantity).toFixed(2)}</div>
        </div>
    `).join('');
    
    updateCheckoutSummary();
}

function updateCheckoutSummary() {
    const subtotal = getCartTotal();
    const tax = subtotal * 0.08;
    const total = subtotal + tax;
    
    document.getElementById('checkoutSubtotal').textContent = '$' + subtotal.toFixed(2);
    document.getElementById('checkoutTax').textContent = '$' + tax.toFixed(2);
    document.getElementById('checkoutTotal').textContent = '$' + total.toFixed(2);
}
</script>
