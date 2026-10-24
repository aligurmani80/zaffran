<?php
/**
 * Zaffran Admin Panel — Homepage Hero Slider Management
 * Manages full-width hero slides while preserving 3D typewriter and full-bleed visuals
 */
define('ZAFFRAN_APP', true);
$pageTitle = 'Hero Slider Management';
require_once __DIR__ . '/header.php';

$db = getDbConnection();

$successMsg = '';
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateAdminCsrfToken($token)) {
        $errorMsg = 'Security validation failed.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'edit_slide') {
            $slideId = (int)($_POST['slide_id'] ?? 0);
            $smallBadge = trim($_POST['small_badge'] ?? '');
            $titlePrefix = trim($_POST['title_prefix'] ?? '');
            $animatedWord = trim($_POST['animated_word'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $primaryCtaLabel = trim($_POST['primary_cta_label'] ?? 'Explore');
            $primaryCtaUrl = trim($_POST['primary_cta_url'] ?? 'shop.php');
            $secondaryCtaLabel = trim($_POST['secondary_cta_label'] ?? '');
            $secondaryCtaUrl = trim($_POST['secondary_cta_url'] ?? '');
            $guaranteeText = trim($_POST['guarantee_text'] ?? '');
            $displayOrder = (int)($_POST['display_order'] ?? 0);
            $isActive = !empty($_POST['is_active']) ? 1 : 0;

            if ($slideId > 0 && !empty($animatedWord) && !empty($description)) {
                $curStmt = $db->prepare("SELECT * FROM hero_slides WHERE id = ?");
                $curStmt->execute([$slideId]);
                $slide = $curStmt->fetch();

                if ($slide) {
                    $desktopImg = $slide['desktop_image'];
                    if (!empty($_FILES['desktop_image']['name'])) {
                        $up = handleImageUpload($_FILES['desktop_image'], 'hero');
                        if ($up['success']) {
                            $desktopImg = $up['path'];
                        }
                    }

                    try {
                        $upd = $db->prepare("UPDATE hero_slides SET 
                            small_badge = ?, title_prefix = ?, animated_word = ?, description = ?, 
                            desktop_image = ?, primary_cta_label = ?, primary_cta_url = ?, 
                            secondary_cta_label = ?, secondary_cta_url = ?, guarantee_text = ?, 
                            display_order = ?, is_active = ?, updated_at = NOW() 
                            WHERE id = ?");
                        $upd->execute([
                            $smallBadge, $titlePrefix, $animatedWord, $description,
                            $desktopImg, $primaryCtaLabel, $primaryCtaUrl,
                            $secondaryCtaLabel, $secondaryCtaUrl, $guaranteeText,
                            $displayOrder, $isActive, $slideId
                        ]);

                        logAdminAudit('edit_hero_slide', 'hero_slide', (string)$slideId, "Updated hero slide: {$animatedWord}");
                        $successMsg = "Hero slide '{$animatedWord}' updated successfully.";
                    } catch (Exception $e) {
                        $errorMsg = 'Failed to update slide: ' . $e->getMessage();
                    }
                }
            }
        }
    }
}

// Fetch all slides
$slides = $db->query("SELECT * FROM hero_slides ORDER BY display_order ASC, id ASC")->fetchAll();
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

<div class="notice-box notice-info" style="margin-bottom: 24px;">
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
  <div>
    <strong>Preserved Signature 3D Typewriter Feature:</strong> The "Animated Word" below is typed letter-by-letter with a 3D depth effect, briefly displayed, fully erased, paused for exactly 1000ms, and looped smoothly on the active slide.
  </div>
</div>

<div style="display: flex; flex-direction: column; gap: 24px;">
  <?php foreach ($slides as $idx => $s): ?>
    <div class="admin-card">
      <div class="admin-card-header">
        <div class="admin-card-title">
          Slide #<?php echo ($idx + 1); ?>: <?php echo htmlspecialchars($s['animated_word']); ?> 
          (<?php echo htmlspecialchars($s['slide_class']); ?>)
        </div>
        <div>
          <?php if (!empty($s['is_active'])): ?>
            <span class="badge badge-active">Live on Homepage</span>
          <?php else: ?>
            <span class="badge badge-inactive">Inactive</span>
          <?php endif; ?>
        </div>
      </div>
      <div class="admin-card-body">
        
        <form method="POST" enctype="multipart/form-data">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
          <input type="hidden" name="action" value="edit_slide">
          <input type="hidden" name="slide_id" value="<?php echo $s['id']; ?>">

          <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px;">
            
            <!-- Visual Preview & Image Upload -->
            <div>
              <div style="font-size: 13px; font-weight: 600; color: var(--admin-forest); margin-bottom: 8px;">
                Slide Background Image
              </div>
              <div style="width: 100%; height: 160px; border-radius: 8px; overflow: hidden; background: #2A3B30; position: relative; border: 1px solid var(--admin-card-border); margin-bottom: 12px;">
                <img src="../<?php echo htmlspecialchars($s['desktop_image']); ?>" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                <div style="position: absolute; bottom: 8px; left: 8px; background: rgba(0,0,0,0.6); color: #FFF; font-size: 11px; padding: 2px 8px; border-radius: 4px;">
                  Current Image
                </div>
              </div>

              <div class="form-group">
                <label class="form-label" for="desktop_img_<?php echo $s['id']; ?>">Replace Image</label>
                <input type="file" id="desktop_img_<?php echo $s['id']; ?>" name="desktop_image" class="form-control" accept="image/jpeg,image/png,image/webp,image/svg+xml">
                <div class="form-help">Upload high-res JPG, PNG, WebP or SVG.</div>
              </div>

              <div class="form-group">
                <label class="form-label">Display Order</label>
                <input type="number" name="display_order" class="form-control" value="<?php echo (int)$s['display_order']; ?>">
              </div>

              <label style="display: flex; align-items: center; gap: 8px; font-weight: 600; cursor: pointer; margin-top: 12px;">
                <input type="checkbox" name="is_active" value="1" <?php echo !empty($s['is_active']) ? 'checked' : ''; ?>>
                Slide is Active
              </label>
            </div>

            <!-- Content Details -->
            <div>
              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Badge Label</label>
                  <input type="text" name="small_badge" class="form-control" value="<?php echo htmlspecialchars($s['small_badge']); ?>" placeholder="E.g. Artisanal Haute Parfumerie">
                </div>

                <div class="form-group">
                  <label class="form-label">Headline Prefix</label>
                  <input type="text" name="title_prefix" class="form-control" value="<?php echo htmlspecialchars($s['title_prefix']); ?>" placeholder="E.g. Discover Rare & Captivating">
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">3D Animated Typewriter Word <span class="required">*</span></label>
                <input type="text" name="animated_word" class="form-control" required value="<?php echo htmlspecialchars($s['animated_word']); ?>" style="font-weight: 700; color: var(--admin-forest);">
                <div class="form-help">This word will type letter-by-letter with a 3D effect and exact 1000ms pause between cycles.</div>
              </div>

              <div class="form-group">
                <label class="form-label">Description Paragraph <span class="required">*</span></label>
                <textarea name="description" class="form-control" rows="3" required><?php echo htmlspecialchars($s['description']); ?></textarea>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Primary CTA Button Label</label>
                  <input type="text" name="primary_cta_label" class="form-control" value="<?php echo htmlspecialchars($s['primary_cta_label']); ?>">
                </div>

                <div class="form-group">
                  <label class="form-label">Primary CTA Destination Link</label>
                  <input type="text" name="primary_cta_url" class="form-control" value="<?php echo htmlspecialchars($s['primary_cta_url']); ?>">
                </div>
              </div>

              <div class="form-row">
                <div class="form-group">
                  <label class="form-label">Secondary CTA Button Label</label>
                  <input type="text" name="secondary_cta_label" class="form-control" value="<?php echo htmlspecialchars($s['secondary_cta_label']); ?>">
                </div>

                <div class="form-group">
                  <label class="form-label">Secondary CTA Destination Link</label>
                  <input type="text" name="secondary_cta_url" class="form-control" value="<?php echo htmlspecialchars($s['secondary_cta_url']); ?>">
                </div>
              </div>

              <div class="form-group">
                <label class="form-label">Trust Guarantee Badge</label>
                <input type="text" name="guarantee_text" class="form-control" value="<?php echo htmlspecialchars($s['guarantee_text']); ?>" placeholder="E.g. 100% Pure Essential Extrait Concentrations">
              </div>

              <div style="text-align: right; margin-top: 16px;">
                <button type="submit" class="btn btn-primary">Save Slide Changes</button>
              </div>
            </div>

          </div>
        </form>

      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
