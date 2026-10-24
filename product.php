<?php
/**
 * Complete Product Details Page
 * Dynamic database-driven product info, sale price calculation, quantity stepper,
 * CSRF-protected Add-to-Cart, and related real products.
 */

define('ZAFFRAN_APP', true);

require_once __DIR__ . '/config/database.php';
initSession();

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$product = null;
if (!empty($slug)) {
    $product = getProductBySlug($slug);
} elseif ($productId > 0) {
    $product = getProductById($productId);
}

// Proper Friendly 404 Page (No raw PHP exceptions)
if (!$product) {
    http_response_code(404);
    $pageTitle = 'Product Not Found | Zaffran';
    require_once __DIR__ . '/includes/header.php';
    ?>
    <main id="main-content" class="notfound-section">
      <div class="container">
        <div class="notfound-card">
          <div class="notfound-symbol">Z</div>
          <h1 style="font-family:var(--font-serif); font-size:2rem; color:var(--color-forest-900); margin-bottom:12px;">Product Not Found</h1>
          <p style="color:var(--color-text-muted); margin-bottom:24px;">
            The requested product is unavailable or the link may be outdated. Explore our full botanical collection.
          </p>
          <a href="shop.php" class="btn btn-primary">Return to Shop</a>
        </div>
      </div>
    </main>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$pricing = calculateProductPricing($product['price'], $product['sale_price'] ?? null);
$imagePath = resolveProductImage($product);
$relatedProducts = getRelatedProducts($product['id'], $product['category_slug']);
$isInStock = ($product['stock'] ?? 1) > 0;

$pageTitle = htmlspecialchars($product['name']) . ' | Zaffran Artisanal Store';
$pageDescription = htmlspecialchars($product['short_desc'] ?? 'Authentic handcrafted botanical product from Zaffran.');

require_once __DIR__ . '/includes/header.php';
?>

<div class="container">
  <!-- Breadcrumb Navigation -->
  <nav class="breadcrumb-nav" aria-label="Breadcrumb">
    <ul class="breadcrumb-list">
      <li><a href="index.php">Home</a></li>
      <li class="breadcrumb-separator">&rsaquo;</li>
      <li><a href="shop.php">Shop</a></li>
      <li class="breadcrumb-separator">&rsaquo;</li>
      <li><a href="shop.php?category=<?php echo urlencode($product['category_slug']); ?>"><?php echo htmlspecialchars($product['category']); ?></a></li>
      <li class="breadcrumb-separator">&rsaquo;</li>
      <li class="breadcrumb-current"><?php echo htmlspecialchars($product['name']); ?></li>
    </ul>
  </nav>
</div>

<!-- Main Product View -->
<main id="main-content" class="product-detail-section">
  <div class="container">
    
    <div class="product-detail-grid">
      
      <!-- Left Column: Product Visual Frame -->
      <div class="detail-gallery-wrap">
        <div class="detail-main-frame">
          <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" width="600" height="600" id="detail-main-img">
        </div>

        <!-- Authenticity Trust Strip -->
        <div class="detail-trust-strip">
          <div class="trust-strip-item">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            <span>100% Authentic</span>
          </div>
          <div class="trust-strip-item">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
            <span>Slow Matured</span>
          </div>
          <div class="trust-strip-item">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
            <span>Secure Dispatch</span>
          </div>
        </div>
      </div>

      <!-- Right Column: Product Information & Purchase Controls -->
      <div class="detail-info-wrap">
        
        <span class="detail-category-badge"><?php echo htmlspecialchars($product['category']); ?></span>
        
        <h1 class="detail-product-title"><?php echo htmlspecialchars($product['name']); ?></h1>

        <!-- Sale Pricing System -->
        <div class="detail-price-box">
          <?php if ($pricing['has_discount']): ?>
            <span class="detail-sale-price"><?php echo $pricing['formatted_effective']; ?></span>
            <span class="detail-regular-price"><?php echo $pricing['formatted_regular']; ?></span>
            <span class="detail-discount-badge"><?php echo $pricing['discount_percent']; ?>% DISCOUNT</span>
          <?php else: ?>
            <span class="detail-sale-price" style="color:var(--color-forest-900);"><?php echo $pricing['formatted_regular']; ?></span>
          <?php endif; ?>
        </div>

        <!-- Product Description -->
        <div class="detail-description">
          <p><?php echo htmlspecialchars($product['short_desc']); ?></p>
          <?php if (!empty($product['long_desc'])): ?>
            <p style="margin-top:12px;"><?php echo nl2br(htmlspecialchars($product['long_desc'])); ?></p>
          <?php endif; ?>
        </div>

        <!-- Purchase & Quantity Controls -->
        <div class="detail-purchase-form">
          <?php if ($isInStock): ?>
            <form id="product-detail-form" onsubmit="return false;">
              <input type="hidden" name="product_id" value="<?php echo (int)$product['id']; ?>" id="detail-product-id">

              <div class="quantity-control-wrap">
                <label for="detail-qty-input" class="quantity-label">Quantity:</label>
                <div class="quantity-stepper">
                  <button type="button" class="qty-step-btn" id="qty-decrease-btn" aria-label="Decrease quantity">&minus;</button>
                  <input type="number" id="detail-qty-input" name="quantity" value="1" min="1" max="<?php echo min(50, (int)$product['stock']); ?>" class="qty-input" readonly>
                  <button type="button" class="qty-step-btn" id="qty-increase-btn" aria-label="Increase quantity">&plus;</button>
                </div>
                <span style="font-size:0.8125rem; color:var(--color-forest-700); font-weight:600;">
                  In Stock (<?php echo (int)$product['stock']; ?> units ready)
                </span>
              </div>

              <div class="detail-cta-row">
                <button type="button" class="btn btn-primary js-detail-add-to-cart" data-id="<?php echo (int)$product['id']; ?>" data-name="<?php echo htmlspecialchars($product['name']); ?>">
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line></svg>
                  Add to Cart
                </button>
                <a href="shop.php" class="btn btn-secondary">Continue Shopping</a>
              </div>
            </form>
          <?php else: ?>
            <div style="padding:12px; background-color:var(--color-cream-100); border-radius:var(--radius-md); text-align:center;">
              <p style="color:var(--color-saffron-600); font-weight:700; margin-bottom:8px;">Currently Out of Stock</p>
              <p style="font-size:0.875rem; color:var(--color-text-muted);">This harvest batch is complete. Next batch updates will be announced soon.</p>
            </div>
          <?php endif; ?>
        </div>

        <!-- Specifications Tab / Table -->
        <div class="detail-specs-box">
          <h2 class="specs-title">Authentic Batch Specifications</h2>
          <table class="specs-table">
            <tbody>
              <tr>
                <th>Category</th>
                <td><?php echo htmlspecialchars($product['category']); ?></td>
              </tr>
              <?php if (!empty($product['sku'])): ?>
                <tr>
                  <th>Product SKU</th>
                  <td><?php echo htmlspecialchars($product['sku']); ?></td>
                </tr>
              <?php endif; ?>
              <tr>
                <th>Origin / Source</th>
                <td>Himalayan Highlands &amp; Kashmiri Terroir</td>
              </tr>
              <tr>
                <th>Formulation</th>
                <td>Pure Unadulterated Botanical</td>
              </tr>
              <tr>
                <th>Packaging</th>
                <td>Light-Protected Amber Glass &amp; Presentation Case</td>
              </tr>
            </tbody>
          </table>
        </div>

      </div>

    </div>

    <!-- Related Products From Database -->
    <?php if (!empty($relatedProducts)): ?>
      <div style="border-top:1px solid var(--color-border-subtle); padding-top:var(--space-3xl); margin-top:var(--space-xl);">
        <div class="section-header" style="text-align:left; margin-bottom:var(--space-xl);">
          <span class="section-eyebrow">Complementary Curation</span>
          <h2 class="section-title" style="font-size:1.85rem;">You May Also Appreciate</h2>
        </div>

        <div class="product-grid">
          <?php foreach ($relatedProducts as $product): ?>
            <?php include __DIR__ . '/includes/product_card.php'; ?>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

  </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
