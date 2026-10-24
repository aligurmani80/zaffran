<?php
/**
 * Dedicated Category Page
 * Handles filtered listings for Perfume, Shilajit, Zaffran, and Perfume Oils with B2B block
 */

define('ZAFFRAN_APP', true);

require_once __DIR__ . '/config/database.php';
initSession();

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$category = getCategoryBySlug($slug);

if (!$category) {
    http_response_code(404);
    $pageTitle = 'Category Not Found | Zaffran';
    require_once __DIR__ . '/includes/header.php';
    ?>
    <main id="main-content" class="notfound-section">
      <div class="container">
        <div class="notfound-card">
          <div class="notfound-symbol">Z</div>
          <h1 style="font-family:var(--font-serif); font-size:2rem; color:var(--color-forest-900); margin-bottom:12px;">Category Not Found</h1>
          <p style="color:var(--color-text-muted); margin-bottom:24px;">The botanical category you are looking for does not exist or may have been relocated.</p>
          <a href="shop.php" class="btn btn-primary">Browse All Categories</a>
        </div>
      </div>
    </main>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$products = getProductsCatalog($category['slug']);
$storeSettings = getStoreSettings();

$supplierPhone = $storeSettings['store_phone'] ?? '0304-1202111';
$supplierWaNumber = $storeSettings['whatsapp_number'] ?? '03041202111';
$supplierWaLink = !empty($storeSettings['whatsapp_contact_link']) 
    ? $storeSettings['whatsapp_contact_link'] 
    : "https://wa.me/{$supplierWaNumber}";

$pageTitle = htmlspecialchars($category['name']) . ' Collection | Zaffran Artisanal Store';
$pageDescription = htmlspecialchars($category['description']);

require_once __DIR__ . '/includes/header.php';
?>

<!-- Category Header -->
<section class="shop-hero">
  <div class="container shop-hero-inner">
    <nav class="breadcrumb-nav" aria-label="Breadcrumb">
      <ul class="breadcrumb-list" style="justify-content:center;">
        <li><a href="index.php">Home</a></li>
        <li class="breadcrumb-separator">&rsaquo;</li>
        <li><a href="shop.php">Categories</a></li>
        <li class="breadcrumb-separator">&rsaquo;</li>
        <li class="breadcrumb-current"><?php echo htmlspecialchars($category['name']); ?></li>
      </ul>
    </nav>
    <h1 class="section-title"><?php echo htmlspecialchars($category['name']); ?></h1>
    <p class="section-desc"><?php echo htmlspecialchars($category['description']); ?></p>
  </div>
</section>

<!-- Category Product Listing -->
<main id="main-content" class="section" style="padding-top: var(--space-2xl);">
  <div class="container">

    <!-- Category Switcher Bar -->
    <div class="shop-toolbar" style="margin-bottom: var(--space-2xl);">
      <div class="category-filter-pills">
        <a href="shop.php" class="filter-pill">All Products</a>
        <?php foreach (getCategoriesWithCounts() as $cat): ?>
          <a href="category.php?slug=<?php echo urlencode($cat['slug']); ?>" 
             class="filter-pill <?php echo ($cat['slug'] === $category['slug']) ? 'active' : ''; ?>">
            <?php echo htmlspecialchars($cat['name']); ?> (<?php echo (int)$cat['product_count']; ?>)
          </a>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Products Grid or Empty Category State -->
    <?php if (!empty($products)): ?>
      <div class="product-grid">
        <?php foreach ($products as $product): ?>
          <?php include __DIR__ . '/includes/product_card.php'; ?>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="product-empty-state" style="margin-bottom: var(--space-3xl);">
        <div class="empty-state-icon">
          <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
          </svg>
        </div>
        <h2 style="font-family:var(--font-serif); font-size:1.6rem; color:var(--color-forest-900); margin-bottom:10px;">
          New perfume oils will be added soon.
        </h2>
        <p style="color:var(--color-text-muted); max-width:520px; margin:0 auto 24px; line-height:1.65;">
          Our new batch of undiluted perfume oil concentrates is currently undergoing maceration and quality certification. Explore supply options below.
        </p>
        <a href="#business-support" class="btn btn-primary">Perfume Oil Supply Details</a>
      </div>
    <?php endif; ?>

    <!-- 7. PERFUME OILS CATEGORY: MANDATORY INFORMATIONAL BLOCK -->
    <?php if ($category['slug'] === 'perfume-oils'): ?>
      <div class="business-section" id="business-support" style="border-radius: var(--radius-xl); padding: var(--space-3xl); margin-top: var(--space-3xl);">
        <div class="business-grid">
          
          <div class="business-content">
            <span class="section-eyebrow">Perfume Entrepreneurship</span>
            <h2 class="business-title" style="font-size:2rem;">Start Your Own Perfume Business</h2>
            
            <p class="business-lead">
              Planning to launch your own perfume brand or open a fragrance shop? Contact us to explore available perfume oils and discuss your supply requirements.
            </p>

            <ul class="business-feature-list">
              <li class="business-feature-item">
                <div class="feature-check-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                </div>
                <div class="feature-text-block">
                  <strong>High-Sillage Undiluted Concentrates</strong>
                  <span>Tested long-lasting fragrance oils suitable for artisanal perfumes, roll-ons, and alcohol formulations.</span>
                </div>
              </li>
              <li class="business-feature-item">
                <div class="feature-check-icon">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                </div>
                <div class="feature-text-block">
                  <strong>Startup Guidance &amp; Direct Consultation</strong>
                  <span>Guidance on compounding, packaging sources, and seasonal fragrance curation.</span>
                </div>
              </li>
            </ul>

            <!-- Direct Contact Actions (Informational Contact Only, Not Checkout) -->
            <div class="business-inquiry-box" style="margin-top:var(--space-md);">
              <div class="inquiry-box-title">Discuss Your Wholesale Requirements</div>
              <p class="inquiry-box-note">
                Direct phone and WhatsApp support is dedicated to supplier consultation and order questions.
              </p>
              
              <div class="inquiry-actions">
                <?php if (!empty($supplierPhone)): ?>
                  <a href="tel:<?php echo htmlspecialchars(preg_replace('/[^0-9+]/', '', $supplierPhone)); ?>" class="btn btn-primary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
                    Call Supplier: <?php echo htmlspecialchars($supplierPhone); ?>
                  </a>
                <?php endif; ?>

                <?php if (!empty($supplierWaLink)): ?>
                  <a href="<?php echo htmlspecialchars($supplierWaLink); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-secondary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                    WhatsApp Business Inquiry
                  </a>
                <?php endif; ?>
              </div>
            </div>

          </div>

          <div class="business-visual-wrap">
            <div class="business-image-card">
              <img src="assets/images/business/perfume_oils_business.jpg" alt="Perfume Oils and Compounding Pipettes" width="600" height="450" loading="lazy">
            </div>
          </div>

        </div>
      </div>
    <?php endif; ?>

  </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
