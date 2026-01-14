<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

// If already logged in as admin, redirect to dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin Login - Bake & Take</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --admin-primary: #6366f1;
            --admin-primary-dark: #4f46e5;
            --admin-dark: #0f172a;
            --admin-dark-secondary: #1e293b;
            --admin-dark-tertiary: #334155;
            --admin-text: #f1f5f9;
            --admin-text-muted: #94a3b8;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--admin-dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .login-container {
            width: 100%;
            max-width: 420px;
        }
        
        .login-brand {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-brand i {
            font-size: 3rem;
            color: var(--admin-primary);
            margin-bottom: 1rem;
            display: block;
        }
        
        .login-brand h1 {
            color: var(--admin-text);
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .login-brand p {
            color: var(--admin-text-muted);
            font-size: 0.95rem;
        }
        
        .login-card {
            background: var(--admin-dark-secondary);
            border: 1px solid var(--admin-dark-tertiary);
            border-radius: 16px;
            padding: 2rem;
        }
        
        .form-label {
            color: var(--admin-text);
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            background: var(--admin-dark);
            border: 1px solid var(--admin-dark-tertiary);
            color: var(--admin-text);
            padding: 0.875rem 1rem;
            border-radius: 10px;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }
        
        .form-control::placeholder {
            color: var(--admin-text-muted);
        }
        
        .form-control:focus {
            background: var(--admin-dark);
            border-color: var(--admin-primary);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
            color: var(--admin-text);
        }
        
        .input-group-text {
            background: var(--admin-dark);
            border: 1px solid var(--admin-dark-tertiary);
            border-right: none;
            color: var(--admin-text-muted);
            border-radius: 10px 0 0 10px;
        }
        
        .input-group .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
        }
        
        .input-group:focus-within .input-group-text {
            border-color: var(--admin-primary);
        }
        
        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, var(--admin-primary) 0%, var(--admin-primary-dark) 100%);
            border: none;
            color: white;
            padding: 0.875rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-top: 1rem;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(99, 102, 241, 0.4);
        }
        
        .alert {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
            border: none;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
        
        .back-link {
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            color: var(--admin-text-muted);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.2s ease;
        }
        
        .back-link:hover {
            color: var(--admin-primary);
        }
        
        .back-link i {
            margin-right: 0.5rem;
        }
        
        /* Password Toggle */
        .password-toggle-btn {
            background: var(--admin-dark-secondary);
            border-color: var(--admin-dark-tertiary);
            color: var(--admin-text-muted);
            transition: all 0.2s ease;
        }
        
        .password-toggle-btn:hover {
            background: var(--admin-dark-tertiary);
            border-color: var(--admin-primary);
            color: var(--admin-primary);
        }
        
        .password-toggle-btn:focus {
            box-shadow: 0 0 0 0.25rem rgba(99, 102, 241, 0.25);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-brand">
            <i class="bi bi-shield-lock"></i>
            <h1>Admin Panel</h1>
            <p>Bake & Take Management System</p>
        </div>
        
        <div class="login-card">
            <?php if ($error): ?>
            <div class="alert">
                <i class="bi bi-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            
            <form action="includes/process_login.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="admin@email.com" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" id="admin_password" class="form-control" placeholder="Enter your password" required>
                        <button type="button" class="btn btn-outline-secondary password-toggle-btn" onclick="togglePassword('admin_password')" aria-label="Show password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Sign In to Admin
                </button>
            </form>
        </div>
        
        <a href="../index.php" class="back-link">
            <i class="bi bi-arrow-left"></i>Back to Main Site
        </a>
    </div>
    
    <script>
        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const button = input.parentElement.querySelector('.password-toggle-btn');
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
                button.setAttribute('aria-label', 'Hide password');
            } else {
                input.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
                button.setAttribute('aria-label', 'Show password');
            }
        }
    </script>
</body>
</html>
