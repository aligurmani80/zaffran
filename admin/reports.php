<?php
/**
 * Zaffran Admin — Sales & Operations Reports
 * Real MySQL aggregations only. No estimated or fabricated figures.
 */
define('ZAFFRAN_APP', true);
require_once __DIR__ . '/auth.php';
requireAdminAuth();

$db = getDbConnection();

$today = date('Y-m-d');
$defaultFrom = date('Y-m-d', strtotime('-29 days'));
$fromInput = trim($_GET['from'] ?? $defaultFrom);
$toInput = trim($_GET['to'] ?? $today);

function parseReportDate(string $value): ?string {
    $dt = DateTime::createFromFormat('Y-m-d', $value);
    return ($dt && $dt->format('Y-m-d') === $value) ? $value : null;
}

$from = parseReportDate($fromInput) ?? $defaultFrom;
$to = parseReportDate($toInput) ?? $today;
if ($from > $to) {
    [$from, $to] = [$to, $from];
}

$export = trim($_GET['export'] ?? '');

$summaryStmt = $db->prepare("SELECT
        COUNT(*) AS total_orders,
        COALESCE(SUM(grand_total), 0) AS gross_sales,
        COALESCE(AVG(grand_total), 0) AS avg_order_value,
        COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN grand_total ELSE 0 END), 0) AS collected_revenue,
        COALESCE(SUM(CASE WHEN order_status = 'cancelled' THEN 1 ELSE 0 END), 0) AS cancelled_orders
    FROM orders
    WHERE DATE(created_at) BETWEEN ? AND ?");
$summaryStmt->execute([$from, $to]);
$summary = $summaryStmt->fetch() ?: [];

$nonCancelledStmt = $db->prepare("SELECT COUNT(*) AS cnt, COALESCE(SUM(grand_total), 0) AS gross, COALESCE(AVG(grand_total), 0) AS aov
    FROM orders WHERE DATE(created_at) BETWEEN ? AND ? AND order_status != 'cancelled'");
$nonCancelledStmt->execute([$from, $to]);
$netSummary = $nonCancelledStmt->fetch() ?: ['cnt' => 0, 'gross' => 0, 'aov' => 0];

$unitsStmt = $db->prepare("SELECT COALESCE(SUM(oi.quantity), 0) AS units
    FROM order_items oi
    INNER JOIN orders o ON o.id = oi.order_id
    WHERE o.order_status != 'cancelled' AND DATE(o.created_at) BETWEEN ? AND ?");
$unitsStmt->execute([$from, $to]);
$unitsSold = (int)$unitsStmt->fetchColumn();

$dailyStmt = $db->prepare("SELECT DATE(created_at) AS day, COUNT(*) AS orders, COALESCE(SUM(grand_total), 0) AS revenue
    FROM orders WHERE DATE(created_at) BETWEEN ? AND ? AND order_status != 'cancelled'
    GROUP BY DATE(created_at) ORDER BY day ASC");
$dailyStmt->execute([$from, $to]);
$dailyRows = $dailyStmt->fetchAll();

$weeklyStmt = $db->prepare("SELECT DATE_SUB(DATE(created_at), INTERVAL WEEKDAY(created_at) DAY) AS week_start,
        COUNT(*) AS orders, COALESCE(SUM(grand_total), 0) AS revenue
    FROM orders WHERE DATE(created_at) BETWEEN ? AND ? AND order_status != 'cancelled'
    GROUP BY week_start ORDER BY week_start DESC");
$weeklyStmt->execute([$from, $to]);
$weeklyRows = $weeklyStmt->fetchAll();

$monthlyStmt = $db->prepare("SELECT DATE_FORMAT(created_at, '%Y-%m') AS month_key,
        COUNT(*) AS orders, COALESCE(SUM(grand_total), 0) AS revenue
    FROM orders WHERE DATE(created_at) BETWEEN ? AND ? AND order_status != 'cancelled'
    GROUP BY month_key ORDER BY month_key DESC");
$monthlyStmt->execute([$from, $to]);
$monthlyRows = $monthlyStmt->fetchAll();

$productStmt = $db->prepare("SELECT oi.product_name,
        SUM(oi.quantity) AS units,
        SUM(oi.total_price) AS revenue,
        COUNT(DISTINCT oi.order_id) AS orders
    FROM order_items oi
    INNER JOIN orders o ON o.id = oi.order_id
    WHERE o.order_status != 'cancelled' AND DATE(o.created_at) BETWEEN ? AND ?
    GROUP BY oi.product_name ORDER BY revenue DESC");
$productStmt->execute([$from, $to]);
$productRows = $productStmt->fetchAll();

$statusStmt = $db->prepare("SELECT order_status, COUNT(*) AS cnt, COALESCE(SUM(grand_total), 0) AS revenue
    FROM orders WHERE DATE(created_at) BETWEEN ? AND ? GROUP BY order_status ORDER BY cnt DESC");
$statusStmt->execute([$from, $to]);
$statusRows = $statusStmt->fetchAll();

$paymentStmt = $db->prepare("SELECT payment_method, COUNT(*) AS cnt, COALESCE(SUM(grand_total), 0) AS revenue
    FROM orders WHERE DATE(created_at) BETWEEN ? AND ? AND order_status != 'cancelled'
    GROUP BY payment_method ORDER BY cnt DESC");
$paymentStmt->execute([$from, $to]);
$paymentRows = $paymentStmt->fetchAll();

$recentStmt = $db->prepare("SELECT id, order_number, customer_name, grand_total, order_status, payment_status, payment_method, created_at
    FROM orders WHERE DATE(created_at) BETWEEN ? AND ?
    ORDER BY created_at DESC LIMIT 15");
$recentStmt->execute([$from, $to]);
$recentOrders = $recentStmt->fetchAll();

$maxDailyRevenue = 0.0;
$chartRows = array_slice($dailyRows, -31);
foreach ($chartRows as $row) {
    $maxDailyRevenue = max($maxDailyRevenue, (float)$row['revenue']);
}

function csvSafe($value): string {
    $value = (string)$value;
    return preg_match('/^[=+\-@]/', $value) ? "'" . $value : $value;
}

if ($export !== '') {
    $filename = 'zaffran-report-' . $export . '-' . $from . '_to_' . $to . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");

    if ($export === 'orders') {
        $exportOrdersStmt = $db->prepare("SELECT order_number, created_at, customer_name, payment_method, order_status, payment_status, grand_total
            FROM orders WHERE DATE(created_at) BETWEEN ? AND ? ORDER BY created_at DESC");
        $exportOrdersStmt->execute([$from, $to]);
        $recentOrders = $exportOrdersStmt->fetchAll();
    }

    if ($export === 'products') {
        fputcsv($out, ['Product', 'Orders', 'Units Sold', 'Revenue (PKR)']);
        foreach ($productRows as $row) {
            fputcsv($out, [csvSafe($row['product_name']), (int)$row['orders'], (int)$row['units'], number_format((float)$row['revenue'], 2, '.', '')]);
        }
    } elseif ($export === 'daily') {
        fputcsv($out, ['Date', 'Orders', 'Revenue (PKR)']);
        foreach ($dailyRows as $row) {
            fputcsv($out, [$row['day'], (int)$row['orders'], number_format((float)$row['revenue'], 2, '.', '')]);
        }
    } else {
        fputcsv($out, ['Order Number', 'Date', 'Customer', 'Payment Method', 'Order Status', 'Payment Status', 'Total (PKR)']);
        foreach ($recentOrders as $row) {
            fputcsv($out, [
                csvSafe($row['order_number']),
                $row['created_at'],
                csvSafe($row['customer_name']),
                csvSafe($row['payment_method']),
                csvSafe($row['order_status']),
                csvSafe($row['payment_status']),
                number_format((float)$row['grand_total'], 2, '.', '')
            ]);
        }
    }

    logAdminAudit('export_report_csv', 'reports', null, "Exported {$export} report for {$from} to {$to}");
    fclose($out);
    exit;
}

$pageTitle = 'Sales & Operations Reports';
require_once __DIR__ . '/header.php';
$csrfToken = getAdminCsrfToken();

$periodLabel = date('d M Y', strtotime($from)) . ' — ' . date('d M Y', strtotime($to));
$statusLabels = [
    'pending' => 'Pending', 'confirmed' => 'Confirmed', 'processing' => 'Processing',
    'shipped' => 'Shipped', 'delivered' => 'Delivered', 'cancelled' => 'Cancelled',
];
$statusColors = [
    'pending' => 'var(--admin-warning)', 'confirmed' => 'var(--admin-info)', 'processing' => '#6D28D9',
    'shipped' => '#4338CA', 'delivered' => 'var(--admin-success)', 'cancelled' => 'var(--admin-danger)',
];
$paymentLabels = ['cod' => 'Cash on Delivery', 'bank_transfer' => 'Manual Bank Transfer'];
?>

<div class="report-toolbar">
  <form method="GET" class="report-filter-form">
    <div class="report-filter-field">
      <label for="from">From</label>
      <input type="date" id="from" name="from" value="<?php echo htmlspecialchars($from); ?>" max="<?php echo htmlspecialchars($today); ?>">
    </div>
    <div class="report-filter-field">
      <label for="to">To</label>
      <input type="date" id="to" name="to" value="<?php echo htmlspecialchars($to); ?>" max="<?php echo htmlspecialchars($today); ?>">
    </div>
    <button type="submit" class="btn btn-primary btn-sm">Apply Range</button>
  </form>

  <div class="report-presets">
    <a href="reports.php?from=<?php echo date('Y-m-d', strtotime('-6 days')); ?>&to=<?php echo $today; ?>" class="btn btn-secondary btn-sm">Last 7 days</a>
    <a href="reports.php?from=<?php echo $defaultFrom; ?>&to=<?php echo $today; ?>" class="btn btn-secondary btn-sm">Last 30 days</a>
    <a href="reports.php?from=<?php echo date('Y-m-d', strtotime('-89 days')); ?>&to=<?php echo $today; ?>" class="btn btn-secondary btn-sm">Last 90 days</a>
  </div>
</div>

<div class="report-period-line">
  <span>Reporting period: <strong><?php echo htmlspecialchars($periodLabel); ?></strong></span>
  <div class="report-export-group">
    <a href="reports.php?from=<?php echo urlencode($from); ?>&to=<?php echo urlencode($to); ?>&export=orders" class="btn btn-secondary btn-sm">Export Orders CSV</a>
    <a href="reports.php?from=<?php echo urlencode($from); ?>&to=<?php echo urlencode($to); ?>&export=products" class="btn btn-secondary btn-sm">Export Product Sales CSV</a>
    <a href="reports.php?from=<?php echo urlencode($from); ?>&to=<?php echo urlencode($to); ?>&export=daily" class="btn btn-secondary btn-sm">Export Daily CSV</a>
  </div>
</div>

<div class="kpi-grid">
  <div class="kpi-card accent-forest">
    <div class="kpi-header"><span class="kpi-title">Net Sales</span></div>
    <div class="kpi-val">PKR <?php echo number_format((float)$netSummary['gross'], 0); ?></div>
    <div class="kpi-sub">Excludes cancelled orders</div>
  </div>
  <div class="kpi-card accent-saffron">
    <div class="kpi-header"><span class="kpi-title">Orders</span></div>
    <div class="kpi-val"><?php echo number_format((int)$netSummary['cnt']); ?></div>
    <div class="kpi-sub"><?php echo (int)$summary['cancelled_orders']; ?> cancelled in period</div>
  </div>
  <div class="kpi-card">
    <div class="kpi-header"><span class="kpi-title">Average Order Value</span></div>
    <div class="kpi-val">PKR <?php echo number_format((float)$netSummary['aov'], 0); ?></div>
    <div class="kpi-sub">Across non-cancelled orders</div>
  </div>
  <div class="kpi-card accent-info">
    <div class="kpi-header"><span class="kpi-title">Units Sold</span></div>
    <div class="kpi-val"><?php echo number_format($unitsSold); ?></div>
    <div class="kpi-sub">Total item quantities</div>
  </div>
  <div class="kpi-card accent-forest">
    <div class="kpi-header"><span class="kpi-title">Verified Collections</span></div>
    <div class="kpi-val">PKR <?php echo number_format((float)$summary['collected_revenue'], 0); ?></div>
    <div class="kpi-sub">Only payment status = paid</div>
  </div>
</div>

<div class="report-grid">
  <div class="admin-card report-chart-card">
    <div class="admin-card-header">
      <div class="admin-card-title">Daily Sales Trend</div>
      <span class="report-muted-note">Revenue per day · non-cancelled orders</span>
    </div>
    <div class="admin-card-body">
      <?php if (empty($dailyRows)): ?>
        <div class="report-empty">No sales recorded in this period.</div>
      <?php else: ?>
        <div class="report-chart" role="img" aria-label="Daily revenue chart from <?php echo htmlspecialchars($from); ?> to <?php echo htmlspecialchars($to); ?>">
          <?php foreach ($chartRows as $row): ?>
            <?php
              $height = $maxDailyRevenue > 0 ? max(4, round(((float)$row['revenue'] / $maxDailyRevenue) * 100)) : 4;
              $dayLabel = date('d M', strtotime($row['day']));
            ?>
            <div class="report-bar-col" title="<?php echo htmlspecialchars($dayLabel . ' — Rs. ' . number_format((float)$row['revenue'], 0) . ' · ' . (int)$row['orders'] . ' orders'); ?>">
              <div class="report-bar" style="height: <?php echo $height; ?>%;"></div>
              <span class="report-bar-label"><?php echo date('d', strtotime($row['day'])); ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="admin-card">
    <div class="admin-card-header">
      <div class="admin-card-title">Order Status Breakdown</div>
    </div>
    <div class="admin-card-body">
      <?php if (empty($statusRows)): ?>
        <div class="report-empty">No orders in this period.</div>
      <?php else: ?>
        <?php
          $statusTotal = array_sum(array_map(fn($r) => (int)$r['cnt'], $statusRows));
        ?>
        <div class="report-breakdown">
          <?php foreach ($statusRows as $row): ?>
            <?php $pct = $statusTotal > 0 ? round(((int)$row['cnt'] / $statusTotal) * 100, 1) : 0; ?>
            <div class="report-breakdown-row">
              <div class="report-breakdown-head">
                <span><?php echo htmlspecialchars($statusLabels[$row['order_status']] ?? ucfirst($row['order_status'])); ?></span>
                <span><?php echo (int)$row['cnt']; ?> · <?php echo $pct; ?>%</span>
              </div>
              <div class="report-progress"><span style="width: <?php echo $pct; ?>%; background: <?php echo $statusColors[$row['order_status']] ?? 'var(--admin-info)'; ?>;"></span></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="report-grid">
  <div class="admin-card">
    <div class="admin-card-header">
      <div class="admin-card-title">Product-wise Sales</div>
      <span class="report-muted-note">From real order line items</span>
    </div>
    <div class="admin-card-body" style="padding: 0;">
      <div class="table-responsive">
        <table class="admin-table">
          <thead>
            <tr><th>Product</th><th>Orders</th><th>Units</th><th>Revenue (PKR)</th></tr>
          </thead>
          <tbody>
            <?php if (empty($productRows)): ?>
              <tr><td colspan="4" class="report-empty-cell">No product sales in this period.</td></tr>
            <?php else: ?>
              <?php foreach ($productRows as $row): ?>
                <tr>
                  <td style="font-weight: 600; color: var(--admin-forest);"><?php echo htmlspecialchars($row['product_name']); ?></td>
                  <td><?php echo (int)$row['orders']; ?></td>
                  <td><span class="badge badge-active"><?php echo (int)$row['units']; ?> units</span></td>
                  <td style="font-weight: 700;">Rs. <?php echo number_format((float)$row['revenue'], 0); ?></td>
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
      <div class="admin-card-title">Payment Methods</div>
    </div>
    <div class="admin-card-body">
      <?php if (empty($paymentRows)): ?>
        <div class="report-empty">No payment records in this period.</div>
      <?php else: ?>
        <table class="admin-table">
          <thead><tr><th>Method</th><th>Orders</th><th>Value</th></tr></thead>
          <tbody>
            <?php foreach ($paymentRows as $row): ?>
              <tr>
                <td style="font-weight: 600;"><?php echo htmlspecialchars($paymentLabels[$row['payment_method']] ?? ucfirst(str_replace('_', ' ', $row['payment_method']))); ?></td>
                <td><?php echo (int)$row['cnt']; ?></td>
                <td style="font-weight: 600;">Rs. <?php echo number_format((float)$row['revenue'], 0); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <p class="report-footnote">Values reflect placed orders, not verified collections. Only orders in the period are included.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="report-grid report-grid-sales">
  <div class="admin-card">
    <div class="admin-card-header"><div class="admin-card-title">Weekly Sales</div></div>
    <div class="admin-card-body" style="padding: 0;">
      <table class="admin-table">
        <thead><tr><th>Week Beginning</th><th>Orders</th><th>Revenue</th></tr></thead>
        <tbody>
          <?php if (empty($weeklyRows)): ?>
            <tr><td colspan="3" class="report-empty-cell">No weekly data.</td></tr>
          <?php else: ?>
            <?php foreach ($weeklyRows as $row): ?>
              <tr>
                <td><?php echo date('d M Y', strtotime($row['week_start'])); ?></td>
                <td><?php echo (int)$row['orders']; ?></td>
                <td style="font-weight: 600;">Rs. <?php echo number_format((float)$row['revenue'], 0); ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="admin-card">
    <div class="admin-card-header"><div class="admin-card-title">Monthly Sales</div></div>
    <div class="admin-card-body" style="padding: 0;">
      <table class="admin-table">
        <thead><tr><th>Month</th><th>Orders</th><th>Revenue</th></tr></thead>
        <tbody>
          <?php if (empty($monthlyRows)): ?>
            <tr><td colspan="3" class="report-empty-cell">No monthly data.</td></tr>
          <?php else: ?>
            <?php foreach ($monthlyRows as $row): ?>
              <tr>
                <td><?php echo date('F Y', strtotime($row['month_key'] . '-01')); ?></td>
                <td><?php echo (int)$row['orders']; ?></td>
                <td style="font-weight: 600;">Rs. <?php echo number_format((float)$row['revenue'], 0); ?></td>
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
    <div class="admin-card-title">Orders in Reporting Period</div>
    <a href="orders.php" class="btn btn-secondary btn-sm">Open Orders Manager &rarr;</a>
  </div>
  <div class="admin-card-body" style="padding: 0;">
    <div class="table-responsive">
      <table class="admin-table">
        <thead>
          <tr><th>Order #</th><th>Date</th><th>Customer</th><th>Payment</th><th>Total</th><th>Status</th></tr>
        </thead>
        <tbody>
          <?php if (empty($recentOrders)): ?>
            <tr><td colspan="6" class="report-empty-cell">No orders recorded in this period.</td></tr>
          <?php else: ?>
            <?php foreach ($recentOrders as $ord): ?>
              <tr>
                <td><a href="order-details.php?id=<?php echo (int)$ord['id']; ?>" style="font-weight: 600; color: var(--admin-forest); text-decoration: none;"><?php echo htmlspecialchars($ord['order_number']); ?></a></td>
                <td style="font-size: 12px; color: var(--admin-text-muted);"><?php echo date('d M Y, H:i', strtotime($ord['created_at'])); ?></td>
                <td><?php echo htmlspecialchars($ord['customer_name']); ?></td>
                <td style="font-size: 12px;"><?php echo htmlspecialchars($paymentLabels[$ord['payment_method']] ?? ucfirst($ord['payment_method'])); ?></td>
                <td style="font-weight: 600;">Rs. <?php echo number_format((float)$ord['grand_total'], 0); ?></td>
                <td><span class="badge badge-<?php echo htmlspecialchars($ord['order_status']); ?>"><?php echo htmlspecialchars($ord['order_status']); ?></span></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <p class="report-footnote" style="padding: 12px 20px 0;">Table shows the 15 most recent orders in the selected period. Download CSV for the full period.</p>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
