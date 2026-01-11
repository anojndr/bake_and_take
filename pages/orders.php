<?php
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?page=login');
    exit;
}

// Fetch user's orders from database
$orders = [];
if ($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT o.*, 
                   GROUP_CONCAT(CONCAT(oi.product_name, ' x', oi.quantity) SEPARATOR ', ') as items_summary
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            WHERE o.user_id = ?
            GROUP BY o.id
            ORDER BY o.created_at DESC
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $orders = $stmt->fetchAll();
    } catch (PDOException $e) {
        // Handle error silently
    }
}
?>

<!-- Page Header -->
<header class="page-header">
    <div class="container">
        <h1>My Orders</h1>
        <div class="breadcrumb-custom">
            <a href="index.php">Home</a>
            <span>/</span>
            <span>My Orders</span>
        </div>
    </div>
</header>

<!-- Orders Section -->
<section class="section orders-section">
    <div class="container">
        <?php if (empty($orders)): ?>
            <!-- Empty State -->
            <div class="empty-orders">
                <div class="empty-orders-icon">
                    <i class="bi bi-bag-x"></i>
                </div>
                <h3>No Orders Yet</h3>
                <p>Looks like you haven't placed any orders yet. Start exploring our delicious menu!</p>
                <a href="index.php?page=menu" class="btn btn-hero btn-hero-primary">
                    <i class="bi bi-basket me-2"></i>Browse Menu
                </a>
            </div>
        <?php else: ?>
            <!-- Orders List -->
            <div class="orders-container">
                <div class="orders-header">
                    <h3><i class="bi bi-receipt me-2"></i>Order History</h3>
                    <span class="orders-count"><?php echo count($orders); ?> order<?php echo count($orders) !== 1 ? 's' : ''; ?></span>
                </div>
                
                <div class="orders-list">
                    <?php foreach ($orders as $order): ?>
                        <div class="order-card" data-order-id="<?php echo $order['id']; ?>">
                            <div class="order-card-header">
                                <div class="order-info">
                                    <span class="order-number">#<?php echo htmlspecialchars($order['order_number']); ?></span>
                                    <span class="order-date">
                                        <i class="bi bi-calendar3 me-1"></i>
                                        <?php echo date('M j, Y \a\t g:i A', strtotime($order['created_at'])); ?>
                                    </span>
                                </div>
                                <div class="order-status status-<?php echo strtolower($order['status']); ?>">
                                    <?php
                                    $statusIcons = [
                                        'confirmed' => 'bi-check-circle',
                                        'preparing' => 'bi-fire',
                                        'ready' => 'bi-box-seam',
                                        'delivered' => 'bi-check-all',
                                        'cancelled' => 'bi-x-circle'
                                    ];
                                    $statusLabels = [
                                        'confirmed' => 'Confirmed',
                                        'preparing' => 'Preparing',
                                        'ready' => 'Ready',
                                        'delivered' => 'Picked Up',
                                        'cancelled' => 'Cancelled'
                                    ];
                                    $icon = $statusIcons[$order['status']] ?? 'bi-circle';
                                    ?>
                                    <i class="bi <?php echo $icon; ?> me-1"></i>
                                    <?php echo $statusLabels[$order['status']] ?? ucfirst($order['status']); ?>
                                </div>
                            </div>
                            
                            <div class="order-card-body">
                                <div class="order-items-preview">
                                    <i class="bi bi-bag me-2"></i>
                                    <span><?php echo htmlspecialchars($order['items_summary'] ?? 'Order items'); ?></span>
                                </div>
                                
                                <div class="order-meta">
                                    <div class="order-delivery">
                                        <i class="bi bi-<?php echo $order['delivery_method'] === 'delivery' ? 'truck' : 'shop'; ?> me-1"></i>
                                        <?php echo $order['delivery_method'] === 'delivery' ? 'Home Delivery' : 'Store Pickup'; ?>
                                    </div>
                                    <div class="order-payment">
                                        <?php 
                                        $paymentMethod = $order['payment_method'] ?? 'paypal';
                                        $paymentIcons = [
                                            'paypal' => 'bi-paypal'
                                        ];
                                        $paymentLabels = [
                                            'paypal' => 'PayPal'
                                        ];
                                        $icon = $paymentIcons[$paymentMethod] ?? 'bi-credit-card';
                                        $label = $paymentLabels[$paymentMethod] ?? ucfirst($paymentMethod);
                                        ?>
                                        <i class="bi <?php echo $icon; ?> me-1"></i>
                                        <?php echo $label; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="order-card-footer">
                                <div class="order-total">
                                    <span class="total-label">Total</span>
                                    <span class="total-amount">₱<?php echo number_format($order['total'], 2); ?></span>
                                </div>
                                <button class="btn btn-view-details" onclick="viewOrderDetails(<?php echo $order['id']; ?>)">
                                    View Details <i class="bi bi-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content order-modal-content">
            <div class="modal-header order-modal-header">
                <h5 class="modal-title"><i class="bi bi-receipt me-2"></i>Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="orderDetailsBody">
                <!-- Loaded dynamically -->
            </div>
        </div>
    </div>
</div>

<style>
.orders-section {
    background: var(--cream);
    min-height: 60vh;
}

/* Empty State */
.empty-orders {
    text-align: center;
    padding: 4rem 2rem;
    background: var(--white);
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-md);
    max-width: 500px;
    margin: 0 auto;
}

.empty-orders-icon {
    width: 120px;
    height: 120px;
    background: linear-gradient(135deg, var(--cream) 0%, var(--cream-dark) 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 2rem;
    animation: pulse 2s infinite;
}

.empty-orders-icon i {
    font-size: 3.5rem;
    color: var(--primary-dark);
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

.empty-orders h3 {
    color: var(--dark);
    margin-bottom: 0.75rem;
    font-family: var(--font-display);
}

.empty-orders p {
    color: var(--text-secondary);
    margin-bottom: 2rem;
}

/* Orders Container */
.orders-container {
    max-width: 900px;
    margin: 0 auto;
}

.orders-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.orders-header h3 {
    color: var(--dark);
    margin: 0;
    font-family: var(--font-display);
}

.orders-count {
    background: var(--accent);
    color: var(--primary-dark);
    padding: 0.4rem 1rem;
    border-radius: 20px;
    font-weight: 500;
    font-size: 0.9rem;
}

/* Order Card */
.orders-list {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.order-card {
    background: var(--white);
    border-radius: var(--radius-lg);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
    transition: var(--transition);
    border: 2px solid transparent;
}

.order-card:hover {
    box-shadow: var(--shadow-md);
    border-color: var(--accent);
    transform: translateY(-2px);
}

.order-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem 1.5rem;
    background: linear-gradient(135deg, var(--cream) 0%, rgba(212, 165, 116, 0.1) 100%);
    border-bottom: 1px solid var(--cream-dark);
}

.order-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.order-number {
    font-family: var(--font-display);
    font-weight: 600;
    color: var(--dark);
    font-size: 1.1rem;
}

.order-date {
    font-size: 0.85rem;
    color: var(--text-secondary);
}

/* Status Badges */
.order-status {
    display: inline-flex;
    align-items: center;
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-weight: 500;
    font-size: 0.85rem;
}

.status-pending {
    background: rgba(255, 193, 7, 0.15);
    color: #856404;
}

.status-confirmed {
    background: rgba(13, 110, 253, 0.15);
    color: #084298;
}

.status-preparing {
    background: rgba(255, 107, 53, 0.15);
    color: #d63f00;
}

.status-ready {
    background: rgba(25, 135, 84, 0.15);
    color: #0a3622;
}

.status-delivered {
    background: rgba(25, 135, 84, 0.2);
    color: #198754;
}

.status-cancelled {
    background: rgba(220, 53, 69, 0.15);
    color: #842029;
}

.order-card-body {
    padding: 1.25rem 1.5rem;
}

.order-items-preview {
    color: var(--text-secondary);
    font-size: 0.95rem;
    margin-bottom: 0.75rem;
    display: flex;
    align-items: flex-start;
}

.order-items-preview i {
    color: var(--primary);
    margin-top: 0.1rem;
}

.order-items-preview span {
    line-height: 1.5;
}

.order-meta {
    display: flex;
    gap: 1.5rem;
}

.order-delivery {
    color: var(--text-light);
    font-size: 0.9rem;
}

.order-payment {
    color: var(--text-light);
    font-size: 0.9rem;
}

.order-payment .bi-paypal {
    color: #003087;
}

.order-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.25rem 1.5rem;
    border-top: 1px solid var(--cream-dark);
    background: rgba(247, 242, 232, 0.5);
}

.order-total {
    display: flex;
    flex-direction: column;
}

.total-label {
    font-size: 0.8rem;
    color: var(--text-light);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.total-amount {
    font-family: var(--font-display);
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--secondary);
}

.btn-view-details {
    background: transparent;
    border: 2px solid var(--primary);
    color: var(--primary-dark);
    padding: 0.6rem 1.25rem;
    border-radius: var(--radius-md);
    font-weight: 500;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-view-details:hover {
    background: var(--primary);
    color: var(--white);
}

/* Modal Styles */
.order-modal-content {
    border: none;
    border-radius: var(--radius-xl);
    overflow: hidden;
}

.order-modal-header {
    background: linear-gradient(135deg, var(--cream) 0%, var(--accent) 100%);
    border-bottom: none;
    padding: 1.5rem;
}

.order-modal-header .modal-title {
    color: var(--dark);
    font-family: var(--font-display);
    font-weight: 600;
}

.modal-body {
    padding: 2rem;
}

/* Order Details in Modal */
.order-details-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.detail-block {
    padding: 1.25rem;
    background: var(--cream);
    border-radius: var(--radius-md);
}

.detail-block h6 {
    color: var(--primary-dark);
    margin-bottom: 0.75rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.detail-block p {
    margin: 0;
    color: var(--text-secondary);
    line-height: 1.6;
}

.order-items-list {
    margin-bottom: 2rem;
}

.order-items-list h6 {
    margin-bottom: 1rem;
    color: var(--dark);
}

.modal-order-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--cream);
    border-radius: var(--radius-md);
    margin-bottom: 0.75rem;
}

.modal-order-item:last-child {
    margin-bottom: 0;
}

.modal-item-details {
    flex: 1;
}

.modal-item-name {
    font-weight: 500;
    color: var(--dark);
}

.modal-item-qty {
    font-size: 0.85rem;
    color: var(--text-light);
}

.modal-item-price {
    font-weight: 600;
    color: var(--secondary);
}

.order-totals {
    background: var(--cream);
    padding: 1.5rem;
    border-radius: var(--radius-md);
}

.totals-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.75rem;
    color: var(--text-secondary);
}

.totals-row:last-child {
    margin-bottom: 0;
    padding-top: 0.75rem;
    margin-top: 0.5rem;
    border-top: 2px solid rgba(212, 165, 116, 0.3);
    font-weight: 600;
    color: var(--dark);
    font-size: 1.1rem;
}

.totals-row:last-child span:last-child {
    color: var(--secondary);
    font-size: 1.25rem;
}

/* Loading Spinner */
.order-loading {
    text-align: center;
    padding: 3rem;
    color: var(--text-light);
}

.order-loading i {
    font-size: 2rem;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 767px) {
    .order-card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .order-card-footer {
        flex-direction: column;
        gap: 1rem;
    }
    
    .btn-view-details {
        width: 100%;
        justify-content: center;
    }
    
    .order-details-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
const ordersData = <?php echo json_encode($orders); ?>;

async function viewOrderDetails(orderId) {
    const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
    const modalBody = document.getElementById('orderDetailsBody');
    
    // Show loading
    modalBody.innerHTML = `
        <div class="order-loading">
            <i class="bi bi-arrow-repeat"></i>
            <p class="mt-2">Loading order details...</p>
        </div>
    `;
    modal.show();
    
    // Find order in our data
    const order = ordersData.find(o => o.id == orderId);
    
    if (!order) {
        modalBody.innerHTML = '<p class="text-center text-muted">Order not found.</p>';
        return;
    }
    
    // Fetch order items via AJAX (or use embedded data)
    // For simplicity, we'll display what we have
    const statusClass = 'status-' + order.status.toLowerCase();
    const statusIcons = {
        'confirmed': 'bi-check-circle',
        'preparing': 'bi-fire',
        'ready': 'bi-box-seam',
        'delivered': 'bi-check-all',
        'cancelled': 'bi-x-circle'
    };
    const statusLabels = {
        'confirmed': 'Confirmed',
        'preparing': 'Preparing',
        'ready': 'Ready',
        'delivered': 'Picked Up',
        'cancelled': 'Cancelled'
    };
    
    modalBody.innerHTML = `
        <div class="order-details-grid">
            <div class="detail-block">
                <h6><i class="bi bi-receipt"></i> Order Info</h6>
                <p>
                    <strong>Order #:</strong> ${order.order_number}<br>
                    <strong>Date:</strong> ${new Date(order.created_at).toLocaleString()}<br>
                    <strong>Status:</strong> <span class="order-status ${statusClass}"><i class="bi ${statusIcons[order.status] || 'bi-circle'} me-1"></i>${statusLabels[order.status] || order.status.charAt(0).toUpperCase() + order.status.slice(1)}</span>
                </p>
            </div>
            <div class="detail-block">
                <h6><i class="bi bi-person"></i> Contact</h6>
                <p>
                    ${order.first_name} ${order.last_name}<br>
                    ${order.email}<br>
                    ${order.phone}
                </p>
            </div>
            <div class="detail-block">
                <h6><i class="bi bi-${order.delivery_method === 'delivery' ? 'truck' : 'shop'}"></i> ${order.delivery_method === 'delivery' ? 'Delivery Address' : 'Pickup Location'}</h6>
                <p>
                    ${order.delivery_method === 'delivery' 
                        ? `${order.address || ''}<br>${order.city || ''}${order.state ? ', ' + order.state : ''} ${order.zip || ''}`
                        : 'PUP Sto. Tomas, Batangas'}
                </p>
            </div>
            <div class="detail-block">
                <h6><i class="bi bi-wallet2"></i> Payment Method</h6>
                <p>
                    ${(() => {
                        const paymentMethod = order.payment_method || 'paypal';
                        const paymentIcons = {
                            'paypal': 'bi-paypal'
                        };
                        const paymentLabels = {
                            'paypal': 'PayPal'
                        };
                        const paymentColors = {
                            'paypal': '#003087'
                        };
                        const icon = paymentIcons[paymentMethod] || 'bi-credit-card';
                        const label = paymentLabels[paymentMethod] || paymentMethod.charAt(0).toUpperCase() + paymentMethod.slice(1);
                        const color = paymentColors[paymentMethod] || '#6c757d';
                        return `<i class="bi ${icon}" style="color: ${color}; margin-right: 0.5rem;"></i>${label}`;
                    })()}
                    ${order.instructions ? `<br><small class="text-muted">Note: ${order.instructions}</small>` : ''}
                </p>
            </div>
        </div>
        
        <div class="order-items-list">
            <h6><i class="bi bi-bag me-2"></i>Order Items</h6>
            <p class="text-muted">${order.items_summary || 'Order items'}</p>
        </div>
        
        <div class="order-totals">
            <div class="totals-row">
                <span>Subtotal</span>
                <span>₱${parseFloat(order.subtotal).toFixed(2)}</span>
            </div>
            <div class="totals-row">
                <span>Delivery Fee</span>
                <span>${parseFloat(order.delivery_fee) > 0 ? '₱' + parseFloat(order.delivery_fee).toFixed(2) : 'Free'}</span>
            </div>
            <div class="totals-row">
                <span>Tax</span>
                <span>₱${parseFloat(order.tax).toFixed(2)}</span>
            </div>
            <div class="totals-row">
                <span>Total</span>
                <span>₱${parseFloat(order.total).toFixed(2)}</span>
            </div>
        </div>
    `;
}
</script>
