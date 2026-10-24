<?php
/**
 * Order Confirmation & Success Page - Phase 3
 * Secured by an unguessable 64-character cryptographically random access token.
 * Prevents ID enumeration and data leakage of customer personal details.
 */

define('ZAFFRAN_APP', true);

require_once __DIR__ . '/config/database.php';
initSession();

$token = trim($_GET['token'] ?? '');
$order = null;

if (!empty($token)) {
    $order = getOrderByAccessToken($token);
}

// 404 / Forbidden if token is invalid or missing
if (!$order) {
    http_response_code(404);
    $pageTitle = 'Order Not Found | Zaffran';
    require_once __DIR__ . '/includes/header.php';
    ?>
    <main id="main-content" class="notfound-section">
      <div class="container">
        <div class="notfound-card">
          <div class="notfound-symbol">Z</div>
          <h1 style="font-family:var(--font-serif); font-size:2rem; color:var(--color-forest-900); margin-bottom:12px;">Order Not Found</h1>
          <p style="color:var(--color-text-muted); margin-bottom:24px;">
            The order access link is invalid, expired, or access is restricted.
          </p>
          <a href="shop.php" class="btn btn-primary">Return to Shop</a>
        </div>
      </div>
    </main>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = 'Order Confirmed (' . htmlspecialchars($order['order_number']) . ') | Zaffran';
$pageDescription = 'Thank you for your order. Your authentic botanical selection is now recorded in our fulfillment queue.';
$storeSettings = getStoreSettings();
$isBankTransfer = (($order['payment_method'] ?? 'cod') === 'bank_transfer');

require_once __DIR__ . '/includes/header.php';
?>

<main id="main-content" class="order-success-section">
  <div class="container">
    
    <div class="order-success-card">
      
      <!-- Top Success Header -->
      <div class="order-success-header">
        <div class="success-check-badge">
          <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <polyline points="20 6 9 17 4 12"></polyline>
          </svg>
        </div>
        <h1 style="font-family:var(--font-serif); font-size:2.2rem; color:var(--color-forest-900); margin-bottom:8px;">
          Thank You, <?php echo htmlspecialchars($order['customer_name']); ?>!
        </h1>
        <p style="color:var(--color-text-muted); font-size:1.0625rem;">
          Your order has been placed and permanently registered in our system.
        </p>
        <div class="order-number-banner">
          Order Number: <span><?php echo htmlspecialchars($order['order_number']); ?></span>
        </div>
      </div>

      <!-- Order Details Grid -->
      <div class="order-details-grid">
        
        <div class="order-info-block">
          <h4>Delivery Address</h4>
          <p>
            <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br>
            <?php echo htmlspecialchars($order['delivery_address'] ?? ''); ?><br>
            <?php echo htmlspecialchars($order['city'] ?? ''); ?>, <?php echo htmlspecialchars($order['province'] ?? ''); ?> <?php echo htmlspecialchars($order['postal_code'] ?? ''); ?><br>
            Phone: <?php echo htmlspecialchars($order['customer_phone']); ?>
          </p>
        </div>

        <div class="order-info-block">
          <h4>Order &amp; Payment Details</h4>
          <p>
            <strong>Payment Method:</strong> <?php echo $isBankTransfer ? 'Manual Bank Transfer' : 'Cash on Delivery (COD)'; ?><br>
            <strong>Payment Status:</strong> 
            <span class="order-status-badge status-pending"><?php echo htmlspecialchars(ucfirst($order['payment_status'])); ?></span><br>
            <strong>Order Status:</strong> 
            <span class="order-status-badge status-confirmed"><?php echo htmlspecialchars(ucfirst($order['order_status'])); ?></span><br>
            <strong>Order Placed:</strong> <?php echo date('d M Y, h:i A', strtotime($order['created_at'])); ?>
          </p>
        </div>

      </div>

      <?php if ($isBankTransfer): ?>
        <div class="bank-instructions-card">
          <div class="bank-instructions-head">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><polyline points="9 12 11 14 15 10"></polyline></svg>
            <div>
              <strong>Complete your bank transfer</strong>
              <span>Your order is reserved pending payment verification. No payment has been marked as received.</span>
            </div>
          </div>
          <dl class="bank-detail-list">
            <div><dt>Bank</dt><dd><?php echo htmlspecialchars($storeSettings['bank_name'] ?? ''); ?></dd></div>
            <div><dt>Account Title</dt><dd><?php echo htmlspecialchars($storeSettings['bank_account_title'] ?? ''); ?></dd></div>
            <div><dt>Account Number</dt><dd><?php echo htmlspecialchars($storeSettings['bank_account_number'] ?? ''); ?></dd></div>
            <?php if (!empty($storeSettings['bank_iban'])): ?>
              <div><dt>IBAN</dt><dd><?php echo htmlspecialchars($storeSettings['bank_iban']); ?></dd></div>
            <?php endif; ?>
            <div><dt>Amount</dt><dd><strong>Rs. <?php echo number_format((float)$order['total_amount'], 0); ?></strong></dd></div>
            <div><dt>Payment Reference</dt><dd><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></dd></div>
          </dl>
          <p class="bank-transfer-note">
            Share the transfer receipt with our support team and quote your order number. Your order moves to processing after the transfer is verified by our accounts team.
          </p>
        </div>
      <?php endif; ?>

      <!-- Ordered Items Breakdown -->
      <h3 style="font-family:var(--font-serif); font-size:1.25rem; color:var(--color-forest-900); margin-bottom:var(--space-md);">
        Purchased Botanical Items
      </h3>

      <ul class="order-items-preview" style="max-height:none; margin-bottom:var(--space-xl);">
        <?php foreach ($order['items'] as $item): ?>
          <li class="order-item-preview-row" style="padding:12px 0;">
            <img src="<?php echo htmlspecialchars($item['product_image'] ?? 'assets/images/hero/hero_perfume.jpg'); ?>" alt="<?php echo htmlspecialchars($item['product_name']); ?>" class="order-item-preview-thumb" style="width:52px; height:52px;">
            <div class="order-item-preview-meta">
              <span class="order-item-preview-title" style="font-size:1rem;"><?php echo htmlspecialchars($item['product_name']); ?></span>
              <span class="order-item-preview-qty">Quantity: <?php echo (int)$item['quantity']; ?> &times; Rs. <?php echo number_format($item['price'], 0); ?></span>
            </div>
            <div class="order-item-preview-price" style="font-size:1.0625rem;">
              Rs. <?php echo number_format($item['total_price'], 0); ?>
            </div>
          </li>
        <?php endforeach; ?>
      </ul>

      <!-- Summary Pricing Footprint -->
      <div style="background-color:var(--color-cream-100); border-radius:var(--radius-lg); padding:var(--space-lg); border:1px solid var(--color-border-subtle); margin-bottom:var(--space-2xl);">
        <div class="summary-row" style="margin-bottom:8px;">
          <span>Items Subtotal</span>
          <strong>Rs. <?php echo number_format($order['subtotal'], 0); ?></strong>
        </div>
        <div class="summary-row" style="margin-bottom:8px;">
          <span>Shipping &amp; Secure Handling</span>
          <strong><?php echo ($order['shipping_fee'] > 0) ? 'Rs. ' . number_format($order['shipping_fee'], 0) : 'Free Delivery'; ?></strong>
        </div>
        <div class="summary-row grand-total" style="border-top:1px solid var(--color-border); padding-top:10px; margin-top:8px;">
          <span>Total Payable Amount</span>
          <strong style="color:var(--color-forest-900); font-size:1.35rem;">Rs. <?php echo number_format($order['total_amount'], 0); ?></strong>
        </div>
      </div>

      <!-- Next Steps & Assistance -->
      <div style="text-align:center;">
        <p style="font-size:0.9375rem; color:var(--color-text-muted); margin-bottom:var(--space-lg);">
          <?php if ($isBankTransfer): ?>
            Our dispatch team reserves your parcel while our accounts team verifies your bank transfer. Once confirmed, your order moves into processing.
          <?php else: ?>
            Our dispatch team will prepare your parcel. You will only pay in cash upon receiving your package from the courier agent.
          <?php endif; ?>
        </p>
        <a href="shop.php" class="btn btn-primary" style="padding:14px 36px;">
          Continue Exploring Shop &rarr;
        </a>
      </div>

    </div>

  </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
