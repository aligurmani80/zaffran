<?php
/**
 * Zaffran Admin Panel — Customer Reviews & WhatsApp Feedback Screenshot Management
 */
define('ZAFFRAN_APP', true);
$pageTitle = 'Customer Reviews / WhatsApp Proof';
require_once __DIR__ . '/header.php';

$db = getDbConnection();

$successMsg = '';
$errorMsg = '';

// Products for optional tagging
$products = $db->query("SELECT id, name FROM products WHERE is_active = 1 ORDER BY name ASC")->fetchAll();

// Handle Screenshot Proof Upload / Edit / Status Toggle / Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateAdminCsrfToken($token)) {
        $errorMsg = 'Security validation failed.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'upload_proof') {
            $customerName = trim(strip_tags($_POST['customer_display_name'] ?? ''));
            $productId = !empty($_POST['product_id']) ? (int)$_POST['product_id'] : null;
            $caption = trim(strip_tags($_POST['caption'] ?? ''));
            $rating = !empty($_POST['rating']) ? min(5, max(1, (int)$_POST['rating'])) : null;
            $order = (int)($_POST['display_order'] ?? 0);
            $isPublished = !empty($_POST['is_published']) ? 1 : 0;
            $privacyAgreed = !empty($_POST['privacy_confirmed']);

            if (!$privacyAgreed && $isPublished) {
                $errorMsg = 'Please verify that private customer phone numbers/addresses have been redacted before publishing.';
            } elseif (empty($_FILES['proof_screenshot']['name'])) {
                $errorMsg = 'Please select a WhatsApp screenshot image to upload.';
            } else {
                $upload = handleImageUpload($_FILES['proof_screenshot'], 'whatsapp_proofs');
                if (!$upload['success']) {
                    $errorMsg = 'Screenshot upload failed: ' . $upload['error'];
                } else {
                    try {
                        $imagePath = $upload['path'];
                        $stmt = $db->prepare("INSERT INTO whatsapp_proofs (image_path, customer_display_name, product_id, rating, caption, is_published, display_order, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
                        $stmt->execute([$imagePath, $customerName, $productId, $rating, $caption, $isPublished, $order]);
                        $newId = $db->lastInsertId();
                        logAdminAudit('upload_whatsapp_proof', 'whatsapp_proof', (string)$newId, "Uploaded review screenshot for '{$customerName}' (Published: {$isPublished})");
                        $successMsg = 'WhatsApp feedback screenshot uploaded successfully.' . ($isPublished ? ' (Published to homepage)' : ' (Saved as Draft)');
                    } catch (Exception $e) {
                        $errorMsg = 'Database error: ' . $e->getMessage();
                    }
                }
            }
        } elseif ($action === 'edit_proof') {
            $proofId = (int)($_POST['proof_id'] ?? 0);
            $customerName = trim(strip_tags($_POST['customer_display_name'] ?? ''));
            $productId = !empty($_POST['product_id']) ? (int)$_POST['product_id'] : null;
            $caption = trim(strip_tags($_POST['caption'] ?? ''));
            $rating = !empty($_POST['rating']) ? min(5, max(1, (int)$_POST['rating'])) : null;
            $order = (int)($_POST['display_order'] ?? 0);
            $isPublished = !empty($_POST['is_published']) ? 1 : 0;

            if ($proofId > 0) {
                $cur = $db->prepare("SELECT * FROM whatsapp_proofs WHERE id = ?");
                $cur->execute([$proofId]);
                $proof = $cur->fetch();

                if ($proof) {
                    $imagePath = $proof['image_path'];
                    if (!empty($_FILES['proof_screenshot']['name'])) {
                        $upload = handleImageUpload($_FILES['proof_screenshot'], 'whatsapp_proofs');
                        if ($upload['success']) {
                            $imagePath = $upload['path'];
                        }
                    }

                    try {
                        $upd = $db->prepare("UPDATE whatsapp_proofs SET image_path = ?, customer_display_name = ?, product_id = ?, rating = ?, caption = ?, is_published = ?, display_order = ?, updated_at = NOW() WHERE id = ?");
                        $upd->execute([$imagePath, $customerName, $productId, $rating, $caption, $isPublished, $order, $proofId]);
                        logAdminAudit('edit_whatsapp_proof', 'whatsapp_proof', (string)$proofId, "Updated proof #{$proofId}");
                        $successMsg = 'Review screenshot details updated successfully.';
                    } catch (Exception $e) {
                        $errorMsg = 'Database error: ' . $e->getMessage();
                    }
                }
            }
        } elseif ($action === 'toggle_publish') {
            $proofId = (int)($_POST['proof_id'] ?? 0);
            if ($proofId > 0) {
                $cur = $db->prepare("SELECT is_published, customer_display_name FROM whatsapp_proofs WHERE id = ?");
                $cur->execute([$proofId]);
                $row = $cur->fetch();
                if ($row) {
                    $newStatus = $row['is_published'] ? 0 : 1;
                    $db->prepare("UPDATE whatsapp_proofs SET is_published = ?, updated_at = NOW() WHERE id = ?")->execute([$newStatus, $proofId]);
                    logAdminAudit('toggle_whatsapp_proof', 'whatsapp_proof', (string)$proofId, "Changed publish status to " . ($newStatus ? 'Published' : 'Unpublished'));
                    $successMsg = "Screenshot visibility updated.";
                }
            }
        } elseif ($action === 'delete_proof') {
            $proofId = (int)($_POST['proof_id'] ?? 0);
            if ($proofId > 0) {
                $db->prepare("DELETE FROM whatsapp_proofs WHERE id = ?")->execute([$proofId]);
                logAdminAudit('delete_whatsapp_proof', 'whatsapp_proof', (string)$proofId, 'Removed WhatsApp feedback record');
                $successMsg = 'Screenshot review deleted.';
            }
        } elseif ($action === 'save_review') {
            $reviewId = (int)($_POST['review_id'] ?? 0);
            $customerName = trim(strip_tags($_POST['review_customer_name'] ?? ''));
            $rating = min(5, max(1, (int)($_POST['review_rating'] ?? 5)));
            $reviewText = trim(strip_tags($_POST['review_text'] ?? ''));
            $isApproved = !empty($_POST['review_is_approved']) ? 1 : 0;

            if ($customerName === '' || $reviewText === '') {
                $errorMsg = 'Customer name and review text are both required.';
            } else {
                try {
                    if ($reviewId > 0) {
                        $upd = $db->prepare("UPDATE reviews SET customer_name = ?, rating = ?, review_text = ?, is_approved = ? WHERE id = ?");
                        $upd->execute([$customerName, $rating, $reviewText, $isApproved, $reviewId]);
                        logAdminAudit('edit_customer_review', 'review', (string)$reviewId, "Updated written review by '{$customerName}'");
                        $successMsg = 'Written review updated.';
                    } else {
                        $ins = $db->prepare("INSERT INTO reviews (customer_name, rating, review_text, is_approved, created_at) VALUES (?, ?, ?, ?, NOW())");
                        $ins->execute([$customerName, $rating, $reviewText, $isApproved]);
                        $newId = (int)$db->lastInsertId();
                        logAdminAudit('create_customer_review', 'review', (string)$newId, "Added written review by '{$customerName}'");
                        $successMsg = $isApproved
                            ? 'Genuine written review published to the homepage.'
                            : 'Written review saved as pending approval.';
                    }
                } catch (Exception $e) {
                    $errorMsg = 'Database error: ' . $e->getMessage();
                }
            }
        } elseif ($action === 'toggle_review') {
            $reviewId = (int)($_POST['review_id'] ?? 0);
            if ($reviewId > 0) {
                $cur = $db->prepare("SELECT is_approved, customer_name FROM reviews WHERE id = ?");
                $cur->execute([$reviewId]);
                $row = $cur->fetch();
                if ($row) {
                    $newStatus = $row['is_approved'] ? 0 : 1;
                    $db->prepare("UPDATE reviews SET is_approved = ? WHERE id = ?")->execute([$newStatus, $reviewId]);
                    logAdminAudit('toggle_customer_review', 'review', (string)$reviewId, "Set written review approval to " . ($newStatus ? 'Approved' : 'Pending'));
                    $successMsg = 'Written review visibility updated.';
                }
            }
        } elseif ($action === 'delete_review') {
            $reviewId = (int)($_POST['review_id'] ?? 0);
            if ($reviewId > 0) {
                $db->prepare("DELETE FROM reviews WHERE id = ?")->execute([$reviewId]);
                logAdminAudit('delete_customer_review', 'review', (string)$reviewId, 'Deleted written customer review');
                $successMsg = 'Written review deleted.';
            }
        }
    }
}

// Fetch WhatsApp proofs with product name
$proofs = $db->query("SELECT wp.*, p.name as product_name FROM whatsapp_proofs wp LEFT JOIN products p ON wp.product_id = p.id ORDER BY wp.display_order ASC, wp.id DESC")->fetchAll();
$writtenReviews = $db->query("SELECT * FROM reviews ORDER BY is_approved ASC, id DESC")->fetchAll();
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

<!-- PRIVACY & INTEGRITY NOTICE -->
<div class="notice-box notice-info" style="margin-bottom: 24px;">
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
  <div>
    <strong>Genuine WhatsApp Social Proof Guidelines:</strong><br>
    Only upload authentic chat screenshots received from real buyers. Please ensure customer phone numbers and residential addresses are blurred/redacted before publishing to protect privacy.
  </div>
</div>

<div class="admin-two-col">
  
  <!-- WhatsApp Screenshots List -->
  <div class="admin-card">
    <div class="admin-card-header">
      <div class="admin-card-title">WhatsApp Customer Proofs (<?php echo count($proofs); ?>)</div>
      <a href="../index.php#reviews" target="_blank" class="btn btn-secondary btn-sm">Preview on Homepage &rarr;</a>
    </div>
    <div class="admin-card-body" style="padding: 0;">
      <div class="table-responsive">
        <table class="admin-table">
          <thead>
            <tr>
              <th style="width: 70px;">Preview</th>
              <th>Customer Label</th>
              <th>Product Tag</th>
              <th>Rating</th>
              <th>Caption / Excerpt</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($proofs)): ?>
              <tr>
                <td colspan="7" style="text-align: center; padding: 36px; color: var(--admin-text-muted);">
                  No WhatsApp screenshot feedback uploaded yet. Upload a screenshot on the right.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($proofs as $p): ?>
                <tr>
                  <td>
                    <div class="proof-thumb" onclick="openAdminLightbox('<?php echo htmlspecialchars('../' . $p['image_path']); ?>')">
                      <img src="../<?php echo htmlspecialchars($p['image_path']); ?>" alt="Proof" onerror="this.classList.add('is-missing')">
                      <span class="proof-thumb-missing">No file</span>
                    </div>
                  </td>
                  <td>
                    <div style="font-weight: 600; color: var(--admin-forest);">
                      <?php echo htmlspecialchars($p['customer_display_name'] ?: 'Verified Customer'); ?>
                    </div>
                    <div style="font-size: 11px; color: var(--admin-text-muted);">
                      Sequence: #<?php echo (int)$p['display_order']; ?>
                    </div>
                  </td>
                  <td>
                    <?php if (!empty($p['product_name'])): ?>
                      <span class="badge" style="background: #F0F2F1; color: var(--admin-forest);">
                        <?php echo htmlspecialchars($p['product_name']); ?>
                      </span>
                    <?php else: ?>
                      <span style="color: var(--admin-text-muted); font-size: 12px;">General Store</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if (!empty($p['rating'])): ?>
                      <span style="color: var(--admin-saffron); font-size: 13px;">
                        <?php echo str_repeat('★', (int)$p['rating']); ?>
                      </span>
                    <?php else: ?>
                      <span style="color: var(--admin-text-muted); font-size: 12px;">—</span>
                    <?php endif; ?>
                  </td>
                  <td style="font-size: 12px; max-width: 220px; line-height: 1.4;">
                    <?php echo htmlspecialchars($p['caption'] ?: '—'); ?>
                  </td>
                  <td>
                    <?php if (!empty($p['is_published'])): ?>
                      <span class="badge badge-active">Published</span>
                    <?php else: ?>
                      <span class="badge badge-pending">Draft / Hidden</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                      <button type="button" class="btn btn-secondary btn-sm" onclick='editProof(<?php echo json_encode($p); ?>)'>
                        Edit
                      </button>
                      
                      <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="action" value="toggle_publish">
                        <input type="hidden" name="proof_id" value="<?php echo $p['id']; ?>">
                        <button type="submit" class="btn btn-secondary btn-sm">
                          <?php echo !empty($p['is_published']) ? 'Unpublish' : 'Publish'; ?>
                        </button>
                      </form>

                      <form method="POST" onsubmit="return confirm('Delete this screenshot review?');">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="action" value="delete_proof">
                        <input type="hidden" name="proof_id" value="<?php echo $p['id']; ?>">
                        <button type="submit" class="btn btn-secondary btn-sm" style="color: var(--admin-danger);">
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

  <!-- Upload / Edit Form -->
  <div class="admin-card">
    <div class="admin-card-header">
      <div class="admin-card-title" id="formCardTitle">Upload WhatsApp Screenshot</div>
    </div>
    <div class="admin-card-body">
      
      <form method="POST" enctype="multipart/form-data" id="proofForm">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="action" id="formAction" value="upload_proof">
        <input type="hidden" name="proof_id" id="proofId" value="">

        <div class="form-group">
          <label class="form-label" for="proof_screenshot">Screenshot Image <span class="required" id="fileRequired">*</span></label>
          <input type="file" id="proof_screenshot" name="proof_screenshot" class="form-control" accept="image/jpeg,image/png,image/webp" onchange="previewUploadImage(this)">
          <div class="form-help">Formats: JPG, PNG, WebP (Max 5MB). High clarity preserved.</div>
          
          <div id="imagePreviewBox" style="margin-top: 10px; display: none;">
            <div style="width: 100px; height: 150px; border-radius: 6px; overflow: hidden; border: 1px solid var(--admin-card-border); background: #EEE;">
              <img id="imgPreviewTarget" src="#" alt="Selected preview" style="width: 100%; height: 100%; object-fit: contain;">
            </div>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="customer_name">Customer Display Label</label>
          <input type="text" id="customer_name" name="customer_display_name" class="form-control" placeholder="E.g. Bilal A. (Lahore) or Dr. Naveed">
          <div class="form-help">Use safe, non-identifying name or city without phone number.</div>
        </div>

        <div class="form-group">
          <label class="form-label" for="product_id">Associated Product (Optional)</label>
          <select id="product_id" name="product_id" class="form-control">
            <option value="">General Store Experience</option>
            <?php foreach ($products as $pr): ?>
              <option value="<?php echo $pr['id']; ?>"><?php echo htmlspecialchars($pr['name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="rating">Genuine Rating (Optional)</label>
          <select id="rating" name="rating" class="form-control">
            <option value="">No Star Rating (Unspecified)</option>
            <option value="5">5 Stars (★★★★★)</option>
            <option value="4">4 Stars (★★★★☆)</option>
            <option value="3">3 Stars (★★★☆☆)</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="caption">Review Caption / Key Excerpt</label>
          <textarea id="caption" name="caption" class="form-control" rows="3" placeholder="Brief quote or highlight from customer feedback..."></textarea>
        </div>

        <div class="form-group">
          <label class="form-label" for="display_order">Display Sequence</label>
          <input type="number" id="display_order" name="display_order" class="form-control" value="0">
        </div>

        <div class="form-group" style="margin-top: 14px;">
          <label style="display: flex; align-items: flex-start; gap: 8px; font-size: 13px; cursor: pointer; color: var(--admin-forest);">
            <input type="checkbox" id="privacy_confirmed" name="privacy_confirmed" value="1" checked style="margin-top: 3px;">
            <span>I confirm phone numbers/addresses in this screenshot are redacted or authorized for marketing display.</span>
          </label>
        </div>

        <div class="form-group">
          <label style="display: flex; align-items: center; gap: 8px; font-weight: 600; cursor: pointer;">
            <input type="checkbox" id="is_published" name="is_published" value="1" checked>
            Publish to Homepage Gallery Immediately
          </label>
        </div>

        <div style="display: flex; gap: 8px; margin-top: 20px;">
          <button type="submit" class="btn btn-primary" id="submitBtn" style="flex: 1;">Save Screenshot Review</button>
          <button type="button" class="btn btn-secondary" id="resetBtn" style="display: none;" onclick="resetProofForm()">Cancel</button>
        </div>
      </form>

    </div>
  </div>

</div>

<!-- WRITTEN CUSTOMER REVIEWS -->
<div style="margin-top: 32px;">
  <div class="notice-box notice-info" style="margin-bottom: 20px;">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
    <div>
      <strong>Genuine Written Reviews Only:</strong><br>
      Add feedback exactly as received from real customers. Do not write, embellish, or invent testimonials. New entries stay pending until you approve them for the homepage.
    </div>
  </div>

  <div class="admin-two-col">
    <div class="admin-card">
      <div class="admin-card-header">
        <div class="admin-card-title">Written Customer Reviews (<?php echo count($writtenReviews); ?>)</div>
        <a href="../index.php#reviews" target="_blank" class="btn btn-secondary btn-sm">Preview Homepage &rarr;</a>
      </div>
      <div class="admin-card-body" style="padding: 0;">
        <div class="table-responsive">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Customer</th>
                <th>Rating</th>
                <th>Review</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($writtenReviews)): ?>
                <tr>
                  <td colspan="5" style="text-align: center; padding: 32px; color: var(--admin-text-muted);">
                    No written reviews yet. Add one using the form on the right — only from genuine customer feedback.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($writtenReviews as $rev): ?>
                  <tr>
                    <td>
                      <div style="font-weight: 600; color: var(--admin-forest);"><?php echo htmlspecialchars($rev['customer_name']); ?></div>
                      <div style="font-size: 11px; color: var(--admin-text-muted);"><?php echo date('d M Y', strtotime($rev['created_at'])); ?></div>
                    </td>
                    <td style="color: var(--admin-saffron); font-size: 13px; white-space: nowrap;">
                      <?php echo str_repeat('★', (int)$rev['rating']); ?>
                    </td>
                    <td style="font-size: 12px; max-width: 320px; line-height: 1.5;">
                      <?php echo htmlspecialchars(mb_strimwidth($rev['review_text'], 0, 150, '…')); ?>
                    </td>
                    <td>
                      <?php if (!empty($rev['is_approved'])): ?>
                        <span class="badge badge-active">Approved</span>
                      <?php else: ?>
                        <span class="badge badge-pending">Pending</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <div style="display: flex; gap: 4px; flex-wrap: wrap;">
                        <button type="button" class="btn btn-secondary btn-sm" onclick='editWrittenReview(<?php echo json_encode($rev); ?>)'>Edit</button>
                        <form method="POST">
                          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                          <input type="hidden" name="action" value="toggle_review">
                          <input type="hidden" name="review_id" value="<?php echo (int)$rev['id']; ?>">
                          <button type="submit" class="btn btn-secondary btn-sm"><?php echo !empty($rev['is_approved']) ? 'Unpublish' : 'Approve'; ?></button>
                        </form>
                        <form method="POST" onsubmit="return confirm('Delete this written review?');">
                          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                          <input type="hidden" name="action" value="delete_review">
                          <input type="hidden" name="review_id" value="<?php echo (int)$rev['id']; ?>">
                          <button type="submit" class="btn btn-secondary btn-sm" style="color: var(--admin-danger);">&times;</button>
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

    <div class="admin-card">
      <div class="admin-card-header">
        <div class="admin-card-title" id="reviewFormTitle">Add Written Review</div>
      </div>
      <div class="admin-card-body">
        <form method="POST" id="writtenReviewForm">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
          <input type="hidden" name="action" value="save_review">
          <input type="hidden" name="review_id" id="review_id" value="">

          <div class="form-group">
            <label class="form-label" for="review_customer_name">Customer Name <span class="required">*</span></label>
            <input type="text" id="review_customer_name" name="review_customer_name" class="form-control" placeholder="As shared by the customer" required>
          </div>

          <div class="form-group">
            <label class="form-label" for="review_rating">Star Rating</label>
            <select id="review_rating" name="review_rating" class="form-control">
              <option value="5">5 Stars</option>
              <option value="4">4 Stars</option>
              <option value="3">3 Stars</option>
              <option value="2">2 Stars</option>
              <option value="1">1 Star</option>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label" for="review_text">Review Text <span class="required">*</span></label>
            <textarea id="review_text" name="review_text" class="form-control" rows="5" placeholder="Exact customer feedback, unedited..." required></textarea>
          </div>

          <div class="form-group">
            <label style="display: flex; align-items: center; gap: 8px; font-weight: 600; cursor: pointer;">
              <input type="checkbox" id="review_is_approved" name="review_is_approved" value="1">
              Approve and publish on homepage immediately
            </label>
          </div>

          <div style="display: flex; gap: 8px; margin-top: 16px;">
            <button type="submit" class="btn btn-primary" id="reviewSubmitBtn" style="flex: 1;">Save Written Review</button>
            <button type="button" class="btn btn-secondary" id="reviewResetBtn" style="display: none;" onclick="resetWrittenReviewForm()">Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- LIGHTBOX MODAL FOR ADMIN PREVIEW -->
<div id="adminLightboxModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.85); z-index: 9999; align-items: center; justify-content: center; padding: 20px;" onclick="closeAdminLightbox()">
  <div style="position: relative; max-width: 90vw; max-height: 90vh;" onclick="event.stopPropagation()">
    <img id="adminLightboxImg" src="" style="max-width: 100%; max-height: 85vh; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
    <button type="button" style="position: absolute; top: -35px; right: 0; background: transparent; border: none; color: #FFF; font-size: 28px; cursor: pointer;" onclick="closeAdminLightbox()">&times;</button>
  </div>
</div>

<script>
function previewUploadImage(input) {
  const box = document.getElementById('imagePreviewBox');
  const target = document.getElementById('imgPreviewTarget');
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = function(e) {
      target.src = e.target.result;
      box.style.display = 'block';
    }
    reader.readAsDataURL(input.files[0]);
  }
}

function openAdminLightbox(src) {
  document.getElementById('adminLightboxImg').src = src;
  document.getElementById('adminLightboxModal').style.display = 'flex';
}

function closeAdminLightbox() {
  document.getElementById('adminLightboxModal').style.display = 'none';
}

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeAdminLightbox();
});

function editProof(p) {
  document.getElementById('formCardTitle').textContent = 'Edit Review Proof #' + p.id;
  document.getElementById('formAction').value = 'edit_proof';
  document.getElementById('proofId').value = p.id;
  document.getElementById('customer_name').value = p.customer_display_name || '';
  document.getElementById('product_id').value = p.product_id || '';
  document.getElementById('rating').value = p.rating || '';
  document.getElementById('caption').value = p.caption || '';
  document.getElementById('display_order').value = p.display_order || '0';
  document.getElementById('is_published').checked = p.is_published == 1;
  document.getElementById('fileRequired').style.display = 'none';
  
  if (p.image_path) {
    document.getElementById('imgPreviewTarget').src = '../' + p.image_path;
    document.getElementById('imagePreviewBox').style.display = 'block';
  }

  document.getElementById('submitBtn').textContent = 'Update Screenshot Details';
  document.getElementById('resetBtn').style.display = 'inline-flex';
}

function resetProofForm() {
  document.getElementById('formCardTitle').textContent = 'Upload WhatsApp Screenshot';
  document.getElementById('formAction').value = 'upload_proof';
  document.getElementById('proofId').value = '';
  document.getElementById('proof_screenshot').value = '';
  document.getElementById('customer_name').value = '';
  document.getElementById('product_id').value = '';
  document.getElementById('rating').value = '';
  document.getElementById('caption').value = '';
  document.getElementById('display_order').value = '0';
  document.getElementById('is_published').checked = true;
  document.getElementById('fileRequired').style.display = 'inline';
  document.getElementById('imagePreviewBox').style.display = 'none';
  document.getElementById('submitBtn').textContent = 'Save Screenshot Review';
  document.getElementById('resetBtn').style.display = 'none';
}

function editWrittenReview(review) {
  document.getElementById('reviewFormTitle').textContent = 'Edit Written Review #' + review.id;
  document.getElementById('review_id').value = review.id;
  document.getElementById('review_customer_name').value = review.customer_name || '';
  document.getElementById('review_rating').value = review.rating || 5;
  document.getElementById('review_text').value = review.review_text || '';
  document.getElementById('review_is_approved').checked = review.is_approved == 1;
  document.getElementById('reviewSubmitBtn').textContent = 'Update Written Review';
  document.getElementById('reviewResetBtn').style.display = 'inline-flex';
  document.getElementById('review_customer_name').focus();
}

function resetWrittenReviewForm() {
  document.getElementById('reviewFormTitle').textContent = 'Add Written Review';
  document.getElementById('review_id').value = '';
  document.getElementById('review_customer_name').value = '';
  document.getElementById('review_rating').value = '5';
  document.getElementById('review_text').value = '';
  document.getElementById('review_is_approved').checked = false;
  document.getElementById('reviewSubmitBtn').textContent = 'Save Written Review';
  document.getElementById('reviewResetBtn').style.display = 'none';
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
