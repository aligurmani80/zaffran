<?php
/**
 * Zaffran Admin Panel — Create Product
 */
define('ZAFFRAN_APP', true);
$pageTitle = 'Add New Product';
require_once __DIR__ . '/header.php';

$db = getDbConnection();
$errorMsg = '';
$successMsg = '';

// Load categories
$categories = $db->query("SELECT id, name, slug FROM categories WHERE is_active = 1 ORDER BY display_order ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateAdminCsrfToken($token)) {
        $errorMsg = 'Security validation failed.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $categorySlug = trim($_POST['category_slug'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $salePrice = !empty($_POST['sale_price']) ? (float)$_POST['sale_price'] : null;
        $sku = trim($_POST['sku'] ?? '');
        $badge = trim($_POST['badge'] ?? '');
        $shortDesc = trim($_POST['short_desc'] ?? '');
        $longDesc = trim($_POST['long_desc'] ?? '');
        $isFeatured = !empty($_POST['is_featured']) ? 1 : 0;
        $isActive = !empty($_POST['is_active']) ? 1 : 0;
        $trackStock = !empty($_POST['track_stock']) ? 1 : 0;
        $stock = $trackStock ? max(0, (int)($_POST['stock'] ?? 0)) : 50;

        // Auto slug if empty
        if (empty($slug)) {
            $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $name));
            $slug = trim($slug, '-');
        }

        // Validate
        if (empty($name)) {
            $errorMsg = 'Product name is required.';
        } elseif ($price <= 0) {
            $errorMsg = 'Regular price must be greater than zero.';
        } elseif ($salePrice !== null && $salePrice >= $price) {
            $errorMsg = 'Sale price must be strictly lower than regular price.';
        } elseif ($salePrice !== null && $salePrice < 0) {
            $errorMsg = 'Sale price cannot be negative.';
        } else {
            // Find category display name
            $categoryName = 'Uncategorized';
            foreach ($categories as $c) {
                if ($c['slug'] === $categorySlug) {
                    $categoryName = $c['name'];
                    break;
                }
            }

            // Handle Image Upload if provided
            $imageUrl = 'assets/images/products/perfume.svg'; // default fallback
            if (!empty($_FILES['product_image']['name'])) {
                $uploadResult = handleImageUpload($_FILES['product_image'], 'products');
                if ($uploadResult['success']) {
                    $imageUrl = $uploadResult['path'];
                } else {
                    $errorMsg = 'Image upload error: ' . $uploadResult['error'];
                }
            }

            if (empty($errorMsg)) {
                try {
                    // Check slug uniqueness
                    $slugCheck = $db->prepare("SELECT id FROM products WHERE slug = ?");
                    $slugCheck->execute([$slug]);
                    if ($slugCheck->fetch()) {
                        $slug = $slug . '-' . time();
                    }

                    $insertStmt = $db->prepare("INSERT INTO products (name, slug, category, category_slug, badge, short_desc, long_desc, price, sale_price, image_url, is_featured, is_active, stock, track_stock, sku, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                    $insertStmt->execute([
                        $name,
                        $slug,
                        $categoryName,
                        $categorySlug,
                        $badge,
                        $shortDesc,
                        $longDesc,
                        $price,
                        $salePrice,
                        $imageUrl,
                        $isFeatured,
                        $isActive,
                        $stock,
                        $trackStock,
                        $sku
                    ]);

                    $newProductId = $db->lastInsertId();
                    logAdminAudit('create_product', 'product', (string)$newProductId, "Created product '{$name}' (SKU: {$sku}, Price: PKR {$price})");

                    header("Location: products.php?created=1");
                    exit;
                } catch (Exception $e) {
                    $errorMsg = 'Database error: ' . $e->getMessage();
                }
            }
        }
    }
}

$csrfToken = getAdminCsrfToken();
?>

<div style="max-width: 900px; margin: 0 auto;">
  <div style="margin-bottom: 20px;">
    <a href="products.php" style="color: var(--admin-sage); text-decoration: none; font-size: 13px;">&larr; Back to Products</a>
    <h2 style="font-size: 24px; color: var(--admin-forest); margin-top: 4px;">Add New Product</h2>
  </div>

  <?php if (!empty($errorMsg)): ?>
    <div class="notice-box notice-error">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
      <div><?php echo htmlspecialchars($errorMsg); ?></div>
    </div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

    <div class="admin-card">
      <div class="admin-card-header">
        <div class="admin-card-title">Basic Information</div>
      </div>
      <div class="admin-card-body">
        
        <div class="form-row">
          <div class="form-group" style="flex: 2;">
            <label class="form-label" for="name">Product Name <span class="required">*</span></label>
            <input type="text" id="name" name="name" class="form-control" placeholder="E.g. Royal Kashmiri Saffron Grade 1" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
          </div>

          <div class="form-group" style="flex: 1;">
            <label class="form-label" for="category_slug">Category <span class="required">*</span></label>
            <select id="category_slug" name="category_slug" class="form-control" required>
              <?php foreach ($categories as $c): ?>
                <option value="<?php echo htmlspecialchars($c['slug']); ?>" <?php echo ($_POST['category_slug'] ?? '') === $c['slug'] ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($c['name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="slug">URL Slug (leave empty to generate automatically)</label>
            <input type="text" id="slug" name="slug" class="form-control" placeholder="kashmiri-saffron-grade-1" value="<?php echo htmlspecialchars($_POST['slug'] ?? ''); ?>">
          </div>

          <div class="form-group">
            <label class="form-label" for="sku">SKU Code</label>
            <input type="text" id="sku" name="sku" class="form-control" placeholder="ZAF-SAF-001" value="<?php echo htmlspecialchars($_POST['sku'] ?? ''); ?>">
          </div>

          <div class="form-group">
            <label class="form-label" for="badge">Product Badge (Optional)</label>
            <input type="text" id="badge" name="badge" class="form-control" placeholder="E.g. Best Seller, Pure Harvest" value="<?php echo htmlspecialchars($_POST['badge'] ?? ''); ?>">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="short_desc">Short Summary (1-2 sentences)</label>
          <input type="text" id="short_desc" name="short_desc" class="form-control" placeholder="Hand-harvested Super Negin whole saffron stigmas with intense crocin aroma..." value="<?php echo htmlspecialchars($_POST['short_desc'] ?? ''); ?>">
        </div>

        <div class="form-group">
          <label class="form-label" for="long_desc">Detailed Description</label>
          <textarea id="long_desc" name="long_desc" class="form-control" rows="5" placeholder="Full product details, sourcing provenance, usage instructions..."><?php echo htmlspecialchars($_POST['long_desc'] ?? ''); ?></textarea>
        </div>

      </div>
    </div>

    <!-- Pricing & Inventory -->
    <div class="admin-card">
      <div class="admin-card-header">
        <div class="admin-card-title">Pricing & Inventory</div>
      </div>
      <div class="admin-card-body">
        
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="price">Regular Price (PKR) <span class="required">*</span></label>
            <input type="number" step="0.01" min="0" id="price" name="price" class="form-control" placeholder="8900" required value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>">
          </div>

          <div class="form-group">
            <label class="form-label" for="sale_price">Sale Price (PKR, optional)</label>
            <input type="number" step="0.01" min="0" id="sale_price" name="sale_price" class="form-control" placeholder="7990" value="<?php echo htmlspecialchars($_POST['sale_price'] ?? ''); ?>">
            <div class="form-help">Must be lower than Regular Price to activate discount badge.</div>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label style="display: flex; align-items: center; gap: 8px; font-weight: 600; cursor: pointer;">
              <input type="checkbox" name="track_stock" value="1" id="trackStockCheck" <?php echo !empty($_POST['track_stock']) ? 'checked' : ''; ?>>
              Enable Explicit Stock Quantity Tracking
            </label>
            <div class="form-help">If disabled, the product operates as standard artisanal supply without rigid stock limits.</div>
          </div>

          <div class="form-group" id="stockGroup" style="<?php echo !empty($_POST['track_stock']) ? '' : 'display:none;'; ?>">
            <label class="form-label" for="stock">Available Stock Units</label>
            <input type="number" min="0" id="stock" name="stock" class="form-control" value="<?php echo htmlspecialchars($_POST['stock'] ?? '50'); ?>">
          </div>
        </div>

      </div>
    </div>

    <!-- Product Image & Visibility -->
    <div class="admin-card">
      <div class="admin-card-header">
        <div class="admin-card-title">Media & Publishing</div>
      </div>
      <div class="admin-card-body">
        
        <div class="form-group">
          <label class="form-label" for="product_image">Primary Product Image</label>
          <input type="file" id="product_image" name="product_image" class="form-control" accept="image/jpeg,image/png,image/webp">
          <div class="form-help">Accepted formats: JPG, PNG, WebP (Max 5MB). Images will be stored securely.</div>
        </div>

        <div style="display: flex; gap: 24px; margin-top: 16px;">
          <label style="display: flex; align-items: center; gap: 8px; font-weight: 600; cursor: pointer;">
            <input type="checkbox" name="is_active" value="1" checked>
            Product is Active &amp; Published in Catalog
          </label>

          <label style="display: flex; align-items: center; gap: 8px; font-weight: 600; cursor: pointer;">
            <input type="checkbox" name="is_featured" value="1" <?php echo !empty($_POST['is_featured']) ? 'checked' : ''; ?>>
            Feature on Homepage
          </label>
        </div>

      </div>
    </div>

    <div style="display: flex; justify-content: flex-end; gap: 12px; margin-bottom: 40px;">
      <a href="products.php" class="btn btn-secondary">Cancel</a>
      <button type="submit" class="btn btn-primary" style="padding: 12px 28px;">Save Product</button>
    </div>

  </form>
</div>

<script>
const trackCheck = document.getElementById('trackStockCheck');
const stockGroup = document.getElementById('stockGroup');
if (trackCheck && stockGroup) {
  trackCheck.addEventListener('change', () => {
    stockGroup.style.display = trackCheck.checked ? 'block' : 'none';
  });
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
