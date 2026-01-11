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
                    
                    <!-- Payment Section with Payment Method Selection -->
                    <div class="checkout-card">
                        <h4><i class="bi bi-wallet2 me-2"></i>Payment Method</h4>
                        <p class="text-muted mb-3">Choose your preferred payment method to complete your order.</p>
                        
                        <!-- Payment Method Tabs -->
                        <div class="payment-method-tabs">
                            <button type="button" class="payment-tab active" data-method="gcash" id="gcash-tab">
                                <div class="tab-icon gcash-icon">
                                    <img src="assets/images/gcash-logo.png" alt="GCash">
                                </div>
                                <span>GCash</span>
                            </button>
                            <button type="button" class="payment-tab" data-method="cash" id="cash-tab">
                                <div class="tab-icon cash-icon">
                                    <i class="bi bi-cash-coin"></i>
                                </div>
                                <span>Cash</span>
                            </button>
                            <button type="button" class="payment-tab" data-method="paypal" id="paypal-tab">
                                <div class="tab-icon paypal-icon">
                                    <i class="bi bi-paypal"></i>
                                </div>
                                <span>PayPal</span>
                            </button>
                        </div>
                        
                        <!-- GCash Payment Section -->
                        <div id="gcash-payment-section" class="payment-section active">
                            <div class="gcash-info">
                                <div class="gcash-header">
                                    <img src="assets/images/gcash-logo.png" alt="GCash" class="gcash-header-logo">
                                    <span>Pay via GCash QR Code</span>
                                </div>
                                <p class="text-muted mb-3">Scan the QR code using your GCash app to complete the payment. After paying, click the confirmation button below.</p>
                                
                                <button type="button" class="btn btn-gcash" id="show-gcash-qr">
                                    <i class="bi bi-qr-code me-2"></i>Show GCash QR Code
                                </button>
                                
                                <div class="gcash-instructions mt-3">
                                    <h6><i class="bi bi-info-circle me-2"></i>How to Pay:</h6>
                                    <ol>
                                        <li>Click "Show GCash QR Code" above</li>
                                        <li>Open your GCash app and tap "Scan QR"</li>
                                        <li>Scan the QR code displayed</li>
                                        <li>Enter the exact amount shown in your order</li>
                                        <li>Complete the payment in GCash</li>
                                        <li>Click "I've Completed Payment" button</li>
                                    </ol>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Cash Payment Section -->
                        <div id="cash-payment-section" class="payment-section">
                            <div class="cash-info">
                                <div class="cash-header">
                                    <i class="bi bi-cash-coin"></i>
                                    <span>Pay Cash on Pickup</span>
                                </div>
                                <p class="text-muted mb-3">Pay for your order with cash when you pick it up at our store. No advance payment required!</p>
                                
                                <div class="cash-benefits">
                                    <div class="benefit-item">
                                        <i class="bi bi-check-circle-fill"></i>
                                        <span>No online payment needed</span>
                                    </div>
                                    <div class="benefit-item">
                                        <i class="bi bi-check-circle-fill"></i>
                                        <span>Pay when you pick up your order</span>
                                    </div>
                                    <div class="benefit-item">
                                        <i class="bi bi-check-circle-fill"></i>
                                        <span>Exact change appreciated</span>
                                    </div>
                                </div>
                                
                                <div class="cash-amount-display">
                                    <span class="label">Total to Pay at Pickup:</span>
                                    <span class="amount" id="cash-total-amount">₱0.00</span>
                                </div>
                                
                                <button type="button" class="btn btn-cash" id="place-cash-order">
                                    <i class="bi bi-bag-check me-2"></i>Place Order (Pay at Pickup)
                                </button>
                                
                                <div class="cash-note mt-3">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <span>Your order will be prepared and ready for pickup. Please bring the exact amount.</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- PayPal Payment Section -->
                        <div id="paypal-payment-section" class="payment-section">
                            <p class="text-muted mb-3">Complete your payment securely with PayPal. You can pay with your PayPal account or debit/credit card.</p>
                            
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
                    
                    <!-- GCash QR Code Modal -->
                    <div class="gcash-modal" id="gcash-modal">
                        <div class="gcash-modal-content">
                            <button type="button" class="gcash-modal-close" id="close-gcash-modal">
                                <i class="bi bi-x-lg"></i>
                            </button>
                            <div class="gcash-modal-header">
                                <img src="assets/images/gcash-logo.png" alt="GCash" class="gcash-modal-logo">
                                <h4>Scan to Pay</h4>
                            </div>
                            <div class="gcash-qr-container">
                                <img src="assets/images/gcash.png" alt="GCash QR Code" class="gcash-qr-image" id="gcash-qr-image">
                            </div>
                            <div class="gcash-amount">
                                <span class="label">Amount to Pay:</span>
                                <span class="amount" id="gcash-modal-amount">₱0.00</span>
                            </div>
                            <div class="gcash-modal-instructions">
                                <p><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>Please enter the <strong>exact amount</strong> when paying.</p>
                            </div>
                            <button type="button" class="btn btn-gcash-confirm" id="confirm-gcash-payment">
                                <i class="bi bi-check-circle me-2"></i>I've Completed Payment
                            </button>
                        </div>
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

/* Payment Method Tabs */
.payment-method-tabs {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.payment-tab {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1.25rem 1rem;
    border: 2px solid var(--cream-dark);
    border-radius: var(--radius-lg);
    background: var(--white);
    cursor: pointer;
    transition: all 0.3s ease;
}

.payment-tab:hover {
    border-color: var(--primary);
    background: var(--accent);
}

.payment-tab.active {
    border-color: var(--primary);
    background: var(--accent);
    box-shadow: 0 0 0 3px rgba(212, 165, 116, 0.2);
}

.payment-tab .tab-icon {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    font-size: 1.5rem;
}

.payment-tab .tab-icon img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.payment-tab .gcash-icon {
    background: transparent;
    overflow: hidden;
}

.payment-tab .gcash-icon img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

.payment-tab .paypal-icon {
    background: #003087;
    color: white;
}

.payment-tab span {
    font-weight: 600;
    color: var(--dark);
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

/* GCash Styles */
.gcash-info {
    background: linear-gradient(135deg, #E8F4FD, #F0F8FF);
    padding: 1.5rem;
    border-radius: var(--radius-lg);
    border: 1px solid rgba(0, 125, 254, 0.2);
}

.gcash-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
    font-weight: 600;
    color: #007DFE;
    font-size: 1.1rem;
}

.gcash-header-logo {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    object-fit: cover;
}

.btn-gcash {
    background: linear-gradient(135deg, #007DFE, #0056B3);
    color: white;
    border: none;
    padding: 1rem 2rem;
    border-radius: var(--radius-lg);
    font-weight: 600;
    font-size: 1rem;
    width: 100%;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.btn-gcash:hover {
    background: linear-gradient(135deg, #0056B3, #004494);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 125, 254, 0.3);
}

.gcash-instructions {
    background: white;
    padding: 1rem;
    border-radius: var(--radius-md);
    border-left: 4px solid #007DFE;
}

.gcash-instructions h6 {
    color: #007DFE;
    margin-bottom: 0.75rem;
}

.gcash-instructions ol {
    margin: 0;
    padding-left: 1.25rem;
    color: var(--text-secondary);
}

.gcash-instructions li {
    margin-bottom: 0.5rem;
}

.gcash-instructions li:last-child {
    margin-bottom: 0;
}

/* GCash Modal */
.gcash-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.8);
    z-index: 10000;
    align-items: flex-start;
    justify-content: center;
    padding: 1rem;
    overflow-y: auto;
    animation: fadeIn 0.3s ease;
}

.gcash-modal.active {
    display: flex;
}

.gcash-modal-content {
    background: white;
    border-radius: var(--radius-xl);
    padding: 1.5rem;
    max-width: 380px;
    width: 100%;
    position: relative;
    text-align: center;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    margin: auto;
}

.gcash-modal-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: none;
    background: var(--cream);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.gcash-modal-close:hover {
    background: var(--cream-dark);
}

.gcash-modal-header {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
    margin-bottom: 0.75rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid var(--accent);
}

.gcash-modal-logo {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    object-fit: cover;
}

.gcash-modal-header h4 {
    margin: 0;
    color: #007DFE;
    font-size: 1.1rem;
}

.gcash-brand {
    color: #007DFE;
    font-weight: 700;
    font-size: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.gcash-qr-container {
    background: white;
    padding: 0.5rem;
    border-radius: var(--radius-lg);
    margin-bottom: 0.75rem;
    border: 2px solid var(--cream);
    max-width: 280px;
    margin-left: auto;
    margin-right: auto;
}

.gcash-qr-image {
    max-width: 100%;
    max-height: 280px;
    height: auto;
    border-radius: var(--radius-md);
    object-fit: contain;
}

.gcash-amount {
    background: linear-gradient(135deg, #007DFE, #0056B3);
    color: white;
    padding: 0.75rem 1.25rem;
    border-radius: var(--radius-lg);
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.gcash-amount .label {
    font-size: 0.85rem;
    opacity: 0.9;
}

.gcash-amount .amount {
    font-size: 1.3rem;
    font-weight: 700;
}

.gcash-modal-instructions {
    background: #FFF3CD;
    padding: 0.5rem 0.75rem;
    border-radius: var(--radius-md);
    margin-bottom: 0.75rem;
}

.gcash-modal-instructions p {
    margin: 0;
    font-size: 0.8rem;
    color: #856404;
}

.btn-gcash-confirm {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    border: none;
    padding: 0.875rem 1.5rem;
    border-radius: var(--radius-lg);
    font-weight: 600;
    font-size: 0.95rem;
    width: 100%;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.btn-gcash-confirm:hover {
    background: linear-gradient(135deg, #20c997, #1aa179);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
}

@media (max-width: 576px) {
    .payment-method-tabs {
        flex-direction: column;
    }
    
    .payment-tab {
        flex-direction: row;
        justify-content: center;
    }
    
    .gcash-modal-content {
        padding: 1.5rem;
    }
    
    .gcash-amount {
        flex-direction: column;
        gap: 0.5rem;
        text-align: center;
    }
}

/* Cash Payment Styles */
.payment-tab .cash-icon {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
}

.cash-info {
    background: linear-gradient(135deg, #E8F5E9, #F1F8E9);
    padding: 1.5rem;
    border-radius: var(--radius-lg);
    border: 1px solid rgba(40, 167, 69, 0.2);
}

.cash-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
    font-weight: 600;
    color: #28a745;
    font-size: 1.1rem;
}

.cash-header i {
    font-size: 1.5rem;
}

.cash-benefits {
    background: white;
    padding: 1rem;
    border-radius: var(--radius-md);
    margin-bottom: 1rem;
}

.cash-benefits .benefit-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 0;
    color: var(--text-secondary);
}

.cash-benefits .benefit-item:not(:last-child) {
    border-bottom: 1px solid var(--cream);
}

.cash-benefits .benefit-item i {
    color: #28a745;
    font-size: 1.1rem;
}

.cash-amount-display {
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
    padding: 1rem 1.25rem;
    border-radius: var(--radius-lg);
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.cash-amount-display .label {
    font-size: 0.9rem;
    opacity: 0.9;
}

.cash-amount-display .amount {
    font-size: 1.4rem;
    font-weight: 700;
}

.btn-cash {
    background: linear-gradient(135deg, #28a745, #218838);
    color: white;
    border: none;
    padding: 1rem 2rem;
    border-radius: var(--radius-lg);
    font-weight: 600;
    font-size: 1rem;
    width: 100%;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.btn-cash:hover {
    background: linear-gradient(135deg, #218838, #1e7e34);
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
}

.cash-note {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    padding: 0.75rem;
    background: white;
    border-radius: var(--radius-md);
    border-left: 4px solid #28a745;
    font-size: 0.85rem;
    color: var(--text-secondary);
}

.cash-note i {
    color: #28a745;
    margin-top: 0.1rem;
}

@media (max-width: 576px) {
    .cash-amount-display {
        flex-direction: column;
        gap: 0.5rem;
        text-align: center;
    }
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
    initPaymentTabs();
    initGCashPayment();
    initCashPayment();
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

// ==================== Payment Method Tabs ====================
function initPaymentTabs() {
    document.querySelectorAll('.payment-tab').forEach(tab => {
        tab.addEventListener('click', function() {
            const method = this.dataset.method;
            
            // Update active tab
            document.querySelectorAll('.payment-tab').forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // Show/hide payment sections
            document.querySelectorAll('.payment-section').forEach(section => {
                section.classList.remove('active');
            });
            
            if (method === 'gcash') {
                document.getElementById('gcash-payment-section').classList.add('active');
            } else if (method === 'cash') {
                document.getElementById('cash-payment-section').classList.add('active');
                // Update cash amount display
                updateCashAmount();
            } else if (method === 'paypal') {
                document.getElementById('paypal-payment-section').classList.add('active');
            }
            
            // Hide any errors
            hidePaymentError();
        });
    });
}

// ==================== Cash Payment ====================
function updateCashAmount() {
    const subtotal = getCartTotal();
    const tax = subtotal * 0.08;
    const total = subtotal + tax;
    const cashAmountEl = document.getElementById('cash-total-amount');
    if (cashAmountEl) {
        cashAmountEl.textContent = '₱' + total.toFixed(2);
    }
}

function initCashPayment() {
    const placeCashOrderBtn = document.getElementById('place-cash-order');
    
    if (!placeCashOrderBtn) return;
    
    // Initialize cash amount display
    updateCashAmount();
    
    placeCashOrderBtn.addEventListener('click', function() {
        // Validate customer info first
        const customerInfo = validateCustomerInfo();
        if (!customerInfo) return;
        
        const cart = getCart();
        if (cart.length === 0) {
            showPaymentError('Your cart is empty.');
            return;
        }
        
        // Show processing overlay
        showProcessingOverlay();
        
        // Process Cash order
        fetch('includes/cash_process_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                customerInfo: customerInfo,
                cart: cart
            })
        })
        .then(response => response.json())
        .then(data => {
            hideProcessingOverlay();
            
            if (data.error) {
                showPaymentError(data.error);
                return;
            }
            
            // Order placed successfully - redirect to success page
            console.log('Cash order placed successfully:', data);
            
            // Clear the cart
            clearCart();
            
            // Redirect to order success page
            window.location.href = 'index.php?page=order-success';
        })
        .catch(error => {
            hideProcessingOverlay();
            console.error('Cash order error:', error);
            showPaymentError('An error occurred while processing your order. Please try again.');
        });
    });
}

// ==================== GCash Payment ====================
function initGCashPayment() {
    const gcashModal = document.getElementById('gcash-modal');
    const showGcashQrBtn = document.getElementById('show-gcash-qr');
    const closeGcashModalBtn = document.getElementById('close-gcash-modal');
    const confirmGcashPaymentBtn = document.getElementById('confirm-gcash-payment');
    const gcashModalAmount = document.getElementById('gcash-modal-amount');

    // Show GCash QR Modal
    showGcashQrBtn.addEventListener('click', function() {
        // Validate customer info first
        const customerInfo = validateCustomerInfo();
        if (!customerInfo) return;
        
        // Update amount in modal
        const subtotal = getCartTotal();
        const tax = subtotal * 0.08;
        const total = subtotal + tax;
        gcashModalAmount.textContent = '₱' + total.toFixed(2);
        
        // Show modal
        gcashModal.classList.add('active');
        document.body.style.overflow = 'hidden';
    });

    // Close GCash Modal
    closeGcashModalBtn.addEventListener('click', function() {
        gcashModal.classList.remove('active');
        document.body.style.overflow = '';
    });

    // Close on backdrop click
    gcashModal.addEventListener('click', function(e) {
        if (e.target === gcashModal) {
            gcashModal.classList.remove('active');
            document.body.style.overflow = '';
        }
    });

    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && gcashModal.classList.contains('active')) {
            gcashModal.classList.remove('active');
            document.body.style.overflow = '';
        }
    });

    // Confirm GCash Payment
    confirmGcashPaymentBtn.addEventListener('click', function() {
        const customerInfo = validateCustomerInfo();
        if (!customerInfo) {
            gcashModal.classList.remove('active');
            document.body.style.overflow = '';
            return;
        }
        
        const cart = getCart();
        if (cart.length === 0) {
            showPaymentError('Your cart is empty.');
            gcashModal.classList.remove('active');
            document.body.style.overflow = '';
            return;
        }
        
        // Close modal and show processing
        gcashModal.classList.remove('active');
        document.body.style.overflow = '';
        showProcessingOverlay();
        
        // Process GCash order
        fetch('includes/gcash_process_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                customerInfo: customerInfo,
                cart: cart
            })
        })
        .then(response => response.json())
        .then(data => {
            hideProcessingOverlay();
            
            if (data.error) {
                showPaymentError(data.error);
                return;
            }
            
            // Payment successful - redirect to success page
            console.log('GCash order placed successfully:', data);
            
            // Clear the cart
            clearCart();
            
            // Redirect to order success page
            window.location.href = 'index.php?page=order-success';
        })
        .catch(error => {
            hideProcessingOverlay();
            console.error('GCash order error:', error);
            showPaymentError('An error occurred while processing your order. Please try again.');
        });
    });
}
</script>

