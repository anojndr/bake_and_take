<?php
$flash = getFlashMessage();
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h2>Forgot Password</h2>
            <p>Enter your email and we’ll send a reset link</p>
        </div>

        <?php if ($flash): ?>
        <div class="alert-custom alert-<?php echo $flash['type']; ?>">
            <i class="bi bi-<?php echo $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'info' ? 'info-circle' : 'exclamation-circle'); ?>"></i>
            <?php echo $flash['message']; ?>
        </div>
        <?php endif; ?>

        <form action="includes/process_forgot_password.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <div class="input-icon-wrapper">
                    <i class="bi bi-envelope input-icon"></i>
                    <input type="email" class="form-control form-control-custom with-icon" name="email" placeholder="your@email.com" required>
                </div>
                <small class="text-muted">If an account exists for this email, you’ll receive a reset link.</small>
            </div>

            <button type="submit" class="btn btn-hero btn-hero-primary w-100 mb-3">
                <i class="bi bi-send me-2"></i> Send Reset Link
            </button>
        </form>

        <div class="auth-footer">
            Remembered your password? <a href="index.php?page=login">Back to sign in</a>
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
    padding-top: 100px;
    background: linear-gradient(135deg, var(--cream) 0%, var(--accent) 100%);
}

.auth-card {
    background: var(--white);
    padding: 3rem;
    border-radius: var(--radius-xl);
    box-shadow: var(--shadow-lg);
    width: 100%;
    max-width: 480px;
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
</style>
