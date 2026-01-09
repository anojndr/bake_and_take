<?php $flash = getFlashMessage(); ?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <a href="index.php" class="auth-logo">
                <i class="bi bi-cake2"></i> Bake & Take
            </a>
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
                    <input type="password" class="form-control form-control-custom with-icon" name="password" placeholder="••••••••" required>
                </div>
            </div>
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="remember" name="remember">
                    <label class="form-check-label" for="remember">Remember me</label>
                </div>
                <a href="#" class="forgot-link">Forgot password?</a>
            </div>
            
            <button type="submit" class="btn btn-hero btn-hero-primary w-100 mb-3">
                <i class="bi bi-box-arrow-in-right me-2"></i> Sign In
            </button>
            
            <div class="divider">
                <span>or continue with</span>
            </div>
            
            <div class="social-login">
                <button type="button" class="btn-social">
                    <i class="bi bi-google"></i>
                </button>
                <button type="button" class="btn-social">
                    <i class="bi bi-facebook"></i>
                </button>
                <button type="button" class="btn-social">
                    <i class="bi bi-apple"></i>
                </button>
            </div>
        </form>
        
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
</style>
