<?php
/**
 * Zaffran Admin Panel — Perfume Oil Supply B2B Content
 */
define('ZAFFRAN_APP', true);
$pageTitle = 'Perfume Oil Supply Settings';
require_once __DIR__ . '/header.php';

$db = getDbConnection();

$successMsg = '';
$errorMsg = '';

// Load current settings
$settings = getStoreSettings();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateAdminCsrfToken($token)) {
        $errorMsg = 'Security validation failed.';
    } else {
        $title = trim($_POST['perfume_oil_supply_title'] ?? '');
        $desc = trim($_POST['perfume_oil_supply_desc'] ?? '');
        $phone = trim($_POST['perfume_oil_supply_phone'] ?? '');
        $whatsapp = trim($_POST['perfume_oil_supply_whatsapp'] ?? '');
        $visible = !empty($_POST['perfume_oil_supply_visible']) ? '1' : '0';

        $img = $settings['perfume_oil_supply_image'] ?? 'assets/images/business/perfume_oils_business.svg';
        if (!empty($_FILES['supply_image']['name'])) {
            $up = handleImageUpload($_FILES['supply_image'], 'business');
            if ($up['success']) {
                $img = $up['path'];
            }
        }

        $updates = [
            'perfume_oil_supply_title' => $title,
            'perfume_oil_supply_desc' => $desc,
            'perfume_oil_supply_phone' => $phone,
            'perfume_oil_supply_whatsapp' => $whatsapp,
            'perfume_oil_supply_image' => $img,
            'perfume_oil_supply_visible' => $visible
        ];

        try {
            $stmt = $db->prepare("INSERT INTO store_settings (setting_key, setting_value, created_at, updated_at) VALUES (?, ?, NOW(), NOW()) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()");
            foreach ($updates as $k => $v) {
                $stmt->execute([$k, $v]);
            }
            logAdminAudit('update_settings', 'perfume_oil_supply', null, 'Updated Perfume Oil B2B Supply section content');
            $successMsg = 'Perfume Oil Supply section updated successfully.';
            $settings = getStoreSettings(); // reload
        } catch (Exception $e) {
            $errorMsg = 'Failed to save settings: ' . $e->getMessage();
        }
    }
}

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

<div style="max-width: 800px; margin: 0 auto;">
  <div class="notice-box notice-info" style="margin-bottom: 20px;">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
    <div>
      This section powers the B2B Perfume Oil Entrepreneurship banner on the homepage and Category pages. It provides contact information for business inquiries without bypassing standard cart checkout.
    </div>
  </div>

  <form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

    <div class="admin-card">
      <div class="admin-card-header">
        <div class="admin-card-title">Perfume Oil Supply Information</div>
      </div>
      <div class="admin-card-body">
        
        <div class="form-group">
          <label class="form-label" for="perfume_oil_supply_title">Section Headline</label>
          <input type="text" id="perfume_oil_supply_title" name="perfume_oil_supply_title" class="form-control" value="<?php echo htmlspecialchars($settings['perfume_oil_supply_title'] ?? 'Bulk Artisanal Perfume Oil Concentrates'); ?>">
        </div>

        <div class="form-group">
          <label class="form-label" for="perfume_oil_supply_desc">Business Description & Value Proposition</label>
          <textarea id="perfume_oil_supply_desc" name="perfume_oil_supply_desc" class="form-control" rows="4"><?php echo htmlspecialchars($settings['perfume_oil_supply_desc'] ?? ''); ?></textarea>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="perfume_oil_supply_phone">Supplier Phone Number</label>
            <input type="text" id="perfume_oil_supply_phone" name="perfume_oil_supply_phone" class="form-control" value="<?php echo htmlspecialchars($settings['perfume_oil_supply_phone'] ?? '0304-1202111'); ?>">
          </div>

          <div class="form-group">
            <label class="form-label" for="perfume_oil_supply_whatsapp">WhatsApp Inquiry Number</label>
            <input type="text" id="perfume_oil_supply_whatsapp" name="perfume_oil_supply_whatsapp" class="form-control" value="<?php echo htmlspecialchars($settings['perfume_oil_supply_whatsapp'] ?? '03041202111'); ?>">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="supply_image">Supporting Section Image</label>
          <input type="file" id="supply_image" name="supply_image" class="form-control" accept="image/jpeg,image/png,image/webp,image/svg+xml">
          <?php if (!empty($settings['perfume_oil_supply_image'])): ?>
            <div style="margin-top: 8px; font-size: 12px; color: var(--admin-text-muted);">
              Current image: <code><?php echo htmlspecialchars($settings['perfume_oil_supply_image']); ?></code>
            </div>
          <?php endif; ?>
        </div>

        <div class="form-group" style="margin-top: 16px;">
          <label style="display: flex; align-items: center; gap: 8px; font-weight: 600; cursor: pointer;">
            <input type="checkbox" name="perfume_oil_supply_visible" value="1" <?php echo !empty($settings['perfume_oil_supply_visible']) ? 'checked' : ''; ?>>
            Show Perfume Oil Supply Section on Storefront
          </label>
        </div>

        <div style="text-align: right; margin-top: 24px;">
          <button type="submit" class="btn btn-primary" style="padding: 12px 28px;">Save Settings</button>
        </div>

      </div>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
