<?php
/**
 * Zaffran Admin Panel — Website Settings Management
 */
define('ZAFFRAN_APP', true);
require_once __DIR__ . '/auth.php';

$currentAdmin = requireAdminAuth('super_admin');
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
        // Collect safe, public-facing store configuration fields
        $updates = [
            'store_name' => trim($_POST['store_name'] ?? 'Zaffran'),
            'store_phone' => trim($_POST['store_phone'] ?? ''),
            'store_email' => trim($_POST['store_email'] ?? ''),
            'store_address' => trim($_POST['store_address'] ?? ''),
            'whatsapp_number' => trim($_POST['whatsapp_number'] ?? ''),
            'whatsapp_contact_link' => trim($_POST['whatsapp_contact_link'] ?? ''),
            'facebook_url' => trim($_POST['facebook_url'] ?? ''),
            'instagram_url' => trim($_POST['instagram_url'] ?? ''),
            'tiktok_url' => trim($_POST['tiktok_url'] ?? ''),
            'default_delivery_charges' => max(0, (float)($_POST['default_delivery_charges'] ?? 250)),
            'free_delivery_threshold' => max(0, (float)($_POST['free_delivery_threshold'] ?? 5000)),
            'enable_cod' => !empty($_POST['enable_cod']) ? '1' : '0',
            'enable_bank_transfer' => !empty($_POST['enable_bank_transfer']) ? '1' : '0',
            'bank_name' => trim($_POST['bank_name'] ?? ''),
            'bank_account_title' => trim($_POST['bank_account_title'] ?? ''),
            'bank_account_number' => trim($_POST['bank_account_number'] ?? ''),
            'bank_iban' => trim($_POST['bank_iban'] ?? ''),
        ];

        if (!empty($_FILES['store_logo']['name'])) {
            $logoUpload = handleImageUpload($_FILES['store_logo'], 'branding');
            if ($logoUpload['success']) {
                $updates['store_logo'] = $logoUpload['path'];
            } else {
                $errorMsg = 'Logo upload failed: ' . $logoUpload['error'];
            }
        }

        if (!empty($_POST['remove_store_logo'])) {
            $updates['store_logo'] = '';
        }

        try {
            $stmt = $db->prepare("INSERT INTO store_settings (setting_key, setting_value, created_at, updated_at) VALUES (?, ?, NOW(), NOW()) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()");
            foreach ($updates as $k => $v) {
                $stmt->execute([$k, (string)$v]);
            }
            logAdminAudit('update_website_settings', 'store_settings', null, 'Super Admin updated store settings (shipping, COD, brand info)');
            $successMsg = 'Website settings updated successfully.';
            $settings = getStoreSettings(); // reload
        } catch (Exception $e) {
            $errorMsg = 'Failed to save settings: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Website Settings';
require_once __DIR__ . '/header.php';
$csrfToken = getAdminCsrfToken();

// Safe Mailer status check (NO CREDENTIAL LEAK)
$mailDriver = env('MAIL_DRIVER', 'smtp');
$smtpConfigured = !empty(env('SMTP_HOST')) && !empty(env('SMTP_USERNAME'));
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

<form method="POST" enctype="multipart/form-data">
  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

  <!-- GENERAL INFORMATION -->
  <div class="admin-card">
    <div class="admin-card-header">
      <div class="admin-card-title">General Brand & Storefront Information</div>
    </div>
    <div class="admin-card-body">
      
      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="store_name">Store Brand Name</label>
          <input type="text" id="store_name" name="store_name" class="form-control" value="<?php echo htmlspecialchars($settings['store_name'] ?? 'Zaffran'); ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="store_phone">Customer Support Phone</label>
          <input type="text" id="store_phone" name="store_phone" class="form-control" value="<?php echo htmlspecialchars($settings['store_phone'] ?? ''); ?>">
        </div>

        <div class="form-group">
          <label class="form-label" for="store_email">Store Contact Email</label>
          <input type="email" id="store_email" name="store_email" class="form-control" value="<?php echo htmlspecialchars($settings['store_email'] ?? ''); ?>">
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" for="store_logo">Brand Logo (Optional)</label>
        <?php
          $currentLogo = (string)($settings['store_logo'] ?? '');
          $currentLogoValid = preg_match('#^assets/uploads/branding/[A-Za-z0-9._-]+$#', $currentLogo)
              && is_file(dirname(__DIR__) . '/' . $currentLogo);
        ?>
        <?php if ($currentLogoValid): ?>
          <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 10px; padding: 10px 12px; background: #F8F6F0; border: 1px solid var(--admin-card-border); border-radius: 10px;">
            <img src="../<?php echo htmlspecialchars($currentLogo); ?>" alt="Current store logo" style="max-height: 48px; max-width: 160px; object-fit: contain; background: #FFF; border-radius: 6px; padding: 4px;">
            <label style="display: flex; align-items: center; gap: 7px; font-size: 12px; font-weight: 600; cursor: pointer; color: var(--admin-danger);">
              <input type="checkbox" name="remove_store_logo" value="1">
              Remove current logo
            </label>
          </div>
        <?php else: ?>
          <div style="margin-bottom: 10px; padding: 10px 12px; background: #F8F6F0; border: 1px solid var(--admin-card-border); border-radius: 10px; font-size: 12px; color: var(--admin-text-muted);">
            No custom logo uploaded — the Zaffran emblem and wordmark are used in the header.
          </div>
        <?php endif; ?>
        <input type="file" id="store_logo" name="store_logo" class="form-control" accept="image/jpeg,image/png,image/webp">
        <div class="form-help">JPG, PNG or WebP, max 5MB. Displayed in the storefront header. If no logo is uploaded, the Zaffran emblem and wordmark are used.</div>
      </div>

      <div class="form-group">
        <label class="form-label" for="store_address">Physical Store / Workshop Address</label>
        <input type="text" id="store_address" name="store_address" class="form-control" value="<?php echo htmlspecialchars($settings['store_address'] ?? ''); ?>">
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="whatsapp_number">Support WhatsApp Number (Digits only)</label>
          <input type="text" id="whatsapp_number" name="whatsapp_number" class="form-control" value="<?php echo htmlspecialchars($settings['whatsapp_number'] ?? ''); ?>" placeholder="03041202111">
        </div>

        <div class="form-group">
          <label class="form-label" for="whatsapp_contact_link">Full WhatsApp Direct Link</label>
          <input type="text" id="whatsapp_contact_link" name="whatsapp_contact_link" class="form-control" value="<?php echo htmlspecialchars($settings['whatsapp_contact_link'] ?? ''); ?>" placeholder="https://wa.me/923041202111">
        </div>
      </div>

    </div>
  </div>

  <!-- SHIPPING & COD POLICIES -->
  <div class="admin-card">
    <div class="admin-card-header">
      <div class="admin-card-title">Shipping &amp; Checkout Rules</div>
    </div>
    <div class="admin-card-body">
      
      <div class="notice-box notice-warning" style="margin-bottom: 20px;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
        <div>
          <strong>Merchant Policy Notice:</strong> Values below reflect current defaults (Standard Shipping: PKR 250, Free Threshold: PKR 5,000). Please adjust to match your courier logistics agreement.
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="default_delivery_charges">Standard Nationwide Delivery Fee (PKR)</label>
          <input type="number" step="1" min="0" id="default_delivery_charges" name="default_delivery_charges" class="form-control" value="<?php echo htmlspecialchars($settings['default_delivery_charges'] ?? '250'); ?>" required>
        </div>

        <div class="form-group">
          <label class="form-label" for="free_delivery_threshold">Free Delivery Minimum Order Amount (PKR)</label>
          <input type="number" step="1" min="0" id="free_delivery_threshold" name="free_delivery_threshold" class="form-control" value="<?php echo htmlspecialchars($settings['free_delivery_threshold'] ?? '5000'); ?>" required>
        </div>
      </div>

      <div style="display: flex; gap: 24px; margin-top: 12px;">
        <label style="display: flex; align-items: center; gap: 8px; font-weight: 600; cursor: pointer;">
          <input type="checkbox" name="enable_cod" value="1" <?php echo !empty($settings['enable_cod']) ? 'checked' : ''; ?>>
          Enable Cash on Delivery (COD) Checkout
        </label>

        <label style="display: flex; align-items: center; gap: 8px; font-weight: 600; cursor: pointer;">
          <input type="checkbox" name="enable_bank_transfer" value="1" <?php echo !empty($settings['enable_bank_transfer']) ? 'checked' : ''; ?>>
          Enable Direct Bank Transfer Option
        </label>
      </div>

    </div>
  </div>

  <!-- BANK TRANSFER SETTINGS -->
  <div class="admin-card">
    <div class="admin-card-header">
      <div class="admin-card-title">Direct Bank Transfer Details (Displayed on Checkout)</div>
    </div>
    <div class="admin-card-body">
      
      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="bank_name">Bank Name</label>
          <input type="text" id="bank_name" name="bank_name" class="form-control" value="<?php echo htmlspecialchars($settings['bank_name'] ?? ''); ?>" placeholder="Meezan Bank Limited">
        </div>

        <div class="form-group">
          <label class="form-label" for="bank_account_title">Account Title</label>
          <input type="text" id="bank_account_title" name="bank_account_title" class="form-control" value="<?php echo htmlspecialchars($settings['bank_account_title'] ?? ''); ?>" placeholder="Zaffran & Oud Luxury Store">
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="bank_account_number">Account Number</label>
          <input type="text" id="bank_account_number" name="bank_account_number" class="form-control" value="<?php echo htmlspecialchars($settings['bank_account_number'] ?? ''); ?>">
        </div>

        <div class="form-group">
          <label class="form-label" for="bank_iban">IBAN</label>
          <input type="text" id="bank_iban" name="bank_iban" class="form-control" value="<?php echo htmlspecialchars($settings['bank_iban'] ?? ''); ?>">
        </div>
      </div>

    </div>
  </div>

  <!-- SOCIAL MEDIA LINKS -->
  <div class="admin-card">
    <div class="admin-card-header">
      <div class="admin-card-title">Footer Social Media Profiles</div>
    </div>
    <div class="admin-card-body">
      
      <div class="form-row">
        <div class="form-group">
          <label class="form-label" for="instagram_url">Instagram Profile Link</label>
          <input type="url" id="instagram_url" name="instagram_url" class="form-control" value="<?php echo htmlspecialchars($settings['instagram_url'] ?? ''); ?>" placeholder="https://instagram.com/zaffran">
        </div>

        <div class="form-group">
          <label class="form-label" for="facebook_url">Facebook Page Link</label>
          <input type="url" id="facebook_url" name="facebook_url" class="form-control" value="<?php echo htmlspecialchars($settings['facebook_url'] ?? ''); ?>" placeholder="https://facebook.com/zaffran">
        </div>

        <div class="form-group">
          <label class="form-label" for="tiktok_url">TikTok Profile Link</label>
          <input type="url" id="tiktok_url" name="tiktok_url" class="form-control" value="<?php echo htmlspecialchars($settings['tiktok_url'] ?? ''); ?>" placeholder="https://tiktok.com/@zaffran">
        </div>
      </div>

    </div>
  </div>

  <!-- ADMIN SECURITY OVERVIEW -->
  <div class="admin-card">
    <div class="admin-card-header">
      <div class="admin-card-title">Administration Security</div>
    </div>
    <div class="admin-card-body" style="font-size: 13px;">
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
        <div style="padding: 12px; background: #F8F6F0; border-radius: 10px; border: 1px solid var(--admin-card-border);">
          <strong style="display: block; margin-bottom: 4px;">Isolated Admin Session</strong>
          <span style="color: var(--admin-text-muted);">Admin authentication uses a separate session name and cookie path from customer accounts.</span>
        </div>
        <div style="padding: 12px; background: #F8F6F0; border-radius: 10px; border: 1px solid var(--admin-card-border);">
          <strong style="display: block; margin-bottom: 4px;">Current Account</strong>
          <span style="color: var(--admin-text-muted);">
            <?php echo htmlspecialchars($currentAdmin['username'] ?? 'Admin'); ?>
            · <?php echo htmlspecialchars(str_replace('_', ' ', $currentAdmin['role'] ?? 'staff')); ?>
          </span>
        </div>
        <div style="padding: 12px; background: #F8F6F0; border-radius: 10px; border: 1px solid var(--admin-card-border);">
          <strong style="display: block; margin-bottom: 4px;">Customer Sign-In</strong>
          <span style="color: var(--admin-text-muted);">Email OTP only. No customer passwords are stored or accepted.</span>
        </div>
      </div>
      <div class="form-help" style="margin-top: 12px;">
        Manage administrator accounts and roles in
        <a href="admins.php" style="font-weight: 700; color: var(--admin-forest);">Admin Accounts</a>.
        Admin activity is recorded in <a href="audit-logs.php" style="font-weight: 700; color: var(--admin-forest);">Audit Logs</a>.
      </div>
    </div>
  </div>

  <!-- SENSITIVE SYSTEM SECURITY INDICATOR (NO CREDENTIALS SHOWN) -->
  <div class="admin-card">
    <div class="admin-card-header">
      <div class="admin-card-title">System &amp; Email Gateway Status</div>
    </div>
    <div class="admin-card-body" style="font-size: 13px;">
      <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px; background: #F8F6F0; border-radius: var(--admin-radius-sm); border: 1px solid var(--admin-card-border);">
        <div>
          <strong>Email Authentication Driver:</strong> <?php echo strtoupper(htmlspecialchars($mailDriver)); ?> Mode
          <div style="font-size: 12px; color: var(--admin-text-muted);">
            <?php if ($smtpConfigured): ?>
              Host connected via TLS. OTP challenges and customer notifications are delivering securely.
            <?php else: ?>
              Standard test logging driver active.
            <?php endif; ?>
          </div>
        </div>
        <div>
          <span class="badge <?php echo $smtpConfigured ? 'badge-active' : 'badge-pending'; ?>">
            <?php echo $smtpConfigured ? 'Operational' : 'Simulated / Test Mode'; ?>
          </span>
        </div>
      </div>
      <div class="form-help" style="margin-top: 8px;">
        Note: Secret credentials (database passwords, SMTP passwords, APP_KEY) are protected in the root <code>.env</code> file outside browser forms for security.
      </div>
    </div>
  </div>

  <div style="text-align: right; margin-bottom: 40px;">
    <button type="submit" class="btn btn-primary" style="padding: 12px 32px; font-size: 14px;">Save All Website Settings</button>
  </div>

</form>

<?php require_once __DIR__ . '/footer.php'; ?>
