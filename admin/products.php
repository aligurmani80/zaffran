<?php
/**
 * Zaffran Admin Panel — Product Catalog Management
 */
define('ZAFFRAN_APP', true);
$pageTitle = 'Products Catalog';
require_once __DIR__ . '/header.php';

$db = getDbConnection();

// Status messages
$successMsg = '';
$errorMsg = '';

// Toggle active status or archive action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateAdminCsrfToken($token)) {
        $errorMsg = 'Security validation failed.';
    } else {
        $prodId = (int)($_POST['product_id'] ?? 0);
        $action = $_POST['action'];

        if ($action === 'toggle_active' && $prodId > 0) {
            $stmt = $db->prepare("SELECT is_active, name FROM products WHERE id = ?");
            $stmt->execute([$prodId]);
            $p = $stmt->fetch();
            if ($p) {
                $newStatus = $p['is_active'] ? 0 : 1;
                $db->prepare("UPDATE products SET is_active = ? WHERE id = ?")->execute([$newStatus, $prodId]);
                logAdminAudit('toggle_product_status', 'product', (string)$prodId, "Product '{$p['name']}' status changed to " . ($newStatus ? 'Active' : 'Inactive'));
                $successMsg = "Product '{$p['name']}' status updated.";
            }
        }
    }
}

// Search and filter
$search = trim($_GET['search'] ?? '');
$catFilter = trim($_GET['category'] ?? '');

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(name LIKE ? OR sku LIKE ? OR short_desc LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if (!empty($catFilter)) {
    $where[] = "category_slug = ?";
    $params[] = $catFilter;
}

$whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
$stmt = $db->prepare("SELECT * FROM products {$whereSql} ORDER BY id ASC");
$stmt->execute($params);
$products = $stmt->fetchAll();

// Categories for filter
$categories = $db->query("SELECT slug, name FROM categories WHERE is_active = 1 ORDER BY display_order ASC")->fetchAll();
$csrfToken = getAdminCsrfToken();
?>

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

<!-- TOP ACTION & FILTER BAR -->
<div class="admin-card">
  <div class="admin-card-body" style="padding: 16px 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
      
      <form method="GET" action="products.php" style="display: flex; flex-wrap: wrap; gap: 12px; flex: 1;">
        <div style="flex: 1; min-width: 220px;">
          <input type="text" name="search" class="form-control" placeholder="Search product name, SKU..." value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div style="min-width: 180px;">
          <select name="category" class="form-control">
            <option value="">All Categories</option>
            <?php foreach ($categories as $c): ?>
              <option value="<?php echo htmlspecialchars($c['slug']); ?>" <?php echo $catFilter === $c['slug'] ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($c['name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <button type="submit" class="btn btn-secondary">Filter</button>
        <?php if (!empty($search) || !empty($catFilter)): ?>
          <a href="products.php" class="btn btn-secondary">Reset</a>
        <?php endif; ?>
      </form>

      <a href="product-create.php" class="btn btn-primary">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        Add New Product
      </a>

    </div>
  </div>
</div>

<!-- PRODUCTS TABLE -->
<div class="admin-card">
  <div class="admin-card-header">
    <div class="admin-card-title">Products (<?php echo count($products); ?>)</div>
  </div>
  <div class="admin-card-body" style="padding: 0;">
    <div class="table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th style="width: 60px;">Image</th>
            <th>Product Name</th>
            <th>Category</th>
            <th>SKU</th>
            <th>Regular Price</th>
            <th>Sale Price</th>
            <th>Stock Tracking</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($products)): ?>
            <tr>
              <td colspan="9" style="text-align: center; padding: 36px; color: var(--admin-text-muted);">
                No products found.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($products as $p): ?>
              <tr>
                <td>
                  <div style="width: 44px; height: 44px; border-radius: 6px; overflow: hidden; background: #EEE; display: flex; align-items: center; justify-content: center; border: 1px solid var(--admin-card-border);">
                    <?php if (!empty($p['image_url'])): ?>
                      <img src="../<?php echo htmlspecialchars($p['image_url']); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                    <?php else: ?>
                      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                    <?php endif; ?>
                  </div>
                </td>
                <td>
                  <div style="font-weight: 600; color: var(--admin-forest);">
                    <a href="product-edit.php?id=<?php echo $p['id']; ?>" style="color: inherit; text-decoration: none;">
                      <?php echo htmlspecialchars($p['name']); ?>
                    </a>
                  </div>
                  <div style="font-size: 11px; color: var(--admin-text-muted);">
                    slug: <?php echo htmlspecialchars($p['slug']); ?>
                    <?php if (!empty($p['badge'])): ?>
                      &bull; <span class="badge" style="background: var(--admin-saffron-light); color: var(--admin-saffron-dark); font-size: 10px;"><?php echo htmlspecialchars($p['badge']); ?></span>
                    <?php endif; ?>
                  </div>
                </td>
                <td>
                  <span class="badge" style="background: #F0F2F1; color: var(--admin-forest);">
                    <?php echo htmlspecialchars($p['category']); ?>
                  </span>
                </td>
                <td style="font-family: monospace; font-size: 12px; color: var(--admin-text-muted);">
                  <?php echo htmlspecialchars($p['sku'] ?: '—'); ?>
                </td>
                <td style="font-weight: 600;">
                  Rs. <?php echo number_format($p['price'], 0); ?>
                </td>
                <td>
                  <?php if (!empty($p['sale_price']) && $p['sale_price'] > 0): ?>
                    <span style="font-weight: 700; color: var(--admin-danger);">
                      Rs. <?php echo number_format($p['sale_price'], 0); ?>
                    </span>
                  <?php else: ?>
                    <span style="color: var(--admin-text-muted);">—</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if (!empty($p['track_stock'])): ?>
                    <span class="badge badge-active"><?php echo (int)$p['stock']; ?> in stock</span>
                  <?php else: ?>
                    <span class="badge" style="background: #F3F4F6; color: #6B7280;">Standard Supply</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($p['is_active']): ?>
                    <span class="badge badge-active">Active</span>
                  <?php else: ?>
                    <span class="badge badge-inactive">Archived</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div style="display: flex; gap: 6px;">
                    <a href="product-edit.php?id=<?php echo $p['id']; ?>" class="btn btn-secondary btn-sm">
                      Edit
                    </a>
                    <form method="POST" onsubmit="return confirm('Change status for this product?');">
                      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                      <input type="hidden" name="action" value="toggle_active">
                      <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                      <button type="submit" class="btn btn-secondary btn-sm" title="<?php echo $p['is_active'] ? 'Archive Product' : 'Restore Product'; ?>">
                        <?php echo $p['is_active'] ? 'Archive' : 'Restore'; ?>
                      </button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
