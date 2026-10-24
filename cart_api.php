<?php
/**
 * Server-Side Cart API Handler - Phase 3 Enhanced
 * Supports 'add', 'update', 'remove', and 'get' actions with strict CSRF verification
 */

define('ZAFFRAN_APP', true);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/config/database.php';
initSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// Read raw JSON or form-encoded POST
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}

$action = $input['action'] ?? 'add';
$csrfToken = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

// CSRF Protection
if (!verifyCsrfToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Security token expired or invalid. Please refresh the page.']);
    exit;
}

// 1. ADD ITEM
if ($action === 'add') {
    $productId = (int)($input['product_id'] ?? 0);
    $quantity = (int)($input['quantity'] ?? 1);

    if ($quantity < 1) $quantity = 1;
    if ($quantity > 50) $quantity = 50;

    $result = addToCartServer($productId, $quantity);
    if (!$result['success']) {
        http_response_code(400);
    }
    echo json_encode($result);
    exit;
}

// 2. UPDATE ITEM QUANTITY
if ($action === 'update') {
    $productId = (int)($input['product_id'] ?? 0);
    $quantity = (int)($input['quantity'] ?? 1);

    $result = updateCartQuantityServer($productId, $quantity);
    if (!$result['success']) {
        http_response_code(400);
    }
    echo json_encode($result);
    exit;
}

// 3. REMOVE ITEM
if ($action === 'remove') {
    $productId = (int)($input['product_id'] ?? 0);

    $result = removeFromCartServer($productId);
    if (!$result['success']) {
        http_response_code(400);
    }
    echo json_encode($result);
    exit;
}

// 4. GET CART STATUS
if ($action === 'get') {
    $verifiedCart = getVerifiedCart();
    echo json_encode([
        'success' => true,
        'cart' => $verifiedCart,
        'cart_count' => getCartCount()
    ]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Unrecognized cart action.']);
exit;
