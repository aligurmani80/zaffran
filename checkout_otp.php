<?php
/**
 * Inline checkout email-OTP endpoints.
 * Actions: request | verify. CSRF + session challenge binding + rate limits.
 */

define('ZAFFRAN_APP', true);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
initSession();

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate');

function checkoutOtpJson(array $payload, int $status = 200): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    checkoutOtpJson(['success' => false, 'message' => 'Method not allowed.'], 405);
}

$csrf = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($csrf)) {
    checkoutOtpJson(['success' => false, 'message' => 'Your session expired. Please refresh the page.'], 403);
}

$action = $_POST['action'] ?? '';
$ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

if ($action === 'request') {
    $email = trim((string)($_POST['email'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        checkoutOtpJson(['success' => false, 'message' => 'Enter a valid email address.']);
    }

    $result = CustomerAuth::requestOtp($email, $ip, $ua);

    if ($result['success']) {
        $_SESSION['checkout_otp_challenge'] = $result['challenge_token'];
        $_SESSION['checkout_otp_email'] = $result['email'];
        checkoutOtpJson([
            'success' => true,
            'message' => 'A 6-digit code has been sent to your email. It expires in 5 minutes.',
            'cooldown_seconds' => 60,
        ]);
    }

    $response = ['success' => false, 'message' => $result['message']];
    if (!empty($result['cooldown_seconds'])) {
        $response['cooldown_seconds'] = (int)$result['cooldown_seconds'];
    }
    checkoutOtpJson($response);
}

if ($action === 'verify') {
    $challenge = $_SESSION['checkout_otp_challenge'] ?? '';
    $expectedEmail = $_SESSION['checkout_otp_email'] ?? '';

    if (empty($challenge)) {
        checkoutOtpJson(['success' => false, 'message' => 'Please request a new verification code.']);
    }

    $code = trim((string)($_POST['code'] ?? ''));
    $result = CustomerAuth::verifyOtp($challenge, $code);

    if (!$result['success']) {
        checkoutOtpJson(['success' => false, 'message' => $result['message']]);
    }

    unset($_SESSION['checkout_otp_challenge'], $_SESSION['checkout_otp_email']);

    $customer = CustomerAuth::getCustomer();

    checkoutOtpJson([
        'success' => true,
        'message' => 'Verified successfully.',
        'csrf_token' => getCsrfToken(),
        'customer' => [
            'name' => $customer['name'] ?? '',
            'email' => $customer['email'] ?? $expectedEmail,
            'phone' => $customer['phone'] ?? '',
        ],
    ]);
}

checkoutOtpJson(['success' => false, 'message' => 'Unknown action.'], 400);
