<?php
/**
 * Customer Account Dashboard
 * Displays verified profile summary, order statistics, and recent orders.
 */

define('ZAFFRAN_APP', true);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
initSession();

// Authentication Protection
if (!CustomerAuth::isAuthenticated()) {
    header('Location: login.php?return=' . urlencode('account.php'));
    exit;
}

$customer = CustomerAuth::getCustomer();
if (!$customer) {
    CustomerAuth::logout();
    header('Location: login.php');
    exit;
}

$orders = CustomerAuth::getCustomerOrders((int)$customer['id']);
$totalOrders = count($orders);

$pendingOrders = 0;
foreach ($orders as $ord) {
    if (in_array(strtolower($ord['order_status']), ['pending', 'processing'])) {
        $pendingOrders++;
    }
}

$recentOrders = array_slice($orders, 0, 5);

$pageTitle = 'My Account Dashboard | Zaffran';
$pageDescription = 'Manage your Zaffran orders, review purchased botanicals, and update your personal details.';

require_once __DIR__ . '/includes/header.php';
?>

<section class="shop-hero">
  <div class="container shop-hero-inner">
    <nav class="breadcrumb-nav" aria-label="Breadcrumb">
      <ul class="breadcrumb-list" style="justify-content:center;">
        <li><a href="index.php">Home</a></li>
        <li class="breadcrumb-separator">&rsaquo;</li>
        <li class="breadcrumb-current">My Account</li>
      </ul>
    </nav>
    <h1 class="section-title">Customer Dashboard</h1>
    <p class="section-desc">
      Welcome back, <?php echo htmlspecialchars($customer['name']); ?>. Review your order history and account settings.
    </p>
  </div>
</section>

<main id="main-content" class="dashboard-section">
  <div class="container">
    
    <div class="dashboard-layout-grid">
      
      <!-- Left: Account Navigation Sidebar -->
      <aside class="dashboard-sidebar-card">
        <div class="user-profile-summary">
          <div class="user-avatar-circle">
            <?php echo strtoupper(substr($customer['name'], 0, 1)); ?>
          </div>
          <div class="user-display-name"><?php echo htmlspecialchars($customer['name']); ?></div>
          <div class="user-display-email"><?php echo htmlspecialchars($customer['email']); ?></div>
          <span class="order-status-badge status-confirmed" style="margin-top:8px; font-size:0.6875rem;">Verified Customer</span>
        </div>

        <ul class="dashboard-nav-list">
          <li class="dashboard-nav-item active">
            <a href="account.php">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
              <span>Dashboard</span>
            </a>
          </li>
          <li class="dashboard-nav-item">
            <a href="account/orders.php">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line></svg>
              <span>My Orders (<?php echo $totalOrders; ?>)</span>
            </a>
          </li>
          <li class="dashboard-nav-item">
            <a href="account/profile.php">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
              <span>Profile Details</span>
            </a>
          </li>
          <li class="dashboard-nav-item logout-link">
            <a href="logout.php">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
              <span>Sign Out</span>
            </a>
          </li>
        </ul>
      </aside>

      <!-- Right: Main Dashboard Area -->
      <div class="dashboard-main-area">
        
        <!-- Metric Cards -->
        <div class="dashboard-metrics-grid">
          
          <div class="metric-card">
            <div class="metric-icon-box">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line></svg>
            </div>
            <div>
              <div class="metric-val"><?php echo $totalOrders; ?></div>
              <div class="metric-label">Total Orders Placed</div>
            </div>
          </div>

          <div class="metric-card">
            <div class="metric-icon-box" style="color:var(--color-saffron-500); background-color:var(--color-saffron-100);">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            </div>
            <div>
              <div class="metric-val"><?php echo $pendingOrders; ?></div>
              <div class="metric-label">Active / In Progress</div>
            </div>
          </div>

          <div class="metric-card">
            <div class="metric-icon-box" style="color:#27ae60; background-color:#e8f8f0;">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </div>
            <div>
              <div class="metric-val">Verified</div>
              <div class="metric-label">Email OTP Security</div>
            </div>
          </div>

        </div>

        <!-- Recent Orders Panel -->
        <div class="dashboard-main-panel">
          <div class="panel-header-row">
            <h2 class="panel-title">Recent Orders</h2>
            <?php if ($totalOrders > 5): ?>
              <a href="account/orders.php" class="btn btn-secondary" style="padding:6px 16px; font-size:0.875rem;">View All Orders</a>
            <?php endif; ?>
          </div>

          <?php if (!empty($recentOrders)): ?>
            <table class="orders-table">
              <thead>
                <tr>
                  <th>Order Number</th>
                  <th>Date</th>
                  <th>Total</th>
                  <th>Order Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recentOrders as $ord): ?>
                  <tr>
                    <td>
                      <strong><?php echo htmlspecialchars($ord['order_number']); ?></strong>
                    </td>
                    <td>
                      <?php echo date('d M Y', strtotime($ord['created_at'])); ?>
                    </td>
                    <td>
                      <strong>Rs. <?php echo number_format($ord['total_amount'], 0); ?></strong>
                    </td>
                    <td>
                      <span class="order-status-badge status-pending">
                        <?php echo htmlspecialchars(ucfirst($ord['order_status'])); ?>
                      </span>
                    </td>
                    <td>
                      <a href="account/order-details.php?id=<?php echo (int)$ord['id']; ?>" class="order-link-btn">
                        View Details &rarr;
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php else: ?>
            <div style="text-align:center; padding:var(--space-2xl) var(--space-lg);">
              <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="var(--color-sage-300)" stroke-width="1.5" style="margin-bottom:12px;">
                <circle cx="12" cy="12" r="10"></circle>
                <path d="M16 16s-1.5-2-4-2-4 2-4 2"></path>
                <line x1="9" y1="9" x2="9.01" y2="9"></line>
                <line x1="15" y1="9" x2="15.01" y2="9"></line>
              </svg>
              <h3 style="font-family:var(--font-serif); font-size:1.25rem; color:var(--color-forest-900); margin-bottom:6px;">No Orders Placed Yet</h3>
              <p style="color:var(--color-text-muted); font-size:0.9375rem; margin-bottom:18px;">
                You haven&rsquo;t ordered any botanical items yet under this account.
              </p>
              <a href="shop.php" class="btn btn-primary" style="padding:10px 24px; font-size:0.9375rem;">Start Shopping</a>
            </div>
          <?php endif; ?>
        </div>

      </div>

    </div>

  </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
