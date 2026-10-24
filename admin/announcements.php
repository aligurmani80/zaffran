<?php
/**
 * Zaffran Admin Panel — Announcement Bar Management
 */
define('ZAFFRAN_APP', true);
$pageTitle = 'Announcement Bar Management';
require_once __DIR__ . '/header.php';

$db = getDbConnection();

$db->exec("CREATE TABLE IF NOT EXISTS announcement_revisions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    announcement_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NULL,
    message TEXT NOT NULL,
    link_url VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 0,
    display_order INT NOT NULL DEFAULT 0,
    bg_color VARCHAR(20) NULL,
    text_color VARCHAR(20) NULL,
    display_type VARCHAR(20) NULL,
    saved_by VARCHAR(191) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_announcement (announcement_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$successMsg = '';
$errorMsg = '';

function sanitizeHexColor(string $value, string $fallback): string {
    $value = trim($value);
    return preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtolower($value) : $fallback;
}

function snapshotAnnouncement(PDO $db, array $ann): void {
    try {
        $stmt = $db->prepare("INSERT INTO announcement_revisions
            (announcement_id, title, message, link_url, is_active, display_order, bg_color, text_color, display_type, saved_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([
            (int)$ann['id'],
            $ann['title'] ?? '',
            $ann['message'] ?? '',
            $ann['link_url'] ?? '',
            (int)($ann['is_active'] ?? 0),
            (int)($ann['display_order'] ?? 0),
            $ann['bg_color'] ?? '#d4af37',
            $ann['text_color'] ?? '#000000',
            $ann['display_type'] ?? 'scrolling',
            $_SESSION['admin_email'] ?? 'system'
        ]);
    } catch (Exception $e) {
        error_log('Announcement revision snapshot failed: ' . $e->getMessage());
    }
}

// Handle Create / Edit / Toggle / Delete / Restore
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateAdminCsrfToken($token)) {
        $errorMsg = 'Security validation failed.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create_announcement') {
            $title = trim(strip_tags($_POST['title'] ?? ''));
            $message = trim(strip_tags($_POST['message'] ?? ''));
            $linkUrl = trim(filter_var($_POST['link_url'] ?? '', FILTER_SANITIZE_URL));
            $order = (int)($_POST['display_order'] ?? 0);
            $isActive = !empty($_POST['is_active']) ? 1 : 0;
            $bgColor = sanitizeHexColor($_POST['bg_color'] ?? '', '#d4af37');
            $textColor = sanitizeHexColor($_POST['text_color'] ?? '', '#000000');
            $displayType = in_array($_POST['display_type'] ?? '', ['scrolling', 'static'], true) ? $_POST['display_type'] : 'scrolling';

            if (empty($message)) {
                $errorMsg = 'Announcement message cannot be empty.';
            } else {
                try {
                    $stmt = $db->prepare("INSERT INTO announcements (title, message, link_url, is_active, display_order, bg_color, text_color, display_type, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                    $stmt->execute([$title, $message, $linkUrl, $isActive, $order, $bgColor, $textColor, $displayType]);
                    $newId = $db->lastInsertId();
                    logAdminAudit('create_announcement', 'announcement', (string)$newId, "Created announcement: '{$title}'");
                    $successMsg = 'Announcement published successfully.';
                } catch (Exception $e) {
                    $errorMsg = 'Error saving announcement: ' . $e->getMessage();
                }
            }
        } elseif ($action === 'edit_announcement') {
            $annId = (int)($_POST['announcement_id'] ?? 0);
            $title = trim(strip_tags($_POST['title'] ?? ''));
            $message = trim(strip_tags($_POST['message'] ?? ''));
            $linkUrl = trim(filter_var($_POST['link_url'] ?? '', FILTER_SANITIZE_URL));
            $order = (int)($_POST['display_order'] ?? 0);
            $isActive = !empty($_POST['is_active']) ? 1 : 0;
            $bgColor = sanitizeHexColor($_POST['bg_color'] ?? '', '#d4af37');
            $textColor = sanitizeHexColor($_POST['text_color'] ?? '', '#000000');
            $displayType = in_array($_POST['display_type'] ?? '', ['scrolling', 'static'], true) ? $_POST['display_type'] : 'scrolling';

            if ($annId > 0 && !empty($message)) {
                try {
                    $cur = $db->prepare("SELECT * FROM announcements WHERE id = ?");
                    $cur->execute([$annId]);
                    $previous = $cur->fetch();
                    if ($previous) {
                        snapshotAnnouncement($db, $previous);
                    }

                    $stmt = $db->prepare("UPDATE announcements SET title = ?, message = ?, link_url = ?, is_active = ?, display_order = ?, bg_color = ?, text_color = ?, display_type = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$title, $message, $linkUrl, $isActive, $order, $bgColor, $textColor, $displayType, $annId]);
                    logAdminAudit('edit_announcement', 'announcement', (string)$annId, "Updated announcement #{$annId} (previous version saved)");
                    $successMsg = 'Announcement updated successfully. The previous version is available to restore below.';
                } catch (Exception $e) {
                    $errorMsg = 'Error updating announcement: ' . $e->getMessage();
                }
            }
        } elseif ($action === 'toggle_active') {
            $annId = (int)($_POST['announcement_id'] ?? 0);
            if ($annId > 0) {
                $cur = $db->prepare("SELECT * FROM announcements WHERE id = ?");
                $cur->execute([$annId]);
                $row = $cur->fetch();
                if ($row) {
                    snapshotAnnouncement($db, $row);
                    $newStatus = $row['is_active'] ? 0 : 1;
                    $db->prepare("UPDATE announcements SET is_active = ?, updated_at = NOW() WHERE id = ?")->execute([$newStatus, $annId]);
                    logAdminAudit('toggle_announcement', 'announcement', (string)$annId, "Toggled status to " . ($newStatus ? 'Active' : 'Inactive'));
                    $successMsg = "Announcement status updated.";
                }
            }
        } elseif ($action === 'restore_revision') {
            $revisionId = (int)($_POST['revision_id'] ?? 0);
            if ($revisionId > 0) {
                try {
                    $rev = $db->prepare("SELECT * FROM announcement_revisions WHERE id = ?");
                    $rev->execute([$revisionId]);
                    $revision = $rev->fetch();
                    if ($revision) {
                        $annId = (int)$revision['announcement_id'];
                        $exists = $db->prepare("SELECT * FROM announcements WHERE id = ?");
                        $exists->execute([$annId]);
                        $current = $exists->fetch();
                        if ($current) {
                            snapshotAnnouncement($db, $current);
                            $upd = $db->prepare("UPDATE announcements SET title = ?, message = ?, link_url = ?, is_active = ?, display_order = ?, bg_color = ?, text_color = ?, display_type = ?, updated_at = NOW() WHERE id = ?");
                            $upd->execute([
                                $revision['title'], $revision['message'], $revision['link_url'], (int)$revision['is_active'],
                                (int)$revision['display_order'], $revision['bg_color'], $revision['text_color'],
                                $revision['display_type'], $annId
                            ]);
                        } else {
                            $ins = $db->prepare("INSERT INTO announcements (title, message, link_url, is_active, display_order, bg_color, text_color, display_type, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                            $ins->execute([
                                $revision['title'], $revision['message'], $revision['link_url'], (int)$revision['is_active'],
                                (int)$revision['display_order'], $revision['bg_color'], $revision['text_color'], $revision['display_type']
                            ]);
                            $annId = (int)$db->lastInsertId();
                        }
                        logAdminAudit('restore_announcement', 'announcement', (string)$annId, "Restored announcement from revision #{$revisionId} (previous state snapshotted)");
                        $successMsg = 'Announcement restored. The state it replaced is saved in history below.';
                    }
                } catch (Exception $e) {
                    $errorMsg = 'Restore failed: ' . $e->getMessage();
                }
            }
        } elseif ($action === 'delete_announcement') {
            $annId = (int)($_POST['announcement_id'] ?? 0);
            if ($annId > 0) {
                $cur = $db->prepare("SELECT * FROM announcements WHERE id = ?");
                $cur->execute([$annId]);
                $row = $cur->fetch();
                if ($row) {
                    snapshotAnnouncement($db, $row);
                }
                $db->prepare("DELETE FROM announcements WHERE id = ?")->execute([$annId]);
                logAdminAudit('delete_announcement', 'announcement', (string)$annId, 'Deleted announcement (revision retained for restore)');
                $successMsg = 'Announcement removed. Its last version remains available in history.';
            }
        }
    }
}

// Fetch all announcements ordered by display_order
$announcements = $db->query("SELECT * FROM announcements ORDER BY display_order ASC, id DESC")->fetchAll();
$revisions = $db->query("SELECT * FROM announcement_revisions ORDER BY id DESC LIMIT 12")->fetchAll();
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

<!-- LIVE VISUAL PREVIEW OF TOP STRIP -->
<div class="admin-card" style="margin-bottom: 24px;">
  <div class="admin-card-header">
    <div class="admin-card-title">Live Announcement Bar Preview (Storefront Replica)</div>
    <span style="font-size: 12px; color: var(--admin-text-muted);">Updates as you type, choose colors, or switch presentation</span>
  </div>
  <div class="admin-card-body" style="padding: 16px 20px;">
    <div class="ann-preview-stage">
      <div id="liveStripPreview" class="ann-live-strip is-scrolling">
        <div id="liveStripTrack" class="ann-live-track">
          <span class="ann-preview-unit" data-unit="1">
            <span class="ann-preview-badge" id="prevBadge">Notice</span>
            <span class="ann-preview-message" id="prevMessage">Special Offer: 100% Pure Himalayan Shilajit, Imperial Zaffran &amp; Luxury Perfumes.</span>
            <a href="javascript:void(0)" class="ann-preview-link" id="prevLink">Discover &rarr;</a>
          </span>
          <span class="ann-preview-unit" data-unit="2" aria-hidden="true">
            <span class="ann-preview-badge">Notice</span>
            <span class="ann-preview-message">Special Offer: 100% Pure Himalayan Shilajit, Imperial Zaffran &amp; Luxury Perfumes.</span>
            <a href="javascript:void(0)" class="ann-preview-link">Discover &rarr;</a>
          </span>
        </div>
      </div>
    </div>
    <p style="margin: 10px 0 0; font-size: 11px; color: var(--admin-text-muted);">
      Keep the marquee message concise. Static banners center automatically; scrolling banners repeat seamlessly.
    </p>
  </div>
</div>

<div class="ann-layout">
  
  <!-- Announcements List -->
  <div class="admin-card">
    <div class="admin-card-header">
      <div class="admin-card-title">Active &amp; Scheduled Announcements (<?php echo count($announcements); ?>)</div>
      <a href="../index.php" target="_blank" class="btn btn-secondary btn-sm">View Homepage &rarr;</a>
    </div>
    <div class="admin-card-body" style="padding: 0;">
      <div class="table-responsive">
        <table class="admin-table">
          <thead>
            <tr>
              <th style="width: 50px;">Order</th>
              <th>Badge Title</th>
              <th>Announcement Message</th>
              <th>Destination Link</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($announcements)): ?>
              <tr>
                <td colspan="6" style="text-align: center; padding: 36px; color: var(--admin-text-muted);">
                  No announcements found. Add one using the form on the right.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($announcements as $ann): ?>
                <tr>
                  <td style="font-weight: 700; color: var(--admin-forest); text-align: center;">
                    <?php echo (int)($ann['display_order'] ?? 0); ?>
                  </td>
                  <td>
                    <?php if (!empty($ann['title'])): ?>
                      <span class="badge" style="background: var(--admin-saffron-light); color: var(--admin-saffron-dark); font-weight: 700;">
                        <?php echo htmlspecialchars($ann['title']); ?>
                      </span>
                    <?php else: ?>
                      <span style="color: var(--admin-text-muted); font-size: 12px;">(No badge)</span>
                    <?php endif; ?>
                  </td>
                  <td style="font-size: 13px; max-width: 280px; line-height: 1.4;">
                    <?php echo htmlspecialchars($ann['message']); ?>
                  </td>
                  <td style="font-size: 12px; font-family: monospace; color: var(--admin-sage);">
                    <?php echo htmlspecialchars($ann['link_url'] ?: '—'); ?>
                  </td>
                  <td>
                    <?php if (!empty($ann['is_active'])): ?>
                      <span class="badge badge-active">Live</span>
                    <?php else: ?>
                      <span class="badge badge-inactive">Disabled</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div style="display: flex; gap: 4px;">
                      <button type="button" class="btn btn-secondary btn-sm" onclick='editAnnouncement(<?php echo json_encode($ann); ?>)' title="Edit Announcement">
                        Edit
                      </button>
                      
                      <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="action" value="toggle_active">
                        <input type="hidden" name="announcement_id" value="<?php echo $ann['id']; ?>">
                        <button type="submit" class="btn btn-secondary btn-sm" title="Toggle active">
                          <?php echo !empty($ann['is_active']) ? 'Disable' : 'Enable'; ?>
                        </button>
                      </form>

                      <form method="POST" onsubmit="return confirm('Delete this announcement?');">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="action" value="delete_announcement">
                        <input type="hidden" name="announcement_id" value="<?php echo $ann['id']; ?>">
                        <button type="submit" class="btn btn-secondary btn-sm" style="color: var(--admin-danger);" title="Delete">
                          &times;
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

  <!-- Form Column (Add / Edit) -->
  <div class="admin-card">
    <div class="admin-card-header">
      <div class="admin-card-title" id="formCardTitle">Add Announcement</div>
    </div>
    <div class="admin-card-body">
      
      <form method="POST" id="annForm">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="action" id="formAction" value="create_announcement">
        <input type="hidden" name="announcement_id" id="annId" value="">

        <div class="form-group">
          <label class="form-label" for="ann_title">Badge Label (Optional)</label>
          <input type="text" id="ann_title" name="title" class="form-control" placeholder="E.g. Pure Harvest, Special Offer, Free Delivery" oninput="updateLivePreview()">
        </div>

        <div class="form-group">
          <label class="form-label" for="ann_message">Announcement Text <span class="required">*</span></label>
          <textarea id="ann_message" name="message" class="form-control" rows="3" required placeholder="Text displayed in the rotating top banner..." oninput="updateLivePreview()"></textarea>
        </div>

        <div class="form-group">
          <label class="form-label" for="ann_link">Destination Link (Optional)</label>
          <input type="text" id="ann_link" name="link_url" class="form-control" placeholder="shop.php or category.php?slug=perfume" oninput="updateLivePreview()">
          <div class="form-help">Examples: <code>shop.php</code>, <code>category.php?slug=shilajit</code>, <code>#business-opportunity</code></div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="ann_order">Display Sequence</label>
            <input type="number" id="ann_order" name="display_order" class="form-control" value="0">
          </div>

          <div class="form-group">
            <label class="form-label" for="ann_type">Presentation</label>
            <select id="ann_type" name="display_type" class="form-control" onchange="updateLivePreview()">
              <option value="scrolling">Scrolling Marquee</option>
              <option value="static">Static Centered</option>
            </select>
          </div>
        </div>

        <div class="form-row" style="margin-top: 12px;">
          <div class="form-group">
            <label class="form-label" for="ann_bg_color">Bar Background Color</label>
            <div class="ann-color-control">
              <input type="color" id="ann_bg_picker" value="#d4af37" aria-label="Choose background color" oninput="syncAnnColor('bg', this)">
              <input type="text" id="ann_bg_color" name="bg_color" class="form-control" value="#d4af37" maxlength="7" pattern="#[0-9a-fA-F]{6}" oninput="syncAnnColor('bg', this)">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label" for="ann_text_color">Bar Text Color</label>
            <div class="ann-color-control">
              <input type="color" id="ann_text_picker" value="#000000" aria-label="Choose text color" oninput="syncAnnColor('text', this)">
              <input type="text" id="ann_text_color" name="text_color" class="form-control" value="#000000" maxlength="7" pattern="#[0-9a-fA-F]{6}" oninput="syncAnnColor('text', this)">
            </div>
          </div>
        </div>

        <div class="form-group" style="margin-top: 12px;">
          <label style="display: flex; align-items: center; gap: 8px; font-weight: 600; cursor: pointer;">
            <input type="checkbox" id="ann_active" name="is_active" value="1" checked>
            Active &amp; Published on Website
          </label>
        </div>

        <div style="display: flex; gap: 8px; margin-top: 20px;">
          <button type="submit" class="btn btn-primary" id="submitBtn" style="flex: 1;">Save Announcement</button>
          <button type="button" class="btn btn-secondary" id="resetBtn" style="display: none;" onclick="resetAnnForm()">Cancel</button>
        </div>
      </form>

    </div>
  </div>

</div>

<!-- VERSION HISTORY & RESTORE -->
<div class="admin-card" style="margin-top: 24px;">
  <div class="admin-card-header">
    <div class="admin-card-title">Version History &amp; Restore (<?php echo count($revisions); ?>)</div>
    <span style="font-size: 12px; color: var(--admin-text-muted);">A snapshot is saved before every edit, toggle, or delete.</span>
  </div>
  <div class="admin-card-body" style="padding: 0;">
    <div class="table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Saved At</th>
            <th>Badge</th>
            <th>Message</th>
            <th>Colors</th>
            <th>Presentation</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($revisions)): ?>
            <tr>
              <td colspan="6" style="text-align: center; padding: 28px; color: var(--admin-text-muted);">
                No saved versions yet. Previous states will appear here after your first edit.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($revisions as $rev): ?>
              <tr>
                <td style="font-size: 12px; color: var(--admin-text-muted); white-space: nowrap;">
                  <?php echo date('d M Y, H:i', strtotime($rev['created_at'])); ?>
                  <div style="font-size: 11px;">by <?php echo htmlspecialchars($rev['saved_by'] ?: 'system'); ?></div>
                </td>
                <td style="font-weight: 600;"><?php echo htmlspecialchars($rev['title'] ?: '(No badge)'); ?></td>
                <td style="font-size: 12px; max-width: 300px; line-height: 1.45;">
                  <?php echo htmlspecialchars(mb_strimwidth($rev['message'], 0, 110, '…')); ?>
                </td>
                <td>
                  <span class="ann-swatch-row">
                    <span class="ann-swatch" style="background: <?php echo htmlspecialchars($rev['bg_color'] ?: '#d4af37'); ?>;" title="Background"></span>
                    <span class="ann-swatch" style="background: <?php echo htmlspecialchars($rev['text_color'] ?: '#000000'); ?>;" title="Text"></span>
                  </span>
                </td>
                <td style="font-size: 12px;"><?php echo $rev['display_type'] === 'static' ? 'Static' : 'Scrolling'; ?></td>
                <td>
                  <form method="POST" onsubmit="return confirm('Restore this saved version? Current content will be replaced and a new snapshot saved.');">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="action" value="restore_revision">
                    <input type="hidden" name="revision_id" value="<?php echo (int)$rev['id']; ?>">
                    <button type="submit" class="btn btn-secondary btn-sm">Restore</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <p style="padding: 12px 20px 0; font-size: 11px; color: var(--admin-text-muted);">
      Restoring replaces the live announcement content. Restored states are also snapshotted so you can revert again.
    </p>
  </div>
</div>

<script>
function syncAnnColor(which, source) {
  var picker = document.getElementById(which === 'bg' ? 'ann_bg_picker' : 'ann_text_picker');
  var text = document.getElementById(which === 'bg' ? 'ann_bg_color' : 'ann_text_color');
  if (!picker || !text) return;
  var value = (source.value || '').trim();
  if (source.type === 'color') {
    text.value = value.toLowerCase();
  } else if (/^#[0-9a-fA-F]{6}$/.test(value)) {
    picker.value = value.toLowerCase();
  }
  updateLivePreview();
}

function updateLivePreview() {
  var preview = document.getElementById('liveStripPreview');
  if (!preview) return;

  var title = document.getElementById('ann_title').value.trim();
  var msg = document.getElementById('ann_message').value.trim();
  var link = document.getElementById('ann_link').value.trim();
  var type = document.getElementById('ann_type').value;
  var bg = document.getElementById('ann_bg_picker').value || '#d4af37';
  var text = document.getElementById('ann_text_picker').value || '#000000';

  preview.style.backgroundColor = bg;
  preview.style.color = text;
  preview.classList.toggle('is-static', type === 'static');
  preview.classList.toggle('is-scrolling', type !== 'static');
  var stage = preview.closest('.ann-preview-stage');
  if (stage) {
    stage.style.setProperty('--ann-bg', bg);
    stage.classList.toggle('is-static', type === 'static');
  }

  preview.querySelectorAll('.ann-preview-unit').forEach(function (unit) {
    var badge = unit.querySelector('.ann-preview-badge');
    var messageEl = unit.querySelector('.ann-preview-message');
    var linkEl = unit.querySelector('.ann-preview-link');
    badge.textContent = title || 'Notice';
    badge.style.display = title ? 'inline-flex' : 'none';
    messageEl.textContent = msg || 'Your announcement text will appear here...';
    linkEl.style.display = link ? 'inline-flex' : 'none';
  });
}

function editAnnouncement(ann) {
  document.getElementById('formCardTitle').textContent = 'Edit Announcement #' + ann.id;
  document.getElementById('formAction').value = 'edit_announcement';
  document.getElementById('annId').value = ann.id;
  document.getElementById('ann_title').value = ann.title || '';
  document.getElementById('ann_message').value = ann.message || '';
  document.getElementById('ann_link').value = ann.link_url || '';
  document.getElementById('ann_order').value = ann.display_order || '0';
  document.getElementById('ann_type').value = ann.display_type || 'scrolling';

  var bg = /^#[0-9a-fA-F]{6}$/.test(ann.bg_color || '') ? ann.bg_color : '#d4af37';
  var fg = /^#[0-9a-fA-F]{6}$/.test(ann.text_color || '') ? ann.text_color : '#000000';
  document.getElementById('ann_bg_color').value = bg;
  document.getElementById('ann_bg_picker').value = bg;
  document.getElementById('ann_text_color').value = fg;
  document.getElementById('ann_text_picker').value = fg;

  document.getElementById('ann_active').checked = ann.is_active == 1;
  document.getElementById('submitBtn').textContent = 'Update Announcement';
  document.getElementById('resetBtn').style.display = 'inline-flex';
  updateLivePreview();
}

function resetAnnForm() {
  document.getElementById('formCardTitle').textContent = 'Add Announcement';
  document.getElementById('formAction').value = 'create_announcement';
  document.getElementById('annId').value = '';
  document.getElementById('ann_title').value = '';
  document.getElementById('ann_message').value = '';
  document.getElementById('ann_link').value = '';
  document.getElementById('ann_order').value = '0';
  document.getElementById('ann_type').value = 'scrolling';
  document.getElementById('ann_bg_color').value = '#d4af37';
  document.getElementById('ann_bg_picker').value = '#d4af37';
  document.getElementById('ann_text_color').value = '#000000';
  document.getElementById('ann_text_picker').value = '#000000';
  document.getElementById('ann_active').checked = true;
  document.getElementById('submitBtn').textContent = 'Save Announcement';
  document.getElementById('resetBtn').style.display = 'none';
  updateLivePreview();
}

document.addEventListener('DOMContentLoaded', updateLivePreview);
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
