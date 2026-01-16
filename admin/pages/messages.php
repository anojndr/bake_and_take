<?php
/**
 * Admin Messages Management - View SMS and Email Logs
 */

// Filter options
$messageType = isset($_GET['type']) ? $_GET['type'] : 'all';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : null;
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';

$emailLogs = [];
$smsLogs = [];
$totalEmails = 0;
$totalSMS = 0;

if ($conn) {
    // Check if email_log table exists
    $emailTableExists = false;
    $tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'email_log'");
    if ($tableCheck && mysqli_num_rows($tableCheck) > 0) {
        $emailTableExists = true;
    }
    
    // Get email logs
    if ($emailTableExists && ($messageType === 'all' || $messageType === 'email')) {
        $sql = "SELECT * FROM email_log WHERE 1=1";
        $params = [];
        $types = "";
        
        if ($statusFilter) {
            $sql .= " AND status = ?";
            $params[] = $statusFilter;
            $types .= "s";
        }
        
        if ($searchQuery) {
            $sql .= " AND (recipient_email LIKE ? OR subject LIKE ?)";
            $searchParam = "%{$searchQuery}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $types .= "ss";
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT 100";
        
        if (!empty($params)) {
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
        } else {
            $result = mysqli_query($conn, $sql);
        }
        
        if ($result) {
            $emailLogs = mysqli_fetch_all($result, MYSQLI_ASSOC);
        }
        
        // Get total count
        $countResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM email_log");
        if ($countResult) {
            $row = mysqli_fetch_assoc($countResult);
            $totalEmails = $row['count'];
        }
    }
    
    // Get SMS logs
    if ($messageType === 'all' || $messageType === 'sms') {
        $sql = "SELECT s.*, o.order_number FROM sms_log s LEFT JOIN orders o ON s.order_id = o.order_id WHERE 1=1";
        $params = [];
        $types = "";
        
        if ($statusFilter) {
            $sql .= " AND s.status = ?";
            $params[] = $statusFilter;
            $types .= "s";
        }
        
        if ($searchQuery) {
            $sql .= " AND (s.phone_number LIKE ? OR s.message LIKE ?)";
            $searchParam = "%{$searchQuery}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $types .= "ss";
        }
        
        $sql .= " ORDER BY s.created_at DESC LIMIT 100";
        
        if (!empty($params)) {
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
        } else {
            $result = mysqli_query($conn, $sql);
        }
        
        if ($result) {
            $smsLogs = mysqli_fetch_all($result, MYSQLI_ASSOC);
        }
        
        // Get total count
        $countResult = mysqli_query($conn, "SELECT COUNT(*) as count FROM sms_log");
        if ($countResult) {
            $row = mysqli_fetch_assoc($countResult);
            $totalSMS = $row['count'];
        }
    }
}

$statusLabels = [
    'pending' => '<span class="badge bg-warning">Pending</span>',
    'sent' => '<span class="badge bg-success">Sent</span>',
    'delivered' => '<span class="badge bg-success">Delivered</span>',
    'failed' => '<span class="badge bg-danger">Failed</span>',
    'received' => '<span class="badge bg-info">Received</span>'
];

$directionLabels = [
    'outbound' => '<span class="badge bg-primary">Outbound</span>',
    'inbound' => '<span class="badge bg-info">Inbound</span>'
];
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1 class="page-title">Messages</h1>
        <p class="page-subtitle">View all sent emails and SMS messages</p>
    </div>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="admin-card">
            <div class="admin-card-body d-flex align-items-center">
                <div class="stat-icon me-3" style="background: var(--admin-primary); color: white; width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-envelope fs-4"></i>
                </div>
                <div>
                    <h3 class="mb-0"><?php echo number_format($totalEmails); ?></h3>
                    <p class="text-muted mb-0">Total Emails</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="admin-card">
            <div class="admin-card-body d-flex align-items-center">
                <div class="stat-icon me-3" style="background: var(--admin-success); color: white; width: 50px; height: 50px; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                    <i class="bi bi-phone fs-4"></i>
                </div>
                <div>
                    <h3 class="mb-0"><?php echo number_format($totalSMS); ?></h3>
                    <p class="text-muted mb-0">Total SMS</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="admin-card mb-4">
    <div class="admin-card-body py-3">
        <form method="GET" action="index.php" class="row g-3 align-items-end">
            <input type="hidden" name="page" value="messages">
            
            <div class="col-md-3">
                <label class="form-label">Message Type</label>
                <select name="type" class="form-select">
                    <option value="all" <?php echo $messageType === 'all' ? 'selected' : ''; ?>>All Messages</option>
                    <option value="email" <?php echo $messageType === 'email' ? 'selected' : ''; ?>>Emails Only</option>
                    <option value="sms" <?php echo $messageType === 'sms' ? 'selected' : ''; ?>>SMS Only</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="sent" <?php echo $statusFilter === 'sent' ? 'selected' : ''; ?>>Sent</option>
                    <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="failed" <?php echo $statusFilter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                </select>
            </div>
            
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" placeholder="Search email, phone, subject..." value="<?php echo sanitize($searchQuery); ?>">
            </div>
            
            <div class="col-md-2">
                <button type="submit" class="btn btn-admin-primary w-100">
                    <i class="bi bi-search me-2"></i>Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Email Logs -->
<?php if ($messageType === 'all' || $messageType === 'email'): ?>
<div class="admin-card mb-4">
    <div class="admin-card-header">
        <h3 class="admin-card-title">
            <i class="bi bi-envelope me-2"></i>Email Logs
            <span class="badge bg-secondary ms-2"><?php echo count($emailLogs); ?></span>
        </h3>
    </div>
    <div class="admin-card-body p-0">
        <?php if (empty($emailLogs)): ?>
        <div class="empty-state">
            <i class="bi bi-envelope"></i>
            <h3>No emails found</h3>
            <p>No email logs matching your criteria.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Recipient</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($emailLogs as $email): ?>
                    <tr>
                        <td>
                            <small style="color: var(--admin-text-muted);"><?php echo date('M j, Y', strtotime($email['created_at'])); ?><br><?php echo date('g:i A', strtotime($email['created_at'])); ?></small>
                        </td>
                        <td>
                            <a href="mailto:<?php echo sanitize($email['recipient_email']); ?>">
                                <?php echo sanitize($email['recipient_email']); ?>
                            </a>
                        </td>
                        <td>
                            <span title="<?php echo sanitize($email['subject']); ?>">
                                <?php echo sanitize(strlen($email['subject']) > 50 ? substr($email['subject'], 0, 50) . '...' : $email['subject']); ?>
                            </span>
                        </td>
                        <td><?php echo $statusLabels[$email['status']] ?? '<span class="badge bg-secondary">' . sanitize($email['status']) . '</span>'; ?></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                    onclick="viewEmailDetails(<?php echo $email['email_id']; ?>)" 
                                    title="View Details">
                                <i class="bi bi-eye"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- SMS Logs -->
<?php if ($messageType === 'all' || $messageType === 'sms'): ?>
<div class="admin-card">
    <div class="admin-card-header">
        <h3 class="admin-card-title">
            <i class="bi bi-phone me-2"></i>SMS Logs
            <span class="badge bg-secondary ms-2"><?php echo count($smsLogs); ?></span>
        </h3>
    </div>
    <div class="admin-card-body p-0">
        <?php if (empty($smsLogs)): ?>
        <div class="empty-state">
            <i class="bi bi-phone"></i>
            <h3>No SMS messages found</h3>
            <p>No SMS logs matching your criteria.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Direction</th>
                        <th>Phone Number</th>
                        <th>Message</th>
                        <th>Order</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($smsLogs as $sms): ?>
                    <tr>
                        <td>
                            <small style="color: var(--admin-text-muted);"><?php echo date('M j, Y', strtotime($sms['created_at'])); ?><br><?php echo date('g:i A', strtotime($sms['created_at'])); ?></small>
                        </td>
                        <td><?php echo $directionLabels[$sms['direction']] ?? sanitize($sms['direction']); ?></td>
                        <td>
                            <a href="tel:<?php echo sanitize($sms['phone_number']); ?>">
                                <?php echo sanitize($sms['phone_number']); ?>
                            </a>
                        </td>
                        <td>
                            <span title="<?php echo sanitize($sms['message']); ?>">
                                <?php echo sanitize(strlen($sms['message']) > 50 ? substr($sms['message'], 0, 50) . '...' : $sms['message']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($sms['order_number']): ?>
                            <a href="index.php?page=orders&order=<?php echo $sms['order_id']; ?>">
                                #<?php echo sanitize($sms['order_number']); ?>
                            </a>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $statusLabels[$sms['status']] ?? '<span class="badge bg-secondary">' . sanitize($sms['status']) . '</span>'; ?></td>
                        <td>
                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                    onclick="viewSMSDetails(<?php echo $sms['sms_id']; ?>)" 
                                    title="View Details">
                                <i class="bi bi-eye"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Email Details Modal -->
<div class="modal fade" id="emailDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="background: var(--admin-dark-secondary); color: var(--admin-text);">
            <div class="modal-header" style="border-color: var(--admin-dark-tertiary);">
                <h5 class="modal-title">Email Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="emailDetailsContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SMS Details Modal -->
<div class="modal fade" id="smsDetailsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="background: var(--admin-dark-secondary); color: var(--admin-text);">
            <div class="modal-header" style="border-color: var(--admin-dark-tertiary);">
                <h5 class="modal-title">SMS Details</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="smsDetailsContent">
                <div class="text-center py-5">
                    <div class="spinner-border text-primary"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Store email and SMS data for modals -->
<script>
const emailData = <?php echo json_encode($emailLogs); ?>;
const smsData = <?php echo json_encode($smsLogs); ?>;

function viewEmailDetails(emailId) {
    const email = emailData.find(e => e.email_id == emailId);
    if (!email) return;
    
    const statusBadge = {
        'pending': '<span class="badge bg-warning">Pending</span>',
        'sent': '<span class="badge bg-success">Sent</span>',
        'failed': '<span class="badge bg-danger">Failed</span>'
    };
    
    let html = `
        <div class="mb-3">
            <strong>Recipient:</strong> <a href="mailto:${escapeHtml(email.recipient_email)}" style="color: var(--admin-primary-light);">${escapeHtml(email.recipient_email)}</a>
        </div>
        <div class="mb-3">
            <strong>Subject:</strong> ${escapeHtml(email.subject)}
        </div>
        <div class="mb-3">
            <strong>Status:</strong> ${statusBadge[email.status] || email.status}
        </div>
        <div class="mb-3">
            <strong>Sent:</strong> ${new Date(email.created_at).toLocaleString()}
        </div>
        ${email.error_message ? `<div class="mb-3"><strong>Error:</strong> <span class="text-danger">${escapeHtml(email.error_message)}</span></div>` : ''}
        <hr style="border-color: var(--admin-dark-tertiary);">
        <div class="mb-2"><strong>Content:</strong></div>
        <div class="border rounded p-3" style="max-height: 400px; overflow-y: auto; background: var(--admin-dark); border-color: var(--admin-dark-tertiary) !important; color: var(--admin-text);">
            ${email.is_html ? email.body : '<pre style="color: var(--admin-text); margin: 0;">' + escapeHtml(email.body) + '</pre>'}
        </div>
    `;
    
    document.getElementById('emailDetailsContent').innerHTML = html;
    new bootstrap.Modal(document.getElementById('emailDetailsModal')).show();
}

function viewSMSDetails(smsId) {
    const sms = smsData.find(s => s.sms_id == smsId);
    if (!sms) return;
    
    const statusBadge = {
        'pending': '<span class="badge bg-warning">Pending</span>',
        'sent': '<span class="badge bg-success">Sent</span>',
        'delivered': '<span class="badge bg-success">Delivered</span>',
        'failed': '<span class="badge bg-danger">Failed</span>',
        'received': '<span class="badge bg-info">Received</span>'
    };
    
    const directionBadge = {
        'outbound': '<span class="badge bg-primary">Outbound</span>',
        'inbound': '<span class="badge bg-info">Inbound</span>'
    };
    
    let html = `
        <div class="mb-3">
            <strong>Phone Number:</strong> <a href="tel:${escapeHtml(sms.phone_number)}" style="color: var(--admin-primary-light);">${escapeHtml(sms.phone_number)}</a>
        </div>
        <div class="mb-3">
            <strong>Direction:</strong> ${directionBadge[sms.direction] || sms.direction}
        </div>
        <div class="mb-3">
            <strong>Status:</strong> ${statusBadge[sms.status] || sms.status}
        </div>
        <div class="mb-3">
            <strong>Date:</strong> ${new Date(sms.created_at).toLocaleString()}
        </div>
        ${sms.order_number ? `<div class="mb-3"><strong>Order:</strong> #${escapeHtml(sms.order_number)}</div>` : ''}
        <hr style="border-color: var(--admin-dark-tertiary);">
        <div class="mb-2"><strong>Message:</strong></div>
        <div class="border rounded p-3" style="background: var(--admin-dark); border-color: var(--admin-dark-tertiary) !important; color: var(--admin-text);">
            ${escapeHtml(sms.message)}
        </div>
        ${sms.gateway_response ? `
        <hr style="border-color: var(--admin-dark-tertiary);">
        <div class="mb-2"><strong>Gateway Response:</strong></div>
        <div class="border rounded p-3" style="max-height: 200px; overflow-y: auto; background: var(--admin-dark); border-color: var(--admin-dark-tertiary) !important;">
            <pre class="mb-0" style="font-size: 12px; color: var(--admin-text);">${escapeHtml(sms.gateway_response)}</pre>
        </div>
        ` : ''}
    `;
    
    document.getElementById('smsDetailsContent').innerHTML = html;
    new bootstrap.Modal(document.getElementById('smsDetailsModal')).show();
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
