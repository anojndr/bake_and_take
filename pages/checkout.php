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
                <div id="checkoutFormContainer">
                    <!-- Contact Information -->
                    <div class="checkout-card mb-4">
                        <h4><i class="bi bi-person me-2"></i>Contact Information</h4>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">First Name *</label>
                                <input type="text" class="form-control form-control-custom" id="checkout_first_name" name="first_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Last Name *</label>
                                <input type="text" class="form-control form-control-custom" id="checkout_last_name" name="last_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email Address *</label>
                                <input type="email" class="form-control form-control-custom" id="checkout_email" name="email" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control form-control-custom" id="checkout_phone" name="phone" required>
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
                    
                    <!-- Special Instructions -->
                    <div class="checkout-card mb-4">
                        <h4><i class="bi bi-chat-text me-2"></i>Special Instructions (Optional)</h4>
                        <textarea class="form-control form-control-custom" id="checkout_instructions" name="instructions" rows="3" placeholder="Any special requests or notes for your order..."></textarea>
                    </div>
                    
                    <!-- Payment Section -->
                    <div class="checkout-card">
                        <h4><i class="bi bi-wallet2 me-2"></i>Payment Method</h4>
                        <p class="text-muted mb-3">Complete your payment securely with PayPal. You can pay with your PayPal account or debit/credit card.</p>
                        
                        <!-- PayPal Payment Section -->
                        <div id="paypal-payment-section" class="payment-section active">
                            <!-- PayPal Button Container -->
                            <div id="paypal-button-container"></div>
                            
                            <!-- Loading state -->
                            <div id="paypal-loading" class="text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading PayPal...</span>
                                </div>
                                <p class="mt-2 text-muted">Loading PayPal...</p>
                            </div>
                            
                            <div class="payment-badges mt-4">
                                <div class="d-flex align-items-center justify-content-center gap-3 flex-wrap">
                                    <img src="https://www.paypalobjects.com/webstatic/mktg/Logo/pp-logo-100px.png" alt="PayPal" height="25">
                                    <span class="badge-separator">|</span>
                                    <span class="payment-badge"><i class="bi bi-credit-card"></i> Visa</span>
                                    <span class="payment-badge"><i class="bi bi-credit-card"></i> Mastercard</span>
                                    <span class="payment-badge"><i class="bi bi-credit-card"></i> Amex</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Error message container -->
                        <div id="payment-error" class="alert alert-danger d-none mt-3"></div>
                    </div>
                </div>
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
                        <span id="checkoutSubtotal">₱0.00</span>
                    </div>
                    <div class="summary-row">
                        <span>Tax (8%)</span>
                        <span id="checkoutTax">₱0.00</span>
                    </div>
                    <div class="summary-row total">
                        <span>Total</span>
                        <span id="checkoutTotal">₱0.00</span>
                    </div>
                    
                    <div class="secure-info text-center mt-4">
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

/* PayPal Button Styling */
#paypal-button-container {
    min-height: 55px;
}

.payment-badges {
    padding-top: 1rem;
    border-top: 1px solid var(--cream-dark);
}

.payment-badge {
    font-size: 0.85rem;
    color: var(--text-secondary);
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
}

.badge-separator {
    color: var(--cream-dark);
}

/* Processing overlay */
.checkout-processing {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.checkout-processing-content {
    background: white;
    padding: 3rem;
    border-radius: var(--radius-xl);
    text-align: center;
    max-width: 400px;
}

.checkout-processing-content .spinner-border {
    width: 3rem;
    height: 3rem;
    margin-bottom: 1rem;
}

@media (max-width: 991px) {
    .order-summary-card { position: static; }
}

/* Payment Sections */
.payment-section {
    display: none;
    animation: fadeIn 0.3s ease;
}

.payment-section.active {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    color: var(--text-secondary);
}

.summary-row.total {
    font-weight: 600;
    font-size: 1.2rem;
    color: var(--dark);
    padding-top: 0.5rem;
    border-top: 2px solid var(--accent);
    margin-top: 0.5rem;
}

.summary-row.total span:last-child {
    color: var(--secondary);
}
</style>

<!-- PayPal SDK -->
<script src="https://www.paypal.com/sdk/js?client-id=<?php echo PAYPAL_CLIENT_ID; ?>&currency=PHP&intent=capture"></script>

<script>
document.addEventListener('DOMContentLoaded', async function() {
    // Wait for the cart to be loaded from the database
    // The initCart() function in main.js is async and fetches from cart_api.php
    
    // First, ensure initCart has been called by main.js
    // We need to wait for the cart data to be available
    let retries = 0;
    const maxRetries = 20; // Wait up to 2 seconds (20 * 100ms)
    
    async function waitForCart() {
        return new Promise((resolve) => {
            const checkCart = setInterval(() => {
                retries++;
                const currentCart = getCart();
                
                // Check if cart has items OR we've waited long enough
                if (currentCart.length > 0 || retries >= maxRetries) {
                    clearInterval(checkCart);
                    resolve(currentCart);
                }
            }, 100);
        });
    }
    
    // Wait for cart to be loaded
    const cartData = await waitForCart();
    
    // If cart is still empty after waiting, redirect to cart page
    if (cartData.length === 0) {
        window.location.href = 'index.php?page=cart';
        return;
    }
    
    // Cart has items, render checkout
    renderCheckoutItems();
    initPayPalButtons();
});

function renderCheckoutItems() {
    const cart = getCart();
    const container = document.getElementById('checkoutItems');
    
    // Cart empty check is handled in DOMContentLoaded, but keep as safety
    if (cart.length === 0) {
        container.innerHTML = '<p class="text-muted">No items in cart</p>';
        return;
    }
    
    container.innerHTML = cart.map(item => `
        <div class="order-item">
            <img src="${item.image}" alt="${item.name}" class="order-item-image" onerror="this.src='assets/images/placeholder.jpg'">
            <div class="order-item-details">
                <div class="order-item-name">${item.name}</div>
                <div class="order-item-qty">Qty: ${item.quantity}</div>
            </div>
            <div class="order-item-price">₱${(item.price * item.quantity).toFixed(2)}</div>
        </div>
    `).join('');
    
    updateCheckoutSummary();
}

function updateCheckoutSummary() {
    const subtotal = getCartTotal();
    const tax = subtotal * 0.08;
    const total = subtotal + tax;
    
    document.getElementById('checkoutSubtotal').textContent = '₱' + subtotal.toFixed(2);
    document.getElementById('checkoutTax').textContent = '₱' + tax.toFixed(2);
    document.getElementById('checkoutTotal').textContent = '₱' + total.toFixed(2);
}

function validateCustomerInfo() {
    const firstName = document.getElementById('checkout_first_name').value.trim();
    const lastName = document.getElementById('checkout_last_name').value.trim();
    const email = document.getElementById('checkout_email').value.trim();
    const phone = document.getElementById('checkout_phone').value.trim();
    
    if (!firstName || !lastName || !email || !phone) {
        showPayPalError('Please fill in all required contact information fields before proceeding with payment.');
        return null;
    }
    
    // Basic email validation
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        showPayPalError('Please enter a valid email address.');
        return null;
    }
    
    return {
        first_name: firstName,
        last_name: lastName,
        email: email,
        phone: phone,
        instructions: document.getElementById('checkout_instructions').value.trim()
    };
}

function showPaymentError(message) {
    const errorContainer = document.getElementById('payment-error');
    errorContainer.textContent = message;
    errorContainer.classList.remove('d-none');
    
    // Scroll to error
    errorContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function hidePaymentError() {
    const errorContainer = document.getElementById('payment-error');
    errorContainer.classList.add('d-none');
}

// Legacy alias for PayPal error functions
function showPayPalError(message) { showPaymentError(message); }
function hidePayPalError() { hidePaymentError(); }

function showProcessingOverlay() {
    const overlay = document.createElement('div');
    overlay.id = 'processing-overlay';
    overlay.className = 'checkout-processing';
    overlay.innerHTML = `
        <div class="checkout-processing-content">
            <div class="spinner-border text-primary" role="status"></div>
            <h4>Processing Payment</h4>
            <p class="text-muted mb-0">Please wait while we complete your order...</p>
        </div>
    `;
    document.body.appendChild(overlay);
}

function hideProcessingOverlay() {
    const overlay = document.getElementById('processing-overlay');
    if (overlay) overlay.remove();
}

function initPayPalButtons() {
    // Hide loading indicator
    document.getElementById('paypal-loading').style.display = 'none';
    
    // Check if PayPal SDK loaded
    if (typeof paypal === 'undefined') {
        showPayPalError('Failed to load PayPal. Please refresh the page and try again.');
        return;
    }
    
    paypal.Buttons({
        style: {
            layout: 'vertical',
            color: 'gold',
            shape: 'rect',
            label: 'paypal',
            height: 55
        },
        
        // Validate before showing PayPal popup
        onClick: function(data, actions) {
            hidePayPalError();
            
            const customerInfo = validateCustomerInfo();
            if (!customerInfo) {
                return actions.reject();
            }
            
            const cart = getCart();
            if (cart.length === 0) {
                showPayPalError('Your cart is empty.');
                return actions.reject();
            }
            
            return actions.resolve();
        },
        
        // Create order through our backend
        createOrder: function(data, actions) {
            const cart = getCart();
            
            return fetch('includes/paypal_create_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    cart: cart
                })
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(orderData) {
                if (orderData.error) {
                    throw new Error(orderData.error);
                }
                return orderData.id;
            });
        },
        
        // Capture payment after approval
        onApprove: function(data, actions) {
            showProcessingOverlay();
            
            const customerInfo = validateCustomerInfo();
            
            return fetch('includes/paypal_capture_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    orderID: data.orderID,
                    customerInfo: customerInfo
                })
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(details) {
                hideProcessingOverlay();
                
                if (details.error) {
                    showPayPalError(details.error + (details.details ? ': ' + details.details : ''));
                    return;
                }
                
                // Payment successful - redirect to success page
                console.log('Payment completed successfully:', details);
                
                // Clear the cart
                clearCart();
                
                // Redirect to order success page
                window.location.href = 'index.php?page=order-success';
            })
            .catch(function(error) {
                hideProcessingOverlay();
                console.error('Payment capture error:', error);
                showPayPalError('An error occurred while processing your payment. Please try again.');
            });
        },
        
        // Handle cancellation
        onCancel: function(data) {
            console.log('Payment cancelled by user');
            // Optional: Show a message
        },
        
        // Handle errors
        onError: function(err) {
            hideProcessingOverlay();
            console.error('PayPal error:', err);
            showPayPalError('An error occurred with PayPal. Please try again or use a different payment method.');
        }
    }).render('#paypal-button-container');
}
</script>
