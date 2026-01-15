<?php $flash = getFlashMessage(); ?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h2>Welcome Back</h2>
            <p>Sign in to continue shopping</p>
        </div>
        
        <?php if ($flash): ?>
        <div class="alert-custom alert-<?php echo $flash['type']; ?>">
            <i class="bi bi-info-circle"></i>
            <?php echo $flash['message']; ?>
        </div>
        <?php endif; ?>
        
        <form action="includes/process_login.php" method="POST" id="loginForm">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <div class="input-icon-wrapper">
                    <i class="bi bi-envelope input-icon"></i>
                    <input type="email" class="form-control form-control-custom with-icon" name="email" placeholder="your@email.com" required>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="input-icon-wrapper">
                    <i class="bi bi-lock input-icon"></i>
                    <input type="password" class="form-control form-control-custom with-icon has-toggle" name="password" id="password" placeholder="••••••••" required>
                    <button type="button" class="password-toggle" onclick="togglePassword('password')" aria-label="Show password">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>
            
            <div class="mb-4 text-center">
                <a href="index.php?page=forgot-password" class="forgot-link">Forgot password?</a>
            </div>
            
            <button type="submit" class="btn btn-hero btn-hero-primary w-100 mb-3">
                <i class="bi bi-box-arrow-in-right me-2"></i> Sign In
            </button>
        </form>
        
        <div class="divider">
            <span>or continue with</span>
        </div>
        
        <button type="button" class="btn btn-ymzm w-100 mb-3" onclick="showYMZMLogin()">
            <i class="bi bi-box-arrow-in-right me-2"></i> Login with YMZM
        </button>
        
        <!-- YMZM Login Modal -->
        <div class="ymzm-login-overlay" id="ymzmLoginOverlay" onclick="hideYMZMLogin(event)">
            <div class="ymzm-login-modal" onclick="event.stopPropagation()">
                <div class="ymzm-modal-header">
                    <h3><i class="bi bi-shield-lock me-2"></i>Login with YMZM</h3>
                    <button type="button" class="btn-close-ymzm" onclick="hideYMZMLogin()">&times;</button>
                </div>
                <p class="ymzm-modal-subtitle">Use your YMZM account credentials</p>
                
                <form action="includes/process_ymzm_login.php" method="POST" id="ymzmLoginForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">YMZM Email</label>
                        <div class="input-icon-wrapper">
                            <i class="bi bi-envelope input-icon"></i>
                            <input type="email" class="form-control form-control-custom with-icon" name="email" placeholder="your@ymzm-email.com" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">YMZM Password</label>
                        <div class="input-icon-wrapper">
                            <i class="bi bi-lock input-icon"></i>
                            <input type="password" class="form-control form-control-custom with-icon has-toggle" name="password" id="ymzmPassword" placeholder="••••••••" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('ymzmPassword')" aria-label="Show password">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-ymzm w-100">
                        <i class="bi bi-box-arrow-in-right me-2"></i> Sign In with YMZM
                    </button>
                </form>
            </div>
        </div>
        
        <div class="auth-footer">
            Don't have an account? <a href="index.php?page=register">Create one</a>
        </div>
    </div>
</div>

<style>
.auth-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    background: linear-gradient(135deg, var(--cream) 0%, var(--accent) 100%);
}

.auth-card {
    background: var(--white);
    padding: 3rem;
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-lg);
    width: 100%;
    max-width: 420px;
}

.auth-logo {
    font-family: var(--font-display);
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--secondary);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
}

.auth-logo i {
    font-size: 2rem;
    color: var(--primary);
}

.auth-header {
    text-align: center;
    margin-bottom: 2rem;
}

.auth-header h2 {
    color: var(--dark);
    margin-bottom: 0.5rem;
}

.auth-header p {
    color: var(--text-secondary);
}

.input-icon-wrapper {
    position: relative;
}

.input-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-light);
}

.form-control-custom.with-icon {
    padding-left: 2.75rem;
}

.forgot-link {
    color: var(--primary-dark);
    font-size: 0.9rem;
}

.forgot-link:hover {
    color: var(--secondary);
}

.divider {
    text-align: center;
    margin: 1.5rem 0;
    position: relative;
}

.divider::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: var(--cream-dark);
}

.divider span {
    background: var(--white);
    padding: 0 1rem;
    color: var(--text-light);
    font-size: 0.9rem;
    position: relative;
}

.social-login {
    display: flex;
    justify-content: center;
    gap: 1rem;
}

.btn-social {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    border: 2px solid var(--cream-dark);
    background: var(--white);
    color: var(--text-secondary);
    font-size: 1.25rem;
    cursor: pointer;
    transition: var(--transition);
}

.btn-social:hover {
    border-color: var(--primary);
    color: var(--primary);
    background: var(--accent);
}

.auth-footer {
    text-align: center;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--cream-dark);
    color: var(--text-secondary);
}

.auth-footer a {
    color: var(--primary-dark);
    font-weight: 600;
}

.auth-footer a:hover {
    color: var(--secondary);
}

/* Alert Styles */
.alert-custom {
    padding: 1rem 1.25rem;
    border-radius: var(--radius-md);
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.95rem;
}

.alert-custom i {
    font-size: 1.1rem;
    flex-shrink: 0;
}

.alert-error {
    background: #fee2e2;
    color: #dc2626;
    border: 1px solid #fecaca;
}

.alert-success {
    background: #dcfce7;
    color: #16a34a;
    border: 1px solid #bbf7d0;
}

.alert-info {
    background: #dbeafe;
    color: #2563eb;
    border: 1px solid #bfdbfe;
}

/* YMZM Button */
.btn-ymzm {
    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: var(--radius-md);
    font-weight: 600;
    transition: var(--transition);
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-ymzm:hover {
    background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
}

/* YMZM Modal Overlay */
.ymzm-login-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(4px);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.ymzm-login-overlay.active {
    display: flex;
}

.ymzm-login-modal {
    background: var(--white);
    padding: 2rem;
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-lg);
    width: 100%;
    max-width: 400px;
    animation: modalSlideIn 0.3s ease;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.ymzm-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.ymzm-modal-header h3 {
    margin: 0;
    color: #4f46e5;
    font-size: 1.25rem;
}

.btn-close-ymzm {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--text-light);
    padding: 0;
    line-height: 1;
}

.btn-close-ymzm:hover {
    color: var(--dark);
}

.ymzm-modal-subtitle {
    color: var(--text-secondary);
    font-size: 0.9rem;
    margin-bottom: 1.5rem;
}
</style>

<script>
function showYMZMLogin() {
    document.getElementById('ymzmLoginOverlay').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function hideYMZMLogin(event) {
    if (event && event.target !== document.getElementById('ymzmLoginOverlay')) {
        return;
    }
    document.getElementById('ymzmLoginOverlay').classList.remove('active');
    document.body.style.overflow = '';
}

// Close modal on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        hideYMZMLogin();
    }
});
</script>
