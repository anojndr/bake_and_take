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
$allowedPages = ['dashboard', 'orders', 'products', 'categories', 'users', 'backup'];

if (!in_array($page, $allowedPages)) {
    $page = 'dashboard';
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
            

            
            <div class="header-actions">

                
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

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Admin JS -->
    <script src="assets/js/admin.js"></script>
</body>
</html>
