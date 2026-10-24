<?php
/**
 * Zaffran Admin Panel — Customer Management
 */
define('ZAFFRAN_APP', true);
$pageTitle = 'Customer Accounts';
require_once __DIR__ . '/header.php';

$db = getDbConnection();

$successMsg = '';
$errorMsg = '';

// Handle Status Change (Suspend / Activate)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateAdminCsrfToken($token)) {
        $errorMsg = 'Security validation failed.';
    } else {
        $userId = (int)($_POST['user_id'] ?? 0);
        $action = $_POST['action'] ?? '';

        if ($action === 'toggle_status' && $userId > 0) {
            $uStmt = $db->prepare("SELECT email, account_status FROM users WHERE id = ?");
            $uStmt->execute([$userId]);
            $u = $uStmt->fetch();

            if ($u) {
                $newStatus = ($u['account_status'] === 'suspended') ? 'active' : 'suspended';
                $db->prepare("UPDATE users SET account_status = ?, updated_at = NOW() WHERE id = ?")->execute([$newStatus, $userId]);
                logAdminAudit('toggle_customer_status', 'customer', (string)$userId, "Customer {$u['email']} status changed to {$newStatus}");
                $successMsg = "Customer account {$u['email']} is now {$newStatus}.";
            }
        }
    }
}

// Search
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $like = "%{$search}%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if (!empty($statusFilter)) {
    $where[] = "account_status = ?";
    $params[] = $statusFilter;
}

$whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $db->prepare("SELECT u.id, u.name, u.email, u.phone, u.account_status, u.created_at, 
                      (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) as order_count,
                      (SELECT COALESCE(SUM(grand_total), 0) FROM orders o WHERE o.user_id = u.id AND o.order_status != 'cancelled') as total_spend
                      FROM users u 
                      {$whereSql} 
                      ORDER BY u.id DESC");
$stmt->execute($params);
$customers = $stmt->fetchAll();
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

<!-- SEARCH BAR -->
<div class="admin-card">
  <div class="admin-card-body" style="padding: 16px 20px;">
    <form method="GET" action="customers.php" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center;">
      <div style="flex: 1; min-width: 240px;">
        <input type="text" name="search" class="form-control" placeholder="Search by name, email, or phone..." value="<?php echo htmlspecialchars($search); ?>">
      </div>

      <div style="min-width: 160px;">
        <select name="status" class="form-control">
          <option value="">All Account Statuses</option>
          <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
          <option value="suspended" <?php echo $statusFilter === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
        </select>
      </div>

      <button type="submit" class="btn btn-primary">Filter Customers</button>
      <?php if (!empty($search) || !empty($statusFilter)): ?>
        <a href="customers.php" class="btn btn-secondary">Reset</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<!-- CUSTOMERS TABLE -->
<div class="admin-card">
  <div class="admin-card-header">
    <div class="admin-card-title">Customer Accounts (<?php echo count($customers); ?>)</div>
  </div>
  <div class="admin-card-body" style="padding: 0;">
    <div class="table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Customer Name</th>
            <th>Verified Email</th>
            <th>Phone</th>
            <th>Registered On</th>
            <th>Total Orders</th>
            <th>Total Spend</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($customers)): ?>
            <tr>
              <td colspan="8" style="text-align: center; padding: 36px; color: var(--admin-text-muted);">
                No customers found.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($customers as $c): ?>
              <tr>
                <td>
                  <a href="customer-details.php?id=<?php echo $c['id']; ?>" style="font-weight: 600; color: var(--admin-forest); text-decoration: none;">
                    <?php echo htmlspecialchars($c['name']); ?>
                  </a>
                </td>
                <td>
                  <span style="font-family: monospace; font-size: 13px;"><?php echo htmlspecialchars($c['email']); ?></span>
                </td>
                <td style="color: var(--admin-text-muted); font-size: 13px;">
                  <?php echo htmlspecialchars($c['phone'] ?: '—'); ?>
                </td>
                <td style="font-size: 13px; color: var(--admin-text-muted);">
                  <?php echo date('M d, Y', strtotime($c['created_at'])); ?>
                </td>
                <td>
                  <span class="badge" style="background: #F0F2F1; color: var(--admin-forest);">
                    <?php echo (int)$c['order_count']; ?> orders
                  </span>
                </td>
                <td style="font-weight: 600; color: var(--admin-forest);">
                  PKR <?php echo number_format($c['total_spend'], 0); ?>
                </td>
                <td>
                  <span class="badge badge-<?php echo htmlspecialchars($c['account_status']); ?>">
                    <?php echo htmlspecialchars($c['account_status']); ?>
                  </span>
                </td>
                <td>
                  <div style="display: flex; gap: 6px;">
                    <a href="customer-details.php?id=<?php echo $c['id']; ?>" class="btn btn-secondary btn-sm">
                      Details
                    </a>
                    <form method="POST" onsubmit="return confirm('Change status for this account?');">
                      <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                      <input type="hidden" name="action" value="toggle_status">
                      <input type="hidden" name="user_id" value="<?php echo $c['id']; ?>">
                      <button type="submit" class="btn btn-secondary btn-sm">
                        <?php echo $c['account_status'] === 'suspended' ? 'Unsuspend' : 'Suspend'; ?>
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
