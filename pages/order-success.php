<?php
// Get order details from session
$lastOrder = $_SESSION['last_order'] ?? null;
$orderNumber = $lastOrder['order_number'] ?? strtoupper(substr(md5(time()), 0, 8));
$orderTotal = $lastOrder['total'] ?? 0;
$paymentMethod = $lastOrder['payment_method'] ?? 'unknown';
$paypalCaptureId = $lastOrder['paypal_capture_id'] ?? null;
?>

<div class="success-container">
    <div class="success-card">
        <div class="success-icon">
            <i class="bi bi-check-circle-fill"></i>
        </div>
        <h1>Order Confirmed!</h1>
        <p class="order-number">Order #<?php echo htmlspecialchars($orderNumber); ?></p>
        <p class="success-message">Thank you for your order! We've received your order and will begin preparing your delicious treats right away.</p>
        
        <?php if ($orderTotal > 0): ?>
        <div class="order-total-display">
            <span class="label">Total Paid</span>
            <span class="amount">$<?php echo number_format($orderTotal, 2); ?></span>
        </div>
        <?php endif; ?>
        
        <div class="order-details">
            <?php if ($paypalCaptureId): ?>
            <div class="detail-item payment-confirmed">
                <i class="bi bi-paypal"></i>
                <div>
                    <strong>Payment Confirmed</strong>
                    <span>PayPal Transaction: <?php echo htmlspecialchars(substr($paypalCaptureId, 0, 17)); ?>...</span>
                </div>
            </div>
            <?php endif; ?>
            <div class="detail-item">
                <i class="bi bi-clock"></i>
                <div>
                    <strong>Estimated Ready Time</strong>
                    <span>30-45 minutes</span>
                </div>
            </div>
            <div class="detail-item">
                <i class="bi bi-envelope"></i>
                <div>
                    <strong>Confirmation Email</strong>
                    <span>Sent to your email</span>
                </div>
            </div>
            <div class="detail-item">
                <i class="bi bi-shop"></i>
                <div>
                    <strong>Pickup Location</strong>
                    <span>PUP Sto. Tomas Branch</span>
                </div>
            </div>
        </div>
        
        <div class="success-actions">
            <a href="index.php?page=orders" class="btn btn-hero btn-hero-primary">
                <i class="bi bi-list-ul me-2"></i> View My Orders
            </a>
            <a href="index.php?page=menu" class="btn btn-hero btn-hero-outline">
                Continue Shopping
            </a>
        </div>
    </div>
</div>

<style>
.success-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    background: var(--cream);
}

.success-card {
    background: var(--white);
    padding: 4rem;
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-lg);
    text-align: center;
    max-width: 550px;
}

.success-icon {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, #28a745, #20c997);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 2rem;
    animation: scaleIn 0.5s ease-out;
}

.success-icon i {
    font-size: 3rem;
    color: var(--white);
}

@keyframes scaleIn {
    from { transform: scale(0); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

.success-card h1 {
    color: var(--dark);
    margin-bottom: 0.5rem;
}

.order-number {
    color: var(--primary-dark);
    font-weight: 600;
    font-size: 1.1rem;
    margin-bottom: 1rem;
}

.success-message {
    color: var(--text-secondary);
    margin-bottom: 1.5rem;
}

.order-total-display {
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    color: white;
    padding: 1rem 2rem;
    border-radius: var(--radius-lg);
    margin-bottom: 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.order-total-display .label {
    font-size: 0.9rem;
    opacity: 0.9;
}

.order-total-display .amount {
    font-size: 1.5rem;
    font-weight: 700;
}

.order-details {
    background: var(--accent);
    padding: 1.5rem;
    border-radius: var(--radius-md);
    margin-bottom: 2rem;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    text-align: left;
}

.detail-item + .detail-item {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid rgba(212, 165, 116, 0.3);
}

.detail-item i {
    font-size: 1.5rem;
    color: var(--primary-dark);
    min-width: 30px;
    text-align: center;
}

.detail-item.payment-confirmed i {
    color: #003087; /* PayPal blue */
}

.detail-item strong {
    display: block;
    color: var(--dark);
}

.detail-item span {
    font-size: 0.9rem;
    color: var(--text-secondary);
}

.success-actions {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.success-actions .btn-hero-outline {
    border: 2px solid var(--primary);
    color: var(--primary-dark);
}

.success-actions .btn-hero-outline:hover {
    background: var(--accent);
}

@media (max-width: 576px) {
    .success-card {
        padding: 2rem;
    }
    
    .order-total-display {
        flex-direction: column;
        gap: 0.5rem;
    }
}
</style>

<script>
// Clear cart after successful order
document.addEventListener('DOMContentLoaded', function() {
    clearCart();
});
</script>

