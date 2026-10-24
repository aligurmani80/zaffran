<?php
/**
 * Zaffran Admin Panel — Category Management
 */
define('ZAFFRAN_APP', true);
$pageTitle = 'Category Management';
require_once __DIR__ . '/header.php';

$db = getDbConnection();

$successMsg = '';
$errorMsg = '';

// Handle Add / Edit Category
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateAdminCsrfToken($token)) {
        $errorMsg = 'Security validation failed.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create_category') {
            $name = trim($_POST['name'] ?? '');
            $slug = trim($_POST['slug'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            $order = (int)($_POST['display_order'] ?? 0);
            $isActive = !empty($_POST['is_active']) ? 1 : 0;

            if (empty($name)) {
                $errorMsg = 'Category name is required.';
            } else {
                if (empty($slug)) {
                    $slug = strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $name));
                    $slug = trim($slug, '-');
                }

                $imageUrl = 'assets/images/categories/cat_perfume.svg';
                if (!empty($_FILES['cat_image']['name'])) {
                    $upload = handleImageUpload($_FILES['cat_image'], 'categories');
                    if ($upload['success']) {
                        $imageUrl = $upload['path'];
                    }
                }

                try {
                    $stmt = $db->prepare("INSERT INTO categories (name, slug, description, image_url, display_order, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([$name, $slug, $desc, $imageUrl, $order, $isActive]);
                    logAdminAudit('create_category', 'category', (string)$db->lastInsertId(), "Created category '{$name}'");
                    $successMsg = "Category '{$name}' created successfully.";
                } catch (Exception $e) {
                    $errorMsg = 'Failed to create category: ' . $e->getMessage();
                }
            }
        } elseif ($action === 'edit_category') {
            $catId = (int)($_POST['category_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            $order = (int)($_POST['display_order'] ?? 0);
            $isActive = !empty($_POST['is_active']) ? 1 : 0;

            if ($catId > 0 && !empty($name)) {
                try {
                    $cStmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
                    $cStmt->execute([$catId]);
                    $cat = $cStmt->fetch();

                    $imageUrl = $cat['image_url'];
                    if (!empty($_FILES['cat_image']['name'])) {
                        $upload = handleImageUpload($_FILES['cat_image'], 'categories');
                        if ($upload['success']) {
                            $imageUrl = $upload['path'];
                        }
                    }

                    $upd = $db->prepare("UPDATE categories SET name = ?, description = ?, image_url = ?, display_order = ?, is_active = ? WHERE id = ?");
                    $upd->execute([$name, $desc, $imageUrl, $order, $isActive, $catId]);
                    logAdminAudit('edit_category', 'category', (string)$catId, "Updated category '{$name}'");
                    $successMsg = "Category '{$name}' updated successfully.";
                } catch (Exception $e) {
                    $errorMsg = 'Failed to update category: ' . $e->getMessage();
                }
            }
        }
    }
}

// Fetch categories with real product counts
$categories = $db->query("SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_slug = c.slug) as product_count FROM categories c ORDER BY c.display_order ASC, c.id ASC")->fetchAll();
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

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
  
  <!-- Categories List -->
  <div class="admin-card">
    <div class="admin-card-header">
      <div class="admin-card-title">Product Categories (<?php echo count($categories); ?>)</div>
    </div>
    <div class="admin-card-body" style="padding: 0;">
      <table class="admin-table">
        <thead>
          <tr>
            <th style="width: 50px;">Media</th>
            <th>Category Name</th>
            <th>Slug</th>
            <th>Products</th>
            <th>Order</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($categories as $cat): ?>
            <tr>
              <td>
                <div style="width: 38px; height: 38px; border-radius: 6px; overflow: hidden; background: #EEE; display: flex; align-items: center; justify-content: center;">
                  <?php if (!empty($cat['image_url'])): ?>
                    <img src="../<?php echo htmlspecialchars($cat['image_url']); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                  <?php endif; ?>
                </div>
              </td>
              <td>
                <div style="font-weight: 600; color: var(--admin-forest);"><?php echo htmlspecialchars($cat['name']); ?></div>
                <div style="font-size: 11px; color: var(--admin-text-muted); max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                  <?php echo htmlspecialchars($cat['description']); ?>
                </div>
              </td>
              <td style="font-family: monospace; font-size: 12px; color: var(--admin-text-muted);">
                <?php echo htmlspecialchars($cat['slug']); ?>
              </td>
              <td>
                <span class="badge" style="background: #F0F2F1; color: var(--admin-forest);">
                  <?php echo (int)$cat['product_count']; ?> active
                </span>
              </td>
              <td><?php echo (int)$cat['display_order']; ?></td>
              <td>
                <?php if ($cat['is_active']): ?>
                  <span class="badge badge-active">Active</span>
                <?php else: ?>
                  <span class="badge badge-inactive">Inactive</span>
                <?php endif; ?>
              </td>
              <td>
                <button type="button" class="btn btn-secondary btn-sm" onclick='editCategory(<?php echo json_encode($cat); ?>)'>
                  Edit
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Form Column (Add / Edit) -->
  <div class="admin-card">
    <div class="admin-card-header">
      <div class="admin-card-title" id="formCardTitle">Add New Category</div>
    </div>
    <div class="admin-card-body">
      
      <form method="POST" enctype="multipart/form-data" id="catForm">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="action" id="formAction" value="create_category">
        <input type="hidden" name="category_id" id="categoryId" value="">

        <div class="form-group">
          <label class="form-label" for="cat_name">Category Name <span class="required">*</span></label>
          <input type="text" id="cat_name" name="name" class="form-control" required placeholder="E.g. Herbal Blends">
        </div>

        <div class="form-group" id="slugGroup">
          <label class="form-label" for="cat_slug">Slug</label>
          <input type="text" id="cat_slug" name="slug" class="form-control" placeholder="herbal-blends">
          <div class="form-help">Leave empty for auto-generation. Note: Slugs for default categories cannot be altered.</div>
        </div>

        <div class="form-group">
          <label class="form-label" for="cat_desc">Description</label>
          <textarea id="cat_desc" name="description" class="form-control" rows="3" placeholder="Category curation summary..."></textarea>
        </div>

        <div class="form-group">
          <label class="form-label" for="cat_image">Category Image</label>
          <input type="file" id="cat_image" name="cat_image" class="form-control" accept="image/jpeg,image/png,image/webp">
        </div>

        <div class="form-group">
          <label class="form-label" for="cat_order">Display Order</label>
          <input type="number" id="cat_order" name="display_order" class="form-control" value="0">
        </div>

        <div class="form-group">
          <label style="display: flex; align-items: center; gap: 8px; font-weight: 600; cursor: pointer;">
            <input type="checkbox" id="cat_active" name="is_active" value="1" checked>
            Category is Active
          </label>
        </div>

        <div style="display: flex; gap: 8px;">
          <button type="submit" class="btn btn-primary" id="submitBtn" style="flex: 1;">Save Category</button>
          <button type="button" class="btn btn-secondary" id="resetBtn" style="display: none;" onclick="resetForm()">Cancel</button>
        </div>
      </form>

    </div>
  </div>

</div>

<script>
function editCategory(cat) {
  document.getElementById('formCardTitle').textContent = 'Edit: ' + cat.name;
  document.getElementById('formAction').value = 'edit_category';
  document.getElementById('categoryId').value = cat.id;
  document.getElementById('cat_name').value = cat.name;
  document.getElementById('cat_slug').value = cat.slug;
  document.getElementById('cat_slug').readOnly = true;
  document.getElementById('cat_desc').value = cat.description;
  document.getElementById('cat_order').value = cat.display_order;
  document.getElementById('cat_active').checked = cat.is_active == 1;
  document.getElementById('submitBtn').textContent = 'Update Category';
  document.getElementById('resetBtn').style.display = 'inline-flex';
}

function resetForm() {
  document.getElementById('formCardTitle').textContent = 'Add New Category';
  document.getElementById('formAction').value = 'create_category';
  document.getElementById('categoryId').value = '';
  document.getElementById('cat_name').value = '';
  document.getElementById('cat_slug').value = '';
  document.getElementById('cat_slug').readOnly = false;
  document.getElementById('cat_desc').value = '';
  document.getElementById('cat_order').value = '0';
  document.getElementById('cat_active').checked = true;
  document.getElementById('submitBtn').textContent = 'Save Category';
  document.getElementById('resetBtn').style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
