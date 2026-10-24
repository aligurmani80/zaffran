<?php
/**
 * Zaffran Admin Panel — Order Details & Status Workflow Management
 */
define('ZAFFRAN_APP', true);
require_once __DIR__ . '/auth.php';

$currentAdmin = requireAdminAuth();
$db = getDbConnection();

$orderId = (int)($_GET['id'] ?? 0);
if ($orderId <= 0) {
    header('Location: orders.php');
    exit;
}

// Fetch Order
$stmt = $db->prepare("SELECT * FROM orders WHERE id = ? LIMIT 1");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: orders.php');
    exit;
}

$successMsg = '';
$errorMsg = '';

// Handle Status & Payment Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token = $_POST['csrf_token'] ?? '';

    if (!validateAdminCsrfToken($token)) {
        $errorMsg = 'Security validation failed.';
    } elseif ($action === 'update_status') {
        $newStatus = trim($_POST['order_status'] ?? '');
        $allowedStatuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];

        if (!in_array($newStatus, $allowedStatuses)) {
            $errorMsg = 'Invalid order status selected.';
        } else {
            $oldStatus = $order['order_status'];
            if ($oldStatus !== $newStatus) {
                $db->beginTransaction();
                try {
                    $upd = $db->prepare("UPDATE orders SET order_status = ?, updated_at = NOW() WHERE id = ?");
                    $upd->execute([$newStatus, $orderId]);

                    // Add note in order_notes
                    $noteText = "Order status updated from '{$oldStatus}' to '{$newStatus}' by {$currentAdmin['username']}.";
                    $noteStmt = $db->prepare("INSERT INTO order_notes (order_id, admin_id, admin_name, note, is_customer_visible, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
                    $noteStmt->execute([$orderId, $currentAdmin['id'], $currentAdmin['username'], $noteText]);

                    logAdminAudit('update_order_status', 'order', (string)$orderId, $noteText);

                    $db->commit();
                    $order['order_status'] = $newStatus;
                    $successMsg = 'Order status successfully updated to ' . ucfirst($newStatus);
                } catch (Exception $e) {
                    $db->rollBack();
                    $errorMsg = 'Failed to update order status: ' . $e->getMessage();
                }
            }
        }
    } elseif ($action === 'update_payment') {
        $newPaymentStatus = trim($_POST['payment_status'] ?? '');
        $allowedPayment = ['pending', 'paid', 'failed'];

        if (!in_array($newPaymentStatus, $allowedPayment)) {
            $errorMsg = 'Invalid payment status selected.';
        } else {
            $oldPayment = $order['payment_status'];
            if ($oldPayment !== $newPaymentStatus) {
                $db->beginTransaction();
                try {
                    $upd = $db->prepare("UPDATE orders SET payment_status = ?, updated_at = NOW() WHERE id = ?");
                    $upd->execute([$newPaymentStatus, $orderId]);

                    $noteText = "Payment status updated from '{$oldPayment}' to '{$newPaymentStatus}' by {$currentAdmin['username']}.";
                    $noteStmt = $db->prepare("INSERT INTO order_notes (order_id, admin_id, admin_name, note, is_customer_visible, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
                    $noteStmt->execute([$orderId, $currentAdmin['id'], $currentAdmin['username'], $noteText]);

                    logAdminAudit('update_order_payment', 'order', (string)$orderId, $noteText);

                    $db->commit();
                    $order['payment_status'] = $newPaymentStatus;
                    $successMsg = 'Payment status successfully updated to ' . ucfirst($newPaymentStatus);
                } catch (Exception $e) {
                    $db->rollBack();
                    $errorMsg = 'Failed to update payment status: ' . $e->getMessage();
                }
            }
        }
    } elseif ($action === 'add_note') {
        $noteText = trim($_POST['internal_note'] ?? '');
        if (!empty($noteText)) {
            $noteStmt = $db->prepare("INSERT INTO order_notes (order_id, admin_id, admin_name, note, is_customer_visible, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
            $noteStmt->execute([$orderId, $currentAdmin['id'], $currentAdmin['username'], $noteText]);
            logAdminAudit('add_order_note', 'order', (string)$orderId, "Added note to order #{$order['order_number']}");
            $successMsg = 'Internal note saved.';
        }
    }
}

// Fetch Order Items
$itemsStmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
$itemsStmt->execute([$orderId]);
$orderItems = $itemsStmt->fetchAll();

// Fetch Order Notes
$notesStmt = $db->prepare("SELECT * FROM order_notes WHERE order_id = ? ORDER BY id DESC");
$notesStmt->execute([$orderId]);
$orderNotes = $notesStmt->fetchAll();

$pageTitle = "Order #{$order['order_number']}";
require_once __DIR__ . '/header.php';
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

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
  <div>
    <a href="orders.php" style="color: var(--admin-sage); text-decoration: none; font-weight: 500; font-size: 13px;">&larr; Back to All Orders</a>
    <h2 style="font-size: 24px; color: var(--admin-forest); margin-top: 4px;">
      Order #<?php echo htmlspecialchars($order['order_number']); ?>
    </h2>
    <div style="font-size: 13px; color: var(--admin-text-muted);">
      Placed on <?php echo date('F d, Y at h:i A', strtotime($order['created_at'])); ?>
    </div>
  </div>

  <div style="display: flex; gap: 10px; align-items: center;">
    <span class="badge badge-<?php echo htmlspecialchars($order['order_status']); ?>" style="padding: 6px 14px; font-size: 13px;">
      Status: <?php echo htmlspecialchars($order['order_status']); ?>
    </span>
    <span class="badge badge-<?php echo htmlspecialchars($order['payment_status']); ?>" style="padding: 6px 14px; font-size: 13px;">
      Payment: <?php echo htmlspecialchars($order['payment_status']); ?>
    </span>
  </div>
</div>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
  
  <!-- LEFT COLUMN: Products & Notes -->
  <div>
    <!-- Ordered Items Card -->
    <div class="admin-card">
      <div class="admin-card-header">
        <div class="admin-card-title">Order Items (<?php echo count($orderItems); ?>)</div>
      </div>
      <div class="admin-card-body" style="padding: 0;">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Product</th>
              <th>Unit Price</th>
              <th>Qty</th>
              <th>Total</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($orderItems as $item): ?>
              <tr>
                <td>
                  <div style="font-weight: 600; color: var(--admin-forest);"><?php echo htmlspecialchars($item['product_name']); ?></div>
                  <?php if (!empty($item['product_id'])): ?>
                    <div style="font-size: 11px; color: var(--admin-text-muted);">Product ID: #<?php echo $item['product_id']; ?></div>
                  <?php endif; ?>
                </td>
                <td>PKR <?php echo number_format($item['price'], 0); ?></td>
                <td><span class="badge badge-active"><?php echo (int)$item['quantity']; ?></span></td>
                <td style="font-weight: 700;">PKR <?php echo number_format($item['total_price'], 0); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <!-- Totals Breakdown -->
        <div style="padding: 20px 24px; background: #FCFAF6; border-top: 1px solid var(--admin-border-subtle);">
          <div style="max-width: 320px; margin-left: auto; display: flex; flex-direction: column; gap: 8px;">
            <div style="display: flex; justify-content: space-between; font-size: 14px;">
              <span style="color: var(--admin-text-muted);">Subtotal:</span>
              <span style="font-weight: 600;">PKR <?php echo number_format($order['subtotal'] ?? ($order['grand_total'] - $order['shipping_fee']), 0); ?></span>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 14px;">
              <span style="color: var(--admin-text-muted);">Shipping Fee:</span>
              <span style="font-weight: 600;">PKR <?php echo number_format($order['shipping_fee'], 0); ?></span>
            </div>
            <?php if (!empty($order['discount_total']) && $order['discount_total'] > 0): ?>
              <div style="display: flex; justify-content: space-between; font-size: 14px; color: var(--admin-danger);">
                <span>Discount:</span>
                <span style="font-weight: 600;">-PKR <?php echo number_format($order['discount_total'], 0); ?></span>
              </div>
            <?php endif; ?>
            <div style="display: flex; justify-content: space-between; font-size: 17px; font-weight: 700; color: var(--admin-forest); border-top: 1px solid var(--admin-border); padding-top: 8px; margin-top: 4px;">
              <span>Grand Total:</span>
              <span>PKR <?php echo number_format($order['grand_total'], 0); ?></span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Internal Activity & Admin Notes -->
    <div class="admin-card">
      <div class="admin-card-header">
        <div class="admin-card-title">Internal Notes & History</div>
      </div>
      <div class="admin-card-body">
        
        <form method="POST" style="margin-bottom: 24px;">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
          <input type="hidden" name="action" value="add_note">
          <div class="form-group" style="margin-bottom: 12px;">
            <label class="form-label">Add Note for Staff</label>
            <textarea name="internal_note" class="form-control" placeholder="E.g. Called customer to confirm delivery address, confirmed dispatch with courier..." rows="2" required></textarea>
          </div>
          <button type="submit" class="btn btn-secondary btn-sm">Add Staff Note</button>
        </form>

        <?php if (empty($orderNotes)): ?>
          <p style="font-size: 13px; color: var(--admin-text-muted);">No notes recorded for this order yet.</p>
        <?php else: ?>
          <div style="display: flex; flex-direction: column; gap: 12px;">
            <?php foreach ($orderNotes as $n): ?>
              <div style="padding: 12px 14px; background: #F8F6F0; border-radius: var(--admin-radius-sm); border: 1px solid var(--admin-card-border); font-size: 13px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 4px; color: var(--admin-text-muted); font-size: 12px;">
                  <strong style="color: var(--admin-forest);"><?php echo htmlspecialchars($n['admin_name'] ?: 'Staff'); ?></strong>
                  <span><?php echo date('d M Y, h:i A', strtotime($n['created_at'])); ?></span>
                </div>
                <div><?php echo nl2br(htmlspecialchars($n['note'])); ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </div>

  <!-- RIGHT COLUMN: Customer Details & Workflow Controls -->
  <div>
    <!-- Status Workflow Action Card -->
    <div class="admin-card">
      <div class="admin-card-header">
        <div class="admin-card-title">Manage Order Status</div>
      </div>
      <div class="admin-card-body">
        
        <form method="POST" style="margin-bottom: 20px;">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
          <input type="hidden" name="action" value="update_status">
          <div class="form-group">
            <label class="form-label">Order Fulfillment Status</label>
            <select name="order_status" class="form-control">
              <option value="pending" <?php echo $order['order_status'] === 'pending' ? 'selected' : ''; ?>>Pending Confirmation</option>
              <option value="confirmed" <?php echo $order['order_status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
              <option value="processing" <?php echo $order['order_status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
              <option value="shipped" <?php echo $order['order_status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
              <option value="delivered" <?php echo $order['order_status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
              <option value="cancelled" <?php echo $order['order_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>
          </div>
          <button type="submit" class="btn btn-primary" style="width: 100%;">Update Order Status</button>
        </form>

        <hr style="border: 0; border-top: 1px solid var(--admin-border-subtle); margin: 20px 0;">

        <form method="POST">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
          <input type="hidden" name="action" value="update_payment">
          <div class="form-group">
            <label class="form-label">Payment Status (COD Collection)</label>
            <select name="payment_status" class="form-control">
              <option value="pending" <?php echo $order['payment_status'] === 'pending' ? 'selected' : ''; ?>>Payment Pending (Uncollected)</option>
              <option value="paid" <?php echo $order['payment_status'] === 'paid' ? 'selected' : ''; ?>>Payment Paid / Collected</option>
              <option value="failed" <?php echo $order['payment_status'] === 'failed' ? 'selected' : ''; ?>>Payment Failed</option>
            </select>
            <div class="form-help">
              Mark as Paid only when COD cash has been received from the courier rider or bank payment verified.
            </div>
          </div>
          <button type="submit" class="btn btn-secondary" style="width: 100%;">Update Payment Status</button>
        </form>

      </div>
    </div>

    <!-- Customer Information Card -->
    <div class="admin-card">
      <div class="admin-card-header">
        <div class="admin-card-title">Customer Details</div>
      </div>
      <div class="admin-card-body" style="font-size: 13px; line-height: 1.6;">
        <div style="margin-bottom: 12px;">
          <div style="font-size: 11px; text-transform: uppercase; color: var(--admin-text-muted); font-weight: 600;">Customer Name</div>
          <div style="font-size: 15px; font-weight: 600; color: var(--admin-forest);">
            <?php echo htmlspecialchars($order['customer_name']); ?>
          </div>
        </div>

        <div style="margin-bottom: 12px;">
          <div style="font-size: 11px; text-transform: uppercase; color: var(--admin-text-muted); font-weight: 600;">Email Address</div>
          <div><a href="mailto:<?php echo htmlspecialchars($order['customer_email']); ?>" style="color: var(--admin-sage);"><?php echo htmlspecialchars($order['customer_email']); ?></a></div>
        </div>

        <div style="margin-bottom: 12px;">
          <div style="font-size: 11px; text-transform: uppercase; color: var(--admin-text-muted); font-weight: 600;">Phone Number</div>
          <div><a href="tel:<?php echo htmlspecialchars($order['customer_phone']); ?>" style="color: var(--admin-sage);"><?php echo htmlspecialchars($order['customer_phone'] ?: 'N/A'); ?></a></div>
        </div>

        <div style="margin-bottom: 12px;">
          <div style="font-size: 11px; text-transform: uppercase; color: var(--admin-text-muted); font-weight: 600;">Delivery Address</div>
          <div style="background: #F8F6F0; padding: 10px; border-radius: var(--admin-radius-sm); border: 1px solid var(--admin-card-border);">
            <?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?><br>
            <strong>City:</strong> <?php echo htmlspecialchars($order['city'] ?? ''); ?><br>
            <?php if (!empty($order['province'])): ?>
              <strong>Province:</strong> <?php echo htmlspecialchars($order['province']); ?><br>
            <?php endif; ?>
          </div>
        </div>

        <?php if (!empty($order['notes'])): ?>
          <div>
            <div style="font-size: 11px; text-transform: uppercase; color: var(--admin-text-muted); font-weight: 600;">Customer Delivery Notes</div>
            <div style="font-style: italic; color: #444;"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></div>
          </div>
        <?php endif; ?>
      </div>
    </div>

  </div>

</div>

<?php require_once __DIR__ . '/footer.php'; ?>
