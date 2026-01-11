<?php
/**
 * Admin Dashboard - Main Overview
 */

// Get dashboard statistics from database
$totalOrders = 0;
$totalRevenue = 0;
$totalUsers = 0;
$totalProducts = 0;
$recentOrders = [];
$pendingOrders = 0;

if ($pdo) {
    try {
        // Total orders
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders");
        $totalOrders = $stmt->fetch()['count'];
        
        // Total revenue
        $stmt = $pdo->query("SELECT COALESCE(SUM(total), 0) as revenue FROM orders WHERE status != 'cancelled'");
        $totalRevenue = $stmt->fetch()['revenue'];
        
        // Total users
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE is_admin = 0");
        $totalUsers = $stmt->fetch()['count'];
        
        // Total products
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM products WHERE active = 1");
        $totalProducts = $stmt->fetch()['count'];
        
        // Pending orders
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
        $pendingOrders = $stmt->fetch()['count'];
        
        // Recent orders
        $stmt = $pdo->query("
            SELECT o.*, 
                   CONCAT(o.first_name, ' ', o.last_name) as customer_name,
                   (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
            FROM orders o 
            ORDER BY o.created_at DESC 
            LIMIT 5
        ");
        $recentOrders = $stmt->fetchAll();
        
    } catch (PDOException $e) {
        // Handle error silently
    }
}

$statusLabels = [
    'pending' => 'Pending',
    'confirmed' => 'Confirmed',
    'preparing' => 'Preparing',
    'ready' => 'Ready',
    'delivered' => 'Picked Up',
    'cancelled' => 'Cancelled'
];
?>

<div class="page-header">
    <h1 class="page-title">Dashboard</h1>
    <p class="page-subtitle">Welcome back! Here's what's happening with your bakery.</p>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card info">
        <div class="stat-header">
            <div class="stat-icon">
                <i class="bi bi-receipt"></i>
            </div>
            <span class="stat-trend up">
                <i class="bi bi-arrow-up"></i> 12%
            </span>
        </div>
        <div class="stat-value"><?php echo number_format($totalOrders); ?></div>
        <div class="stat-label">Total Orders</div>
    </div>
    
    <div class="stat-card success">
        <div class="stat-header">
            <div class="stat-icon">
                <i class="bi bi-currency-dollar"></i>
            </div>
            <span class="stat-trend up">
                <i class="bi bi-arrow-up"></i> 8%
            </span>
        </div>
        <div class="stat-value"><?php echo formatPrice($totalRevenue); ?></div>
        <div class="stat-label">Total Revenue</div>
    </div>
    
    <div class="stat-card warning">
        <div class="stat-header">
            <div class="stat-icon">
                <i class="bi bi-people"></i>
            </div>
            <span class="stat-trend up">
                <i class="bi bi-arrow-up"></i> 24%
            </span>
        </div>
        <div class="stat-value"><?php echo number_format($totalUsers); ?></div>
        <div class="stat-label">Registered Users</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-header">
            <div class="stat-icon">
                <i class="bi bi-box-seam"></i>
            </div>
            <?php if ($pendingOrders > 0): ?>
            <span class="stat-trend down">
                <i class="bi bi-clock"></i> <?php echo $pendingOrders; ?> pending
            </span>
            <?php endif; ?>
        </div>
        <div class="stat-value"><?php echo number_format($totalProducts); ?></div>
        <div class="stat-label">Active Products</div>
    </div>
</div>

<!-- Content Row -->
<div class="row g-4">
    <!-- Recent Orders -->
    <div class="col-lg-8">
        <div class="admin-card">
            <div class="admin-card-header">
                <h3 class="admin-card-title">Recent Orders</h3>
                <a href="index.php?page=orders" class="btn-admin-secondary btn-sm">
                    View All <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="admin-card-body p-0">
                <?php if (empty($recentOrders)): ?>
                <div class="empty-state">
                    <i class="bi bi-receipt"></i>
                    <h3>No orders yet</h3>
                    <p>When customers place orders, they'll appear here.</p>
                </div>
                <?php else: ?>
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td>
                                <strong>#<?php echo sanitize($order['order_number']); ?></strong>
                            </td>
                            <td><?php echo sanitize($order['customer_name']); ?></td>
                            <td><?php echo $order['item_count']; ?> items</td>
                            <td><strong><?php echo formatPrice($order['total']); ?></strong></td>
                            <td>
                                <span class="status-badge <?php echo $order['status']; ?>">
                                    <?php echo $statusLabels[$order['status']] ?? ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="col-lg-4">
        <div class="admin-card">
            <div class="admin-card-header">
                <h3 class="admin-card-title">Quick Actions</h3>
            </div>
            <div class="admin-card-body">
                <div class="quick-actions-grid" style="grid-template-columns: repeat(2, 1fr);">
                    <a href="index.php?page=products" class="quick-action-btn">
                        <i class="bi bi-plus-circle"></i>
                        <span>Add Product</span>
                    </a>
                    <a href="index.php?page=orders" class="quick-action-btn">
                        <i class="bi bi-list-check"></i>
                        <span>View Orders</span>
                    </a>
                    <a href="index.php?page=messages" class="quick-action-btn">
                        <i class="bi bi-envelope"></i>
                        <span>Messages</span>
                    </a>
                    <a href="index.php?page=users" class="quick-action-btn">
                        <i class="bi bi-person-plus"></i>
                        <span>Manage Users</span>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Pending Orders Alert -->
        <?php if ($pendingOrders > 0): ?>
        <div class="admin-card mt-4" style="border-left: 4px solid var(--admin-warning);">
            <div class="admin-card-body">
                <div class="d-flex align-items-center gap-3">
                    <div class="stat-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--admin-warning);">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div>
                        <h4 style="margin: 0; font-size: 1.25rem;"><?php echo $pendingOrders; ?> Pending Orders</h4>
                        <p class="mb-0" style="color: var(--admin-text-muted); font-size: 0.875rem;">Orders awaiting confirmation</p>
                    </div>
                </div>
                <a href="index.php?page=orders&status=pending" class="btn-admin-primary mt-3 w-100 justify-content-center">
                    <i class="bi bi-arrow-right"></i> Review Now
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
