<?php
/**
 * Zaffran Admin Panel — Customer Details & Order History
 */
define('ZAFFRAN_APP', true);
require_once __DIR__ . '/auth.php';

$currentAdmin = requireAdminAuth();
$db = getDbConnection();

$userId = (int)($_GET['id'] ?? 0);
if ($userId <= 0) {
    header('Location: customers.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$userId]);
$customer = $stmt->fetch();

if (!$customer) {
    header('Location: customers.php');
    exit;
}

// Fetch Customer's Orders
$orderStmt = $db->prepare("SELECT * FROM orders WHERE user_id = ? OR customer_email = ? ORDER BY id DESC");
$orderStmt->execute([$userId, $customer['email']]);
$customerOrders = $orderStmt->fetchAll();

// Saved Addresses
$addrStmt = $db->prepare("SELECT * FROM customer_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC");
$addrStmt->execute([$userId]);
$addresses = $addrStmt->fetchAll();

$pageTitle = "Customer: {$customer['name']}";
require_once __DIR__ . '/header.php';
?>

<div style="margin-bottom: 20px;">
  <a href="customers.php" style="color: var(--admin-sage); text-decoration: none; font-size: 13px;">&larr; Back to Customers</a>
  <h2 style="font-size: 24px; color: var(--admin-forest); margin-top: 4px;">Customer Account Details</h2>
</div>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px;">
  
  <!-- Customer Profile Card -->
  <div>
    <div class="admin-card">
      <div class="admin-card-header">
        <div class="admin-card-title">Profile Summary</div>
      </div>
      <div class="admin-card-body" style="font-size: 14px; line-height: 1.6;">
        <div style="text-align: center; margin-bottom: 20px;">
          <div style="width: 64px; height: 64px; border-radius: 50%; background: var(--admin-sage-light); color: var(--admin-forest); display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: 700; margin: 0 auto 10px;">
            <?php echo strtoupper(substr($customer['name'] ?: 'C', 0, 1)); ?>
          </div>
          <div style="font-size: 18px; font-weight: 700; color: var(--admin-forest);"><?php echo htmlspecialchars($customer['name']); ?></div>
          <div style="color: var(--admin-text-muted); font-size: 13px;"><?php echo htmlspecialchars($customer['email']); ?></div>
          <div style="margin-top: 8px;">
            <span class="badge badge-<?php echo htmlspecialchars($customer['account_status']); ?>">
              Account: <?php echo htmlspecialchars($customer['account_status']); ?>
            </span>
          </div>
        </div>

        <div style="border-top: 1px solid var(--admin-border-subtle); padding-top: 14px;">
          <div style="font-size: 11px; text-transform: uppercase; color: var(--admin-text-muted); font-weight: 600;">Contact Phone</div>
          <div><?php echo htmlspecialchars($customer['phone'] ?: 'None provided'); ?></div>
        </div>

        <div style="margin-top: 12px;">
          <div style="font-size: 11px; text-transform: uppercase; color: var(--admin-text-muted); font-weight: 600;">Registered Date</div>
          <div><?php echo date('F d, Y h:i A', strtotime($customer['created_at'])); ?></div>
        </div>

        <div style="margin-top: 12px;">
          <div style="font-size: 11px; text-transform: uppercase; color: var(--admin-text-muted); font-weight: 600;">Email Verification</div>
          <div>
            <?php if (!empty($customer['email_verified_at'])): ?>
              <span style="color: var(--admin-success); font-weight: 600;">&check; Verified (<?php echo date('M d, Y', strtotime($customer['email_verified_at'])); ?>)</span>
            <?php else: ?>
              <span style="color: var(--admin-warning); font-weight: 600;">Pending verification</span>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Order History -->
  <div>
    <div class="admin-card">
      <div class="admin-card-header">
        <div class="admin-card-title">Order History (<?php echo count($customerOrders); ?>)</div>
      </div>
      <div class="admin-card-body" style="padding: 0;">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Order #</th>
              <th>Date</th>
              <th>Total</th>
              <th>Order Status</th>
              <th>Payment</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($customerOrders)): ?>
              <tr>
                <td colspan="6" style="text-align: center; padding: 24px; color: var(--admin-text-muted);">
                  No orders placed by this customer yet.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($customerOrders as $co): ?>
                <tr>
                  <td style="font-weight: 600;">
                    <a href="order-details.php?id=<?php echo $co['id']; ?>" style="color: var(--admin-forest); text-decoration: none;">
                      <?php echo htmlspecialchars($co['order_number']); ?>
                    </a>
                  </td>
                  <td style="font-size: 13px; color: var(--admin-text-muted);">
                    <?php echo date('M d, Y', strtotime($co['created_at'])); ?>
                  </td>
                  <td style="font-weight: 600;">PKR <?php echo number_format($co['grand_total'], 0); ?></td>
                  <td>
                    <span class="badge badge-<?php echo htmlspecialchars($co['order_status']); ?>">
                      <?php echo htmlspecialchars($co['order_status']); ?>
                    </span>
                  </td>
                  <td>
                    <span class="badge badge-<?php echo htmlspecialchars($co['payment_status']); ?>">
                      <?php echo htmlspecialchars($co['payment_status']); ?>
                    </span>
                  </td>
                  <td>
                    <a href="order-details.php?id=<?php echo $co['id']; ?>" class="btn btn-secondary btn-sm">View &rarr;</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/footer.php'; ?>
