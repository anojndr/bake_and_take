<?php
/**
 * Admin SMS Log Management
 */

require_once '../includes/sms_service.php';

// Get filter parameters
$direction = isset($_GET['direction']) ? $_GET['direction'] : null;
$statusFilter = isset($_GET['sms_status']) ? $_GET['sms_status'] : null;

$smsLogs = [];
$stats = [
    'total' => 0,
    'sent' => 0,
    'pending' => 0,
    'failed' => 0,
    'received' => 0
];

if ($pdo) {
    try {
        // Get stats
        $statsStmt = $pdo->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'sent' OR status = 'delivered' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN direction = 'inbound' THEN 1 ELSE 0 END) as received
            FROM sms_log
        ");
        $stats = $statsStmt->fetch();
        
        // Build query with filters
        $sql = "SELECT s.*, o.order_number FROM sms_log s LEFT JOIN orders o ON s.order_id = o.id WHERE 1=1";
        $params = [];
        
        if ($direction) {
            $sql .= " AND s.direction = ?";
            $params[] = $direction;
        }
        
        if ($statusFilter) {
            $sql .= " AND s.status = ?";
            $params[] = $statusFilter;
        }
        
        $sql .= " ORDER BY s.created_at DESC LIMIT 100";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $smsLogs = $stmt->fetchAll();
        
    } catch (PDOException $e) {
        // Handle error silently
    }
}

$statusOptions = ['pending', 'sent', 'delivered', 'failed', 'received'];
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1 class="page-title"><i class="bi bi-phone me-2"></i>SMS Gateway</h1>
        <p class="page-subtitle">Monitor SMS messages and gateway status</p>
    </div>
    <div>
        <button class="btn-admin-primary" data-bs-toggle="modal" data-bs-target="#sendSmsModal">
            <i class="bi bi-send me-2"></i>Send SMS
        </button>
    </div>
</div>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-3 col-6">
        <div class="admin-card">
            <div class="admin-card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="stat-label">Total Messages</p>
                        <h2 class="stat-value"><?php echo number_format($stats['total']); ?></h2>
                    </div>
                    <div class="stat-icon" style="background: rgba(59, 130, 246, 0.15);">
                        <i class="bi bi-chat-dots" style="color: #3b82f6;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="admin-card">
            <div class="admin-card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="stat-label">Sent</p>
                        <h2 class="stat-value" style="color: #22c55e;"><?php echo number_format($stats['sent']); ?></h2>
                    </div>
                    <div class="stat-icon" style="background: rgba(34, 197, 94, 0.15);">
                        <i class="bi bi-check-circle" style="color: #22c55e;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="admin-card">
            <div class="admin-card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="stat-label">Failed</p>
                        <h2 class="stat-value" style="color: #ef4444;"><?php echo number_format($stats['failed']); ?></h2>
                    </div>
                    <div class="stat-icon" style="background: rgba(239, 68, 68, 0.15);">
                        <i class="bi bi-x-circle" style="color: #ef4444;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="admin-card">
            <div class="admin-card-body">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="stat-label">Received</p>
                        <h2 class="stat-value" style="color: #8b5cf6;"><?php echo number_format($stats['received']); ?></h2>
                    </div>
                    <div class="stat-icon" style="background: rgba(139, 92, 246, 0.15);">
                        <i class="bi bi-inbox" style="color: #8b5cf6;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Gateway Status Card -->
<div class="admin-card mb-4">
    <div class="admin-card-header">
        <h3 class="admin-card-title">Gateway Configuration</h3>
    </div>
    <div class="admin-card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label class="form-label" style="color: var(--admin-text-muted);">Gateway URL</label>
                <div class="d-flex align-items-center gap-2">
                    <code style="background: var(--admin-dark); padding: 0.5rem 1rem; border-radius: 8px; flex: 1;">
                        <?php echo defined('SMS_GATEWAY_URL') ? SMS_GATEWAY_URL : 'Not configured'; ?>
                    </code>
                    <span id="gatewayStatus" class="badge bg-secondary">Unknown</span>
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label" style="color: var(--admin-text-muted);">Webhook URL</label>
                <code style="background: var(--admin-dark); padding: 0.5rem 1rem; border-radius: 8px; display: block;">
                    <?php echo SITE_URL; ?>/includes/sms_webhook.php
                </code>
            </div>
            <div class="col-md-4">
                <label class="form-label" style="color: var(--admin-text-muted);">Features</label>
                <div class="d-flex gap-2 flex-wrap">
                    <?php if (defined('SMS_ENABLED') && SMS_ENABLED): ?>
                    <span class="badge bg-success">SMS Enabled</span>
                    <?php else: ?>
                    <span class="badge bg-danger">SMS Disabled</span>
                    <?php endif; ?>
                    <?php if (defined('SMS_OTP_ENABLED') && SMS_OTP_ENABLED): ?>
                    <span class="badge bg-info">OTP Enabled</span>
                    <?php endif; ?>
                    <?php if (defined('SMS_ORDER_NOTIFICATIONS_ENABLED') && SMS_ORDER_NOTIFICATIONS_ENABLED): ?>
                    <span class="badge bg-warning">Order Notifications</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="admin-card mb-4">
    <div class="admin-card-body py-3">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span style="color: var(--admin-text-muted);">Filter:</span>
            <a href="index.php?page=sms" class="btn <?php echo !$direction && !$statusFilter ? 'btn-admin-primary' : 'btn-admin-secondary'; ?> btn-sm">
                All
            </a>
            <a href="index.php?page=sms&direction=outbound" class="btn <?php echo $direction === 'outbound' ? 'btn-admin-primary' : 'btn-admin-secondary'; ?> btn-sm">
                <i class="bi bi-arrow-up-right me-1"></i>Outbound
            </a>
            <a href="index.php?page=sms&direction=inbound" class="btn <?php echo $direction === 'inbound' ? 'btn-admin-primary' : 'btn-admin-secondary'; ?> btn-sm">
                <i class="bi bi-arrow-down-left me-1"></i>Inbound
            </a>
            <span class="mx-2" style="color: var(--admin-dark-tertiary);">|</span>
            <?php foreach ($statusOptions as $stat): ?>
            <a href="index.php?page=sms&sms_status=<?php echo $stat; ?>" 
               class="btn <?php echo $statusFilter === $stat ? 'btn-admin-primary' : 'btn-admin-secondary'; ?> btn-sm">
                <?php echo ucfirst($stat); ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- SMS Log Table -->
<div class="admin-card">
    <div class="admin-card-header">
        <h3 class="admin-card-title">
            SMS Log
            <span class="badge bg-secondary ms-2"><?php echo count($smsLogs); ?></span>
        </h3>
    </div>
    <div class="admin-card-body p-0">
        <?php if (empty($smsLogs)): ?>
        <div class="empty-state">
            <i class="bi bi-chat-left-text"></i>
            <h3>No SMS messages found</h3>
            <p>SMS messages will appear here once sent or received.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Direction</th>
                        <th>Phone Number</th>
                        <th>Message</th>
                        <th>Order</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($smsLogs as $sms): ?>
                    <tr>
                        <td>
                            <?php if ($sms['direction'] === 'outbound'): ?>
                            <span class="badge" style="background: rgba(59, 130, 246, 0.2); color: #3b82f6;">
                                <i class="bi bi-arrow-up-right me-1"></i>Sent
                            </span>
                            <?php else: ?>
                            <span class="badge" style="background: rgba(139, 92, 246, 0.2); color: #8b5cf6;">
                                <i class="bi bi-arrow-down-left me-1"></i>Received
                            </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <code><?php echo sanitize($sms['phone_number']); ?></code>
                        </td>
                        <td style="max-width: 300px;">
                            <span title="<?php echo sanitize($sms['message']); ?>">
                                <?php echo strlen($sms['message']) > 50 ? sanitize(substr($sms['message'], 0, 50)) . '...' : sanitize($sms['message']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($sms['order_number']): ?>
                            <a href="index.php?page=orders" style="color: var(--admin-primary);">
                                #<?php echo sanitize($sms['order_number']); ?>
                            </a>
                            <?php else: ?>
                            <span style="color: var(--admin-text-muted);">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $statusColors = [
                                'pending' => 'warning',
                                'sent' => 'info',
                                'delivered' => 'success',
                                'failed' => 'danger',
                                'received' => 'primary'
                            ];
                            $color = $statusColors[$sms['status']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $color; ?>">
                                <?php echo ucfirst($sms['status']); ?>
                            </span>
                        </td>
                        <td>
                            <span title="<?php echo date('M d, Y h:i:s A', strtotime($sms['created_at'])); ?>">
                                <?php echo date('M d, g:i A', strtotime($sms['created_at'])); ?>
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn-action" title="View Details" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#smsModal<?php echo $sms['id']; ?>">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <?php if ($sms['status'] === 'failed'): ?>
                                <button class="btn-action" title="Retry" 
                                        onclick="retrySms(<?php echo $sms['id']; ?>, '<?php echo $sms['phone_number']; ?>')">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </button>
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

<!-- SMS Detail Modals -->
<?php foreach ($smsLogs as $sms): ?>
<div class="modal fade" id="smsModal<?php echo $sms['id']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background: var(--admin-dark-secondary); border: 1px solid var(--admin-dark-tertiary);">
            <div class="modal-header" style="border-color: var(--admin-dark-tertiary);">
                <h5 class="modal-title" style="color: var(--admin-text);">
                    <i class="bi bi-chat-left-text me-2"></i>SMS Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="color: var(--admin-text);">
                <div class="mb-3">
                    <label class="form-label" style="color: var(--admin-text-muted);">Direction</label>
                    <div>
                        <?php if ($sms['direction'] === 'outbound'): ?>
                        <span class="badge" style="background: rgba(59, 130, 246, 0.2); color: #3b82f6;">
                            <i class="bi bi-arrow-up-right me-1"></i>Outbound (Sent)
                        </span>
                        <?php else: ?>
                        <span class="badge" style="background: rgba(139, 92, 246, 0.2); color: #8b5cf6;">
                            <i class="bi bi-arrow-down-left me-1"></i>Inbound (Received)
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" style="color: var(--admin-text-muted);">Phone Number</label>
                    <div><code><?php echo sanitize($sms['phone_number']); ?></code></div>
                </div>
                <div class="mb-3">
                    <label class="form-label" style="color: var(--admin-text-muted);">Message</label>
                    <div style="background: var(--admin-dark); padding: 1rem; border-radius: 8px;">
                        <?php echo nl2br(sanitize($sms['message'])); ?>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" style="color: var(--admin-text-muted);">Status</label>
                    <div>
                        <?php
                        $color = $statusColors[$sms['status']] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?php echo $color; ?>">
                            <?php echo ucfirst($sms['status']); ?>
                        </span>
                    </div>
                </div>
                <?php if ($sms['gateway_response']): ?>
                <div class="mb-3">
                    <label class="form-label" style="color: var(--admin-text-muted);">Gateway Response</label>
                    <pre style="background: var(--admin-dark); padding: 1rem; border-radius: 8px; font-size: 0.75rem; overflow-x: auto; color: var(--admin-text-muted);"><?php echo sanitize($sms['gateway_response']); ?></pre>
                </div>
                <?php endif; ?>
                <div class="row">
                    <div class="col-6">
                        <label class="form-label" style="color: var(--admin-text-muted);">Created</label>
                        <div><?php echo date('M d, Y h:i:s A', strtotime($sms['created_at'])); ?></div>
                    </div>
                    <div class="col-6">
                        <label class="form-label" style="color: var(--admin-text-muted);">Updated</label>
                        <div><?php echo date('M d, Y h:i:s A', strtotime($sms['updated_at'])); ?></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="border-color: var(--admin-dark-tertiary);">
                <button type="button" class="btn-admin-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Send SMS Modal -->
<div class="modal fade" id="sendSmsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background: var(--admin-dark-secondary); border: 1px solid var(--admin-dark-tertiary);">
            <div class="modal-header" style="border-color: var(--admin-dark-tertiary);">
                <h5 class="modal-title" style="color: var(--admin-text);">
                    <i class="bi bi-send me-2"></i>Send SMS
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="sendSmsForm" action="includes/send_sms.php" method="POST">
                <div class="modal-body" style="color: var(--admin-text);">
                    <div class="mb-3">
                        <label for="smsPhone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                        <input type="tel" id="smsPhone" name="phone" class="form-control" 
                               placeholder="+639123456789" required
                               style="background: var(--admin-dark); border-color: var(--admin-dark-tertiary); color: var(--admin-text);">
                        <small style="color: var(--admin-text-muted);">Include country code (e.g., +63 for Philippines)</small>
                    </div>
                    <div class="mb-3">
                        <label for="smsMessage" class="form-label">Message <span class="text-danger">*</span></label>
                        <textarea id="smsMessage" name="message" class="form-control" rows="4" 
                                  placeholder="Enter your message..." required maxlength="160"
                                  style="background: var(--admin-dark); border-color: var(--admin-dark-tertiary); color: var(--admin-text);"></textarea>
                        <small style="color: var(--admin-text-muted);"><span id="charCount">0</span>/160 characters</small>
                    </div>
                </div>
                <div class="modal-footer" style="border-color: var(--admin-dark-tertiary);">
                    <button type="button" class="btn-admin-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn-admin-primary">
                        <i class="bi bi-send me-2"></i>Send SMS
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Character counter for SMS message
    const messageInput = document.getElementById('smsMessage');
    const charCount = document.getElementById('charCount');
    
    if (messageInput && charCount) {
        messageInput.addEventListener('input', function() {
            charCount.textContent = this.value.length;
            if (this.value.length > 160) {
                charCount.style.color = '#ef4444';
            } else {
                charCount.style.color = '';
            }
        });
    }
    
    // Send SMS form handler
    const sendSmsForm = document.getElementById('sendSmsForm');
    if (sendSmsForm) {
        sendSmsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('includes/send_sms.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('SMS sent successfully!');
                    location.reload();
                } else {
                    alert('Failed to send SMS: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while sending SMS');
            });
        });
    }
});

function retrySms(smsId, phone) {
    if (confirm('Retry sending this SMS?')) {
        fetch('includes/retry_sms.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `sms_id=${smsId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('SMS retry initiated');
                location.reload();
            } else {
                alert('Failed to retry: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred');
        });
    }
}
</script>
