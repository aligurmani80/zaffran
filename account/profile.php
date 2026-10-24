<?php
/**
 * Customer Profile Editing Page
 * Allows editing full name and phone number.
 * Email address remains read-only to prevent unauthorized identity hijacks.
 */

define('ZAFFRAN_APP', true);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/auth.php';
initSession();

// Authentication Protection
if (!CustomerAuth::isAuthenticated()) {
    header('Location: ../login.php?return=' . urlencode('account/profile.php'));
    exit;
}

$customer = CustomerAuth::getCustomer();
if (!$customer) {
    CustomerAuth::logout();
    header('Location: ../login.php');
    exit;
}

$errorMessage = '';
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($csrfToken)) {
        $errorMessage = 'Security token expired. Please try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');

        $res = CustomerAuth::updateProfile((int)$customer['id'], $name, $phone);
        if ($res['success']) {
            $successMessage = $res['message'];
            // Re-fetch updated customer record
            $customer = CustomerAuth::getCustomer();
        } else {
            $errorMessage = $res['message'];
        }
    }
}

$pageTitle = 'Profile Details | Zaffran';
$pageDescription = 'Update your personal profile information and contact telephone.';

require_once dirname(__DIR__) . '/includes/header.php';
?>

<section class="shop-hero">
  <div class="container shop-hero-inner">
    <nav class="breadcrumb-nav" aria-label="Breadcrumb">
      <ul class="breadcrumb-list" style="justify-content:center;">
        <li><a href="../index.php">Home</a></li>
        <li class="breadcrumb-separator">&rsaquo;</li>
        <li><a href="../account.php">My Account</a></li>
        <li class="breadcrumb-separator">&rsaquo;</li>
        <li class="breadcrumb-current">Profile Details</li>
      </ul>
    </nav>
    <h1 class="section-title">Profile Details</h1>
    <p class="section-desc">
      Manage your customer information and primary delivery contact telephone.
    </p>
  </div>
</section>

<main id="main-content" class="dashboard-section">
  <div class="container">
    
    <div class="dashboard-layout-grid">
      
      <!-- Left: Sidebar -->
      <aside class="dashboard-sidebar-card">
        <div class="user-profile-summary">
          <div class="user-avatar-circle">
            <?php echo strtoupper(substr($customer['name'], 0, 1)); ?>
          </div>
          <div class="user-display-name"><?php echo htmlspecialchars($customer['name']); ?></div>
          <div class="user-display-email"><?php echo htmlspecialchars($customer['email']); ?></div>
        </div>

        <ul class="dashboard-nav-list">
          <li class="dashboard-nav-item">
            <a href="../account.php">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
              <span>Dashboard</span>
            </a>
          </li>
          <li class="dashboard-nav-item">
            <a href="orders.php">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line></svg>
              <span>My Orders</span>
            </a>
          </li>
          <li class="dashboard-nav-item active">
            <a href="profile.php">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
              <span>Profile Details</span>
            </a>
          </li>
          <li class="dashboard-nav-item logout-link">
            <a href="../logout.php">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
              <span>Sign Out</span>
            </a>
          </li>
        </ul>
      </aside>

      <!-- Right: Form Panel -->
      <div class="dashboard-main-panel">
        
        <div class="panel-header-row">
          <h2 class="panel-title">Edit Personal Information</h2>
          <span style="font-size:0.8125rem; color:var(--color-text-muted);">
            Member since <?php echo date('M Y', strtotime($customer['created_at'])); ?>
          </span>
        </div>

        <?php if (!empty($errorMessage)): ?>
          <div class="auth-alert-box auth-alert-error" role="alert" style="margin-bottom:var(--space-lg);">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;">
              <circle cx="12" cy="12" r="10"></circle>
              <line x1="12" y1="8" x2="12" y2="12"></line>
              <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <div><?php echo htmlspecialchars($errorMessage); ?></div>
          </div>
        <?php endif; ?>

        <?php if (!empty($successMessage)): ?>
          <div class="auth-alert-box auth-alert-success" role="alert" style="margin-bottom:var(--space-lg);">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;">
              <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
            <div><?php echo htmlspecialchars($successMessage); ?></div>
          </div>
        <?php endif; ?>

        <form action="profile.php" method="POST" class="auth-form" style="max-width:540px;">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(getCsrfToken()); ?>">

          <div class="form-group">
            <label for="prof_email" class="form-label">Verified Email Address</label>
            <input type="email" id="prof_email" class="form-control" value="<?php echo htmlspecialchars($customer['email']); ?>" readonly disabled style="background-color:var(--color-cream-100); cursor:not-allowed;">
            <span style="font-size:0.75rem; color:var(--color-text-muted); margin-top:4px;">
              Your verified email is locked to maintain identity and order security.
            </span>
          </div>

          <div class="form-group">
            <label for="prof_name" class="form-label">Full Name <span class="required-star">*</span></label>
            <input type="text" id="prof_name" name="name" class="form-control" value="<?php echo htmlspecialchars($customer['name']); ?>" required>
          </div>

          <div class="form-group">
            <label for="prof_phone" class="form-label">Primary Mobile Phone (Optional)</label>
            <input type="tel" id="prof_phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>" placeholder="0300-1234567">
            <span style="font-size:0.75rem; color:var(--color-text-muted); margin-top:4px;">
              Used to prefill your delivery contact number at checkout.
            </span>
          </div>

          <div style="margin-top:var(--space-xl);">
            <button type="submit" class="btn btn-primary" style="padding:12px 28px;">
              Save Profile Changes
            </button>
          </div>
        </form>

      </div>

    </div>

  </div>
</main>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
