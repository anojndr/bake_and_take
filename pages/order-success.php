<?php
// Get order details from session
$lastOrder = $_SESSION['last_order'] ?? null;
$orderNumber = $lastOrder['order_number'] ?? strtoupper(substr(md5(time()), 0, 8));
$orderTotal = $lastOrder['total'] ?? 0;
$paypalCaptureId = $lastOrder['paypal_capture_id'] ?? null;
$confirmationMethod = $lastOrder['confirmation_method'] ?? 'sms';
?>

<div class="success-container">
    <div class="success-card">
        <div class="success-icon pending">
            <i class="bi bi-hourglass-split"></i>
        </div>
        <h1>Order Placed!</h1>
        <p class="order-number">Order #<?php echo htmlspecialchars($orderNumber); ?></p>
        
        <div class="confirmation-pending-notice">
            <i class="bi bi-exclamation-circle"></i>
            <div>
                <strong>Action Required: Confirm Your Order</strong>
                <?php if ($confirmationMethod === 'sms'): ?>
                <p>We've sent you an SMS. Please reply <strong>CONFIRM</strong> to complete your order.</p>
                <p class="waiting-status"><i class="bi bi-arrow-repeat spinning"></i> Waiting for your confirmation...</p>
                <?php else: ?>
                <p>We've sent you an email. Please click the confirmation link to complete your order.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <p class="success-message">Thank you for your order! Your payment has been received. Once you confirm, we'll begin preparing your delicious treats.</p>
        
        <?php if ($orderTotal > 0): ?>
        <div class="order-total-display">
            <span class="label">Total Paid</span>
            <span class="amount">₱<?php echo number_format($orderTotal, 2); ?></span>
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
                <i class="bi bi-<?php echo $confirmationMethod === 'sms' ? 'phone' : 'envelope'; ?>"></i>
                <div>
                    <strong>Confirmation Method</strong>
                    <span><?php echo $confirmationMethod === 'sms' ? 'Reply CONFIRM to SMS' : 'Click link in email'; ?></span>
                </div>
            </div>
            <div class="detail-item">
                <i class="bi bi-clock"></i>
                <div>
                    <strong>Estimated Ready Time</strong>
                    <span>30-45 minutes after confirmation</span>
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

.success-icon.pending {
    background: linear-gradient(135deg, #ffc107, #fd7e14);
}

.confirmation-pending-notice {
    background: #fff3cd;
    border: 1px solid #ffc107;
    border-radius: var(--radius-md);
    padding: 1.25rem;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    text-align: left;
}

.confirmation-pending-notice i {
    font-size: 1.5rem;
    color: #856404;
    flex-shrink: 0;
}

.confirmation-pending-notice strong {
    color: #856404;
    display: block;
    margin-bottom: 0.25rem;
}

.confirmation-pending-notice p {
    margin: 0;
    color: #856404;
    font-size: 0.9rem;
}

.confirmation-pending-notice .waiting-status {
    margin-top: 0.5rem;
    font-style: italic;
    opacity: 0.8;
}

.spinning {
    display: inline-block;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
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
    
    // Start polling for order status if order is pending confirmation
    <?php if ($lastOrder && $confirmationMethod === 'sms'): ?>
    const orderNumber = '<?php echo htmlspecialchars($orderNumber); ?>';
    const confirmationToken = '<?php echo htmlspecialchars($lastOrder['confirmation_token'] ?? ''); ?>';
    let pollInterval;
    let pollCount = 0;
    const maxPolls = 360; // Poll for up to 30 minutes (5 second intervals)
    
    function checkOrderStatus() {
        pollCount++;
        
        // Stop polling after max attempts
        if (pollCount > maxPolls) {
            clearInterval(pollInterval);
            console.log('Stopped polling after max attempts');
            return;
        }
        
        fetch('includes/check_order_status.php?order_number=' + encodeURIComponent(orderNumber))
            .then(response => response.json())
            .then(data => {
                if (data.success && data.status === 'confirmed') {
                    // Order confirmed! Redirect to confirmation page
                    clearInterval(pollInterval);
                    
                    // Redirect with token for order details
                    const redirectUrl = 'index.php?page=order-confirmed' + 
                        (confirmationToken ? '&token=' + encodeURIComponent(confirmationToken) : '');
                    
                    window.location.href = redirectUrl;
                }
            })
            .catch(error => {
                console.log('Status check error:', error);
            });
    }
    
    // Start polling every 5 seconds
    pollInterval = setInterval(checkOrderStatus, 5000);
    
    // Also check immediately
    setTimeout(checkOrderStatus, 1000);
    
    // Clean up on page unload
    window.addEventListener('beforeunload', function() {
        if (pollInterval) clearInterval(pollInterval);
    });
    <?php endif; ?>
});
</script>
