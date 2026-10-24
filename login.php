<?php
/**
 * Customer Passwordless Login / Sign Up Page
 * Email-Only OTP Authentication
 */

define('ZAFFRAN_APP', true);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
initSession();

// If already authenticated, redirect directly to account dashboard
if (CustomerAuth::isAuthenticated()) {
    header('Location: account.php');
    exit;
}

$errorMessage = '';
$successMessage = '';
$returnUrl = isset($_GET['return']) ? trim($_GET['return']) : (isset($_POST['return']) ? trim($_POST['return']) : '');

// Safe internal return URL verification (prevents open redirects)
if (!empty($returnUrl)) {
    $parsed = parse_url($returnUrl);
    if (!empty($parsed['host']) && $parsed['host'] !== $_SERVER['HTTP_HOST']) {
        $returnUrl = ''; // discard external URL
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        $errorMessage = 'Security token expired. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $res = CustomerAuth::requestOtp($email, $ip, $ua);
        if ($res['success']) {
            $_SESSION['pending_otp_challenge'] = $res['challenge_token'];
            $_SESSION['pending_otp_email'] = $res['email'];
            if (!empty($returnUrl)) {
                $_SESSION['auth_return_url'] = $returnUrl;
            }

            header('Location: verify-otp.php');
            exit;
        } else {
            $errorMessage = $res['message'];
        }
    }
}

$pageTitle = 'Sign In / Register | Zaffran Artisanal Store';
$pageDescription = 'Access your Zaffran customer account using passwordless email OTP verification.';

require_once __DIR__ . '/includes/header.php';
?>

<main id="main-content" class="auth-page-section">
  <div class="container">
    
    <div class="auth-card">
      
      <div class="auth-brand-badge" aria-hidden="true">Z</div>
      <h1 class="auth-title">Welcome to Zaffran</h1>
      <p class="auth-subtitle">
        Enter your email to sign in or register instantly. We will send you a 6-digit verification code. No password required.
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

      <form action="login.php" method="POST" class="auth-form">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCsrfToken()); ?>">
        <?php if (!empty($returnUrl)): ?>
          <input type="hidden" name="return" value="<?php echo htmlspecialchars($returnUrl); ?>">
        <?php endif; ?>

        <div class="form-group">
          <label for="customer_email" class="form-label">Email Address <span class="required-star">*</span></label>
          <input type="email" id="customer_email" name="email" class="form-control" placeholder="name@example.com" required autofocus autocomplete="email">
        </div>

        <button type="submit" class="btn btn-primary auth-btn-full" id="send-otp-btn">
          Send 6-Digit Code &rarr;
        </button>
      </form>

      <div style="margin-top:var(--space-2xl); font-size:0.8125rem; color:var(--color-text-muted); line-height:1.6;">
        <p>By proceeding, you agree to Zaffran's <a href="index.php#terms" style="text-decoration:underline;">Terms</a> and <a href="index.php#privacy" style="text-decoration:underline;">Privacy Policy</a>.</p>
        <p style="margin-top:12px;">
          <a href="shop.php" style="color:var(--color-forest-800); font-weight:600;">&larr; Return to Shopping</a>
        </p>
      </div>

    </div>

  </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
