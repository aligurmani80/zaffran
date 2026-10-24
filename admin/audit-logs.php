<?php
/**
 * Zaffran Admin Panel — Audit Logs Viewer
 */
define('ZAFFRAN_APP', true);
$pageTitle = 'Security & Audit Logs';
require_once __DIR__ . '/header.php';

$db = getDbConnection();

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$actionFilter = trim($_GET['action'] ?? '');
$entityFilter = trim($_GET['entity'] ?? '');

$where = [];
$params = [];

if (!empty($actionFilter)) {
    $where[] = "action LIKE ?";
    $params[] = "%{$actionFilter}%";
}

if (!empty($entityFilter)) {
    $where[] = "entity_type = ?";
    $params[] = $entityFilter;
}

$whereSql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $db->prepare("SELECT COUNT(*) FROM admin_audit_logs {$whereSql}");
$countStmt->execute($params);
$totalLogs = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalLogs / $perPage);

$query = "SELECT * FROM admin_audit_logs {$whereSql} ORDER BY id DESC LIMIT {$perPage} OFFSET {$offset}";
$stmt = $db->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll();
?>

<!-- FILTER BAR -->
<div class="admin-card">
  <div class="admin-card-body" style="padding: 16px 20px;">
    <form method="GET" action="audit-logs.php" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center;">
      <div style="flex: 1; min-width: 200px;">
        <input type="text" name="action" class="form-control" placeholder="Search action keyword..." value="<?php echo htmlspecialchars($actionFilter); ?>">
      </div>

      <div style="min-width: 180px;">
        <select name="entity" class="form-control">
          <option value="">All Entities</option>
          <option value="admin" <?php echo $entityFilter === 'admin' ? 'selected' : ''; ?>>Admin Authentication</option>
          <option value="order" <?php echo $entityFilter === 'order' ? 'selected' : ''; ?>>Orders & Payments</option>
          <option value="product" <?php echo $entityFilter === 'product' ? 'selected' : ''; ?>>Products</option>
          <option value="category" <?php echo $entityFilter === 'category' ? 'selected' : ''; ?>>Categories</option>
          <option value="hero_slide" <?php echo $entityFilter === 'hero_slide' ? 'selected' : ''; ?>>Hero Slider</option>
          <option value="announcement" <?php echo $entityFilter === 'announcement' ? 'selected' : ''; ?>>Announcements</option>
          <option value="store_settings" <?php echo $entityFilter === 'store_settings' ? 'selected' : ''; ?>>Settings</option>
        </select>
      </div>

      <button type="submit" class="btn btn-primary">Filter Logs</button>
      <?php if (!empty($actionFilter) || !empty($entityFilter)): ?>
        <a href="audit-logs.php" class="btn btn-secondary">Reset</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<!-- AUDIT LOGS TABLE -->
<div class="admin-card">
  <div class="admin-card-header">
    <div class="admin-card-title">Audit Trail (<?php echo number_format($totalLogs); ?> entries)</div>
  </div>
  <div class="admin-card-body" style="padding: 0;">
    <div class="table-responsive">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Timestamp</th>
            <th>Operator</th>
            <th>Action</th>
            <th>Target Entity</th>
            <th>Safe Details Summary</th>
            <th>IP Address</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($logs)): ?>
            <tr>
              <td colspan="6" style="text-align: center; padding: 36px; color: var(--admin-text-muted);">
                No audit logs recorded yet.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($logs as $l): ?>
              <tr>
                <td style="font-size: 12px; color: var(--admin-text-muted); white-space: nowrap;">
                  <?php echo date('M d, Y H:i:s', strtotime($l['created_at'])); ?>
                </td>
                <td>
                  <span style="font-weight: 600; color: var(--admin-forest);">
                    <?php echo htmlspecialchars($l['admin_email'] ?: 'System'); ?>
                  </span>
                </td>
                <td>
                  <span class="badge" style="background: #EBF2EC; color: var(--admin-forest); font-family: monospace;">
                    <?php echo htmlspecialchars($l['action']); ?>
                  </span>
                </td>
                <td>
                  <span style="font-size: 12px; font-weight: 500;">
                    <?php echo htmlspecialchars($l['entity_type']); ?> 
                    <?php if (!empty($l['entity_id'])): ?>
                      #<?php echo htmlspecialchars($l['entity_id']); ?>
                    <?php endif; ?>
                  </span>
                </td>
                <td style="font-size: 13px; max-width: 380px;">
                  <?php echo htmlspecialchars($l['details'] ?: '—'); ?>
                </td>
                <td style="font-family: monospace; font-size: 12px; color: var(--admin-text-muted);">
                  <?php echo htmlspecialchars($l['ip_address'] ?: '127.0.0.1'); ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

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
