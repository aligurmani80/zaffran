<?php
/**
 * CLI Tool: Create Initial Administrator Account Securely
 * Usage from command line:
 * C:\xampp\php\php.exe cli_create_admin.php
 */

if (php_sapi_name() !== 'cli') {
    die("This script can ONLY be executed via the command line interface (CLI).\n");
}

require_once __DIR__ . '/config/database.php';
$db = getDbConnection();

echo "====================================================\n";
echo "   ZAFFRAN BOTANICAL STORE — ADMIN PROVISIONING     \n";
echo "====================================================\n\n";

// Prompt for username
echo "Enter Administrator Username (e.g. superadmin): ";
$username = trim(fgets(STDIN));
if (empty($username)) {
    die("Error: Username cannot be empty.\n");
}

// Prompt for email
echo "Enter Administrator Email (e.g. admin@zaffran.local): ";
$email = strtolower(trim(fgets(STDIN)));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("Error: Invalid email format.\n");
}

// Prompt for password
echo "Enter Strong Password (min 8 chars): ";
$password = trim(fgets(STDIN));
if (strlen($password) < 8) {
    die("Error: Password must be at least 8 characters.\n");
}

$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $db->prepare("SELECT id FROM admins WHERE email = ?");
    $stmt->execute([$email]);
    $existing = $stmt->fetch();

    if ($existing) {
        $upd = $db->prepare("UPDATE admins SET username = ?, password_hash = ?, role = 'super_admin', status = 'active', updated_at = NOW() WHERE id = ?");
        $upd->execute([$username, $hash, $existing['id']]);
        echo "\n[SUCCESS] Existing administrator ($email) updated as Super Admin!\n";
    } else {
        $ins = $db->prepare("INSERT INTO admins (username, email, role, status, password_hash, created_at, updated_at) VALUES (?, ?, 'super_admin', 'active', ?, NOW(), NOW())");
        $ins->execute([$username, $email, $hash]);
        echo "\n[SUCCESS] New Super Admin ($email) provisioned successfully!\n";
    }

    echo "You may now log in securely at: http://localhost/zaffran/admin/login.php\n";
    echo "====================================================\n";
} catch (Exception $e) {
    echo "\n[ERROR] Database operation failed: " . $e->getMessage() . "\n";
}
