<?php
/**
 * Zaffran Admin Panel — Orders Management
 */
define('ZAFFRAN_APP', true);
$pageTitle = 'Order Management';
require_once __DIR__ . '/header.php';

$db = getDbConnection();

// Filters & Search
$statusFilter = trim($_GET['status'] ?? '');
$searchQuery = trim($_GET['search'] ?? '');
$paymentFilter = trim($_GET['payment'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset = ($page - 1) * $perPage;

$whereClauses = [];
$params = [];

if (!empty($statusFilter)) {
    $whereClauses[] = "order_status = ?";
    $params[] = $statusFilter;
}

if (!empty($paymentFilter)) {
    $whereClauses[] = "payment_status = ?";
    $params[] = $paymentFilter;
}

if (!empty($searchQuery)) {
    $whereClauses[] = "(order_number LIKE ? OR customer_name LIKE ? OR customer_email LIKE ? OR customer_phone LIKE ?)";
    $like = '%' . $searchQuery . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$whereSql = !empty($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// Count total matching
$countStmt = $db->prepare("SELECT COUNT(*) FROM orders {$whereSql}");
$countStmt->execute($params);
$totalRecords = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

// Fetch orders with item count
$query = "SELECT o.*, (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as item_count 
          FROM orders o 
          {$whereSql} 
          ORDER BY o.id DESC 
          LIMIT {$perPage} OFFSET {$offset}";

$stmt = $db->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>

<!-- FILTERS & ACTIONS BAR -->
<div class="admin-card">
  <div class="admin-card-body" style="padding: 16px 20px;">
    <form method="GET" action="orders.php" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center;">
      
      <div style="flex: 1; min-width: 220px;">
        <input type="text" name="search" class="form-control" placeholder="Search by Order #, Name, Email or Phone..." value="<?php echo htmlspecialchars($searchQuery); ?>">
      </div>

      <div style="min-width: 160px;">
        <select name="status" class="form-control">
          <option value="">All Order Statuses</option>
          <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
          <option value="confirmed" <?php echo $statusFilter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
          <option value="processing" <?php echo $statusFilter === 'processing' ? 'selected' : ''; ?>>Processing</option>
          <option value="shipped" <?php echo $statusFilter === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
          <option value="delivered" <?php echo $statusFilter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
          <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
        </select>
      </div>

      <div style="min-width: 160px;">
        <select name="payment" class="form-control">
          <option value="">All Payment States</option>
          <option value="pending" <?php echo $paymentFilter === 'pending' ? 'selected' : ''; ?>>Payment Pending</option>
          <option value="paid" <?php echo $paymentFilter === 'paid' ? 'selected' : ''; ?>>Paid / Collected</option>
          <option value="failed" <?php echo $paymentFilter === 'failed' ? 'selected' : ''; ?>>Failed</option>
        </select>
      </div>

      <button type="submit" class="btn btn-primary">Filter Orders</button>
      
      <?php if (!empty($searchQuery) || !empty($statusFilter) || !empty($paymentFilter)): ?>
        <a href="orders.php" class="btn btn-secondary">Reset</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<!-- ORDERS TABLE -->
<div class="admin-card">
  <div class="admin-card-header">
    <div class="admin-card-title">
      Orders List 
      <span style="font-size: 13px; font-weight: normal; color: var(--admin-text-muted);">
        (<?php echo number_format($totalRecords); ?> total)
      </span>
    </div>
  </div>
  <div class="admin-card-body" style="padding: 0;">
    <div class="table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Order #</th>
            <th>Customer</th>
            <th>Date & Time</th>
            <th>Items</th>
            <th>Grand Total</th>
            <th>Payment</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($orders)): ?>
            <tr>
              <td colspan="8" style="text-align: center; padding: 36px; color: var(--admin-text-muted);">
                No matching orders found.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($orders as $o): ?>
              <tr>
                <td style="font-weight: 600;">
                  <a href="order-details.php?id=<?php echo $o['id']; ?>" style="color: var(--admin-forest); text-decoration: none;">
                    <?php echo htmlspecialchars($o['order_number']); ?>
                  </a>
                </td>
                <td>
                  <div style="font-weight: 600;"><?php echo htmlspecialchars($o['customer_name']); ?></div>
                  <div style="font-size: 12px; color: var(--admin-text-muted);"><?php echo htmlspecialchars($o['customer_phone'] ?: $o['customer_email']); ?></div>
                </td>
                <td style="font-size: 13px; color: var(--admin-text-muted);">
                  <?php echo date('d M Y, h:i A', strtotime($o['created_at'])); ?>
                </td>
                <td>
                  <span class="badge" style="background: #F0F2F1; color: var(--admin-forest);">
                    <?php echo (int)$o['item_count']; ?> item<?php echo $o['item_count'] > 1 ? 's' : ''; ?>
                  </span>
                </td>
                <td style="font-weight: 700; color: var(--admin-forest);">
                  PKR <?php echo number_format($o['grand_total'], 0); ?>
                </td>
                <td>
                  <div style="font-size: 11px; text-transform: uppercase; font-weight: 600; color: var(--admin-text-muted);">
                    <?php echo htmlspecialchars($o['payment_method'] ?? 'COD'); ?>
                  </div>
                  <span class="badge badge-<?php echo htmlspecialchars($o['payment_status']); ?>">
                    <?php echo htmlspecialchars($o['payment_status']); ?>
                  </span>
                </td>
                <td>
                  <span class="badge badge-<?php echo htmlspecialchars($o['order_status']); ?>">
                    <?php echo htmlspecialchars($o['order_status']); ?>
                  </span>
                </td>
                <td>
                  <a href="order-details.php?id=<?php echo $o['id']; ?>" class="btn btn-secondary btn-sm">
                    Manage &rarr;
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- PAGINATION -->
    <?php if ($totalPages > 1): ?>
      <div style="padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--admin-border-subtle);">
        <div style="font-size: 13px; color: var(--admin-text-muted);">
          Page <?php echo $page; ?> of <?php echo $totalPages; ?>
        </div>
        <div style="display: flex; gap: 6px;">
          <?php if ($page > 1): ?>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" class="btn btn-secondary btn-sm">&larr; Prev</a>
          <?php endif; ?>
          <?php if ($page < $totalPages): ?>
            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" class="btn btn-secondary btn-sm">Next &rarr;</a>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
