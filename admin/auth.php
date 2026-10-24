<?php
/**
 * Zaffran E-Commerce — Phase 5 Admin Core & Security Helper
 * Handles Admin Authentication, RBAC, CSRF, Audit Logs, and Safe Sessions
 */

if (!defined('ZAFFRAN_APP')) {
    define('ZAFFRAN_APP', true);
}

require_once dirname(__DIR__) . '/config/database.php';

/**
 * Initialize isolated admin session
 */
function initAdminSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        $lifetime = (int)env('SESSION_LIFETIME', 86400);
        $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
                    (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);

        if (!headers_sent()) {
            session_set_cookie_params([
                'lifetime' => $lifetime,
                'path'     => '/zaffran/admin/',
                'domain'   => '',
                'secure'   => $isSecure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            session_name('zaffran_admin_sess');
        }
        @session_start();
    }

    // Initialize admin CSRF token if missing
    if (empty($_SESSION['admin_csrf_token'])) {
        $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
    }
}

/**
 * Validate admin CSRF token
 */
function validateAdminCsrfToken(?string $token): bool {
    if (empty($token) || empty($_SESSION['admin_csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['admin_csrf_token'], $token);
}

/**
 * Get current admin CSRF token
 */
function getAdminCsrfToken(): string {
    initAdminSession();
    return $_SESSION['admin_csrf_token'] ?? '';
}

/**
 * Verify if admin is logged in
 */
function isAdminLoggedIn(): bool {
    initAdminSession();
    return !empty($_SESSION['admin_id']) && !empty($_SESSION['admin_email']);
}

/**
 * Require admin authentication
 */
function requireAdminAuth(?string $requiredRole = null): array {
    initAdminSession();
    if (!isAdminLoggedIn()) {
        header('Location: login.php');
        exit;
    }

    $admin = [
        'id'       => (int)$_SESSION['admin_id'],
        'username' => $_SESSION['admin_username'] ?? 'Admin',
        'email'    => $_SESSION['admin_email'],
        'role'     => $_SESSION['admin_role'] ?? 'staff'
    ];

    // If specific role required (e.g. 'super_admin')
    if ($requiredRole === 'super_admin' && $admin['role'] !== 'super_admin') {
        http_response_code(403);
        include __DIR__ . '/header.php';
        echo '<div class="admin-content-card"><div class="notice-box notice-error"><h3>Access Denied</h3><p>You do not have Super Admin permissions to access this page.</p><a href="index.php" class="btn btn-secondary mt-3">Return to Dashboard</a></div></div>';
        include __DIR__ . '/footer.php';
        exit;
    }

    return $admin;
}

/**
 * Check if admin is Super Admin
 */
function isSuperAdmin(): bool {
    initAdminSession();
    return ($_SESSION['admin_role'] ?? '') === 'super_admin';
}

/**
 * Record an entry into admin_audit_logs
 */
function logAdminAudit(string $action, string $entityType, ?string $entityId = null, ?string $details = null): void {
    try {
        $db = getDbConnection();
        if (!$db) return;

        initAdminSession();
        $adminId = $_SESSION['admin_id'] ?? null;
        $adminEmail = $_SESSION['admin_email'] ?? 'system';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250);

        $stmt = $db->prepare("INSERT INTO admin_audit_logs (admin_id, admin_email, action, entity_type, entity_id, details, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$adminId, $adminEmail, $action, $entityType, $entityId, $details, $ip, $ua]);
    } catch (Exception $e) {
        error_log("Audit log failed: " . $e->getMessage());
    }
}

/**
 * Rate limit admin login attempts
 */
function checkAdminLoginRateLimit(string $ip): bool {
    $db = getDbConnection();
    if (!$db) return true;

    try {
        $cutoff = date('Y-m-d H:i:s', time() - 900); // 15 min window
        $stmtClean = $db->prepare("DELETE FROM auth_rate_limits WHERE last_attempt < ?");
        $stmtClean->execute([$cutoff]);

        $key = 'admin_login_' . md5($ip);
        $stmt = $db->prepare("SELECT attempts FROM auth_rate_limits WHERE rate_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $row = $stmt->fetch();

        if ($row && (int)$row['attempts'] >= 5) {
            return false; // locked
        }
        return true;
    } catch (Exception $e) {
        return true;
    }
}

function recordAdminLoginFailure(string $ip): void {
    $db = getDbConnection();
    if (!$db) return;

    try {
        $key = 'admin_login_' . md5($ip);
        $now = date('Y-m-d H:i:s');
        $stmt = $db->prepare("SELECT attempts FROM auth_rate_limits WHERE rate_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $row = $stmt->fetch();

        if ($row) {
            $stmtUpd = $db->prepare("UPDATE auth_rate_limits SET attempts = attempts + 1, last_attempt = ? WHERE rate_key = ?");
            $stmtUpd->execute([$now, $key]);
        } else {
            $stmtIns = $db->prepare("INSERT INTO auth_rate_limits (rate_key, attempts, last_attempt) VALUES (?, 1, ?)");
            $stmtIns->execute([$key, $now]);
        }
    } catch (Exception $e) {}
}

function resetAdminLoginRateLimit(string $ip): void {
    $db = getDbConnection();
    if (!$db) return;

    try {
        $key = 'admin_login_' . md5($ip);
        $stmt = $db->prepare("DELETE FROM auth_rate_limits WHERE rate_key = ?");
        $stmt->execute([$key]);
    } catch (Exception $e) {}
}

/**
 * Secure image upload handler
 */
function handleImageUpload(array $file, string $subfolder = 'products'): array {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'error' => 'Invalid file parameter.'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errMap = [
            UPLOAD_ERR_INI_SIZE   => 'File exceeds upload_max_filesize limit.',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds form MAX_FILE_SIZE.',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        ];
        return ['success' => false, 'error' => $errMap[$file['error']] ?? 'Unknown upload error.'];
    }

    // Limit to 5MB
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'error' => 'File size exceeds maximum allowed 5MB.'];
    }

    // Validate MIME type via finfo
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    if (!array_key_exists($mime, $allowedMimes)) {
        return ['success' => false, 'error' => 'Only JPG, PNG, and WebP images are allowed.'];
    }

    // Verify image integrity with getimagesize
    $imgInfo = @getimagesize($file['tmp_name']);
    if ($imgInfo === false) {
        return ['success' => false, 'error' => 'Corrupt or invalid image file.'];
    }

    $ext = $allowedMimes[$mime];
    $filename = bin2hex(random_bytes(16)) . '.' . $ext;

    $baseUploadDir = dirname(__DIR__) . '/assets/uploads/' . trim($subfolder, '/');
    if (!is_dir($baseUploadDir)) {
        mkdir($baseUploadDir, 0755, true);
    }

    $targetPath = $baseUploadDir . '/' . $filename;
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => false, 'error' => 'Failed to move uploaded file.'];
    }

    $publicPath = 'assets/uploads/' . trim($subfolder, '/') . '/' . $filename;
    return ['success' => true, 'path' => $publicPath, 'filename' => $filename];
}

/**
 * Get pending orders count for admin badge
 */
function getPendingOrdersCount(): int {
    try {
        $db = getDbConnection();
        if (!$db) return 0;
        return (int)$db->query("SELECT COUNT(*) FROM orders WHERE order_status = 'pending'")->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}
