<?php
/**
 * Zaffran Admin Panel — Administrator Accounts Management (Super Admin Only)
 */
define('ZAFFRAN_APP', true);
require_once __DIR__ . '/auth.php';

$currentAdmin = requireAdminAuth('super_admin');
$db = getDbConnection();

$successMsg = '';
$errorMsg = '';

// Handle Create / Status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateAdminCsrfToken($token)) {
        $errorMsg = 'Security validation failed.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'create_admin') {
            $username = trim($_POST['username'] ?? '');
            $email = strtolower(trim($_POST['email'] ?? ''));
            $role = in_array($_POST['role'] ?? '', ['super_admin', 'order_manager', 'staff']) ? $_POST['role'] : 'staff';
            $password = $_POST['password'] ?? '';

            if (empty($username) || empty($email) || empty($password)) {
                $errorMsg = 'All fields are required to create an administrative user.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errorMsg = 'Invalid email address provided.';
            } elseif (strlen($password) < 8) {
                $errorMsg = 'Password must be at least 8 characters long.';
            } else {
                // Check if email already exists
                $check = $db->prepare("SELECT id FROM admins WHERE email = ?");
                $check->execute([$email]);
                if ($check->fetch()) {
                    $errorMsg = 'An administrator with this email already exists.';
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $ins = $db->prepare("INSERT INTO admins (username, email, role, status, password_hash, created_at, updated_at) VALUES (?, ?, ?, 'active', ?, NOW(), NOW())");
                    $ins->execute([$username, $email, $role, $hash]);

                    logAdminAudit('create_admin', 'admin', (string)$db->lastInsertId(), "Created admin account '{$email}' with role '{$role}'");
                    $successMsg = "Administrator account for {$email} created successfully.";
                }
            }
        } elseif ($action === 'toggle_status') {
            $targetId = (int)($_POST['target_id'] ?? 0);
            if ($targetId === (int)$currentAdmin['id']) {
                $errorMsg = 'You cannot suspend your own active administrator account.';
            } else {
                $aStmt = $db->prepare("SELECT email, status FROM admins WHERE id = ?");
                $aStmt->execute([$targetId]);
                $a = $aStmt->fetch();
                if ($a) {
                    $newStatus = ($a['status'] === 'suspended') ? 'active' : 'suspended';
                    $db->prepare("UPDATE admins SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$newStatus, $targetId]);
                    logAdminAudit('toggle_admin_status', 'admin', (string)$targetId, "Admin account {$a['email']} status changed to {$newStatus}");
                    $successMsg = "Admin account {$a['email']} is now {$newStatus}.";
                }
            }
        }
    }
}

// Fetch all admins
$admins = $db->query("SELECT id, username, email, role, status, last_login_at, created_at FROM admins ORDER BY id ASC")->fetchAll();
$pageTitle = 'Administrator Accounts';
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

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
  
  <!-- Admins List -->
  <div class="admin-card">
    <div class="admin-card-header">
      <div class="admin-card-title">Authorized Administrators (<?php echo count($admins); ?>)</div>
    </div>
    <div class="admin-card-body" style="padding: 0;">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Username</th>
            <th>Email</th>
            <th>Role</th>
            <th>Last Login</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($admins as $ad): ?>
            <tr>
              <td style="font-weight: 600; color: var(--admin-forest);">
                <?php echo htmlspecialchars($ad['username']); ?>
                <?php if ((int)$ad['id'] === (int)$currentAdmin['id']): ?>
                  <span class="badge" style="background: var(--admin-saffron-light); color: var(--admin-saffron-dark); font-size: 10px;">You</span>
                <?php endif; ?>
              </td>
              <td style="font-family: monospace; font-size: 13px;">
                <?php echo htmlspecialchars($ad['email']); ?>
              </td>
              <td>
                <span class="badge" style="background: #EBF2EC; color: var(--admin-forest); text-transform: capitalize;">
                  <?php echo str_replace('_', ' ', htmlspecialchars($ad['role'])); ?>
                </span>
              </td>
              <td style="font-size: 12px; color: var(--admin-text-muted);">
                <?php echo $ad['last_login_at'] ? date('M d, Y H:i', strtotime($ad['last_login_at'])) : 'Never'; ?>
              </td>
              <td>
                <span class="badge badge-<?php echo htmlspecialchars($ad['status']); ?>">
                  <?php echo htmlspecialchars($ad['status']); ?>
                </span>
              </td>
              <td>
                <?php if ((int)$ad['id'] !== (int)$currentAdmin['id']): ?>
                  <form method="POST" onsubmit="return confirm('Change status of this administrator?');">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="action" value="toggle_status">
                    <input type="hidden" name="target_id" value="<?php echo $ad['id']; ?>">
                    <button type="submit" class="btn btn-secondary btn-sm">
                      <?php echo $ad['status'] === 'suspended' ? 'Activate' : 'Suspend'; ?>
                    </button>
                  </form>
                <?php else: ?>
                  <span style="font-size: 12px; color: var(--admin-text-muted);">Current</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Create New Admin Form -->
  <div class="admin-card">
    <div class="admin-card-header">
      <div class="admin-card-title">Add New Administrator</div>
    </div>
    <div class="admin-card-body">
      
      <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="action" value="create_admin">

        <div class="form-group">
          <label class="form-label" for="username">Username <span class="required">*</span></label>
          <input type="text" id="username" name="username" class="form-control" required placeholder="E.g. staff_ali">
        </div>

        <div class="form-group">
          <label class="form-label" for="email">Admin Email <span class="required">*</span></label>
          <input type="email" id="email" name="email" class="form-control" required placeholder="ali@zaffran.local">
        </div>

        <div class="form-group">
          <label class="form-label" for="role">Assigned Role <span class="required">*</span></label>
          <select id="role" name="role" class="form-control" required>
            <option value="staff">Staff (Operational View & Orders)</option>
            <option value="order_manager">Order Manager (Full Order Lifecycle)</option>
            <option value="super_admin">Super Admin (All Store Settings & Accounts)</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="password">Strong Password <span class="required">*</span></label>
          <input type="password" id="password" name="password" class="form-control" minlength="8" required placeholder="Minimum 8 characters">
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 8px;">Create Administrator</button>
      </form>

    </div>
  </div>

</div>

<?php require_once __DIR__ . '/footer.php'; ?>
