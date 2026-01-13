<?php
/**
 * Email Confirmation Success Page
 * This page is shown after a user confirms their order via email link
 */

// Get confirmed order data from session
$confirmedOrder = $_SESSION['email_order_confirmed'] ?? null;

// Clear the session data after reading
if ($confirmedOrder) {
    unset($_SESSION['email_order_confirmed']);
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
<section class="section email-confirmation-section">
    <div class="container">
        <div class="confirmation-card text-center">
            <div class="confirmation-icon">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <h2>Order Confirmed!</h2>
            <p class="lead">Your order has been successfully confirmed.</p>
            
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
                <p>Thank you for confirming your order! We're now preparing it for you.</p>
                <p>You'll receive a notification when your order is ready for pickup.</p>
            </div>
            
            <div class="confirmation-actions mt-4">
                <?php if (isset($_SESSION['user_id'])): ?>
                <a href="index.php?page=orders" class="btn btn-primary btn-lg">
                    <i class="bi bi-bag-check me-2"></i>View My Orders
                </a>
                <?php else: ?>
                <a href="index.php?page=login" class="btn btn-primary btn-lg">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Login to View Orders
                </a>
                <?php endif; ?>
                <a href="index.php?page=menu" class="btn btn-outline-primary btn-lg">
                    <i class="bi bi-shop me-2"></i>Continue Shopping
                </a>
            </div>
        </div>
    </div>
</section>

<style>
.email-confirmation-section {
    background: linear-gradient(135deg, var(--cream) 0%, #fff8f0 100%);
    min-height: 70vh;
    display: flex;
    align-items: center;
    padding: 4rem 0;
}

.confirmation-card {
    background: var(--white);
    padding: 3rem 2rem;
    border-radius: var(--radius-xl);
    box-shadow: 0 20px 60px rgba(139, 69, 19, 0.15);
    max-width: 550px;
    margin: 0 auto;
    border: 1px solid rgba(139, 69, 19, 0.1);
}

.confirmation-icon {
    font-size: 5rem;
    color: #28a745;
    margin-bottom: 1.5rem;
    animation: bounceIn 0.6s ease, pulse 2s ease-in-out infinite;
}

@keyframes bounceIn {
    0% { transform: scale(0); opacity: 0; }
    50% { transform: scale(1.2); }
    100% { transform: scale(1); opacity: 1; }
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.confirmation-card h2 {
    color: var(--dark);
    margin-bottom: 0.5rem;
    font-size: 2rem;
}

.confirmation-card .lead {
    color: var(--text-secondary);
    font-size: 1.1rem;
}

.order-details {
    background: linear-gradient(135deg, var(--accent) 0%, #fff5e6 100%);
    padding: 1.5rem 2rem;
    border-radius: var(--radius-md);
    display: inline-block;
    border: 1px solid rgba(139, 69, 19, 0.1);
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
    line-height: 1.7;
}

.confirmation-message p {
    margin-bottom: 0.5rem;
}

.confirmation-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
    margin-top: 2rem;
}

.confirmation-actions .btn {
    padding: 0.875rem 1.75rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    transition: all 0.3s ease;
}

.confirmation-actions .btn-primary {
    background: linear-gradient(135deg, var(--primary) 0%, #6d3610 100%);
    border: none;
    box-shadow: 0 4px 15px rgba(139, 69, 19, 0.3);
}

.confirmation-actions .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(139, 69, 19, 0.4);
}

.confirmation-actions .btn-outline-primary:hover {
    transform: translateY(-2px);
}

@media (max-width: 576px) {
    .confirmation-card {
        padding: 2rem 1.5rem;
        margin: 0 1rem;
    }
    
    .confirmation-icon {
        font-size: 4rem;
    }
    
    .confirmation-card h2 {
        font-size: 1.5rem;
    }
    
    .confirmation-actions {
        flex-direction: column;
    }
    
    .confirmation-actions .btn {
        width: 100%;
        justify-content: center;
    }
    
    .order-details {
        width: 100%;
        padding: 1rem;
    }
    
    .detail-item {
        gap: 1rem;
    }
}
</style>
