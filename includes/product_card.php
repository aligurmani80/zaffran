<?php
/**
 * Product Card Reusable Template — Zevyon-Inspired Clean High-Contrast Framing
 * Expects $product array
 */

if (!defined('ZAFFRAN_APP')) {
    exit('Direct access not permitted');
}

$pricing = calculateProductPricing($product['price'], $product['sale_price'] ?? null);

// Resolve clean photographic image
$imagePath = resolveProductImage($product);
if (strpos($product['slug'], 'perfume') !== false && file_exists(__DIR__ . '/../assets/images/products/perfume_clean.jpg')) {
    $imagePath = 'assets/images/products/perfume_clean.jpg';
} elseif (strpos($product['slug'], 'shilajit') !== false && file_exists(__DIR__ . '/../assets/images/products/shilajit_clean.jpg')) {
    $imagePath = 'assets/images/products/shilajit_clean.jpg';
} elseif (strpos($product['slug'], 'zaffran') !== false && file_exists(__DIR__ . '/../assets/images/products/zaffran_clean.jpg')) {
    $imagePath = 'assets/images/products/zaffran_clean.jpg';
}

$productUrl = 'product.php?slug=' . urlencode($product['slug']);
$badgeLabel = trim((string)($product['badge'] ?? ''));
$categoryLabel = trim((string)($product['category'] ?? 'Botanical'));
$showBadgeFlag = $badgeLabel !== '' && strcasecmp($badgeLabel, $categoryLabel) !== 0;
?>
<article class="product-card js-tilt-card" data-product-id="<?php echo (int)$product['id']; ?>">
  <div class="product-media">
    <a href="<?php echo htmlspecialchars($productUrl); ?>" class="product-media-link" aria-label="<?php echo htmlspecialchars($product['name']); ?>">
      <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" loading="lazy" width="450" height="450">
    </a>

    <!-- Discount or Promotional Badge -->
    <?php if ($pricing['has_discount']): ?>
      <span class="product-discount-pill"><?php echo $pricing['discount_percent']; ?>% OFF</span>
    <?php elseif ($showBadgeFlag): ?>
      <span class="product-badge-flag"><?php echo htmlspecialchars($badgeLabel); ?></span>
    <?php endif; ?>

    <!-- In-Stock Indicator -->
    <div class="product-stock-tag">
      <span class="stock-indicator-dot <?php echo (($product['stock'] ?? 1) > 0) ? '' : 'out-of-stock'; ?>"></span>
      <?php echo (($product['stock'] ?? 1) > 0) ? 'In Stock' : 'Sold Out'; ?>
    </div>
  </div>

  <div class="product-body">
    <div class="product-meta-row">
      <span class="product-category-meta"><?php echo htmlspecialchars($categoryLabel); ?></span>
      <?php if (!empty($product['sku'])): ?>
        <span class="product-sku-meta"><?php echo htmlspecialchars($product['sku']); ?></span>
      <?php endif; ?>
    </div>

    <h3 class="product-title">
      <a href="<?php echo htmlspecialchars($productUrl); ?>"><?php echo htmlspecialchars($product['name']); ?></a>
    </h3>

    <?php if (!empty($product['short_desc'])): ?>
      <p class="product-short-desc"><?php echo htmlspecialchars($product['short_desc']); ?></p>
    <?php endif; ?>

    <!-- Real DB Price in PKR -->
    <div class="product-price-box">
      <?php if ($pricing['has_discount']): ?>
        <span class="price-sale-highlight"><?php echo $pricing['formatted_effective']; ?></span>
        <span class="price-regular-strikethrough"><?php echo $pricing['formatted_regular']; ?></span>
      <?php else: ?>
        <span class="price-regular-normal"><?php echo $pricing['formatted_regular']; ?></span>
      <?php endif; ?>
    </div>

    <!-- Actions -->
    <div class="product-actions">
      <?php if (($product['stock'] ?? 1) > 0): ?>
        <button type="button" class="btn btn-primary js-add-to-cart" data-id="<?php echo (int)$product['id']; ?>" data-name="<?php echo htmlspecialchars($product['name']); ?>">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line></svg>
          Add to Cart
        </button>
      <?php else: ?>
        <button type="button" class="btn btn-secondary" disabled style="opacity:0.5; cursor:not-allowed;">Sold Out</button>
      <?php endif; ?>
      <a href="<?php echo htmlspecialchars($productUrl); ?>" class="btn btn-secondary btn-icon-only" title="View details" aria-label="View product details">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
      </a>
    </div>
  </div>
</article>
