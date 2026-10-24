<?php
/**
 * Shopping Cart Page - Phase 3
 * Fully functional server-side cart with real-time MySQL pricing, AJAX quantity modification,
 * item removal, subtotal calculation, verified delivery charge evaluation, and checkout link.
 */

define('ZAFFRAN_APP', true);

require_once __DIR__ . '/config/database.php';
initSession();

$verifiedCart = getVerifiedCart();
$items = $verifiedCart['items'];
$subtotal = $verifiedCart['subtotal'];
$totalCount = $verifiedCart['total_count'];
$shipping = $verifiedCart['shipping'];
$grandTotal = $verifiedCart['grand_total'];

$pageTitle = 'Shopping Bag (' . $totalCount . ') | Zaffran Artisanal Store';
$pageDescription = 'Review your handcrafted botanical selections, update quantities, and proceed to secure checkout.';

require_once __DIR__ . '/includes/header.php';
?>

<section class="shop-hero">
  <div class="container shop-hero-inner">
    <nav class="breadcrumb-nav" aria-label="Breadcrumb">
      <ul class="breadcrumb-list" style="justify-content:center;">
        <li><a href="index.php">Home</a></li>
        <li class="breadcrumb-separator">&rsaquo;</li>
        <li><a href="shop.php">Shop</a></li>
        <li class="breadcrumb-separator">&rsaquo;</li>
        <li class="breadcrumb-current">Shopping Bag</li>
      </ul>
    </nav>
    <h1 class="section-title">Your Shopping Bag</h1>
    <p class="section-desc">
      Every selection is freshly prepared and securely packaged directly from origin.
    </p>
  </div>
</section>

<main id="main-content" class="cart-page-section">
  <div class="container">

    <?php if (!empty($items)): ?>
      <div class="cart-layout-grid" id="cart-content-grid">
        
        <!-- Left: Cart Items List -->
        <div class="cart-items-panel">
          
          <div class="cart-table-header">
            <span>Product</span>
            <span>Price</span>
            <span>Quantity</span>
            <span>Subtotal</span>
            <span></span>
          </div>

          <ul class="cart-items-list" id="cart-items-container">
            <?php foreach ($items as $item): ?>
              <li class="cart-item-row" data-product-id="<?php echo (int)$item['product_id']; ?>" id="cart-row-<?php echo (int)$item['product_id']; ?>">
                
                <!-- Product Column -->
                <div class="cart-product-info">
                  <a href="product.php?slug=<?php echo urlencode($item['slug']); ?>">
                    <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-item-thumb">
                  </a>
                  <div class="cart-item-details">
                    <span class="cart-item-category"><?php echo htmlspecialchars($item['category']); ?></span>
                    <h3 class="cart-item-title">
                      <a href="product.php?slug=<?php echo urlencode($item['slug']); ?>"><?php echo htmlspecialchars($item['name']); ?></a>
                    </h3>
                  </div>
                </div>

                <!-- Unit Price Column -->
                <div class="cart-item-price" data-label="Unit Price">
                  <span><?php echo $item['formatted_unit_price']; ?></span>
                  <?php if ($item['has_discount']): ?>
                    <span style="font-size:0.75rem; color:var(--color-saffron-500); display:block;"><?php echo $item['discount_percent']; ?>% Off</span>
                  <?php endif; ?>
                </div>

                <!-- Quantity Stepper Column -->
                <div class="cart-qty-cell" data-label="Quantity">
                  <div class="quantity-stepper">
                    <button type="button" class="qty-step-btn js-cart-qty-btn" data-action="decrease" data-id="<?php echo (int)$item['product_id']; ?>" aria-label="Decrease quantity">&minus;</button>
                    <input type="number" class="qty-input js-cart-qty-input" data-id="<?php echo (int)$item['product_id']; ?>" value="<?php echo (int)$item['quantity']; ?>" min="1" max="50" readonly>
                    <button type="button" class="qty-step-btn js-cart-qty-btn" data-action="increase" data-id="<?php echo (int)$item['product_id']; ?>" aria-label="Increase quantity">&plus;</button>
                  </div>
                </div>

                <!-- Item Subtotal Column -->
                <div class="cart-item-total" data-label="Subtotal" id="row-total-<?php echo (int)$item['product_id']; ?>">
                  <?php echo $item['formatted_total_price']; ?>
                </div>

                <!-- Remove Button Column -->
                <div>
                  <button type="button" class="cart-remove-btn js-cart-remove-btn" data-id="<?php echo (int)$item['product_id']; ?>" data-name="<?php echo htmlspecialchars($item['name']); ?>" aria-label="Remove item" title="Remove from bag">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                      <polyline points="3 6 5 6 21 6"></polyline>
                      <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                    </svg>
                  </button>
                </div>

              </li>
            <?php endforeach; ?>
          </ul>

          <div class="cart-list-footer">
            <a href="shop.php" class="btn btn-secondary">
              &larr; Continue Shopping
            </a>
            <span class="cart-list-footer-note">
              Prices verified from active MySQL catalog
            </span>
          </div>

        </div>

        <!-- Right: Summary Sidebar -->
        <aside class="cart-summary-sidebar">
          <div class="cart-summary-card">
            
            <h2 class="summary-heading">Order Summary</h2>

            <div class="summary-row">
              <span>Items Subtotal (<span id="summary-items-count"><?php echo (int)$totalCount; ?></span>)</span>
              <strong id="summary-subtotal-val"><?php echo $verifiedCart['formatted_subtotal']; ?></strong>
            </div>

            <!-- Shipping Calculation Status -->
            <div class="summary-row">
              <div>
                <span>Standard Delivery</span>
              </div>
              <span id="summary-shipping-val">
                <?php echo htmlspecialchars($shipping['formatted_fee']); ?>
              </span>
            </div>

            <?php if (!$shipping['is_free'] && $shipping['threshold'] > 0): ?>
              <div style="font-size:0.8125rem; color:var(--color-forest-700); background-color:var(--color-sage-50); padding:8px 12px; border-radius:var(--radius-sm); margin-bottom:var(--space-md); border:1px dashed var(--color-sage-200);">
                Add Rs. <?php echo number_format(max(0, $shipping['threshold'] - $subtotal), 0); ?> more to qualify for Free Nationwide Delivery.
              </div>
            <?php endif; ?>

            <div class="summary-row grand-total">
              <span>Estimated Total</span>
              <strong id="summary-grand-total-val"><?php echo $verifiedCart['formatted_grand_total']; ?></strong>
            </div>

            <!-- Proceed to Checkout Button -->
            <a href="checkout.php" class="btn btn-primary summary-action-btn">
              Proceed to Checkout &rarr;
            </a>

            <!-- Authentic Trust Badges -->
            <div class="cart-assurance-list">
              <div class="cart-assurance-item">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <span>Direct on-site checkout (Orders stored in MySQL)</span>
              </div>
              <div class="cart-assurance-item">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M7 15h0M2 10h20"/></svg>
                <span>Cash on Delivery (COD) supported nationwide</span>
              </div>
            </div>

          </div>
        </aside>

      </div>
    <?php else: ?>
      <!-- Empty Bag State -->
      <div class="product-empty-state" id="cart-empty-panel">
        <div class="empty-state-icon">
          <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <path d="M16 10a4 4 0 0 1-8 0"></path>
          </svg>
        </div>
        <h2 style="font-family:var(--font-serif); font-size:1.65rem; color:var(--color-forest-900); margin-bottom:8px;">
          Your Shopping Bag is Empty
        </h2>
        <p style="color:var(--color-text-muted); max-width:480px; margin:0 auto 24px;">
          You haven&rsquo;t added any botanical extracts or artisanal perfumes to your bag yet. Discover our signature collections.
        </p>
        <a href="shop.php" class="btn btn-primary" style="padding:14px 32px;">Explore Botanical Shop</a>
      </div>
    <?php endif; ?>

  </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
