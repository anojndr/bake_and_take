<?php
/**
 * Admin Users Management
 */

$users = [];

if ($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT u.*, 
                   (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as order_count,
                   (SELECT COALESCE(SUM(total), 0) FROM orders WHERE user_id = u.id AND status != 'cancelled') as total_spent
            FROM users u 
            ORDER BY u.created_at DESC
        ");
        $users = $stmt->fetchAll();
    } catch (PDOException $e) {
        // Handle error
    }
}
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1 class="page-title">Users</h1>
        <p class="page-subtitle">Manage registered users and admins</p>
    </div>
</div>

<!-- Users Table -->
<div class="admin-card">
    <div class="admin-card-header">
        <h3 class="admin-card-title">
            All Users
            <span class="badge bg-secondary ms-2"><?php echo count($users); ?></span>
        </h3>
    </div>
    <div class="admin-card-body p-0">
        <?php if (empty($users)): ?>
        <div class="empty-state">
            <i class="bi bi-people"></i>
            <h3>No users yet</h3>
            <p>When users register, they'll appear here.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Orders</th>
                        <th>Total Spent</th>
                        <th>Role</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td>#<?php echo $user['id']; ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="user-avatar" style="width: 36px; height: 36px; font-size: 0.875rem;">
                                    <?php echo strtoupper(substr($user['first_name'], 0, 1)); ?>
                                </div>
                                <strong><?php echo sanitize($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                            </div>
                        </td>
                        <td>
                            <a href="mailto:<?php echo sanitize($user['email']); ?>" style="color: var(--admin-primary);">
                                <?php echo sanitize($user['email']); ?>
                            </a>
                        </td>
                        <td><?php echo $user['order_count']; ?> orders</td>
                        <td><strong><?php echo formatPrice($user['total_spent']); ?></strong></td>
                        <td>
                            <?php if ($user['is_admin']): ?>
                            <span class="status-badge confirmed">
                                <i class="bi bi-shield-check"></i> Admin
                            </span>
                            <?php else: ?>
                            <span class="status-badge" style="background: var(--admin-dark); color: var(--admin-text-muted);">
                                Customer
                            </span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-action" title="View Details" data-bs-toggle="modal" data-bs-target="#userModal<?php echo $user['id']; ?>">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <?php if ($user['id'] != $_SESSION['admin_id']): ?>
                                <form action="includes/manage_user.php" method="POST" style="display: inline;" onsubmit="return confirm('<?php echo $user['is_admin'] ? 'Remove admin privileges?' : 'Grant admin privileges?'; ?>')">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="action" value="toggle_admin">
                                    <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="btn-action edit" title="Toggle Admin">
                                        <i class="bi bi-shield<?php echo $user['is_admin'] ? '-x' : '-plus'; ?>"></i>
                                    </button>
                                </form>
                                <form action="includes/manage_user.php" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" class="btn-action delete" title="Delete User">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
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

<!-- User Detail Modals - Outside the table -->
<?php foreach ($users as $user): ?>
<div class="modal fade" id="userModal<?php echo $user['id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="background: var(--admin-dark-secondary); border: 1px solid var(--admin-dark-tertiary);">
            <div class="modal-header" style="border-color: var(--admin-dark-tertiary);">
                <h5 class="modal-title" style="color: var(--admin-text);">User Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="color: var(--admin-text);">
                <div class="text-center mb-4">
                    <div class="user-avatar mx-auto mb-3" style="width: 80px; height: 80px; font-size: 2rem;">
                        <?php echo strtoupper(substr($user['first_name'], 0, 1)); ?>
                    </div>
                    <h4 class="mb-1"><?php echo sanitize($user['first_name'] . ' ' . $user['last_name']); ?></h4>
                    <p class="mb-0" style="color: var(--admin-text-muted);"><?php echo sanitize($user['email']); ?></p>
                    <?php if ($user['phone']): ?>
                    <p class="mb-0" style="color: var(--admin-text-muted); font-size: 0.875rem;">
                        <i class="bi bi-telephone me-1"></i><?php echo sanitize($user['phone']); ?>
                    </p>
                    <?php endif; ?>
                </div>
                <div class="row g-3">
                    <div class="col-6">
                        <div class="p-3 rounded text-center" style="background: var(--admin-dark);">
                            <div style="color: var(--admin-text-muted); font-size: 0.75rem; text-transform: uppercase;">Orders</div>
                            <div style="font-size: 1.5rem; font-weight: 700; color: var(--admin-primary);"><?php echo $user['order_count']; ?></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 rounded text-center" style="background: var(--admin-dark);">
                            <div style="color: var(--admin-text-muted); font-size: 0.75rem; text-transform: uppercase;">Total Spent</div>
                            <div style="font-size: 1.5rem; font-weight: 700; color: var(--admin-success);"><?php echo formatPrice($user['total_spent']); ?></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 rounded text-center" style="background: var(--admin-dark);">
                            <div style="color: var(--admin-text-muted); font-size: 0.75rem; text-transform: uppercase;">Role</div>
                            <div style="font-size: 1rem; font-weight: 600;">
                                <?php if ($user['is_admin']): ?>
                                <span style="color: var(--admin-info);"><i class="bi bi-shield-check me-1"></i>Admin</span>
                                <?php else: ?>
                                <span>Customer</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 rounded text-center" style="background: var(--admin-dark);">
                            <div style="color: var(--admin-text-muted); font-size: 0.75rem; text-transform: uppercase;">Joined</div>
                            <div style="font-size: 1rem; font-weight: 600;"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></div>
                        </div>
                    </div>
                </div>
                
                <?php if ($user['address']): ?>
                <div class="mt-3 p-3 rounded" style="background: var(--admin-dark);">
                    <div style="color: var(--admin-text-muted); font-size: 0.75rem; text-transform: uppercase; margin-bottom: 0.5rem;">Address</div>
                    <div style="font-size: 0.9rem;">
                        <?php echo sanitize($user['address']); ?>
                        <?php if ($user['city'] || $user['state'] || $user['zip']): ?>
                        <br><?php echo sanitize(trim($user['city'] . ', ' . $user['state'] . ' ' . $user['zip'], ', ')); ?>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer" style="border-color: var(--admin-dark-tertiary);">
                <button type="button" class="btn-admin-secondary" data-bs-dismiss="modal">Close</button>
                <?php if ($user['id'] != $_SESSION['admin_id']): ?>
                <form action="includes/manage_user.php" method="POST" style="display: inline;" onsubmit="return confirm('<?php echo $user['is_admin'] ? 'Remove admin privileges?' : 'Grant admin privileges?'; ?>')">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="toggle_admin">
                    <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                    <button type="submit" class="btn-admin-primary">
                        <i class="bi bi-shield<?php echo $user['is_admin'] ? '-x' : '-plus'; ?> me-1"></i>
                        <?php echo $user['is_admin'] ? 'Remove Admin' : 'Make Admin'; ?>
                    </button>
                </form>
                <form action="includes/manage_user.php" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                    <button type="submit" class="btn-admin-danger" style="background: var(--admin-danger); color: white; border: none; padding: 0.5rem 1rem; border-radius: 8px; cursor: pointer;">
                        <i class="bi bi-trash me-1"></i>
                        Delete User
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
