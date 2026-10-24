<?php
/**
 * Zaffran E-Commerce — Homepage (Complete Visual Transformation Inspired by Zevyon)
 * Sections:
 * 1. Announcement Bar (Included in header.php)
 * 2. Header & Navigation (Included in header.php)
 * 3. Full-Width Signature Hero (Single large promotional presentation with 3D typewriter headline)
 * 4. Circular Product Category Explorer (4 Black circles on pure white background)
 * 5. Animated Brand Marquee Strip
 * 6. Explore Our Collection (Image-First 3-column product grid)
 * 7. About The Brand — Editorial Dark Section ("The Story Behind Zaffran")
 * 8. Customer Reviews / WhatsApp Proof Gallery
 * 9. Dark Accordion FAQ Section
 * 10. Why Choose Zaffran? (White minimal service pillars)
 * 11. Full-Width Professional Dark Footer (Included in footer.php)
 */

define('ZAFFRAN_APP', true);

require_once __DIR__ . '/config/database.php';
initSession();

$pageTitle = 'Zaffran | Pure Botanicals, Artisanal Fragrances & Essence Oils';
$storeSettings = getStoreSettings();
$featuredProducts = getFeaturedProducts();
$categories = getCategoriesWithCounts();

$productBySlug = [];
foreach ($featuredProducts as $product) {
    $productBySlug[$product['slug']] = $product;
}

$heroCopy = [
    'zaffran-saffron' => [
        'eyebrow' => 'Royal Zaffran · Kashmir Harvest',
        'title' => 'Saffron, <em>in its rarest form.</em>',
        'desc' => 'Hand-harvested Super Negin saffron, selected for deep colour, honeyed aroma and uncompromising purity.',
        'image' => 'assets/images/hero/hero_zaffran.jpg',
        'focus' => '50% 38%',
        'focus_mobile' => '50% 34%',
        'alt' => 'Super Negin saffron filaments presented in a gift box with whole threads',
        'guarantee' => 'Batch-verified purity',
    ],
    'shilajit-resin' => [
        'eyebrow' => 'Himalayan Gold · Lab-Verified Resin',
        'title' => 'High-altitude <em>mineral resonance.</em>',
        'desc' => 'Slow-cured Himalayan Shilajit resin, laboratory-screened for purity and sealed to preserve its natural profile.',
        'image' => 'assets/images/hero/hero_shilajit.jpg',
        'focus' => '50% 46%',
        'focus_mobile' => '50% 50%',
        'alt' => 'Snow-capped Himalayan mountains where Shilajit resin is sourced',
        'guarantee' => 'Laboratory-screened resin',
    ],
    'luxury-perfumes' => [
        'eyebrow' => 'Sovereign Oud · Artisanal Parfumerie',
        'title' => 'A signature <em>worth remembering.</em>',
        'desc' => 'Sovereign oud extrait, slow-matured with luminous florals for a warm, lasting signature trail.',
        'image' => 'assets/images/hero/hero_perfume.jpg',
        'focus' => '50% 42%',
        'focus_mobile' => '50% 38%',
        'alt' => 'Artisanal perfume bottle surrounded by blush roses',
        'guarantee' => 'Small-batch extrait',
    ],
];

$heroSlides = [];
foreach ($heroCopy as $slug => $copy) {
    if (!isset($productBySlug[$slug])) {
        continue;
    }
    $copy['product'] = $productBySlug[$slug];
    $copy['price'] = $copy['product']['sale_price'] !== null && $copy['product']['sale_price'] !== ''
        ? (float)$copy['product']['sale_price']
        : (float)$copy['product']['price'];
    $copy['regular'] = (float)$copy['product']['price'];
    $heroSlides[] = $copy;
}

// Real customer reviews & authentic WhatsApp proofs from DB
$realReviews = [];
$whatsappProofs = getPublishedWhatsAppProofs();
$pdo = getDbConnection();
if ($pdo) {
    try {
        $rStmt = $pdo->query("SELECT customer_name, rating, review_text FROM reviews WHERE is_approved = 1 ORDER BY id DESC LIMIT 6");
        $realReviews = $rStmt->fetchAll() ?: [];
    } catch (Exception $e) {
        $realReviews = [];
    }
}

// Supplier business contact
$supplierPhone = $storeSettings['store_phone'] ?? '0304-1202111';
$supplierWaNumber = $storeSettings['whatsapp_number'] ?? '03041202111';
$supplierWaLink = !empty($storeSettings['whatsapp_contact_link']) 
    ? $storeSettings['whatsapp_contact_link'] 
    : "https://wa.me/{$supplierWaNumber}";

require_once __DIR__ . '/includes/header.php';
?>

<main id="main-content">
<!-- ==========================================================================
     3. SIGNATURE COLLECTION HERO SLIDER
     ========================================================================== -->
<?php if (!empty($heroSlides)): ?>
<section class="hero-slider" id="hero-slider" data-autoplay="7000" aria-roledescription="carousel" aria-label="Featured Zaffran collection">
  <div class="hero-slides">
    <?php foreach ($heroSlides as $index => $slide): ?>
      <?php
        $isFirst = $index === 0;
        $product = $slide['product'];
        $titleTag = $isFirst ? 'h1' : 'h2';
        $hasDiscount = $slide['regular'] > $slide['price'];
        $productUrl = 'product.php?slug=' . urlencode($product['slug']);
      ?>
      <article class="hero-slide<?php echo $isFirst ? ' is-active' : ''; ?>"
               role="group" aria-roledescription="slide"
               aria-label="<?php echo $index + 1; ?> of <?php echo count($heroSlides); ?>: <?php echo htmlspecialchars($product['name']); ?>"
               aria-hidden="<?php echo $isFirst ? 'false' : 'true'; ?>">
        <div class="hero-slide-media">
          <img src="<?php echo htmlspecialchars($slide['image']); ?>"
               alt="<?php echo htmlspecialchars($slide['alt']); ?>"
               style="--hero-focus: <?php echo htmlspecialchars($slide['focus']); ?>; --hero-focus-mobile: <?php echo htmlspecialchars($slide['focus_mobile']); ?>;"
               <?php echo $isFirst ? 'fetchpriority="high"' : 'loading="lazy"'; ?>>
        </div>
        <div class="hero-slide-veil" aria-hidden="true"></div>
        <div class="hero-slide-inner">
          <div class="container">
            <div class="hero-slide-content">
              <span class="hero-eyebrow"><?php echo htmlspecialchars($slide['eyebrow']); ?></span>
              <<?php echo $titleTag; ?> class="hero-title"><?php echo $slide['title']; ?></<?php echo $titleTag; ?>>
              <p class="hero-desc"><?php echo htmlspecialchars($slide['desc']); ?></p>
              <div class="hero-price-row">
                <span class="hero-price">Rs. <?php echo number_format($slide['price']); ?></span>
                <?php if ($hasDiscount): ?>
                  <s class="hero-price-was">Rs. <?php echo number_format($slide['regular']); ?></s>
                <?php endif; ?>
                <span class="hero-price-note">Free nationwide delivery over Rs. 5,000</span>
              </div>
              <div class="hero-actions">
                <a href="<?php echo htmlspecialchars($productUrl); ?>" class="btn btn-primary">Discover <?php echo htmlspecialchars($product['name']); ?></a>
                <a href="shop.php" class="hero-text-link">Shop all collections <span aria-hidden="true">&rarr;</span></a>
              </div>
              <span class="hero-guarantee">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <?php echo htmlspecialchars($slide['guarantee']); ?> · Secure COD &amp; bank transfer
              </span>
            </div>
          </div>
        </div>
      </article>
    <?php endforeach; ?>
  </div>

  <div class="hero-controls" aria-label="Slider controls">
    <button type="button" class="hero-arrow js-hero-prev" aria-label="Previous slide">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="15 18 9 12 15 6"></polyline></svg>
    </button>
    <div class="hero-dots" role="tablist" aria-label="Choose a featured product">
      <?php foreach ($heroSlides as $index => $slide): ?>
        <button type="button" class="hero-dot<?php echo $index === 0 ? ' is-active' : ''; ?>"
                data-goto="<?php echo $index; ?>" role="tab"
                aria-selected="<?php echo $index === 0 ? 'true' : 'false'; ?>"
                aria-label="Show <?php echo htmlspecialchars($slide['product']['name']); ?>"></button>
      <?php endforeach; ?>
    </div>
    <button type="button" class="hero-arrow js-hero-next" aria-label="Next slide">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><polyline points="9 18 15 12 9 6"></polyline></svg>
    </button>
  </div>
  <div class="hero-progress" aria-hidden="true"><span></span></div>
</section>
<?php endif; ?>


<!-- ==========================================================================
     4. CIRCULAR PRODUCT CATEGORY SECTION (Pure Black Circles on Clean White)
     ========================================================================== -->
<section class="section category-circles-section reveal-on-scroll" id="categories">
  <div class="container">
    
    <div class="section-header">
      <span class="section-eyebrow">Pillars of Craft</span>
      <h2 class="section-title">Shop by Category</h2>
      <p class="section-desc">
        Explore our curated botanical harvests, signature scents, and wholesale concentrates.
      </p>
    </div>

    <div class="category-circles-grid reveal-stagger">
      
      <!-- Category 1: Perfume -->
      <a href="shop.php?category=perfume" class="category-circle-card js-tilt-card">
        <div class="category-circle-avatar">
          <img src="assets/images/categories/circle_perfume.jpg" alt="Perfume" loading="lazy">
        </div>
        <h3 class="category-circle-label">Perfume</h3>
        <span class="category-circle-subtext">Artisanal Extraits</span>
      </a>

      <!-- Category 2: Shilajit -->
      <a href="shop.php?category=shilajit" class="category-circle-card js-tilt-card">
        <div class="category-circle-avatar">
          <img src="assets/images/categories/circle_shilajit.jpg" alt="Shilajit" loading="lazy">
        </div>
        <h3 class="category-circle-label">Shilajit</h3>
        <span class="category-circle-subtext">Himalayan Gold Resin</span>
      </a>

      <!-- Category 3: Zaffran -->
      <a href="shop.php?category=zaffran" class="category-circle-card js-tilt-card">
        <div class="category-circle-avatar">
          <img src="assets/images/categories/circle_zaffran.jpg" alt="Zaffran" loading="lazy">
        </div>
        <h3 class="category-circle-label">Zaffran</h3>
        <span class="category-circle-subtext">Super Negin Filaments</span>
      </a>

      <!-- Category 4: Perfume Oils -->
      <a href="category.php?slug=perfume-oils" class="category-circle-card js-tilt-card">
        <div class="category-circle-avatar">
          <img src="assets/images/categories/circle_oils.jpg" alt="Perfume Oils" loading="lazy">
        </div>
        <h3 class="category-circle-label">Perfume Oils</h3>
        <span class="category-circle-subtext">B2B &amp; Brand Supply</span>
      </a>

    </div>

  </div>
</section>


<!-- ==========================================================================
     5. ANIMATED MARQUEE BRAND STRIP
     ========================================================================== -->
<div class="marquee-strip" aria-hidden="true">
  <div class="marquee-track">
    <div class="marquee-content">
      <span class="marquee-item">ZAFFRAN <span class="marquee-sep">&bull;</span></span>
      <span class="marquee-item highlight">PERFUME <span class="marquee-sep">&bull;</span></span>
      <span class="marquee-item">SHILAJIT <span class="marquee-sep">&bull;</span></span>
      <span class="marquee-item highlight">SIGNATURE COLLECTION <span class="marquee-sep">&bull;</span></span>
      <span class="marquee-item">PERFUME OILS <span class="marquee-sep">&bull;</span></span>
      <span class="marquee-item highlight">PURE BOTANICALS <span class="marquee-sep">&bull;</span></span>
      <span class="marquee-item">ARTISANAL HERITAGE <span class="marquee-sep">&bull;</span></span>
    </div>
    <div class="marquee-content">
      <span class="marquee-item">ZAFFRAN <span class="marquee-sep">&bull;</span></span>
      <span class="marquee-item highlight">PERFUME <span class="marquee-sep">&bull;</span></span>
      <span class="marquee-item">SHILAJIT <span class="marquee-sep">&bull;</span></span>
      <span class="marquee-item highlight">SIGNATURE COLLECTION <span class="marquee-sep">&bull;</span></span>
      <span class="marquee-item">PERFUME OILS <span class="marquee-sep">&bull;</span></span>
      <span class="marquee-item highlight">PURE BOTANICALS <span class="marquee-sep">&bull;</span></span>
      <span class="marquee-item">ARTISANAL HERITAGE <span class="marquee-sep">&bull;</span></span>
    </div>
  </div>
</div>

<!-- Curved Transition: Marquee (Black) -> Collections (Offwhite) -->
<div class="curved-divider curved-divider-bottom-dark" aria-hidden="true">
  <svg viewBox="0 0 1200 64" preserveAspectRatio="none">
    <path d="M0,0 C320,64 880,64 1200,0 L1200,64 L0,64 Z"></path>
  </svg>
</div>


<!-- ==========================================================================
     6. PRODUCT COLLECTION GRID (Explore Our Collection)
     ========================================================================== -->
<section class="section products-collection-section reveal-on-scroll" id="collections">
  <div class="container">
    
    <div class="section-header">
      <span class="section-eyebrow">Featured Products</span>
      <h2 class="section-title">Explore Our Collection</h2>
      <p class="section-desc">
        Genuine botanical provenance and slow-matured artisanal essences directly from our active catalog.
      </p>
    </div>

    <div class="product-grid reveal-stagger">
      <?php if (!empty($featuredProducts)): ?>
        <?php foreach ($featuredProducts as $product): ?>
          <?php include __DIR__ . '/includes/product_card.php'; ?>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="reviews-empty-state" style="grid-column: 1 / -1;">
          <h3 style="font-family:var(--font-serif); font-size:1.35rem; margin-bottom:8px;">Botanical Catalog Updating</h3>
          <p style="color:var(--color-text-muted); max-width:460px; margin:0 auto 16px;">
            Authentic product batches are currently being verified and uploaded.
          </p>
          <a href="shop.php" class="btn btn-primary">Explore All Products</a>
        </div>
      <?php endif; ?>
    </div>

    <div style="text-align:center; margin-top:var(--space-3xl);">
      <a href="shop.php" class="btn btn-primary" style="padding:14px 36px;">
        View All Products
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"></polyline></svg>
      </a>
    </div>

  </div>
</section>

<!-- Curved Transition: Collections (Offwhite) -> Perfume Business Setup (Black) -->
<div class="curved-divider curved-divider-to-dark" aria-hidden="true">
  <svg viewBox="0 0 1200 64" preserveAspectRatio="none">
    <path d="M0,64 C360,0 840,0 1200,64 L1200,64 L0,64 Z"></path>
  </svg>
</div>


<!-- ==========================================================================
     7. PERFUME SETUP BUSINESS OPPORTUNITY (EDITORIAL DARK SECTION)
     ========================================================================== -->
<section class="section editorial-about-section reveal-on-scroll" id="business-opportunity" data-section="about">
  <div class="container">
    <div class="editorial-about-grid">
      
      <!-- Left Column: Real Uploaded Perfume Setup Glass Showcase Image -->
      <div class="editorial-about-media js-tilt-card">
        <img src="assets/images/business/perfume_shop_setup.jpg" alt="Perfume Setup Display Glass Showcase with Fragrance Bottles and Concentrates" loading="lazy">
      </div>

      <!-- Right Column: Business Opportunity Content & Financials -->
      <div class="editorial-about-content">
        <span class="section-eyebrow">BUSINESS OPPORTUNITY</span>
        <h2 class="editorial-heading">Apni Shop Par Perfume Setup Lagwayen</h2>
        
        <p class="editorial-text">
          Kya aap apni kiryana shop, medical store ya retail shop par perfume oil ka setup lagwana chahte hain? Hum aapko perfume setup aur khushboo ki mukhtalif varieties provide karte hain.
        </p>

        <!-- Highlight Info: Setup Cost & Estimated Monthly Earnings -->
        <div class="business-metrics-grid">
          <div class="business-metric-card">
            <span class="business-metric-label">Estimated Setup Cost</span>
            <span class="business-metric-value">Rs. 70,000 – 80,000</span>
          </div>
          <div class="business-metric-card">
            <span class="business-metric-label">Estimated Monthly Earnings</span>
            <span class="business-metric-value">Rs. 30,000 – 40,000</span>
          </div>
        </div>

        <!-- Dealer & Additional Availability Info -->
        <div class="business-info-banner">
          <div class="business-info-row">
            <strong>Wholesale Dealer:</strong>
            <span class="info-accent">Molvi Talha Shilajit &amp; Zaffran Dealer</span>
          </div>
          <div class="business-info-row">
            <strong>Additional Availability:</strong>
            <span>Islamic articles bhi wholesale rate par dastiyab hain.</span>
          </div>
        </div>

        <!-- Professional Earnings Disclaimer -->
        <p class="business-disclaimer-text">
          Earnings guaranteed nahi hain. Actual income location, sales, demand aur expenses par depend karegi.
        </p>

        <!-- Inquiry Action Buttons (Using Real Configurable Contact Settings) -->
        <div class="business-cta-row">
          <a href="<?php echo htmlspecialchars($supplierWaLink); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-whatsapp-solid">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
            WhatsApp Inquiry
          </a>
          <a href="tel:<?php echo htmlspecialchars(preg_replace('/[^0-9+]/', '', $supplierPhone)); ?>" class="btn btn-outline-white">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
            Call Now: <?php echo htmlspecialchars($supplierPhone); ?>
          </a>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- Curved Transition: Business Setup (Black) -> Reviews (White) -->
<div class="curved-divider curved-divider-from-dark" aria-hidden="true">
  <svg viewBox="0 0 1200 64" preserveAspectRatio="none">
    <path d="M0,0 C320,64 880,64 1200,0 L1200,64 L0,64 Z"></path>
  </svg>
</div>


<!-- ==========================================================================
     8. CUSTOMER REVIEWS / WHATSAPP PROOF SECTION
     ========================================================================== -->
<section class="section reviews-section reveal-on-scroll" id="reviews">
  <div class="container">
    
    <div class="section-header">
      <span class="section-eyebrow">Genuine Social Proof</span>
      <h2 class="section-title">What Our Customers Say</h2>
      <p class="section-desc">
        Unedited delivery confirmations and feedback screenshots directly from our valued WhatsApp buyers across Pakistan.
      </p>
    </div>

    <?php if (!empty($whatsappProofs)): ?>
      <!-- Authentic WhatsApp Screenshot Gallery -->
      <div class="proofs-carousel-wrap">
        <button class="proofs-nav-btn proofs-prev-btn" aria-label="Previous proof" type="button">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
        </button>

        <div class="proofs-scroll-container" id="proofsScrollContainer">
          <?php foreach ($whatsappProofs as $proof): ?>
            <div class="proof-card js-proof-card" onclick="openPublicLightbox('<?php echo htmlspecialchars($proof['image_path']); ?>', '<?php echo htmlspecialchars(addslashes($proof['customer_display_name'] ?: 'Verified Customer')); ?>', '<?php echo htmlspecialchars(addslashes($proof['caption'] ?: '')); ?>')">
              <div class="proof-media-wrapper">
                <img src="<?php echo htmlspecialchars($proof['image_path']); ?>" alt="<?php echo htmlspecialchars($proof['customer_display_name'] ?: 'Customer feedback screenshot'); ?>" loading="lazy" class="proof-img">
                <div class="proof-zoom-overlay">
                  <span class="proof-zoom-badge">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line><line x1="11" y1="8" x2="11" y2="14"></line><line x1="8" y1="11" x2="14" y2="11"></line></svg>
                    Tap to expand
                  </span>
                </div>
              </div>
              <div class="proof-info">
                <?php if (!empty($proof['rating'])): ?>
                  <div class="proof-stars" aria-label="<?php echo (int)$proof['rating']; ?> out of 5 stars">
                    <?php for ($s = 0; $s < (int)$proof['rating']; $s++): ?>
                      <svg viewBox="0 0 24 24" fill="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                    <?php endfor; ?>
                  </div>
                <?php endif; ?>

                <?php if (!empty($proof['caption'])): ?>
                  <p class="proof-caption">&ldquo;<?php echo htmlspecialchars($proof['caption']); ?>&rdquo;</p>
                <?php endif; ?>

                <div class="proof-author-row">
                  <span class="proof-author-name"><?php echo htmlspecialchars($proof['customer_display_name'] ?: 'Verified Customer'); ?></span>
                  <?php if (!empty($proof['product_name'])): ?>
                    <span class="proof-product-tag"><?php echo htmlspecialchars($proof['product_name']); ?></span>
                  <?php else: ?>
                    <span class="proof-buyer-badge">WhatsApp Buyer</span>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <button class="proofs-nav-btn proofs-next-btn" aria-label="Next proof" type="button">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
        </button>
      </div>

    <?php elseif (!empty($realReviews)): ?>
      <div class="reviews-carousel-wrap">
        <div class="reviews-scroll-container">
          <?php foreach ($realReviews as $rev): ?>
            <div class="review-card">
              <div class="review-stars">
                <?php for ($i = 0; $i < (int)($rev['rating'] ?? 5); $i++): ?>
                  <svg viewBox="0 0 24 24"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                <?php endfor; ?>
              </div>
              <p class="review-quote">&ldquo;<?php echo htmlspecialchars($rev['review_text']); ?>&rdquo;</p>
              <div class="review-author-row">
                <span class="review-author-name"><?php echo htmlspecialchars($rev['customer_name']); ?></span>
                <span class="review-author-badge">Verified Buyer</span>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php else: ?>
      <!-- Tasteful Empty State (Honest, No Fabricated Testimonials) -->
      <div class="reviews-empty-state">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 12px; color:var(--color-saffron-500);"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
        <h3 style="font-family:var(--font-serif); font-size:1.25rem; margin-bottom:8px;">Customer Reviews in Verification</h3>
        <p style="color:var(--color-text-muted); max-width:480px; margin:0 auto;">
          Verified customer feedback and delivery confirmations are reviewed before public display. Place an order today and experience Zaffran authenticity.
        </p>
      </div>
    <?php endif; ?>

  </div>
</section>

<!-- FULL SCREEN LIGHTBOX MODAL FOR WHATSAPP SCREENSHOTS -->
<div id="publicProofLightbox" class="proof-lightbox" style="display:none;" onclick="closePublicLightbox()">
  <div class="proof-lightbox-dialog" onclick="event.stopPropagation()">
    <button type="button" class="proof-lightbox-close" onclick="closePublicLightbox()" aria-label="Close image">&times;</button>
    <div class="proof-lightbox-img-wrap">
      <img id="publicLightboxImg" src="" alt="Full WhatsApp screenshot proof" class="proof-lightbox-img">
    </div>
    <div class="proof-lightbox-caption-bar" id="publicLightboxCaptionBar">
      <div id="publicLightboxCustomer" class="proof-lightbox-customer"></div>
      <div id="publicLightboxCaption" class="proof-lightbox-text"></div>
    </div>
  </div>
</div>

<!-- Curved Transition: Reviews (White) -> FAQ (Black) -->
<div class="curved-divider curved-divider-top-dark" aria-hidden="true">
  <svg viewBox="0 0 1200 64" preserveAspectRatio="none">
    <path d="M0,64 C360,0 840,0 1200,64 L1200,64 L0,64 Z"></path>
  </svg>
</div>


<!-- ==========================================================================
     9. DARK ACCORDION FAQ SECTION
     ========================================================================== -->
<section class="section faq-section reveal-on-scroll" id="faq">
  <div class="container">
    
    <div class="section-header">
      <span class="section-eyebrow">Clear Answers</span>
      <h2 class="section-title">Frequently Asked Questions</h2>
      <p class="section-desc">
        Essential information regarding orders, payment methods, delivery, and perfume oil supply.
      </p>
    </div>

    <div class="faq-accordion-list reveal-stagger">
      
      <!-- FAQ 1 -->
      <div class="faq-item">
        <button type="button" class="faq-header-btn" aria-expanded="false">
          <span>How can I place an order on Zaffran?</span>
          <span class="faq-toggle-icon">+</span>
        </button>
        <div class="faq-body-panel">
          <div class="faq-body-inner">
            You can easily browse our catalog, select your desired items, and click &ldquo;Add to Cart&rdquo;. Once ready, proceed to our secure on-site checkout. Enter your shipping address and choose Cash on Delivery (COD) or Direct Bank Transfer to confirm your order immediately.
          </div>
        </div>
      </div>

      <!-- FAQ 2 -->
      <div class="faq-item">
        <button type="button" class="faq-header-btn" aria-expanded="false">
          <span>Which payment methods are available?</span>
          <span class="faq-toggle-icon">+</span>
        </button>
        <div class="faq-body-panel">
          <div class="faq-body-inner">
            We currently accept <strong>Cash on Delivery (COD)</strong> across Pakistan as well as secure <strong>Direct Bank Transfer</strong> to our verified Meezan Bank corporate account.
          </div>
        </div>
      </div>

      <!-- FAQ 3 -->
      <div class="faq-item">
        <button type="button" class="faq-header-btn" aria-expanded="false">
          <span>How are delivery charges calculated?</span>
          <span class="faq-toggle-icon">+</span>
        </button>
        <div class="faq-body-panel">
          <div class="faq-body-inner">
            Standard nationwide shipping is flat <strong>Rs. <?php echo (int)($storeSettings['default_delivery_charges'] ?? 250); ?></strong>. We offer <strong>FREE nationwide shipping</strong> on all orders exceeding Rs. <?php echo number_format($storeSettings['free_delivery_threshold'] ?? 5000); ?>.
          </div>
        </div>
      </div>

      <!-- FAQ 4 -->
      <div class="faq-item">
        <button type="button" class="faq-header-btn" aria-expanded="false">
          <span>Can I inquire about perfume oils for my own brand or shop?</span>
          <span class="faq-toggle-icon">+</span>
        </button>
        <div class="faq-body-panel">
          <div class="faq-body-inner">
            Yes! Zaffran supplies 100% pure, unadulterated perfume oil concentrates to aspiring entrepreneurs, boutique perfumers, and fragrance startups across Pakistan. You can visit our <a href="category.php?slug=perfume-oils" style="color:var(--color-saffron-400); text-decoration:underline;">Perfume Oil Supply page</a> or contact our fragrance specialist directly via WhatsApp or phone.
          </div>
        </div>
      </div>

      <!-- FAQ 5 -->
      <div class="faq-item">
        <button type="button" class="faq-header-btn" aria-expanded="false">
          <span>How can I contact customer support?</span>
          <span class="faq-toggle-icon">+</span>
        </button>
        <div class="faq-body-panel">
          <div class="faq-body-inner">
            Our customer support is available via phone at <strong><?php echo htmlspecialchars($supplierPhone); ?></strong>, via email at <strong><?php echo htmlspecialchars($storeSettings['store_email'] ?? 'sales@zaffran.local'); ?></strong>, or directly through our official WhatsApp business channel.
          </div>
        </div>
      </div>

    </div>

  </div>
</section>

<!-- Curved Transition: FAQ (Black) -> Why Us (White) -->
<div class="curved-divider curved-divider-from-dark" aria-hidden="true">
  <svg viewBox="0 0 1200 64" preserveAspectRatio="none">
    <path d="M0,0 C320,64 880,64 1200,0 L1200,64 L0,64 Z"></path>
  </svg>
</div>


<!-- ==========================================================================
     10. WHY CHOOSE ZAFFRAN? (Minimal White Service Information Section)
     ========================================================================== -->
<section class="section services-section reveal-on-scroll" id="why-us">
  <div class="container">
    
    <div class="section-header">
      <span class="section-eyebrow">Our Standards</span>
      <h2 class="section-title">Why Choose Zaffran?</h2>
      <p class="section-desc">
        A seamless e-commerce experience backed by real database records, secure dispatch, and dedicated support.
      </p>
    </div>

    <div class="services-grid reveal-stagger">
      
      <!-- Feature 1 -->
      <div class="service-card js-tilt-card">
        <div class="service-icon-box">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
        </div>
        <h3 class="service-title">Website-Based Secure Ordering</h3>
        <p class="service-desc">
          Complete, on-site checkout system with instant order verification and tracking without third-party redirection.
        </p>
      </div>

      <!-- Feature 2 -->
      <div class="service-card js-tilt-card">
        <div class="service-icon-box">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>
        </div>
        <h3 class="service-title">Dedicated Support</h3>
        <p class="service-desc">
          Direct telephone and WhatsApp consultation available for product inquiries and customer guidance.
        </p>
      </div>

      <!-- Feature 3 -->
      <div class="service-card js-tilt-card">
        <div class="service-icon-box">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
        </div>
        <h3 class="service-title">Real-Time Order Updates</h3>
        <p class="service-desc">
          Automated customer account dashboard and order tracking tokens to review order progress at any time.
        </p>
      </div>

      <!-- Feature 4 -->
      <div class="service-card js-tilt-card">
        <div class="service-icon-box">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
        </div>
        <h3 class="service-title">Perfume Oil Business Guidance</h3>
        <p class="service-desc">
          Commercial supply and formulation mentorship for entrepreneurs establishing their own independent fragrance lines.
        </p>
      </div>

    </div>

  </div>
</section>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
