<?php
/**
 * Customer Order Details Page
 * Strictly authorizes order access based on authenticated customer user_id.
 * No email-in-URL or numeric ID enumeration vulnerabilities.
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

$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$order = CustomerAuth::getCustomerOrderDetails((int)$customer['id'], $orderId);

if (!$order) {
    http_response_code(404);
    $pageTitle = 'Order Not Found | Zaffran';
    require_once dirname(__DIR__) . '/includes/header.php';
    ?>
    <main id="main-content" class="notfound-section">
      <div class="container">
        <div class="notfound-card">
          <div class="notfound-symbol">Z</div>
          <h1 style="font-family:var(--font-serif); font-size:2rem; color:var(--color-forest-900); margin-bottom:12px;">Order Not Found</h1>
          <p style="color:var(--color-text-muted); margin-bottom:24px;">
            The requested order does not exist or does not belong to your customer account.
          </p>
          <a href="orders.php" class="btn btn-primary">Back to My Orders</a>
        </div>
      </div>
    </main>
    <?php
    require_once dirname(__DIR__) . '/includes/footer.php';
    exit;
}

$pageTitle = 'Order ' . htmlspecialchars($order['order_number']) . ' Details | Zaffran';
$pageDescription = 'Detailed breakdown of items, pricing, delivery address, and payment method for this order.';

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
        <li><a href="orders.php">My Orders</a></li>
        <li class="breadcrumb-separator">&rsaquo;</li>
        <li class="breadcrumb-current"><?php echo htmlspecialchars($order['order_number']); ?></li>
      </ul>
    </nav>
    <h1 class="section-title">Order Breakdown</h1>
    <p class="section-desc">
      Order Number: <strong style="color:var(--color-forest-900);"><?php echo htmlspecialchars($order['order_number']); ?></strong> &bull; Placed on <?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?>
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
              <span>My Orders</span>
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

      <!-- Right: Detailed Order View Panel -->
      <div class="dashboard-main-panel">
        
        <div class="panel-header-row">
          <div>
            <h2 class="panel-title" style="margin-bottom:4px;">Order <?php echo htmlspecialchars($order['order_number']); ?></h2>
            <span class="order-status-badge status-pending"><?php echo htmlspecialchars(ucfirst($order['order_status'])); ?></span>
          </div>
          <a href="orders.php" class="btn btn-secondary" style="padding:6px 16px; font-size:0.875rem;">&larr; Back to Orders</a>
        </div>

        <!-- Order Information Summary Grid -->
        <div class="order-details-grid" style="border-top:none; padding-top:0;">
          <div class="order-info-block">
            <h4>Shipping Destination</h4>
            <p>
              <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br>
              <?php echo htmlspecialchars($order['delivery_address'] ?? ''); ?><br>
              <?php echo htmlspecialchars($order['city'] ?? ''); ?>, <?php echo htmlspecialchars($order['province'] ?? ''); ?> <?php echo htmlspecialchars($order['postal_code'] ?? ''); ?><br>
              Phone: <?php echo htmlspecialchars($order['customer_phone']); ?>
            </p>
          </div>

          <div class="order-info-block">
            <h4>Payment &amp; Processing</h4>
            <p>
              <strong>Method:</strong> Cash on Delivery (COD)<br>
              <strong>Payment Status:</strong> 
              <span class="order-status-badge status-pending"><?php echo htmlspecialchars(ucfirst($order['payment_status'])); ?></span><br>
              <strong>Order Status:</strong> 
              <span class="order-status-badge status-confirmed"><?php echo htmlspecialchars(ucfirst($order['order_status'])); ?></span>
            </p>
          </div>
        </div>

        <!-- Ordered Items -->
        <h3 style="font-family:var(--font-serif); font-size:1.25rem; color:var(--color-forest-900); margin-bottom:var(--space-md);">
          Ordered Botanical Items
        </h3>

        <ul class="order-items-preview" style="max-height:none; margin-bottom:var(--space-xl);">
          <?php foreach ($order['items'] as $item): ?>
            <li class="order-item-preview-row" style="padding:14px 0;">
              <img src="../<?php echo htmlspecialchars($item['product_image'] ?? 'assets/images/hero/hero_perfume.jpg'); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="order-item-preview-thumb" style="width:56px; height:56px;">
              <div class="order-item-preview-meta">
                <span class="order-item-preview-title" style="font-size:1.0625rem;"><?php echo htmlspecialchars($item['product_name']); ?></span>
                <span class="order-item-preview-qty">Quantity: <?php echo (int)$item['quantity']; ?> &times; Rs. <?php echo number_format($item['price'], 0); ?></span>
              </div>
              <div class="order-item-preview-price" style="font-size:1.125rem;">
                Rs. <?php echo number_format($item['total_price'], 0); ?>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>

        <!-- Summary Totals Table -->
        <div style="background-color:var(--color-cream-100); border-radius:var(--radius-lg); padding:var(--space-lg); border:1px solid var(--color-border-subtle);">
          <div class="summary-row" style="margin-bottom:8px;">
            <span>Subtotal</span>
            <strong>Rs. <?php echo number_format($order['subtotal'], 0); ?></strong>
          </div>
          <div class="summary-row" style="margin-bottom:8px;">
            <span>Shipping Charge</span>
            <strong><?php echo ($order['shipping_fee'] > 0) ? 'Rs. ' . number_format($order['shipping_fee'], 0) : 'Free Delivery'; ?></strong>
          </div>
          <div class="summary-row grand-total" style="border-top:1px solid var(--color-border); padding-top:10px; margin-top:8px;">
            <span>Grand Total</span>
            <strong style="color:var(--color-forest-900); font-size:1.35rem;">Rs. <?php echo number_format($order['total_amount'], 0); ?></strong>
          </div>
        </div>

      </div>

    </div>

  </div>
</main>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
