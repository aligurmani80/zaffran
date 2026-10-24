<?php
/**
 * Header & Navigation Component
 * Zaffran — Quiet luxury storefront navigation.
 */

if (!defined('ZAFFRAN_APP')) {
    exit('Direct access not permitted');
}

require_once __DIR__ . '/../config/auth.php';

$announcements = getAnnouncements();
$cartCount = getCartCount();
$storeSettings = getStoreSettings();
$csrfToken = getCsrfToken();
$isAuthenticated = CustomerAuth::isAuthenticated();
$customer = $isAuthenticated ? CustomerAuth::getCustomer() : null;

// Determine relative base path for links when inside /account/ subdirectory
$isSubdir = (basename(dirname($_SERVER['PHP_SELF'])) === 'account');
$basePrefix = $isSubdir ? '../' : '';

$announcement = !empty($announcements) ? $announcements[0] : null;
$annMode = ($announcement && ($announcement['display_type'] ?? 'scrolling') === 'static') ? 'static' : 'scrolling';
$annBg = ($announcement && preg_match('/^#[0-9a-fA-F]{6}$/', (string)($announcement['bg_color'] ?? ''))) ? $announcement['bg_color'] : '#0D3326';
$annText = ($announcement && preg_match('/^#[0-9a-fA-F]{6}$/', (string)($announcement['text_color'] ?? ''))) ? $announcement['text_color'] : '#FBF8F1';

$currentPage = basename($_SERVER['PHP_SELF']);
$navProductPage = in_array($currentPage, ['shop.php', 'product.php', 'category.php'], true);

$brandLogo = trim((string)($storeSettings['store_logo'] ?? ''));
$brandLogoSrc = (preg_match('#^assets/uploads/branding/[A-Za-z0-9._-]+$#', $brandLogo)) ? $basePrefix . $brandLogo : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php echo htmlspecialchars($csrfToken); ?>">
  <meta name="theme-color" content="#0D3326">
  <title><?php echo htmlspecialchars($pageTitle ?? 'Zaffran | Pure Botanicals, Artisanal Fragrances & Essence Oils'); ?></title>
  <meta name="description" content="<?php echo htmlspecialchars($pageDescription ?? 'Discover handcrafted luxury perfumes, authentic Himalayan Shilajit, pure Super Negin Zaffran, and premium perfume oils.'); ?>">

  <script>
    // Progressive reveal: content is only hidden when JS + IntersectionObserver
    // are available, with a safety timer so nothing can stay invisible.
    (function () {
      var root = document.documentElement;
      root.classList.add('js');
      if ('IntersectionObserver' in window) {
        root.classList.add('js-reveal');
        window.setTimeout(function () {
          document.querySelectorAll('.reveal-on-scroll, .reveal-stagger').forEach(function (el) {
            el.classList.add('revealed');
          });
        }, 6000);
      }
    })();
  </script>

  <!-- Google Fonts: Playfair Display, Plus Jakarta Sans, Cinzel -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Playfair+Display:ital,wght@0,500;0,600;0,700;0,800;1,500;1,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Shared CSS Variables & Core Styles -->
  <?php
  $zafAssetVersion = static function (string $relativePath): string {
      $file = dirname(__DIR__) . '/' . $relativePath;
      return (string)(@filemtime($file) ?: '1');
  };
  ?>
  <link rel="stylesheet" href="<?php echo $basePrefix; ?>assets/css/variables.css?v=<?php echo $zafAssetVersion('assets/css/variables.css'); ?>">
  <link rel="stylesheet" href="<?php echo $basePrefix; ?>assets/css/header.css?v=<?php echo $zafAssetVersion('assets/css/header.css'); ?>">
  <link rel="stylesheet" href="<?php echo $basePrefix; ?>assets/css/hero.css?v=<?php echo $zafAssetVersion('assets/css/hero.css'); ?>">
  <link rel="stylesheet" href="<?php echo $basePrefix; ?>assets/css/sections.css?v=<?php echo $zafAssetVersion('assets/css/sections.css'); ?>">
  <link rel="stylesheet" href="<?php echo $basePrefix; ?>assets/css/shop.css?v=<?php echo $zafAssetVersion('assets/css/shop.css'); ?>">
  <link rel="stylesheet" href="<?php echo $basePrefix; ?>assets/css/cart_checkout.css?v=<?php echo $zafAssetVersion('assets/css/cart_checkout.css'); ?>">
  <link rel="stylesheet" href="<?php echo $basePrefix; ?>assets/css/auth.css?v=<?php echo $zafAssetVersion('assets/css/auth.css'); ?>">
</head>
<body>

  <a class="skip-link" href="#main-content">Skip to main content</a>

  <?php if ($announcement): ?>
    <aside class="announcement-bar announcement-<?php echo $annMode; ?><?php echo count($announcements) === 1 ? ' announcement-single' : ''; ?>"
           aria-label="Store announcement"
           style="--ann-bg: <?php echo htmlspecialchars($annBg); ?>; --ann-text: <?php echo htmlspecialchars($annText); ?>;">
      <div class="container announcement-container">
        <?php if ($annMode === 'scrolling'): ?>
          <?php
          // Repeat the message set so the marquee can loop seamlessly.
          $marqueeItems = array_merge($announcements, $announcements);
          ?>
          <div class="announcement-marquee" aria-label="<?php echo htmlspecialchars($announcement['message'] ?? ''); ?>">
            <div class="announcement-marquee-track">
              <?php foreach ($marqueeItems as $i => $item): ?>
                <span class="announcement-marquee-item" <?php echo $i >= count($announcements) ? 'aria-hidden="true"' : ''; ?>>
                  <?php if (!empty($item['title'])): ?>
                    <span class="announcement-badge"><?php echo htmlspecialchars($item['title']); ?></span>
                  <?php endif; ?>
                  <span class="announcement-text"><?php echo htmlspecialchars($item['message'] ?? ''); ?></span>
                  <?php if (!empty($item['link'])): ?>
                    <a href="<?php echo $basePrefix . htmlspecialchars($item['link']); ?>" class="announcement-link" <?php echo $i >= count($announcements) ? 'tabindex="-1"' : ''; ?>>Discover &rarr;</a>
                  <?php endif; ?>
                  <span class="announcement-diamond" aria-hidden="true">&#9670;</span>
                </span>
              <?php endforeach; ?>
            </div>
          </div>
        <?php else: ?>
          <div class="announcement-static-item">
            <?php if (!empty($announcement['title'])): ?>
              <span class="announcement-badge"><?php echo htmlspecialchars($announcement['title']); ?></span>
            <?php endif; ?>
            <span class="announcement-text"><?php echo htmlspecialchars($announcement['message'] ?? ''); ?></span>
            <?php if (!empty($announcement['link'])): ?>
              <a href="<?php echo $basePrefix . htmlspecialchars($announcement['link']); ?>" class="announcement-link">Discover &rarr;</a>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    </aside>
  <?php endif; ?>

  <!-- MAIN NAVIGATION -->
  <header class="site-header" id="site-header">
    <div class="container header-container">

      <button class="mobile-nav-toggle" id="mobile-nav-toggle" aria-label="Open navigation menu" aria-expanded="false" aria-controls="mobile-drawer" type="button">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
          <line x1="3" y1="7" x2="21" y2="7"></line>
          <line x1="3" y1="12" x2="16" y2="12"></line>
          <line x1="3" y1="17" x2="21" y2="17"></line>
        </svg>
      </button>

      <a href="<?php echo $basePrefix; ?>index.php" class="brand-logo" aria-label="Zaffran — Home">
        <?php if ($brandLogoSrc): ?>
          <img src="<?php echo htmlspecialchars($brandLogoSrc); ?>" alt="" class="brand-logo-img">
        <?php else: ?>
          <span class="brand-symbol" aria-hidden="true">Z</span>
        <?php endif; ?>
        <span class="brand-text">
          <span class="brand-title">ZAFFRAN</span>
          <span class="brand-tagline">Botanicals &amp; Parfumerie</span>
        </span>
      </a>

      <nav class="nav-menu-wrap" aria-label="Main navigation">
        <ul class="nav-menu">
          <li><a href="<?php echo $basePrefix; ?>index.php" class="nav-link <?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">Home</a></li>

          <li><a href="<?php echo $basePrefix; ?>shop.php" class="nav-link <?php echo $navProductPage ? 'active' : ''; ?>">Products</a></li>

          <li><a href="<?php echo $basePrefix; ?>index.php#about" class="nav-link">About</a></li>
          <li><a href="<?php echo $basePrefix; ?>index.php#reviews" class="nav-link">Reviews</a></li>
          <li><a href="<?php echo $basePrefix; ?>index.php#contact" class="nav-link">Contact</a></li>
        </ul>
      </nav>

      <div class="header-actions">
        <button type="button" class="action-btn js-search-trigger" aria-label="Search products" title="Search">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <circle cx="11" cy="11" r="7.5"></circle>
            <line x1="21" y1="21" x2="16.4" y2="16.4"></line>
          </svg>
        </button>

        <?php if ($isAuthenticated && $customer): ?>
          <a href="<?php echo $basePrefix; ?>account.php" class="action-btn" aria-label="My account" title="My Account">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
              <circle cx="12" cy="7" r="4"></circle>
            </svg>
          </a>
        <?php else: ?>
          <a href="<?php echo $basePrefix; ?>login.php" class="action-btn" aria-label="Sign in with email" title="Sign In / Register">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
              <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
              <circle cx="12" cy="7" r="4"></circle>
            </svg>
          </a>
        <?php endif; ?>

        <a href="<?php echo $basePrefix; ?>cart.php" class="action-btn action-cart" aria-label="Shopping bag" title="Shopping Bag">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
            <path d="M6 7h12l1.2 13H4.8L6 7z"></path>
            <path d="M9 9V6a3 3 0 0 1 6 0v3"></path>
          </svg>
          <span class="cart-count-badge" id="header-cart-count" style="<?php echo $cartCount > 0 ? '' : 'display:none;'; ?>">
            <?php echo (int)$cartCount; ?>
          </span>
        </a>
      </div>

    </div>
  </header>

  <!-- SEARCH OVERLAY -->
  <div class="search-overlay" id="search-overlay" aria-hidden="true">
    <div class="search-dialog" role="dialog" aria-modal="true" aria-labelledby="search-dialog-title">
      <div class="search-dialog-header">
        <h3 class="search-dialog-title" id="search-dialog-title">Search the Zaffran Collection</h3>
        <button type="button" class="search-close-btn js-search-close" aria-label="Close search">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
          </svg>
        </button>
      </div>

      <form action="<?php echo $basePrefix; ?>shop.php" method="GET" class="search-form">
        <div class="search-input-wrap">
          <input type="search" name="q" class="search-input-field" placeholder="Search by name, scent or botanical..." required autocomplete="off">
          <button type="submit" class="search-submit-btn" aria-label="Submit search">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
              <circle cx="11" cy="11" r="8"></circle>
              <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
          </button>
        </div>
      </form>

      <div class="search-quick-tags">
        <span class="search-quick-label">Popular:</span>
        <a href="<?php echo $basePrefix; ?>shop.php?category=perfume" class="search-quick-tag">Perfumes</a>
        <a href="<?php echo $basePrefix; ?>shop.php?category=shilajit" class="search-quick-tag">Himalayan Shilajit</a>
        <a href="<?php echo $basePrefix; ?>shop.php?category=zaffran" class="search-quick-tag">Super Negin Saffron</a>
        <a href="<?php echo $basePrefix; ?>category.php?slug=perfume-oils" class="search-quick-tag">Perfume Oils</a>
      </div>
    </div>
  </div>

  <!-- MOBILE NAVIGATION DRAWER -->
  <div class="mobile-drawer-backdrop" id="mobile-drawer-backdrop" aria-hidden="true"></div>
  <aside class="mobile-drawer" id="mobile-drawer" role="dialog" aria-modal="true" aria-label="Mobile navigation drawer">
    <div class="mobile-drawer-header">
      <div class="brand-logo">
        <?php if ($brandLogoSrc): ?>
          <img src="<?php echo htmlspecialchars($brandLogoSrc); ?>" alt="" class="brand-logo-img">
        <?php else: ?>
          <span class="brand-symbol" aria-hidden="true">Z</span>
        <?php endif; ?>
        <span class="brand-text">
          <span class="brand-title" style="font-size:1.2rem;">ZAFFRAN</span>
          <span class="brand-tagline">Botanicals &amp; Parfumerie</span>
        </span>
      </div>
      <button class="drawer-close-btn" id="drawer-close-btn" aria-label="Close menu" type="button">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>

    <ul class="mobile-drawer-menu">
      <li class="mobile-nav-item"><a href="<?php echo $basePrefix; ?>index.php" class="<?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">Home</a></li>
      <li class="mobile-nav-item"><a href="<?php echo $basePrefix; ?>shop.php" class="<?php echo $navProductPage ? 'active' : ''; ?>">Products</a></li>

      <li class="mobile-nav-item"><a href="<?php echo $basePrefix; ?>index.php#about">About</a></li>
      <li class="mobile-nav-item"><a href="<?php echo $basePrefix; ?>index.php#reviews">Reviews</a></li>
      <li class="mobile-nav-item"><a href="<?php echo $basePrefix; ?>index.php#contact">Contact</a></li>
      <li class="mobile-nav-item"><a href="<?php echo $basePrefix; ?>index.php#business-opportunity">Business Setup</a></li>

      <li class="mobile-nav-item drawer-divider"><a href="<?php echo $basePrefix; ?>cart.php">Shopping Bag <span class="drawer-count"><?php echo (int)$cartCount; ?></span></a></li>

      <?php if ($isAuthenticated && $customer): ?>
        <li class="mobile-nav-item"><a href="<?php echo $basePrefix; ?>account.php">My Account</a></li>
        <li class="mobile-nav-item"><a href="<?php echo $basePrefix; ?>account/orders.php">My Orders</a></li>
        <li class="mobile-nav-item"><a href="<?php echo $basePrefix; ?>logout.php" class="drawer-signout">Sign Out</a></li>
      <?php else: ?>
        <li class="mobile-nav-item"><a href="<?php echo $basePrefix; ?>login.php" class="drawer-signin">Sign In / Register</a></li>
      <?php endif; ?>
    </ul>

    <div class="mobile-drawer-footer">
      <p class="mobile-drawer-contact">
        <strong>Customer Support</strong><br>
        <?php echo htmlspecialchars($storeSettings['store_phone'] ?? '0304-1202111'); ?>
      </p>
      <a href="<?php echo $basePrefix; ?>shop.php" class="btn btn-primary" style="width:100%;">Explore the Collection</a>
    </div>
  </aside>
