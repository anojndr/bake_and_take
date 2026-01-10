<?php 
// Check if there's a pending phone verification
if (!isset($_SESSION['pending_verification_user_id']) || 
    !isset($_SESSION['pending_verification_phone']) ||
    $_SESSION['pending_verification_method'] !== 'phone') {
    header('Location: index.php?page=register');
    exit;
}

$maskedPhone = preg_replace('/(\+\d{2})(\d{3})(\d+)(\d{4})/', '$1 $2 *** $4', $_SESSION['pending_verification_phone']);
?>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="otp-icon-wrapper">
                <i class="bi bi-phone-vibrate"></i>
            </div>
            <h2>Verify Your Phone</h2>
            <p>Enter the 6-digit code sent to<br><strong><?php echo htmlspecialchars($maskedPhone); ?></strong></p>
        </div>
        
        <div id="otp-alert" class="alert-custom" style="display: none;"></div>
        
        <form id="otpForm">
            <div class="otp-input-group">
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="0" autofocus>
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="1">
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="2">
                <span class="otp-separator">-</span>
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="3">
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="4">
                <input type="text" class="otp-input" maxlength="1" pattern="[0-9]" inputmode="numeric" data-index="5">
            </div>
            
            <button type="submit" class="btn btn-hero btn-hero-primary w-100 mt-4" id="verifyBtn">
                <i class="bi bi-check-circle me-2"></i> Verify Phone
            </button>
        </form>
        
        <div class="resend-section">
            <p class="resend-text">Didn't receive the code?</p>
            <button type="button" class="btn-resend" id="resendBtn" disabled>
                Resend Code <span id="countdown">(60s)</span>
            </button>
        </div>
        
        <div class="auth-footer">
            <a href="index.php?page=register"><i class="bi bi-arrow-left me-1"></i> Back to Registration</a>
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
    max-width: 440px;
    text-align: center;
}

.auth-header { margin-bottom: 2rem; }
.auth-header h2 { color: var(--dark); margin-bottom: 0.5rem; }
.auth-header p { color: var(--text-secondary); line-height: 1.6; }
.auth-header p strong { color: var(--dark); }

.otp-icon-wrapper {
    width: 80px;
    height: 80px;
    margin: 0 auto 1.5rem;
    background: linear-gradient(135deg, var(--accent) 0%, var(--primary) 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: pulse 2s infinite;
}

.otp-icon-wrapper i {
    font-size: 2.5rem;
    color: var(--white);
}

@keyframes pulse {
    0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(232, 180, 130, 0.4); }
    50% { transform: scale(1.05); box-shadow: 0 0 0 15px rgba(232, 180, 130, 0); }
}

.otp-input-group {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    align-items: center;
}

.otp-input {
    width: 52px;
    height: 60px;
    text-align: center;
    font-size: 1.5rem;
    font-weight: 700;
    border: 2px solid var(--cream-dark);
    border-radius: var(--radius-md);
    transition: all 0.2s ease;
    color: var(--dark);
}

.otp-input:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(232, 180, 130, 0.2);
    outline: none;
}

.otp-input.filled {
    background: var(--accent);
    border-color: var(--primary);
}

.otp-input.error {
    border-color: #dc3545;
    animation: shake 0.3s ease;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

.otp-separator {
    font-size: 1.5rem;
    color: var(--text-light);
    padding: 0 0.25rem;
}

.resend-section {
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--cream-dark);
}

.resend-text {
    color: var(--text-secondary);
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.btn-resend {
    background: none;
    border: none;
    color: var(--primary-dark);
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    padding: 0.5rem 1rem;
    border-radius: var(--radius-md);
}

.btn-resend:hover:not(:disabled) {
    color: var(--secondary);
    background: var(--accent);
}

.btn-resend:disabled {
    color: var(--text-light);
    cursor: not-allowed;
}

.btn-resend #countdown {
    color: var(--text-light);
    font-weight: normal;
}

.auth-footer {
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--cream-dark);
}

.auth-footer a {
    color: var(--text-secondary);
    text-decoration: none;
    transition: color 0.2s ease;
}

.auth-footer a:hover {
    color: var(--primary-dark);
}

.alert-custom {
    padding: 1rem;
    border-radius: var(--radius-md);
    margin-bottom: 1.5rem;
    text-align: left;
}

.alert-custom.alert-error {
    background: #fee2e2;
    color: #dc2626;
    border: 1px solid #fecaca;
}

.alert-custom.alert-success {
    background: #dcfce7;
    color: #16a34a;
    border: 1px solid #bbf7d0;
}

@media (max-width: 480px) {
    .otp-input {
        width: 44px;
        height: 52px;
        font-size: 1.25rem;
    }
    
    .otp-input-group {
        gap: 0.35rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('.otp-input');
    const form = document.getElementById('otpForm');
    const verifyBtn = document.getElementById('verifyBtn');
    const resendBtn = document.getElementById('resendBtn');
    const countdownSpan = document.getElementById('countdown');
    const alertBox = document.getElementById('otp-alert');
    
    let countdown = 60;
    
    // Start countdown timer
    function startCountdown() {
        countdown = 60;
        resendBtn.disabled = true;
        countdownSpan.style.display = 'inline';
        
        const timer = setInterval(() => {
            countdown--;
            countdownSpan.textContent = `(${countdown}s)`;
            
            if (countdown <= 0) {
                clearInterval(timer);
                resendBtn.disabled = false;
                countdownSpan.style.display = 'none';
            }
        }, 1000);
    }
    
    startCountdown();
    
    // Handle OTP input
    inputs.forEach((input, index) => {
        input.addEventListener('input', function(e) {
            // Only allow numbers
            this.value = this.value.replace(/[^0-9]/g, '');
            
            if (this.value) {
                this.classList.add('filled');
                // Move to next input
                if (index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
            } else {
                this.classList.remove('filled');
            }
        });
        
        input.addEventListener('keydown', function(e) {
            // Handle backspace
            if (e.key === 'Backspace' && !this.value && index > 0) {
                inputs[index - 1].focus();
            }
        });
        
        // Handle paste
        input.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedData = e.clipboardData.getData('text').replace(/[^0-9]/g, '');
            
            for (let i = 0; i < Math.min(pastedData.length, inputs.length); i++) {
                inputs[i].value = pastedData[i];
                inputs[i].classList.add('filled');
            }
            
            // Focus last filled input or beyond
            const focusIndex = Math.min(pastedData.length, inputs.length - 1);
            inputs[focusIndex].focus();
        });
    });
    
    // Show alert
    function showAlert(message, type) {
        alertBox.textContent = message;
        alertBox.className = 'alert-custom alert-' + type;
        alertBox.style.display = 'block';
    }
    
    // Hide alert
    function hideAlert() {
        alertBox.style.display = 'none';
    }
    
    // Get OTP value
    function getOTPValue() {
        return Array.from(inputs).map(input => input.value).join('');
    }
    
    // Form submission
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const otp = getOTPValue();
        
        if (otp.length !== 6) {
            showAlert('Please enter all 6 digits.', 'error');
            inputs.forEach(input => input.classList.add('error'));
            setTimeout(() => inputs.forEach(input => input.classList.remove('error')), 300);
            return;
        }
        
        hideAlert();
        verifyBtn.disabled = true;
        verifyBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i> Verifying...';
        
        try {
            const formData = new FormData();
            formData.append('action', 'verify');
            formData.append('otp', otp);
            
            const response = await fetch('includes/verify_phone_api.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                showAlert(result.message, 'success');
                setTimeout(() => {
                    window.location.href = result.redirect || 'index.php?page=login';
                }, 1500);
            } else {
                showAlert(result.message, 'error');
                verifyBtn.disabled = false;
                verifyBtn.innerHTML = '<i class="bi bi-check-circle me-2"></i> Verify Phone';
            }
        } catch (error) {
            showAlert('An error occurred. Please try again.', 'error');
            verifyBtn.disabled = false;
            verifyBtn.innerHTML = '<i class="bi bi-check-circle me-2"></i> Verify Phone';
        }
    });
    
    // Resend OTP
    resendBtn.addEventListener('click', async function() {
        this.disabled = true;
        this.innerHTML = 'Sending...';
        
        try {
            const formData = new FormData();
            formData.append('action', 'resend');
            
            const response = await fetch('includes/verify_phone_api.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                showAlert(result.message, 'success');
                // Clear inputs
                inputs.forEach(input => {
                    input.value = '';
                    input.classList.remove('filled');
                });
                inputs[0].focus();
            } else {
                showAlert(result.message, 'error');
            }
            
            this.innerHTML = 'Resend Code <span id="countdown">(60s)</span>';
            startCountdown();
            
        } catch (error) {
            showAlert('Failed to resend code. Please try again.', 'error');
            this.innerHTML = 'Resend Code';
            this.disabled = false;
        }
    });
});
</script>
