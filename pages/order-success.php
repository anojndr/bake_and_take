<div class="success-container">
    <div class="success-card">
        <div class="success-icon">
            <i class="bi bi-check-circle-fill"></i>
        </div>
        <h1>Order Confirmed!</h1>
        <p class="order-number">Order #<?php echo strtoupper(substr(md5(time()), 0, 8)); ?></p>
        <p class="success-message">Thank you for your order! We've received your order and will begin preparing your delicious treats right away.</p>
        
        <div class="order-details">
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
        </div>
        
        <div class="success-actions">
            <a href="index.php" class="btn btn-hero btn-hero-primary">
                <i class="bi bi-house me-2"></i> Back to Home
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
    max-width: 500px;
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
    margin-bottom: 2rem;
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
</style>

<script>
// Clear cart after successful order
document.addEventListener('DOMContentLoaded', function() {
    clearCart();
});
</script>
