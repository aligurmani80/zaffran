<?php
/**
 * Zaffran Admin Panel — Edit Product & Manage Product Gallery
 */
define('ZAFFRAN_APP', true);
require_once __DIR__ . '/auth.php';

$currentAdmin = requireAdminAuth();
$db = getDbConnection();

$productId = (int)($_GET['id'] ?? 0);
if ($productId <= 0) {
    header('Location: products.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: products.php');
    exit;
}

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
        $stock = $trackStock ? max(0, (int)($_POST['stock'] ?? 0)) : (int)$product['stock'];

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
            // Category title lookup
            $categoryName = $product['category'];
            foreach ($categories as $c) {
                if ($c['slug'] === $categorySlug) {
                    $categoryName = $c['name'];
                    break;
                }
            }

            // Image replacement if uploaded
            $imageUrl = $product['image_url'];
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
                    if (empty($slug)) {
                        $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $name));
                        $slug = trim($slug, '-');
                    }
                    $slugCheck = $db->prepare("SELECT id FROM products WHERE slug = ? AND id != ?");
                    $slugCheck->execute([$slug, $productId]);
                    if ($slugCheck->fetch()) {
                        $slug = $slug . '-' . time();
                    }

                    $updStmt = $db->prepare("UPDATE products SET 
                        name = ?, slug = ?, category = ?, category_slug = ?, badge = ?, 
                        short_desc = ?, long_desc = ?, price = ?, sale_price = ?, image_url = ?, 
                        is_featured = ?, is_active = ?, stock = ?, track_stock = ?, sku = ? 
                        WHERE id = ?");

                    $updStmt->execute([
                        $name, $slug, $categoryName, $categorySlug, $badge,
                        $shortDesc, $longDesc, $price, $salePrice, $imageUrl,
                        $isFeatured, $isActive, $stock, $trackStock, $sku,
                        $productId
                    ]);

                    logAdminAudit('edit_product', 'product', (string)$productId, "Updated product '{$name}' (Price: PKR {$price})");

                    $successMsg = 'Product details updated successfully.';
                    // Refresh data
                    $stmt->execute([$productId]);
                    $product = $stmt->fetch();
                } catch (Exception $e) {
                    $errorMsg = 'Database error: ' . $e->getMessage();
                }
            }
        }
    }
}

$pageTitle = "Edit Product: {$product['name']}";
require_once __DIR__ . '/header.php';
$csrfToken = getAdminCsrfToken();
?>

<div style="max-width: 900px; margin: 0 auto;">
  <div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
    <div>
      <a href="products.php" style="color: var(--admin-sage); text-decoration: none; font-size: 13px;">&larr; Back to Products</a>
      <h2 style="font-size: 24px; color: var(--admin-forest); margin-top: 4px;">Edit Product</h2>
    </div>
    <div>
      <a href="../product.php?slug=<?php echo urlencode($product['slug']); ?>" target="_blank" class="btn btn-secondary btn-sm">
        View on Storefront &rarr;
      </a>
    </div>
  </div>

  <?php if (!empty($successMsg)): ?>
    <div class="notice-box notice-success">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
      <div><?php echo htmlspecialchars($successMsg); ?></div>
    </div>
  <?php endif; ?>

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
        <div class="admin-card-title">Product Details</div>
      </div>
      <div class="admin-card-body">
        
        <div class="form-row">
          <div class="form-group" style="flex: 2;">
            <label class="form-label" for="name">Product Name <span class="required">*</span></label>
            <input type="text" id="name" name="name" class="form-control" required value="<?php echo htmlspecialchars($product['name']); ?>">
          </div>

          <div class="form-group" style="flex: 1;">
            <label class="form-label" for="category_slug">Category <span class="required">*</span></label>
            <select id="category_slug" name="category_slug" class="form-control" required>
              <?php foreach ($categories as $c): ?>
                <option value="<?php echo htmlspecialchars($c['slug']); ?>" <?php echo $product['category_slug'] === $c['slug'] ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($c['name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="slug">URL Slug</label>
            <input type="text" id="slug" name="slug" class="form-control" value="<?php echo htmlspecialchars($product['slug']); ?>">
          </div>

          <div class="form-group">
            <label class="form-label" for="sku">SKU Code</label>
            <input type="text" id="sku" name="sku" class="form-control" value="<?php echo htmlspecialchars($product['sku'] ?? ''); ?>">
          </div>

          <div class="form-group">
            <label class="form-label" for="badge">Product Badge</label>
            <input type="text" id="badge" name="badge" class="form-control" placeholder="E.g. Best Seller" value="<?php echo htmlspecialchars($product['badge'] ?? ''); ?>">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="short_desc">Short Summary</label>
          <input type="text" id="short_desc" name="short_desc" class="form-control" value="<?php echo htmlspecialchars($product['short_desc'] ?? ''); ?>">
        </div>

        <div class="form-group">
          <label class="form-label" for="long_desc">Detailed Description</label>
          <textarea id="long_desc" name="long_desc" class="form-control" rows="5"><?php echo htmlspecialchars($product['long_desc'] ?? ''); ?></textarea>
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
            <input type="number" step="0.01" min="0" id="price" name="price" class="form-control" required value="<?php echo htmlspecialchars($product['price']); ?>">
          </div>

          <div class="form-group">
            <label class="form-label" for="sale_price">Sale Price (PKR, optional)</label>
            <input type="number" step="0.01" min="0" id="sale_price" name="sale_price" class="form-control" placeholder="Leave empty for regular price" value="<?php echo htmlspecialchars($product['sale_price'] ?? ''); ?>">
            <div class="form-help">Must be lower than Regular Price to calculate a genuine discount.</div>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label style="display: flex; align-items: center; gap: 8px; font-weight: 600; cursor: pointer;">
              <input type="checkbox" name="track_stock" value="1" id="trackStockCheck" <?php echo !empty($product['track_stock']) ? 'checked' : ''; ?>>
              Enable Explicit Stock Quantity Tracking
            </label>
            <div class="form-help">If disabled, the product operates as standard artisanal supply.</div>
          </div>

          <div class="form-group" id="stockGroup" style="<?php echo !empty($product['track_stock']) ? '' : 'display:none;'; ?>">
            <label class="form-label" for="stock">Available Stock Units</label>
            <input type="number" min="0" id="stock" name="stock" class="form-control" value="<?php echo htmlspecialchars($product['stock']); ?>">
          </div>
        </div>

      </div>
    </div>

    <!-- Media -->
    <div class="admin-card">
      <div class="admin-card-header">
        <div class="admin-card-title">Product Image</div>
      </div>
      <div class="admin-card-body">
        
        <div style="display: flex; gap: 24px; align-items: flex-start; flex-wrap: wrap;">
          <div style="width: 120px; height: 120px; border-radius: 8px; overflow: hidden; border: 1px solid var(--admin-card-border); background: #F8F6F0; display: flex; align-items: center; justify-content: center;">
            <?php if (!empty($product['image_url'])): ?>
              <img src="../<?php echo htmlspecialchars($product['image_url']); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
            <?php else: ?>
              <span style="color: var(--admin-text-muted); font-size: 12px;">No Image</span>
            <?php endif; ?>
          </div>

          <div style="flex: 1; min-width: 260px;">
            <div class="form-group">
              <label class="form-label" for="product_image">Replace Primary Image</label>
              <input type="file" id="product_image" name="product_image" class="form-control" accept="image/jpeg,image/png,image/webp">
              <div class="form-help">Upload new JPG, PNG or WebP file to replace the current image.</div>
            </div>
          </div>
        </div>

        <div style="display: flex; gap: 24px; margin-top: 24px;">
          <label style="display: flex; align-items: center; gap: 8px; font-weight: 600; cursor: pointer;">
            <input type="checkbox" name="is_active" value="1" <?php echo !empty($product['is_active']) ? 'checked' : ''; ?>>
            Active in Catalog
          </label>

          <label style="display: flex; align-items: center; gap: 8px; font-weight: 600; cursor: pointer;">
            <input type="checkbox" name="is_featured" value="1" <?php echo !empty($product['is_featured']) ? 'checked' : ''; ?>>
            Feature on Homepage
          </label>
        </div>

      </div>
    </div>

    <div style="display: flex; justify-content: flex-end; gap: 12px; margin-bottom: 40px;">
      <a href="products.php" class="btn btn-secondary">Cancel</a>
      <button type="submit" class="btn btn-primary" style="padding: 12px 28px;">Update Product</button>
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
