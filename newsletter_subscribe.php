<?php
/**
 * Newsletter subscription endpoint.
 * Accepts a same-origin, CSRF-protected POST; stores consent; never sends a
 * fake confirmation email and never reports success before the DB write.
 */

define('ZAFFRAN_APP', true);
require_once __DIR__ . '/config/database.php';
initSession();

header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, max-age=0');

function newsletter_is_ajax(): bool
{
    return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
        || strpos(strtolower($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false;
}

function newsletter_respond(bool $success, string $message, string $code, int $status = 200): void
{
    if (newsletter_is_ajax()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'code' => $code,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    header('Location: index.php?newsletter=' . urlencode($code) . '#newsletter', true, 303);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    newsletter_respond(false, 'This form only accepts a subscription request.', 'error', 405);
}

$csrfToken = (string)($_POST['csrf_token'] ?? '');
if (!verifyCsrfToken($csrfToken)) {
    newsletter_respond(false, 'Your session has expired. Please refresh the page and try again.', 'csrf', 403);
}

if (trim((string)($_POST['website'] ?? '')) !== '') {
    newsletter_respond(false, 'We could not process this request. Please try again.', 'error', 422);
}

$email = strtolower(trim((string)($_POST['email'] ?? '')));
if ($email === '' || strlen($email) > 190 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    newsletter_respond(false, 'Please enter a valid email address.', 'invalid', 422);
}

if (($_POST['consent'] ?? '') !== '1') {
    newsletter_respond(false, 'Please confirm that you agree to receive Zaffran updates.', 'consent', 422);
}

$lastAttempt = (int)($_SESSION['newsletter_last_attempt'] ?? 0);
if ($lastAttempt > 0 && (time() - $lastAttempt) < 5) {
    newsletter_respond(false, 'Please wait a moment before trying again.', 'throttled', 429);
}
$_SESSION['newsletter_last_attempt'] = time();

$pdo = getDbConnection();
if (!$pdo) {
    newsletter_respond(false, 'Subscriptions are temporarily unavailable. Please try again later.', 'error', 503);
}

try {
    $existingStmt = $pdo->prepare('SELECT id, status FROM newsletter_subscribers WHERE email = ? LIMIT 1');
    $existingStmt->execute([$email]);
    $existing = $existingStmt->fetch();

    if ($existing) {
        if ($existing['status'] === 'subscribed') {
            newsletter_respond(true, 'This email is already subscribed to Zaffran updates.', 'duplicate');
        }

        $reactivate = $pdo->prepare("UPDATE newsletter_subscribers
            SET status = 'subscribed', consent = 1, consented_at = NOW(), source = 'footer'
            WHERE id = ?");
        $reactivate->execute([(int)$existing['id']]);
        newsletter_respond(true, 'Your subscription has been reactivated.', 'success');
    }

    $insert = $pdo->prepare("INSERT INTO newsletter_subscribers (email, consent, status, source, consented_at)
        VALUES (?, 1, 'subscribed', 'footer', NOW())");
    $insert->execute([$email]);

    newsletter_respond(true, 'You are on the list. Look out for future Zaffran updates.', 'success');
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        newsletter_respond(true, 'This email is already subscribed to Zaffran updates.', 'duplicate');
    }
    error_log('Newsletter subscription failed: ' . $e->getMessage());
    newsletter_respond(false, 'We could not save your subscription. Please try again later.', 'error', 500);
}
