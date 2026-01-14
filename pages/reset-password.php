<?php
$flash = getFlashMessage();
$token = $_GET['token'] ?? '';
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <h2>Reset Password</h2>
            <p>Create a new password for your account</p>
        </div>

        <?php if ($flash): ?>
        <div class="alert-custom alert-<?php echo $flash['type']; ?>">
            <i class="bi bi-<?php echo $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'info' ? 'info-circle' : 'exclamation-circle'); ?>"></i>
            <?php echo $flash['message']; ?>
        </div>
        <?php endif; ?>

        <?php if (empty($token)): ?>
            <div class="alert-custom alert-error">
                <i class="bi bi-exclamation-circle"></i>
                Missing reset token. Please request a new link.
            </div>
            <div class="auth-footer">
                <a href="index.php?page=forgot-password">Request a new reset link</a>
            </div>
        <?php else: ?>
            <form action="includes/process_reset_password.php" method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">

                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <div class="input-icon-wrapper">
                        <i class="bi bi-lock input-icon"></i>
                        <input type="password" class="form-control form-control-custom with-icon has-toggle" name="password" id="reset_password" placeholder="Min 8 characters" required minlength="8">
                        <button type="button" class="password-toggle" onclick="togglePassword('reset_password')" aria-label="Show password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Confirm New Password</label>
                    <div class="input-icon-wrapper">
                        <i class="bi bi-lock-fill input-icon"></i>
                        <input type="password" class="form-control form-control-custom with-icon has-toggle" name="confirm_password" id="reset_confirm_password" placeholder="Repeat new password" required minlength="8">
                        <button type="button" class="password-toggle" onclick="togglePassword('reset_confirm_password')" aria-label="Show password">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-hero btn-hero-primary w-100 mb-3">
                    <i class="bi bi-shield-check me-2"></i> Update Password
                </button>
            </form>

            <div class="auth-footer">
                <a href="index.php?page=login">Back to sign in</a>
            </div>
        <?php endif; ?>
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
