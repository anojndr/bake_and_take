<?php
// Get confirmed order data from session
$confirmedOrder = $_SESSION['order_confirmed'] ?? null;

// Clear the session data after reading
if ($confirmedOrder) {
    unset($_SESSION['order_confirmed']);
}
?>

<!-- Page Header -->
<header class="page-header">
    <div class="container">
        <h1>Order Confirmed</h1>
        <div class="breadcrumb-custom">
            <a href="index.php">Home</a>
            <span>/</span>
            <span>Order Confirmed</span>
        </div>
    </div>
</header>

<!-- Confirmation Section -->
<section class="section confirmation-section">
    <div class="container">
        <div class="confirmation-card text-center">
            <div class="confirmation-icon">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <h2>Thank You!</h2>
            <p class="lead">Your order has been confirmed successfully.</p>
            
            <?php if ($confirmedOrder): ?>
            <div class="order-details mt-4">
                <div class="detail-item">
                    <span class="label">Order Number</span>
                    <span class="value">#<?php echo htmlspecialchars($confirmedOrder['order_number']); ?></span>
                </div>
                <div class="detail-item">
                    <span class="label">Total Amount</span>
                    <span class="value">₱<?php echo number_format($confirmedOrder['total'], 2); ?></span>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="confirmation-message mt-4">
                <p>We're now preparing your order! You'll receive a notification when it's ready for pickup.</p>
            </div>
            
            <div class="confirmation-actions mt-4">
                <a href="index.php?page=menu" class="btn btn-primary">Continue Shopping</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                <a href="index.php?page=orders" class="btn btn-outline-primary">View My Orders</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<style>
.confirmation-section {
    background: var(--cream);
    min-height: 60vh;
    display: flex;
    align-items: center;
}

.confirmation-card {
    background: var(--white);
    padding: 3rem;
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-lg);
    max-width: 600px;
    margin: 0 auto;
}

.confirmation-icon {
    font-size: 5rem;
    color: #28a745;
    margin-bottom: 1.5rem;
    animation: bounceIn 0.6s ease;
}

@keyframes bounceIn {
    0% { transform: scale(0); opacity: 0; }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); opacity: 1; }
}

.confirmation-card h2 {
    color: var(--dark);
    margin-bottom: 0.5rem;
}

.confirmation-card .lead {
    color: var(--text-secondary);
    font-size: 1.1rem;
}

.order-details {
    background: var(--accent);
    padding: 1.5rem;
    border-radius: var(--radius-md);
    display: inline-block;
}

.detail-item {
    display: flex;
    justify-content: space-between;
    gap: 2rem;
    margin-bottom: 0.5rem;
}

.detail-item:last-child {
    margin-bottom: 0;
}

.detail-item .label {
    color: var(--text-secondary);
}

.detail-item .value {
    font-weight: 600;
    color: var(--dark);
}

.confirmation-message {
    color: var(--text-secondary);
}

.confirmation-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}
</style>
