<?php
/**
 * Zaffran Admin Panel — Main Dashboard (Real MySQL Metrics)
 */
define('ZAFFRAN_APP', true);
$pageTitle = 'Overview Dashboard';
require_once __DIR__ . '/header.php';

$db = getDbConnection();

// Real Metrics from Database
// 1. Order Status Counts
$orderCountsStmt = $db->query("SELECT order_status, COUNT(*) as cnt FROM orders GROUP BY order_status");
$statusCounts = [];
$totalOrders = 0;
while ($r = $orderCountsStmt->fetch()) {
    $statusCounts[$r['order_status']] = (int)$r['cnt'];
    $totalOrders += (int)$r['cnt'];
}

$pendingCount = $statusCounts['pending'] ?? 0;
$confirmedCount = $statusCounts['confirmed'] ?? 0;
$deliveredCount = $statusCounts['delivered'] ?? 0;
$processingCount = $statusCounts['processing'] ?? 0;
$shippedCount = $statusCounts['shipped'] ?? 0;
$cancelledCount = $statusCounts['cancelled'] ?? 0;

// 2. Gross Order Value vs Collected Revenue
// Collected revenue ONLY includes orders explicitly marked as payment_status = 'paid'
$grossValue = (float)$db->query("SELECT COALESCE(SUM(grand_total), 0) FROM orders WHERE order_status != 'cancelled'")->fetchColumn();
$collectedRevenue = (float)$db->query("SELECT COALESCE(SUM(grand_total), 0) FROM orders WHERE payment_status = 'paid'")->fetchColumn();

// 3. Customer & Product counts
$customerCount = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$activeProductsCount = (int)$db->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn();

// 4. Recent Orders (Last 5)
$recentOrders = $db->query("SELECT id, order_number, customer_name, customer_email, grand_total, order_status, payment_status, created_at FROM orders ORDER BY id DESC LIMIT 5")->fetchAll();

// 5. Recent Customers (Last 5)
$recentCustomers = $db->query("SELECT id, name, email, phone, account_status, created_at FROM users ORDER BY id DESC LIMIT 5")->fetchAll();

// 6. Top Selling Products based on actual order_items
$topProducts = $db->query("SELECT product_name, SUM(quantity) as total_qty, SUM(total_price) as total_sales FROM order_items GROUP BY product_name ORDER BY total_qty DESC LIMIT 5")->fetchAll();

// 7. Orders per day for the last 7 days (Real SQL aggregation)
$dailyOrders = $db->query("SELECT DATE(created_at) as order_date, COUNT(*) as cnt, COALESCE(SUM(grand_total), 0) as daily_total FROM orders GROUP BY DATE(created_at) ORDER BY order_date DESC LIMIT 7")->fetchAll();
?>

<!-- KPI CARDS -->
<div class="kpi-grid">
  <div class="kpi-card accent-forest">
    <div class="kpi-header">
      <span class="kpi-title">Total Orders</span>
      <div class="kpi-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line></svg>
      </div>
    </div>
    <div class="kpi-val"><?php echo number_format($totalOrders); ?></div>
    <div class="kpi-sub">Lifetime orders placed</div>
  </div>

  <div class="kpi-card accent-saffron">
    <div class="kpi-header">
      <span class="kpi-title">Pending Orders</span>
      <div class="kpi-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
      </div>
    </div>
    <div class="kpi-val"><?php echo number_format($pendingCount); ?></div>
    <div class="kpi-sub">Awaiting confirmation</div>
  </div>

  <div class="kpi-card">
    <div class="kpi-header">
      <span class="kpi-title">Delivered Orders</span>
      <div class="kpi-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
      </div>
    </div>
    <div class="kpi-val"><?php echo number_format($deliveredCount); ?></div>
    <div class="kpi-sub">Successfully fulfilled</div>
  </div>

  <div class="kpi-card accent-info">
    <div class="kpi-header">
      <span class="kpi-title">Active Products</span>
      <div class="kpi-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 2 7 12 12 22 7 12 2"></polygon><polyline points="2 17 12 22 22 17"></polyline><polyline points="2 12 12 17 22 12"></polyline></svg>
      </div>
    </div>
    <div class="kpi-val"><?php echo number_format($activeProductsCount); ?></div>
    <div class="kpi-sub">Listed in store catalog</div>
  </div>

  <div class="kpi-card accent-forest">
    <div class="kpi-header">
      <span class="kpi-title">Gross Order Value</span>
      <div class="kpi-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
      </div>
    </div>
    <div class="kpi-val">PKR <?php echo number_format($grossValue, 0); ?></div>
    <div class="kpi-sub">Total non-cancelled orders</div>
  </div>

  <div class="kpi-card accent-saffron">
    <div class="kpi-header">
      <span class="kpi-title">Collected Revenue</span>
      <div class="kpi-icon">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"></rect><line x1="1" y1="10" x2="23" y2="10"></line></svg>
      </div>
    </div>
    <div class="kpi-val">PKR <?php echo number_format($collectedRevenue, 0); ?></div>
    <div class="kpi-sub">Actual verified payments</div>
  </div>
</div>

<!-- CHARTS & DISTRIBUTION SECTION -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px; margin-bottom: 24px;">
  <!-- Order Status Breakdown -->
  <div class="admin-card" style="margin-bottom: 0;">
    <div class="admin-card-header">
      <div class="admin-card-title">Order Status Distribution</div>
    </div>
    <div class="admin-card-body">
      <?php if ($totalOrders === 0): ?>
        <p style="color: var(--admin-text-muted); font-size: 13px;">No orders recorded yet.</p>
      <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 12px;">
          <?php 
          $statuses = [
            'pending' => ['label' => 'Pending Confirmation', 'color' => 'var(--admin-warning)', 'count' => $pendingCount],
            'confirmed' => ['label' => 'Confirmed', 'color' => 'var(--admin-info)', 'count' => $confirmedCount],
            'processing' => ['label' => 'Processing', 'color' => '#6D28D9', 'count' => $processingCount],
            'shipped' => ['label' => 'Shipped', 'color' => '#4338CA', 'count' => $shippedCount],
            'delivered' => ['label' => 'Delivered', 'color' => 'var(--admin-success)', 'count' => $deliveredCount],
            'cancelled' => ['label' => 'Cancelled', 'color' => 'var(--admin-danger)', 'count' => $cancelledCount],
          ];
          foreach ($statuses as $k => $item):
            $pct = $totalOrders > 0 ? round(($item['count'] / $totalOrders) * 100, 1) : 0;
          ?>
            <div>
              <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 4px;">
                <span style="font-weight: 500; color: var(--admin-text-main);"><?php echo $item['label']; ?></span>
                <span style="color: var(--admin-text-muted); font-weight: 600;"><?php echo $item['count']; ?> (<?php echo $pct; ?>%)</span>
              </div>
              <div style="height: 8px; background: #EEE; border-radius: 4px; overflow: hidden;">
                <div style="height: 100%; width: <?php echo $pct; ?>%; background: <?php echo $item['color']; ?>; border-radius: 4px;"></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Top Selling Products -->
  <div class="admin-card" style="margin-bottom: 0;">
    <div class="admin-card-header">
      <div class="admin-card-title">Top Selling Products</div>
    </div>
    <div class="admin-card-body">
      <?php if (empty($topProducts)): ?>
        <p style="color: var(--admin-text-muted); font-size: 13px;">No product order items recorded yet.</p>
      <?php else: ?>
        <table class="admin-table">
          <thead>
            <tr>
              <th>Product</th>
              <th>Qty Sold</th>
              <th>Total (PKR)</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($topProducts as $tp): ?>
              <tr>
                <td style="font-weight: 600; color: var(--admin-forest);"><?php echo htmlspecialchars($tp['product_name']); ?></td>
                <td><span class="badge badge-active"><?php echo (int)$tp['total_qty']; ?> units</span></td>
                <td style="font-weight: 600;">Rs. <?php echo number_format($tp['total_sales'], 0); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- RECENT ORDERS TABLE -->
<div class="admin-card">
  <div class="admin-card-header">
    <div class="admin-card-title">Recent Customer Orders</div>
    <a href="orders.php" class="btn btn-secondary btn-sm">View All Orders &rarr;</a>
  </div>
  <div class="admin-card-body" style="padding: 0;">
    <div class="table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Order #</th>
            <th>Customer</th>
            <th>Date</th>
            <th>Total (PKR)</th>
            <th>Order Status</th>
            <th>Payment Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($recentOrders)): ?>
            <tr>
              <td colspan="7" style="text-align: center; padding: 24px; color: var(--admin-text-muted);">No orders found.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($recentOrders as $ord): ?>
              <tr>
                <td style="font-weight: 600; color: var(--admin-forest);">
                  <a href="order-details.php?id=<?php echo $ord['id']; ?>" style="color: inherit; text-decoration: none;">
                    <?php echo htmlspecialchars($ord['order_number']); ?>
                  </a>
                </td>
                <td>
                  <div style="font-weight: 500;"><?php echo htmlspecialchars($ord['customer_name']); ?></div>
                  <div style="font-size: 12px; color: var(--admin-text-muted);"><?php echo htmlspecialchars($ord['customer_email']); ?></div>
                </td>
                <td style="font-size: 13px; color: var(--admin-text-muted);">
                  <?php echo date('M d, Y H:i', strtotime($ord['created_at'])); ?>
                </td>
                <td style="font-weight: 700; color: var(--admin-forest);">
                  Rs. <?php echo number_format($ord['grand_total'], 0); ?>
                </td>
                <td>
                  <span class="badge badge-<?php echo htmlspecialchars($ord['order_status']); ?>">
                    <?php echo htmlspecialchars($ord['order_status']); ?>
                  </span>
                </td>
                <td>
                  <span class="badge badge-<?php echo htmlspecialchars($ord['payment_status']); ?>">
                    <?php echo htmlspecialchars($ord['payment_status']); ?>
                  </span>
                </td>
                <td>
                  <a href="order-details.php?id=<?php echo $ord['id']; ?>" class="btn btn-secondary btn-sm">
                    Details
                  </a>
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
