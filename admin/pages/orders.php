<?php
/**
 * Admin Orders Management
 */

// Filter by status
$statusFilter = isset($_GET['status']) ? $_GET['status'] : null;
$orders = [];

if ($pdo) {
    try {
        $sql = "
            SELECT o.*, 
                   CONCAT(o.first_name, ' ', o.last_name) as customer_name,
                   (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
            FROM orders o
        ";
        
        if ($statusFilter) {
            $sql .= " WHERE o.status = ?";
            $sql .= " ORDER BY o.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$statusFilter]);
        } else {
            $sql .= " ORDER BY o.created_at DESC";
            $stmt = $pdo->query($sql);
        }
        
        $orders = $stmt->fetchAll();
    } catch (PDOException $e) {
        // Handle error
    }
}

$statusOptions = ['pending', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled'];
$statusLabels = [
    'pending' => 'Pending',
    'confirmed' => 'Confirmed',
    'preparing' => 'Preparing',
    'ready' => 'Ready',
    'delivered' => 'Picked Up',
    'cancelled' => 'Cancelled'
];
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1 class="page-title">Orders</h1>
        <p class="page-subtitle">Manage and track all customer orders</p>
    </div>
    <form action="includes/delete_all.php" method="POST" onsubmit="return confirm('WARNING: Are you sure you want to delete ALL orders? This action CANNOT be undone.');">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        <input type="hidden" name="type" value="orders">
        <button type="submit" class="btn-admin-danger" style="background: var(--admin-danger); color: white; padding: 0.5rem 1rem; border-radius: 8px; border: none; cursor: pointer;">
            <i class="bi bi-trash me-2"></i>Delete All Orders
        </button>
    </form>
</div>

<!-- Filters -->
<div class="admin-card mb-4">
    <div class="admin-card-body py-3">
        <div class="d-flex flex-wrap gap-2">
            <a href="index.php?page=orders" class="btn <?php echo !$statusFilter ? 'btn-admin-primary' : 'btn-admin-secondary'; ?> btn-sm">
                All Orders
            </a>
            <?php foreach ($statusOptions as $status): ?>
            <a href="index.php?page=orders&status=<?php echo $status; ?>" 
               class="btn <?php echo $statusFilter === $status ? 'btn-admin-primary' : 'btn-admin-secondary'; ?> btn-sm">
                <?php echo $statusLabels[$status]; ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Orders Table -->
<div class="admin-card">
    <div class="admin-card-header">
        <h3 class="admin-card-title">
            <?php echo $statusFilter ? ($statusLabels[$statusFilter] ?? ucfirst($statusFilter)) . ' Orders' : 'All Orders'; ?>
            <span class="badge bg-secondary ms-2"><?php echo count($orders); ?></span>
        </h3>
    </div>
    <div class="admin-card-body p-0">
        <?php if (empty($orders)): ?>
        <div class="empty-state">
            <i class="bi bi-receipt"></i>
            <h3>No orders found</h3>
            <p>There are no orders matching your filter criteria.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>
                            <strong>#<?php echo sanitize($order['order_number']); ?></strong>
                        </td>
                        <td><?php echo sanitize($order['customer_name']); ?></td>
                        <td>
                            <a href="mailto:<?php echo sanitize($order['email']); ?>" style="color: var(--admin-primary);">
                                <?php echo sanitize($order['email']); ?>
                            </a>
                        </td>
                        <td><?php echo $order['item_count']; ?> items</td>
                        <td><strong><?php echo formatPrice($order['total']); ?></strong></td>
                        <td>
                            <select class="form-select form-select-sm status-select" 
                                    data-order-id="<?php echo $order['id']; ?>"
                                    style="background: var(--admin-dark); border-color: var(--admin-dark-tertiary); color: var(--admin-text); width: 130px;">
                                <?php foreach ($statusOptions as $status): ?>
                                <option value="<?php echo $status; ?>" <?php echo $order['status'] === $status ? 'selected' : ''; ?>>
                                    <?php echo $statusLabels[$status]; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                        <td>
                            <span title="<?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?>">
                                <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <?php if ($order['status'] === 'pending'): ?>
                                <button class="btn-action btn-action-success" title="Confirm Order" onclick="confirmOrder(<?php echo $order['id']; ?>, '<?php echo sanitize($order['order_number']); ?>')">
                                    <i class="bi bi-check-lg"></i>
                                </button>
                                <?php endif; ?>
                                <button class="btn-action" title="View Details" data-bs-toggle="modal" data-bs-target="#orderModal<?php echo $order['id']; ?>">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn-action btn-action-danger" title="Delete Order" onclick="deleteOrder(<?php echo $order['id']; ?>, '<?php echo sanitize($order['order_number']); ?>')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Order Detail Modals -->
<?php foreach ($orders as $order): 
    // Get order items
    $orderItems = [];
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
            $stmt->execute([$order['id']]);
            $orderItems = $stmt->fetchAll();
        } catch (PDOException $e) {}
    }
?>
<div class="modal fade" id="orderModal<?php echo $order['id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="background: var(--admin-dark-secondary); border: 1px solid var(--admin-dark-tertiary);">
            <div class="modal-header" style="border-color: var(--admin-dark-tertiary);">
                <h5 class="modal-title" style="color: var(--admin-text);">
                    Order #<?php echo sanitize($order['order_number']); ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="color: var(--admin-text);">
                <div class="row g-4">
                    <div class="col-md-6">
                        <h6 style="color: var(--admin-text-muted);">Customer Details</h6>
                        <p class="mb-1"><strong><?php echo sanitize($order['first_name'] . ' ' . $order['last_name']); ?></strong></p>
                        <p class="mb-1"><?php echo sanitize($order['email']); ?></p>
                        <p class="mb-0"><?php echo sanitize($order['phone']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <h6 style="color: var(--admin-text-muted);">Pickup Details</h6>
                        <p class="mb-1">
                            <strong>Method:</strong> <?php echo ucfirst($order['delivery_method']); ?>
                        </p>
                        <p class="mb-1">
                            <strong>Status:</strong> 
                            <span class="badge bg-<?php 
                                echo $order['status'] === 'pending' ? 'warning' : 
                                    ($order['status'] === 'confirmed' ? 'primary' : 
                                    ($order['status'] === 'preparing' ? 'info' : 
                                    ($order['status'] === 'ready' ? 'success' : 
                                    ($order['status'] === 'cancelled' ? 'danger' : 'secondary')))); 
                            ?>">
                                <?php echo $statusLabels[$order['status']] ?? ucfirst($order['status']); ?>
                            </span>
                        </p>
                        <?php if (isset($order['confirmation_method']) && $order['confirmation_method']): ?>
                        <p class="mb-1">
                            <strong>Confirmation:</strong> 
                            <?php echo ucfirst($order['confirmation_method']); ?>
                            <?php if (isset($order['confirmed_at']) && $order['confirmed_at']): ?>
                                <span class="text-success ms-1"><i class="bi bi-check-circle"></i> Confirmed</span>
                            <?php elseif ($order['status'] === 'pending'): ?>
                                <span class="text-warning ms-1"><i class="bi bi-clock"></i> Awaiting</span>
                            <?php endif; ?>
                        </p>
                        <?php endif; ?>
                        <?php if ($order['delivery_method'] === 'delivery' && $order['address']): ?>
                        <p class="mb-0">
                            <?php echo sanitize($order['address']); ?><br>
                            <?php echo sanitize($order['city'] . ', ' . $order['state'] . ' ' . $order['zip']); ?>
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <hr style="border-color: var(--admin-dark-tertiary);">
                
                <h6 style="color: var(--admin-text-muted);">Order Items</h6>
                <table class="table table-dark table-sm">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Price</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orderItems as $item): ?>
                        <tr>
                            <td><?php echo sanitize($item['product_name']); ?></td>
                            <td class="text-center"><?php echo $item['quantity']; ?></td>
                            <td class="text-end"><?php echo formatPrice($item['price']); ?></td>
                            <td class="text-end"><?php echo formatPrice($item['total']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end">Subtotal:</td>
                            <td class="text-end"><?php echo formatPrice($order['subtotal']); ?></td>
                        </tr>
                        <?php if ($order['delivery_fee'] > 0): ?>
                        <tr>
                            <td colspan="3" class="text-end">Delivery Fee:</td>
                            <td class="text-end"><?php echo formatPrice($order['delivery_fee']); ?></td>
                        </tr>
                        <?php endif; ?>
                        <?php if ($order['tax'] > 0): ?>
                        <tr>
                            <td colspan="3" class="text-end">Tax:</td>
                            <td class="text-end"><?php echo formatPrice($order['tax']); ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <td colspan="3" class="text-end"><strong>Total:</strong></td>
                            <td class="text-end"><strong><?php echo formatPrice($order['total']); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
                
                <?php if ($order['instructions']): ?>
                <div class="mt-3 p-3" style="background: var(--admin-dark); border-radius: var(--radius-md);">
                    <h6 style="color: var(--admin-text-muted);">Special Instructions</h6>
                    <p class="mb-0"><?php echo nl2br(sanitize($order['instructions'])); ?></p>
                </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer" style="border-color: var(--admin-dark-tertiary);">
                <button type="button" class="btn-admin-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script>
function deleteOrder(orderId, orderNumber) {
    Swal.fire({
        title: 'Delete Order?',
        html: `Are you sure you want to delete order <strong>#${orderNumber}</strong>?<br><small class="text-muted">This action cannot be undone.</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        background: 'var(--admin-dark-secondary)',
        color: 'var(--admin-text)'
    }).then((result) => {
        if (result.isConfirmed) {
            // Send delete request
            fetch('includes/delete_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'order_id=' + orderId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Deleted!',
                        text: 'Order has been deleted.',
                        icon: 'success',
                        background: 'var(--admin-dark-secondary)',
                        color: 'var(--admin-text)'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: data.error || 'Failed to delete order.',
                        icon: 'error',
                        background: 'var(--admin-dark-secondary)',
                        color: 'var(--admin-text)'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred while deleting the order.',
                    icon: 'error',
                    background: 'var(--admin-dark-secondary)',
                    color: 'var(--admin-text)'
                });
            });
        }
    });
}

function confirmOrder(orderId, orderNumber) {
    Swal.fire({
        title: 'Confirm Order?',
        html: `Are you sure you want to manually confirm order <strong>#${orderNumber}</strong>?<br><small class="text-muted">This will notify the customer that their order is confirmed.</small>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, confirm it!',
        cancelButtonText: 'Cancel',
        background: 'var(--admin-dark-secondary)',
        color: 'var(--admin-text)'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('includes/confirm_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'order_id=' + orderId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Confirmed!',
                        text: data.message || 'Order has been confirmed.',
                        icon: 'success',
                        background: 'var(--admin-dark-secondary)',
                        color: 'var(--admin-text)'
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: data.message || 'Failed to confirm order.',
                        icon: 'error',
                        background: 'var(--admin-dark-secondary)',
                        color: 'var(--admin-text)'
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    title: 'Error!',
                    text: 'An error occurred while confirming the order.',
                    icon: 'error',
                    background: 'var(--admin-dark-secondary)',
                    color: 'var(--admin-text)'
                });
            });
        }
    });
}
</script>
