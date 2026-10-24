<?php
/**
 * Zaffran Admin Panel — Login
 */
define('ZAFFRAN_APP', true);
require_once __DIR__ . '/auth.php';

initAdminSession();

// Redirect if already logged in
if (isAdminLoggedIn()) {
    header('Location: index.php');
    exit;
}

$errorMessage = '';
$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateAdminCsrfToken($token)) {
        $errorMessage = 'Security validation failed. Please refresh and try again.';
    } elseif (!checkAdminLoginRateLimit($ip)) {
        $errorMessage = 'Too many failed login attempts. Please wait 15 minutes before trying again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $errorMessage = 'Please enter both email and password.';
        } else {
            $db = getDbConnection();
            $stmt = $db->prepare("SELECT id, username, email, password_hash, role, status FROM admins WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password_hash'])) {
                if ($admin['status'] === 'suspended') {
                    $errorMessage = 'Your administrator account has been suspended. Please contact Super Admin.';
                } else {
                    // Success! Reset rate limit
                    resetAdminLoginRateLimit($ip);

                    // Regenerate session ID for fixation defense
                    session_regenerate_id(true);
                    $_SESSION['admin_id'] = (int)$admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_email'] = $admin['email'];
                    $_SESSION['admin_role'] = $admin['role'] ?? 'super_admin';
                    $_SESSION['admin_logged_in_at'] = time();

                    // Update last login
                    $db->prepare("UPDATE admins SET last_login_at = NOW() WHERE id = ?")->execute([$admin['id']]);

                    // Audit log
                    logAdminAudit('admin_login_success', 'admin', (string)$admin['id'], "Admin logged in successfully from IP {$ip}");

                    header('Location: index.php');
                    exit;
                }
            } else {
                recordAdminLoginFailure($ip);
                logAdminAudit('admin_login_failed', 'admin', null, "Failed login attempt for {$email} from IP {$ip}");
                $errorMessage = 'Invalid email address or password.';
            }
        }
    }
}

$csrfToken = getAdminCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login — Zaffran Botanical Portal</title>
  <link rel="stylesheet" href="admin.css">
  <link rel="icon" type="image/jpeg" href="../assets/images/logo.jpg">
  <style>
    body.login-body {
      background: #F4F1EA;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 20px;
    }
    .login-container {
      width: 100%;
      max-width: 420px;
      background: #FFFFFF;
      border: 1px solid var(--admin-card-border);
      border-radius: var(--admin-radius);
      box-shadow: var(--admin-shadow-lg);
      padding: 36px 32px;
    }
    .login-header {
      text-align: center;
      margin-bottom: 28px;
    }
    .login-logo {
      width: 52px;
      height: 52px;
      margin: 0 auto 16px;
      background: linear-gradient(135deg, var(--admin-saffron), #E89E2E);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #FFFFFF;
      font-size: 24px;
      font-weight: 700;
      box-shadow: 0 4px 12px rgba(212, 136, 26, 0.3);
    }
    .login-title {
      font-size: 22px;
      font-weight: 700;
      color: var(--admin-forest);
      letter-spacing: -0.3px;
    }
    .login-subtitle {
      font-size: 13px;
      color: var(--admin-text-muted);
      margin-top: 4px;
    }
  </style>
</head>
<body class="login-body">

<div class="login-container">
  <div class="login-header">
    <div class="login-logo">Z</div>
    <h1 class="login-title">Zaffran Portal</h1>
    <p class="login-subtitle">Internal Store Management System</p>
  </div>

  <?php if (!empty($errorMessage)): ?>
    <div class="notice-box notice-error" style="margin-bottom: 20px;">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
      <div><?php echo htmlspecialchars($errorMessage); ?></div>
    </div>
  <?php endif; ?>

  <form method="POST" action="login.php" novalidate>
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

    <div class="form-group">
      <label class="form-label" for="email">Administrator Email</label>
      <input type="email" id="email" name="email" class="form-control" placeholder="admin@zaffran.local" required autofocus value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
    </div>

    <div class="form-group">
      <label class="form-label" for="password">Password</label>
      <input type="password" id="password" name="password" class="form-control" placeholder="••••••••••••" required>
    </div>

    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 14px; margin-top: 8px;">
      Sign In to Portal
    </button>
  </form>

  <div style="text-align: center; margin-top: 24px; padding-top: 18px; border-top: 1px solid var(--admin-border-subtle); font-size: 12px; color: var(--admin-text-muted);">
    <a href="../index.php" style="color: var(--admin-sage); text-decoration: none; font-weight: 500;">&larr; Return to Zaffran Storefront</a>
  </div>
</div>

</body>
</html>
