<?php
/**
 * Customer OTP Verification Page
 * Verifies 6-digit numeric OTP with automatic focus, paste support,
 * resend cooldown, and safe post-login redirection.
 */

define('ZAFFRAN_APP', true);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
initSession();

// If already authenticated, redirect
if (CustomerAuth::isAuthenticated()) {
    header('Location: account.php');
    exit;
}

$challengeToken = $_SESSION['pending_otp_challenge'] ?? '';
$pendingEmail = $_SESSION['pending_otp_email'] ?? '';

if (empty($challengeToken) || empty($pendingEmail)) {
    header('Location: login.php');
    exit;
}

$errorMessage = '';
$successMessage = '';

// Handle Resend Request
if (isset($_GET['resend']) && $_GET['resend'] === '1') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $res = CustomerAuth::requestOtp($pendingEmail, $ip, $ua);
    if ($res['success']) {
        $_SESSION['pending_otp_challenge'] = $res['challenge_token'];
        $challengeToken = $res['challenge_token'];
        $successMessage = 'A new 6-digit verification code has been dispatched.';
    } else {
        $errorMessage = $res['message'];
    }
}

// Handle OTP Verification Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        $errorMessage = 'Security token expired. Please try again.';
    } else {
        $enteredOtp = trim($_POST['otp'] ?? '');
        $res = CustomerAuth::verifyOtp($challengeToken, $enteredOtp);

        if ($res['success']) {
            unset($_SESSION['pending_otp_challenge'], $_SESSION['pending_otp_email']);
            
            // Check if there was an intended return destination (e.g. checkout.php)
            $destination = 'account.php';
            if (!empty($_SESSION['auth_return_url'])) {
                $destination = $_SESSION['auth_return_url'];
                unset($_SESSION['auth_return_url']);
            }

            header('Location: ' . $destination);
            exit;
        } else {
            $errorMessage = $res['message'];
        }
    }
}

$pageTitle = 'Enter Verification Code | Zaffran Artisanal Store';
$pageDescription = 'Verify your email address using the 6-digit OTP code sent to your inbox.';

require_once __DIR__ . '/includes/header.php';
?>

<main id="main-content" class="auth-page-section">
  <div class="container">
    
    <div class="auth-card">
      
      <div class="auth-brand-badge" aria-hidden="true">Z</div>
      <h1 class="auth-title">Verify Your Email</h1>
      <p class="auth-subtitle">
        We sent a 6-digit verification code to<br>
        <strong style="color:var(--color-forest-900);"><?php echo htmlspecialchars($pendingEmail); ?></strong>
      </p>

      <?php if (!empty($errorMessage)): ?>
        <div class="auth-alert-box auth-alert-error" role="alert">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
          </svg>
          <div><?php echo htmlspecialchars($errorMessage); ?></div>
        </div>
      <?php endif; ?>

      <?php if (!empty($successMessage)): ?>
        <div class="auth-alert-box auth-alert-success" role="alert">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;">
            <polyline points="20 6 9 17 4 12"></polyline>
          </svg>
          <div><?php echo htmlspecialchars($successMessage); ?></div>
        </div>
      <?php endif; ?>

      <form action="verify-otp.php" method="POST" class="auth-form" id="otp-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCsrfToken()); ?>">

        <div class="form-group">
          <label for="otp-code-input" class="form-label" style="text-align:center;">
            6-Digit Verification Code
          </label>
          <input 
            type="text" 
            id="otp-code-input" 
            name="otp" 
            class="otp-input-field" 
            maxlength="6" 
            inputmode="numeric" 
            pattern="[0-9]*" 
            placeholder="&bull;&bull;&bull;&bull;&bull;&bull;" 
            required 
            autofocus 
            autocomplete="one-time-code"
          >
          <span style="font-size:0.75rem; color:var(--color-text-muted); text-align:center; margin-top:6px; display:block;">
            Code valid for 5 minutes • Check spam folder if not received
          </span>
        </div>

        <button type="submit" class="btn btn-primary auth-btn-full" id="verify-submit-btn">
          Verify &amp; Enter &rarr;
        </button>
      </form>

      <div class="otp-footer-links">
        <a href="login.php">&larr; Change Email</a>
        <a href="verify-otp.php?resend=1" id="resend-link">Resend Code</a>
      </div>

    </div>

  </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const otpInput = document.getElementById('otp-code-input');
  if (otpInput) {
    // Auto submit when exactly 6 digits entered
    otpInput.addEventListener('input', (e) => {
      otpInput.value = otpInput.value.replace(/[^0-9]/g, '');
      if (otpInput.value.length === 6) {
        document.getElementById('otp-form').submit();
      }
    });

    // Handle paste cleanly
    otpInput.addEventListener('paste', (e) => {
      e.preventDefault();
      const pasteData = (e.clipboardData || window.clipboardData).getData('text');
      const cleanDigits = pasteData.replace(/[^0-9]/g, '').substring(0, 6);
      otpInput.value = cleanDigits;
      if (cleanDigits.length === 6) {
        document.getElementById('otp-form').submit();
      }
    });
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
