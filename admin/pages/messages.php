<?php
/**
 * Admin Messages Management
 */

$messages = [];

if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT * FROM contact_messages ORDER BY created_at DESC");
        $messages = $stmt->fetchAll();
    } catch (PDOException $e) {
        // Handle error
    }
}

$unreadCount = count(array_filter($messages, fn($m) => !$m['read_status']));
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1 class="page-title">Messages</h1>
        <p class="page-subtitle">Customer inquiries and contact form submissions</p>
    </div>
    <?php if ($unreadCount > 0): ?>
    <span class="badge bg-danger" style="font-size: 0.875rem; padding: 0.5rem 1rem;">
        <?php echo $unreadCount; ?> unread
    </span>
    <?php endif; ?>
</div>

<!-- Messages List -->
<div class="admin-card">
    <div class="admin-card-header">
        <h3 class="admin-card-title">
            All Messages
            <span class="badge bg-secondary ms-2"><?php echo count($messages); ?></span>
        </h3>
    </div>
    <div class="admin-card-body p-0">
        <?php if (empty($messages)): ?>
        <div class="empty-state">
            <i class="bi bi-envelope"></i>
            <h3>No messages yet</h3>
            <p>When customers send messages through the contact form, they'll appear here.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width: 30px;"></th>
                        <th>From</th>
                        <th>Subject</th>
                        <th>Message</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($messages as $message): ?>
                    <tr style="<?php echo !$message['read_status'] ? 'background: rgba(99, 102, 241, 0.1);' : ''; ?>">
                        <td>
                            <?php if (!$message['read_status']): ?>
                            <span class="notification-badge" style="position: static; width: 10px; height: 10px;"></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div>
                                <strong><?php echo sanitize($message['first_name'] . ' ' . $message['last_name']); ?></strong>
                                <div style="font-size: 0.75rem; color: var(--admin-text-muted);">
                                    <?php echo sanitize($message['email']); ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge" style="background: var(--admin-dark); text-transform: capitalize;">
                                <?php echo sanitize($message['subject']); ?>
                            </span>
                        </td>
                        <td style="max-width: 300px;">
                            <p style="margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                <?php echo sanitize($message['message']); ?>
                            </p>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($message['created_at'])); ?></td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-action" title="View Message" data-bs-toggle="modal" data-bs-target="#messageModal<?php echo $message['id']; ?>">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <a href="mailto:<?php echo sanitize($message['email']); ?>" class="btn-action edit" title="Reply">
                                    <i class="bi bi-reply"></i>
                                </a>
                                <form action="includes/manage_message.php" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this message?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $message['id']; ?>">
                                    <button type="submit" class="btn-action delete" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
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

<!-- Message Detail Modals -->
<?php foreach ($messages as $message): ?>
<div class="modal fade" id="messageModal<?php echo $message['id']; ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="background: var(--admin-dark-secondary); border: 1px solid var(--admin-dark-tertiary);">
            <div class="modal-header" style="border-color: var(--admin-dark-tertiary);">
                <h5 class="modal-title" style="color: var(--admin-text);">
                    Message from <?php echo sanitize($message['first_name'] . ' ' . $message['last_name']); ?>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="color: var(--admin-text);">
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label style="color: var(--admin-text-muted); font-size: 0.75rem; text-transform: uppercase;">From</label>
                        <p class="mb-0"><strong><?php echo sanitize($message['first_name'] . ' ' . $message['last_name']); ?></strong></p>
                    </div>
                    <div class="col-md-6">
                        <label style="color: var(--admin-text-muted); font-size: 0.75rem; text-transform: uppercase;">Email</label>
                        <p class="mb-0">
                            <a href="mailto:<?php echo sanitize($message['email']); ?>" style="color: var(--admin-primary);">
                                <?php echo sanitize($message['email']); ?>
                            </a>
                        </p>
                    </div>
                    <?php if ($message['phone']): ?>
                    <div class="col-md-6">
                        <label style="color: var(--admin-text-muted); font-size: 0.75rem; text-transform: uppercase;">Phone</label>
                        <p class="mb-0"><?php echo sanitize($message['phone']); ?></p>
                    </div>
                    <?php endif; ?>
                    <div class="col-md-6">
                        <label style="color: var(--admin-text-muted); font-size: 0.75rem; text-transform: uppercase;">Subject</label>
                        <p class="mb-0"><?php echo sanitize($message['subject']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <label style="color: var(--admin-text-muted); font-size: 0.75rem; text-transform: uppercase;">Date</label>
                        <p class="mb-0"><?php echo date('F d, Y \a\t h:i A', strtotime($message['created_at'])); ?></p>
                    </div>
                </div>
                
                <hr style="border-color: var(--admin-dark-tertiary);">
                
                <label style="color: var(--admin-text-muted); font-size: 0.75rem; text-transform: uppercase;">Message</label>
                <div class="p-3 mt-2" style="background: var(--admin-dark); border-radius: var(--radius-md);">
                    <p class="mb-0" style="white-space: pre-wrap;"><?php echo sanitize($message['message']); ?></p>
                </div>
            </div>
            <div class="modal-footer" style="border-color: var(--admin-dark-tertiary);">
                <button type="button" class="btn-admin-secondary" data-bs-dismiss="modal">Close</button>
                <a href="mailto:<?php echo sanitize($message['email']); ?>?subject=Re: <?php echo rawurlencode($message['subject']); ?>" 
                   class="btn-admin-primary">
                    <i class="bi bi-reply"></i> Reply
                </a>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>
