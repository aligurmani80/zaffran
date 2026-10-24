<?php
/**
 * Professional Shop Catalog Page
 * Supports category filters, search query, real price sorting, allowlisted queries
 */

define('ZAFFRAN_APP', true);

require_once __DIR__ . '/config/database.php';
initSession();

$categories = getCategoriesWithCounts();
$selectedCategory = isset($_GET['category']) ? trim($_GET['category']) : 'all';
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
$sortOption = isset($_GET['sort']) ? trim($_GET['sort']) : 'default';

// Fetch filtered, searched and sorted products
$products = getProductsCatalog($selectedCategory, $searchQuery, $sortOption);
$totalProducts = count($products);

// Determine active category name
$activeCategoryName = 'All Collections';
if ($selectedCategory !== 'all' && !empty($selectedCategory)) {
    foreach ($categories as $cat) {
        if ($cat['slug'] === $selectedCategory) {
            $activeCategoryName = $cat['name'];
            break;
        }
    }
}

$pageTitle = 'Shop ' . $activeCategoryName . ' | Zaffran Artisanal Store';
$pageDescription = 'Browse handcrafted perfumes, pure Himalayan Shilajit, Super Negin saffron, and premium botanical extracts directly from source.';

require_once __DIR__ . '/includes/header.php';
?>

<!-- Shop Hero Banner -->
<section class="shop-hero">
  <div class="container shop-hero-inner">
    <span class="section-eyebrow">Botanical Catalog</span>
    <h1 class="section-title"><?php echo htmlspecialchars($activeCategoryName); ?></h1>
    <p class="section-desc">
      Explore slow-matured artisanal perfumes, laboratory-verified Himalayan Shilajit resin, and hand-harvested Kashmir Super Negin saffron.
    </p>
  </div>
</section>

<!-- Main Shop Content -->
<main id="main-content" class="section" style="padding-top: var(--space-2xl);">
  <div class="container">

    <!-- Interactive Filters & Sort Toolbar -->
    <div class="shop-toolbar">
      
      <!-- Category Filter Pills -->
      <div class="category-filter-pills" role="navigation" aria-label="Filter products by category">
        <a href="shop.php<?php echo !empty($searchQuery) ? '?q=' . urlencode($searchQuery) : ''; ?>" 
           class="filter-pill <?php echo ($selectedCategory === 'all') ? 'active' : ''; ?>">
          All Products
        </a>
        <?php foreach ($categories as $cat): ?>
          <a href="shop.php?category=<?php echo urlencode($cat['slug']); ?><?php echo !empty($searchQuery) ? '&q=' . urlencode($searchQuery) : ''; ?>" 
             class="filter-pill <?php echo ($selectedCategory === $cat['slug']) ? 'active' : ''; ?>">
            <?php echo htmlspecialchars($cat['name']); ?>
            <span class="filter-count-badge">(<?php echo (int)$cat['product_count']; ?>)</span>
          </a>
        <?php endforeach; ?>
      </div>

      <!-- Allowlisted Sorting Dropdown -->
      <form action="shop.php" method="GET" class="shop-sort-wrap">
        <?php if ($selectedCategory !== 'all'): ?>
          <input type="hidden" name="category" value="<?php echo htmlspecialchars($selectedCategory); ?>">
        <?php endif; ?>
        <?php if (!empty($searchQuery)): ?>
          <input type="hidden" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>">
        <?php endif; ?>

        <label for="sort-select" class="shop-sort-label">Sort by:</label>
        <select name="sort" id="sort-select" class="shop-sort-select" onchange="this.form.submit()">
          <option value="default" <?php echo ($sortOption === 'default') ? 'selected' : ''; ?>>Featured / Default</option>
          <option value="price_asc" <?php echo ($sortOption === 'price_asc') ? 'selected' : ''; ?>>Price: Low to High</option>
          <option value="price_desc" <?php echo ($sortOption === 'price_desc') ? 'selected' : ''; ?>>Price: High to Low</option>
          <option value="name_asc" <?php echo ($sortOption === 'name_asc') ? 'selected' : ''; ?>>Name: A to Z</option>
          <option value="newest" <?php echo ($sortOption === 'newest') ? 'selected' : ''; ?>>Newest Harvest</option>
        </select>
      </form>

    </div>

    <!-- Active Search / Filter Feedback Bar -->
    <div class="shop-meta-bar">
      <div>
        Showing <strong><?php echo $totalProducts; ?></strong> <?php echo ($totalProducts === 1) ? 'product' : 'products'; ?>
        <?php if (!empty($searchQuery)): ?>
          matching &ldquo;<em><?php echo htmlspecialchars($searchQuery); ?></em>&rdquo;
          <a href="shop.php<?php echo ($selectedCategory !== 'all') ? '?category=' . urlencode($selectedCategory) : ''; ?>" style="color:var(--color-saffron-500); margin-left:8px; font-weight:600;">Clear search</a>
        <?php endif; ?>
      </div>
      <div>
        <span>All prices in Pakistani Rupee (PKR)</span>
      </div>
    </div>

    <!-- Product Grid or Empty State -->
    <?php if (!empty($products)): ?>
      <div class="product-grid">
        <?php foreach ($products as $product): ?>
          <?php include __DIR__ . '/includes/product_card.php'; ?>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <!-- Dedicated Empty Results State -->
      <div class="product-empty-state">
        <div class="empty-state-icon">
          <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="11" cy="11" r="8"></circle>
            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            <line x1="8" y1="11" x2="14" y2="11"></line>
          </svg>
        </div>
        <h3 style="font-family:var(--font-serif); font-size:1.45rem; color:var(--color-forest-900); margin-bottom:8px;">
          <?php if ($selectedCategory === 'perfume-oils'): ?>
            New perfume oils will be added soon.
          <?php elseif (!empty($searchQuery)): ?>
            No products found matching &ldquo;<?php echo htmlspecialchars($searchQuery); ?>&rdquo;
          <?php else: ?>
            No items currently found in this category.
          <?php endif; ?>
        </h3>
        <p style="color:var(--color-text-muted); max-width:500px; margin:0 auto 20px;">
          <?php if ($selectedCategory === 'perfume-oils'): ?>
            We supply wholesale undiluted fragrance oils for brand startups and perfume shops. In the meantime, you can contact our supplier directly.
          <?php else: ?>
            Try adjusting your search criteria or explore our complete handcrafted inventory.
          <?php endif; ?>
        </p>

        <?php if ($selectedCategory === 'perfume-oils'): ?>
          <a href="category.php?slug=perfume-oils" class="btn btn-primary">View Perfume Oil Supply &amp; Contact</a>
        <?php else: ?>
          <a href="shop.php" class="btn btn-primary">Browse All Products</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>

  </div>
</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
