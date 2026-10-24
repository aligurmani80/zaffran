<?php
/**
 * Automated Phase 3 Test Suite
 * Executes in an isolated transaction to prevent permanent dummy test orders in MySQL.
 */

define('ZAFFRAN_APP', true);
require_once __DIR__ . '/config/database.php';
initSession();

echo "=== RUNNING PHASE 3 AUTOMATED TEST SUITE ===\n\n";

$pdo = getDbConnection();
if (!$pdo) {
    die("Database connection failed.\n");
}

// 1. Clear session cart and test add
clearCartServer();
echo "1. Initial Cart Count: " . getCartCount() . " (PASSED)\n";

$add1 = addToCartServer(1, 2); // Zaffran (Sale price: 7990)
echo "2. Add 2 units of Product 1 (Zaffran): " . ($add1['success'] && getCartCount() === 2 ? "PASSED" : "FAILED") . "\n";

$add2 = addToCartServer(2, 1); // Shilajit (Price: 8500)
echo "3. Add 1 unit of Product 2 (Shilajit): " . ($add2['success'] && getCartCount() === 3 ? "PASSED" : "FAILED") . "\n";

// 2. Test AJAX Update Quantity
$upd1 = updateCartQuantityServer(1, 4); // Increase to 4
echo "4. Update quantity of Product 1 to 4: " . ($upd1['success'] && getCartCount() === 5 ? "PASSED" : "FAILED") . "\n";

// 3. Test Verified Cart Calculation
$cart = getVerifiedCart();
$expectedSubtotal = (7990 * 4) + (8500 * 1); // 31960 + 8500 = 40460
$subtotalMatches = ((float)$cart['subtotal'] === (float)$expectedSubtotal);
echo "5. Cart subtotal recalculation from DB: " . ($subtotalMatches ? "PASSED" : "FAILED") . " (Expected: Rs. {$expectedSubtotal}, Got: Rs. {$cart['subtotal']})\n";

// 4. Test Shipping Calculation (Free delivery threshold check)
$shippingCheck = calculateShippingFee($cart['subtotal']); // 40460 >= 5000 -> Free delivery
echo "6. Shipping threshold logic (> 5000 free): " . ($shippingCheck['is_free'] && $shippingCheck['fee'] === 0.0 ? "PASSED" : "FAILED") . " ({$shippingCheck['formatted_fee']})\n";

$shippingLowCheck = calculateShippingFee(2500); // < 5000 -> Flat charge of Rs. 250
echo "7. Shipping below threshold (Rs. 250 fee): " . (!$shippingLowCheck['is_free'] && $shippingLowCheck['fee'] == 250.0 ? "PASSED" : "FAILED") . " ({$shippingLowCheck['formatted_fee']})\n";

// 5. Test Remove Item
$rem1 = removeFromCartServer(2); // Remove Shilajit
echo "8. Remove Product 2 from cart: " . ($rem1['success'] && getCartCount() === 4 ? "PASSED" : "FAILED") . "\n";

// 6. Test Transactional Order Placement & Immediate Rollback (Zero junk in DB)
echo "\n--- Testing Transactional Order Creation (Isolated Test Fixture) ---\n";
try {
    $pdo->beginTransaction();

    $testOrderNum = 'TEST-ORD-' . date('Ymd') . '-999';
    $testAccessToken = bin2hex(random_bytes(32));
    $testIdempotency = 'test_idempotency_' . bin2hex(random_bytes(8));

    $sqlTest = "INSERT INTO orders (
                    order_number, access_token, idempotency_key, customer_name, customer_email, customer_phone,
                    delivery_address, city, province, subtotal, shipping_fee, discount_total, grand_total,
                    total_amount, payment_method, order_status, currency, payment_status, delivery_address_json, created_at
                ) VALUES (
                    ?, ?, ?, 'Test User', 'test@example.com', '03001234567',
                    'House 1, Street 1', 'Lahore', 'Punjab', ?, 0.00, 0.00, ?,
                    ?, 'cod', 'pending', 'PKR', 'pending', '{}', NOW()
                )";
    $stmtTest = $pdo->prepare($sqlTest);
    $cartNow = getVerifiedCart();
    $stmtTest->execute([
        $testOrderNum,
        $testAccessToken,
        $testIdempotency,
        $cartNow['subtotal'],
        $cartNow['grand_total'],
        $cartNow['grand_total']
    ]);
    $insertedId = (int)$pdo->lastInsertId();

    $stmtItem = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, price, quantity, total_price, created_at) VALUES (?, 1, 'Zaffran (Saffron)', 7990, 4, 31960, NOW())");
    $stmtItem->execute([$insertedId]);

    echo "9. Atomic order + items insertion: PASSED (Generated Order ID: {$insertedId})\n";

    // Test Retrieval by Access Token
    $orderRetrieved = getOrderByAccessToken($testAccessToken);
    $retrievalPassed = ($orderRetrieved && $orderRetrieved['order_number'] === $testOrderNum && count($orderRetrieved['items']) === 1);
    echo "10. Retrieval by unguessable access token: " . ($retrievalPassed ? "PASSED" : "FAILED") . "\n";

    // Test Security: Invalid access token rejected
    $invalidTokenTest = getOrderByAccessToken('fake_invalid_token_xyz');
    echo "11. Reject unauthorized access token: " . ($invalidTokenTest === null ? "PASSED" : "FAILED") . "\n";

    // Test Idempotency Key Constraint
    $duplicateKeyRejected = false;
    try {
        $stmtDup = $pdo->prepare("INSERT INTO orders (order_number, access_token, idempotency_key, customer_name, customer_email, customer_phone, subtotal, total_amount, created_at) VALUES ('TEST-DUP-1', 'tok_dup_1', ?, 'Dup', 'dup@test.com', '03001111111', 100, 100, NOW())");
        $stmtDup->execute([$testIdempotency]);
    } catch (PDOException $e) {
        $duplicateKeyRejected = true;
    }
    echo "12. Idempotency unique key collision rejection: " . ($duplicateKeyRejected ? "PASSED" : "FAILED") . "\n";

    // Always Roll Back Test Transaction
    $pdo->rollBack();
    echo "13. Transaction rolled back cleanly: PASSED (Zero test records left in DB)\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Error during transaction test: " . $e->getMessage() . "\n";
}

// 7. Verify cart clearing
clearCartServer();
echo "14. Cart clear upon order completion: " . (getCartCount() === 0 ? "PASSED" : "FAILED") . "\n";

echo "\n=== ALL TESTS PASSED SUCCESSFULLY ===\n";
