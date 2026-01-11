<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// Check if admin is logged in (separate admin session)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$allowedPages = ['dashboard', 'orders', 'products', 'categories', 'users', 'messages', 'sms', 'backup'];

if (!in_array($page, $allowedPages)) {
    $page = 'dashboard';
}

// Fetch notification data
$notifications = [];
$notificationCount = 0;

if ($pdo) {
    try {
        // Pending orders
        $stmt = $pdo->query("SELECT id, order_number, first_name, created_at FROM orders WHERE status = 'pending' ORDER BY created_at DESC LIMIT 5");
        $pendingOrders = $stmt->fetchAll();
        foreach ($pendingOrders as $order) {
            $notifications[] = [
                'type' => 'order',
                'icon' => 'bi-cart-check',
                'color' => 'warning',
                'title' => 'New Order #' . $order['order_number'],
                'text' => 'From ' . $order['first_name'],
                'time' => $order['created_at'],
                'link' => 'index.php?page=orders'
            ];
        }
        
        // Unread messages
        $stmt = $pdo->query("SELECT id, first_name, subject, created_at FROM contact_messages WHERE read_status = 0 ORDER BY created_at DESC LIMIT 5");
        $unreadMessages = $stmt->fetchAll();
        foreach ($unreadMessages as $msg) {
            $notifications[] = [
                'type' => 'message',
                'icon' => 'bi-envelope',
                'color' => 'info',
                'title' => 'New message from ' . $msg['first_name'],
                'text' => $msg['subject'],
                'time' => $msg['created_at'],
                'link' => 'index.php?page=messages'
            ];
        }
        
        // Sort by time
        usort($notifications, fn($a, $b) => strtotime($b['time']) - strtotime($a['time']));
        $notifications = array_slice($notifications, 0, 5);
        $notificationCount = count($pendingOrders) + count($unreadMessages);
        
    } catch (PDOException $e) {
        // Ignore
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin Panel - Bake & Take</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Admin CSS -->
    <link href="assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <!-- Sidebar -->
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="sidebar-header">
            <a href="index.php" class="sidebar-brand">
                <i class="bi bi-cake2"></i>
                <span>Bake & Take</span>
            </a>
            <button class="sidebar-toggle d-lg-none" id="sidebarClose">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-section">
                <span class="nav-section-title">Main</span>
                <a href="index.php" class="nav-link <?php echo $page === 'dashboard' ? 'active' : ''; ?>">
                    <i class="bi bi-grid-1x2"></i>
                    <span>Dashboard</span>
                </a>
            </div>
            
            <div class="nav-section">
                <span class="nav-section-title">Management</span>
                <a href="index.php?page=orders" class="nav-link <?php echo $page === 'orders' ? 'active' : ''; ?>">
                    <i class="bi bi-receipt"></i>
                    <span>Orders</span>
                </a>
                <a href="index.php?page=products" class="nav-link <?php echo $page === 'products' ? 'active' : ''; ?>">
                    <i class="bi bi-box-seam"></i>
                    <span>Products</span>
                </a>
                <a href="index.php?page=categories" class="nav-link <?php echo $page === 'categories' ? 'active' : ''; ?>">
                    <i class="bi bi-tags"></i>
                    <span>Categories</span>
                </a>
                <a href="index.php?page=users" class="nav-link <?php echo $page === 'users' ? 'active' : ''; ?>">
                    <i class="bi bi-people"></i>
                    <span>Users</span>
                </a>
            </div>
            
            <div class="nav-section">
                <span class="nav-section-title">Communication</span>
                <a href="index.php?page=messages" class="nav-link <?php echo $page === 'messages' ? 'active' : ''; ?>">
                    <i class="bi bi-envelope"></i>
                    <span>Messages</span>
                </a>
                <a href="index.php?page=sms" class="nav-link <?php echo $page === 'sms' ? 'active' : ''; ?>">
                    <i class="bi bi-phone"></i>
                    <span>SMS Gateway</span>
                </a>
            </div>
            
            <div class="nav-section">
                <span class="nav-section-title">Settings</span>
                <a href="index.php?page=backup" class="nav-link <?php echo $page === 'backup' ? 'active' : ''; ?>">
                    <i class="bi bi-database-gear"></i>
                    <span>Database Backup</span>
                </a>
            </div>
        </nav>
        
        <div class="sidebar-footer">
            <a href="../index.php" class="btn btn-outline-light btn-sm w-100">
                <i class="bi bi-house me-2"></i>View Site
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <div class="admin-main">
        <!-- Top Header -->
        <header class="admin-header">
            <button class="sidebar-toggle d-lg-none" id="sidebarOpen">
                <i class="bi bi-list"></i>
            </button>
            
            <div class="header-search">
                <i class="bi bi-search"></i>
                <input type="text" placeholder="Search..." class="form-control">
            </div>
            
            <div class="header-actions">
                <div class="dropdown">
                    <button class="btn-icon" data-bs-toggle="dropdown">
                        <i class="bi bi-bell"></i>
                        <?php if ($notificationCount > 0): ?>
                        <span class="notification-badge"><?php echo $notificationCount > 9 ? '9+' : $notificationCount; ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" style="width: 320px; padding: 0;">
                        <div class="p-3" style="border-bottom: 1px solid var(--admin-dark-tertiary);">
                            <h6 class="mb-0" style="color: var(--admin-text);">Notifications</h6>
                        </div>
                        <div style="max-height: 300px; overflow-y: auto;">
                            <?php if (empty($notifications)): ?>
                            <div class="p-4 text-center">
                                <i class="bi bi-bell-slash" style="font-size: 2rem; color: var(--admin-text-muted);"></i>
                                <p class="mb-0 mt-2" style="color: var(--admin-text-muted);">No new notifications</p>
                            </div>
                            <?php else: ?>
                            <?php foreach ($notifications as $notif): ?>
                            <a href="<?php echo $notif['link']; ?>" class="d-flex align-items-start gap-3 p-3 text-decoration-none" style="border-bottom: 1px solid var(--admin-dark-tertiary);" onmouseover="this.style.background='var(--admin-dark)'" onmouseout="this.style.background='transparent'">
                                <div style="width: 40px; height: 40px; background: rgba(<?php echo $notif['color'] === 'warning' ? '245, 158, 11' : '59, 130, 246'; ?>, 0.15); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <i class="bi <?php echo $notif['icon']; ?>" style="color: var(--admin-<?php echo $notif['color']; ?>);"></i>
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <div style="color: var(--admin-text); font-weight: 500; font-size: 0.875rem;"><?php echo sanitize($notif['title']); ?></div>
                                    <div style="color: var(--admin-text-muted); font-size: 0.75rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo sanitize($notif['text']); ?></div>
                                    <div style="color: var(--admin-text-muted); font-size: 0.7rem; margin-top: 0.25rem;">
                                        <?php echo date('M d, g:i A', strtotime($notif['time'])); ?>
                                    </div>
                                </div>
                            </a>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($notifications)): ?>
                        <div class="p-2" style="border-top: 1px solid var(--admin-dark-tertiary);">
                            <a href="index.php?page=orders&status=pending" class="btn btn-sm w-100" style="background: var(--admin-dark); color: var(--admin-text-muted);">View All</a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="dropdown">
                    <button class="user-dropdown" data-bs-toggle="dropdown">
                        <div class="user-avatar">
                            <i class="bi bi-person"></i>
                        </div>
                        <div class="user-info">
                            <span class="user-name"><?php echo sanitize($_SESSION['admin_name']); ?></span>
                            <span class="user-role">Administrator</span>
                        </div>
                        <i class="bi bi-chevron-down"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>Profile</a></li>
                        <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i>Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="includes/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <main class="admin-content">
            <?php 
            // Display flash messages
            $flash = getFlashMessage();
            if ($flash): 
            ?>
            <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : $flash['type']; ?> alert-dismissible fade show">
                <?php echo $flash['message']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php include "pages/{$page}.php"; ?>
        </main>
    </div>

    <!-- Sidebar Overlay for Mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Admin JS -->
    <script src="assets/js/admin.js"></script>
</body>
</html>
