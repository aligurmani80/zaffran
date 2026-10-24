<?php
/**
 * Customer Orders History Page
 * Displays authorized orders for the currently authenticated customer.
 */

define('ZAFFRAN_APP', true);

require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/auth.php';
initSession();

// Authentication Protection
if (!CustomerAuth::isAuthenticated()) {
    header('Location: ../login.php?return=' . urlencode('account/orders.php'));
    exit;
}

$customer = CustomerAuth::getCustomer();
if (!$customer) {
    CustomerAuth::logout();
    header('Location: ../login.php');
    exit;
}

$orders = CustomerAuth::getCustomerOrders((int)$customer['id']);
$totalOrders = count($orders);

$pageTitle = 'My Order History | Zaffran';
$pageDescription = 'Review all historical and active orders placed under your verified customer account.';

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
        <li class="breadcrumb-current">My Orders</li>
      </ul>
    </nav>
    <h1 class="section-title">My Orders</h1>
    <p class="section-desc">
      Track active delivery packages and inspect past botanical purchases.
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
          <li class="dashboard-nav-item active">
            <a href="orders.php">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line></svg>
              <span>My Orders (<?php echo $totalOrders; ?>)</span>
            </a>
          </li>
          <li class="dashboard-nav-item">
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

      <!-- Right: Orders Table Panel -->
      <div class="dashboard-main-panel">
        <div class="panel-header-row">
          <h2 class="panel-title">All Customer Orders (<?php echo $totalOrders; ?>)</h2>
          <a href="../shop.php" class="btn btn-secondary" style="padding:6px 16px; font-size:0.875rem;">Browse Shop</a>
        </div>

        <?php if (!empty($orders)): ?>
          <table class="orders-table">
            <thead>
              <tr>
                <th>Order Number</th>
                <th>Date Placed</th>
                <th>Items</th>
                <th>Total</th>
                <th>Payment</th>
                <th>Order Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($orders as $ord): ?>
                <tr>
                  <td>
                    <strong><?php echo htmlspecialchars($ord['order_number']); ?></strong>
                  </td>
                  <td>
                    <?php echo date('d M Y', strtotime($ord['created_at'])); ?>
                  </td>
                  <td>
                    <?php echo (int)($ord['item_count'] ?? 1); ?> item(s)
                  </td>
                  <td>
                    <strong>Rs. <?php echo number_format($ord['total_amount'], 0); ?></strong>
                  </td>
                  <td>
                    <span style="font-size:0.8125rem; font-weight:600; text-transform:uppercase;">
                      <?php echo htmlspecialchars($ord['payment_method']); ?>
                    </span>
                  </td>
                  <td>
                    <span class="order-status-badge status-pending">
                      <?php echo htmlspecialchars(ucfirst($ord['order_status'])); ?>
                    </span>
                  </td>
                  <td>
                    <a href="order-details.php?id=<?php echo (int)$ord['id']; ?>" class="order-link-btn">
                      View Details &rarr;
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php else: ?>
          <div style="text-align:center; padding:var(--space-3xl) var(--space-lg);">
            <h3 style="font-family:var(--font-serif); font-size:1.35rem; color:var(--color-forest-900); margin-bottom:8px;">No Orders Found</h3>
            <p style="color:var(--color-text-muted); font-size:0.9375rem; margin-bottom:20px;">
              You have not placed any orders yet. Explore our handcrafted perfumes, pure shilajit and Kashmiri saffron.
            </p>
            <a href="../shop.php" class="btn btn-primary">Start Shopping Now</a>
          </div>
        <?php endif; ?>

      </div>

    </div>

  </div>
</main>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
