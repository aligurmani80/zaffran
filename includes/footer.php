<?php
/**
 * Footer Component — Zaffran storefront footer, support channel and scripts.
 */

if (!defined('ZAFFRAN_APP')) {
    exit('Direct access not permitted');
}

$storeSettings = getStoreSettings();
$footerIsSubdir = (basename(dirname($_SERVER['PHP_SELF'])) === 'account');
$footerPrefix = $footerIsSubdir ? '../' : '';

$supportWaLink = trim((string)($storeSettings['whatsapp_contact_link'] ?? ''));
if ($supportWaLink === '') {
    $waDigits = preg_replace('/\D+/', '', (string)($storeSettings['whatsapp_number'] ?? '03041202111'));
    if (strpos($waDigits, '0') === 0) {
        $waDigits = '92' . substr($waDigits, 1);
    }
    $supportWaLink = 'https://wa.me/' . $waDigits;
}
?>

  <footer class="site-footer" id="contact">
    <div class="container">
      <div class="footer-main-grid">

        <div class="footer-col footer-brand-col">
          <div class="footer-brand-title">ZAFFRAN</div>
          <span class="footer-brand-tagline">Botanicals &amp; Parfumerie</span>
          <p class="footer-brand-bio">
            Pure Himalayan Shilajit, hand-harvested Super Negin saffron, artisanal perfumes and fragrance oils — sourced with provenance, prepared in small batches, and shipped across Pakistan.
          </p>
          <div class="footer-social-row">
            <?php if (!empty($storeSettings['instagram_url'])): ?>
              <a href="<?php echo htmlspecialchars($storeSettings['instagram_url']); ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram">Instagram</a>
            <?php endif; ?>
            <?php if (!empty($storeSettings['facebook_url'])): ?>
              <a href="<?php echo htmlspecialchars($storeSettings['facebook_url']); ?>" target="_blank" rel="noopener noreferrer" aria-label="Facebook">Facebook</a>
            <?php endif; ?>
            <?php if (!empty($storeSettings['tiktok_url'])): ?>
              <a href="<?php echo htmlspecialchars($storeSettings['tiktok_url']); ?>" target="_blank" rel="noopener noreferrer" aria-label="TikTok">TikTok</a>
            <?php endif; ?>
          </div>
        </div>

        <div class="footer-col">
          <h3 class="footer-heading">Collections</h3>
          <ul class="footer-links-list">
            <li><a href="<?php echo $footerPrefix; ?>shop.php?category=perfume">Artisanal Perfumes</a></li>
            <li><a href="<?php echo $footerPrefix; ?>shop.php?category=shilajit">Himalayan Shilajit</a></li>
            <li><a href="<?php echo $footerPrefix; ?>shop.php?category=zaffran">Royal Zaffran</a></li>
            <li><a href="<?php echo $footerPrefix; ?>category.php?slug=perfume-oils">Perfume Oil Supply</a></li>
            <li><a href="<?php echo $footerPrefix; ?>shop.php">All Products</a></li>
          </ul>
        </div>

        <div class="footer-col">
          <h3 class="footer-heading">The House</h3>
          <ul class="footer-links-list">
            <li><a href="<?php echo $footerPrefix; ?>index.php#about">Our Story</a></li>
            <li><a href="<?php echo $footerPrefix; ?>index.php#reviews">Customer Reviews</a></li>
            <li><a href="<?php echo $footerPrefix; ?>index.php#business-opportunity">Business Setup</a></li>
            <li><a href="<?php echo $footerPrefix; ?>index.php#faq">Questions &amp; Answers</a></li>
            <li><a href="<?php echo $footerPrefix; ?>index.php#why-us">Our Standards</a></li>
          </ul>
        </div>

        <div class="footer-col">
          <h3 class="footer-heading">Client Care</h3>
          <div class="footer-contact-block">
            <div class="footer-contact-item">
              <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                <circle cx="12" cy="10" r="3"></circle>
              </svg>
              <span><?php echo htmlspecialchars($storeSettings['store_address'] ?? 'Circular Road, Kot Addu, Punjab, Pakistan'); ?></span>
            </div>
            <div class="footer-contact-item">
              <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
              </svg>
              <a href="tel:<?php echo htmlspecialchars(preg_replace('/[^0-9+]/', '', (string)($storeSettings['store_phone'] ?? ''))); ?>"><?php echo htmlspecialchars($storeSettings['store_phone'] ?? '0304-1202111'); ?></a>
            </div>
            <div class="footer-contact-item">
              <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                <polyline points="22,6 12,13 2,6"></polyline>
              </svg>
              <a href="mailto:<?php echo htmlspecialchars($storeSettings['store_email'] ?? 'sales@zaffran.local'); ?>"><?php echo htmlspecialchars($storeSettings['store_email'] ?? 'sales@zaffran.local'); ?></a>
            </div>
          </div>
          <a href="<?php echo $footerPrefix; ?>cart.php" class="footer-bag-link">View Shopping Bag &rarr;</a>
        </div>

      </div>

      <div class="footer-bottom-bar">
        <div>&copy; <?php echo date('Y'); ?> Zaffran. All rights reserved.</div>
        <div class="footer-bottom-links">
          <a href="<?php echo $footerPrefix; ?>shop.php">Shop</a>
          <a href="<?php echo $footerPrefix; ?>checkout.php">Secure Checkout</a>
          <a href="<?php echo $footerPrefix; ?>index.php#faq">Support</a>
        </div>
      </div>
    </div>
  </footer>

  <!-- WhatsApp is offered for customer support only — never for checkout or payments. -->
  <?php if ($supportWaLink): ?>
    <a href="<?php echo htmlspecialchars($supportWaLink); ?>" class="wa-support-fab" target="_blank" rel="noopener noreferrer" aria-label="Chat with Zaffran customer support on WhatsApp">
      <span class="wa-support-icon" aria-hidden="true">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M17.6 6.32A7.85 7.85 0 0 0 12.05 4a7.94 7.94 0 0 0-6.9 11.9L4 20l4.2-1.1a7.9 7.9 0 0 0 3.84.98h.01a7.94 7.94 0 0 0 5.55-13.56zM12.05 18.5a6.6 6.6 0 0 1-3.36-.92l-.24-.14-2.49.65.66-2.43-.16-.25a6.6 6.6 0 1 1 5.59 3.09zm3.62-4.94c-.2-.1-1.17-.58-1.35-.64-.18-.07-.31-.1-.44.1-.13.2-.5.64-.62.77-.11.13-.23.15-.43.05-.2-.1-.84-.31-1.6-.99-.59-.53-.99-1.18-1.1-1.38-.12-.2-.01-.31.09-.41.09-.09.2-.23.3-.35.1-.12.13-.2.2-.33.06-.13.03-.25-.02-.35-.05-.1-.44-1.06-.6-1.45-.16-.38-.32-.33-.44-.33h-.38c-.13 0-.34.05-.52.25-.18.2-.68.67-.68 1.63 0 .96.7 1.89.8 2.02.1.13 1.38 2.1 3.34 2.95.47.2.83.32 1.12.41.47.15.9.13 1.24.08.38-.06 1.17-.48 1.34-.94.16-.46.16-.85.11-.94-.05-.08-.18-.13-.38-.23z"/></svg>
      </span>
      <span class="wa-support-label">Support</span>
    </a>
  <?php endif; ?>

  <?php
  $zafScriptVersion = static function (string $relativePath): string {
      $file = dirname(__DIR__) . '/' . $relativePath;
      return (string)(@filemtime($file) ?: '1');
  };
  ?>
  <script src="<?php echo $footerPrefix; ?>assets/js/main.js?v=<?php echo $zafScriptVersion('assets/js/main.js'); ?>"></script>
  <script src="<?php echo $footerPrefix; ?>assets/js/hero.js?v=<?php echo $zafScriptVersion('assets/js/hero.js'); ?>"></script>
</body>
</html>
