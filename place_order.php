<?php
/**
 * Server-Side Order Placement Controller - Phase 4 Enhanced
 * Handles form submission, CSRF token validation, input sanitization,
 * attaches authenticated customer user_id when logged in while preserving guest checkout,
 * server-side price re-fetching from MySQL, database transactions,
 * atomic order/items insertion, and session clearing upon success.
 */

define('ZAFFRAN_APP', true);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
initSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: checkout.php');
    exit;
}

// 1. Validate CSRF Token
$csrfToken = $_POST['csrf_token'] ?? '';
if (!verifyCsrfToken($csrfToken)) {
    die('Security token expired or invalid. Please return to the cart and try again.');
}

// 2. Validate Cart in Session
$verifiedCart = getVerifiedCart();
$cartItems = $verifiedCart['items'];
if (empty($cartItems)) {
    header('Location: cart.php');
    exit;
}

// Determine if customer is authenticated
$authenticatedUserId = null;
if (CustomerAuth::isAuthenticated()) {
    $currentCust = CustomerAuth::getCustomer();
    if ($currentCust) {
        $authenticatedUserId = (int)$currentCust['id'];
    }
}

// 3. Validate Inputs
$customerName = trim($_POST['customer_name'] ?? '');
$customerEmail = filter_var(trim($_POST['customer_email'] ?? ''), FILTER_VALIDATE_EMAIL);
$customerPhone = trim($_POST['customer_phone'] ?? '');
$fullAddress = trim($_POST['full_address'] ?? '');
$deliveryAddress = trim($_POST['delivery_address'] ?? '');
$city = trim($_POST['city'] ?? '');
$province = trim($_POST['province'] ?? '');
$postalCode = trim($_POST['postal_code'] ?? '');
$notes = trim($_POST['notes'] ?? '');
$paymentMethod = trim($_POST['payment_method'] ?? '');
$idempotencyKey = trim($_POST['idempotency_key'] ?? '');
$billingSame = !empty($_POST['billing_same']);
$billingName = trim($_POST['billing_name'] ?? '');
$billingAddress = trim($_POST['billing_address'] ?? '');
$billingCity = trim($_POST['billing_city'] ?? '');

$primaryAddress = !empty($fullAddress) ? $fullAddress : $deliveryAddress;

if (empty($customerName) || !$customerEmail || empty($customerPhone) || empty($primaryAddress) || empty($city) || empty($province)) {
    die('Please complete all required fields (Name, Email, Phone, Address, City, Province).');
}

if (!$billingSame && (empty($billingName) || empty($billingAddress) || empty($billingCity))) {
    die('Please complete the billing address or confirm it matches your delivery address.');
}

// Validate Phone
$cleanPhone = preg_replace('/[^0-9+]/', '', $customerPhone);
if (strlen($cleanPhone) < 10) {
    die('Please provide a valid Pakistani contact phone number.');
}

// Validate payment method against live store configuration.
$storeSettings = getStoreSettings();
$codEnabled = ($storeSettings['enable_cod'] ?? '1') === '1';
$bankEnabled = ($storeSettings['enable_bank_transfer'] ?? '0') === '1'
    && !empty($storeSettings['bank_name'])
    && !empty($storeSettings['bank_account_number']);

if ($paymentMethod === 'cod' && !$codEnabled) {
    die('Cash on Delivery is not currently available.');
}
if ($paymentMethod === 'bank_transfer' && !$bankEnabled) {
    die('Manual bank transfer is not currently available.');
}
if (!in_array($paymentMethod, ['cod', 'bank_transfer'], true)) {
    die('Please select a valid payment method.');
}

// 4. Calculate Verified Totals from DB (Never trust client)
$subtotal = $verifiedCart['subtotal'];
$shipping = $verifiedCart['shipping'];
if (!$shipping['is_configured']) {
    die('Shipping charges have not been configured by the store.');
}
$shippingFee = $shipping['fee'];
$grandTotal = $subtotal + $shippingFee;

// 5. Database Transaction & Idempotency Check
$pdo = getDbConnection();
if (!$pdo) {
    die('Database connection failure. Please try again shortly.');
}

// Check if idempotency key was already processed
if (!empty($idempotencyKey)) {
    $stmtCheck = $pdo->prepare("SELECT id, access_token FROM orders WHERE idempotency_key = ? LIMIT 1");
    $stmtCheck->execute([$idempotencyKey]);
    $existingOrder = $stmtCheck->fetch();
    if ($existingOrder) {
        clearCartServer();
        unset($_SESSION['checkout_idempotency_key']);
        header('Location: order-success.php?token=' . urlencode($existingOrder['access_token']));
        exit;
    }
}

try {
    $pdo->beginTransaction();

    // Generate Unique Order Number & Unguessable 64-char Access Token
    $orderNumber = 'ZAF-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    $accessToken = bin2hex(random_bytes(32));

    // Construct Delivery Address JSON
    $deliveryJson = json_encode([
        'customer_name' => $customerName,
        'customer_email' => $customerEmail,
        'customer_phone' => $customerPhone,
        'full_address' => $fullAddress,
        'delivery_address' => $deliveryAddress,
        'city' => $city,
        'province' => $province,
        'postal_code' => $postalCode,
        'notes' => $notes,
        'billing_same_as_delivery' => $billingSame,
        'billing_name' => $billingSame ? $customerName : $billingName,
        'billing_address' => $billingSame ? ($fullAddress ?: $deliveryAddress) : $billingAddress,
        'billing_city' => $billingSame ? $city : $billingCity,
        'country' => 'Pakistan'
    ], JSON_UNESCAPED_UNICODE);

    // Composite address string for storage: prefer fullAddress or combine with street
    $storedDeliveryAddress = !empty($fullAddress) 
        ? (!empty($deliveryAddress) && $fullAddress !== $deliveryAddress ? $fullAddress . ' (' . $deliveryAddress . ')' : $fullAddress)
        : $deliveryAddress;

    // Insert Order Record (attaching verified customer_id when logged in)
    $sqlOrder = "INSERT INTO orders (
                    order_number, access_token, idempotency_key, user_id, customer_name, customer_email, customer_phone,
                    delivery_address, city, province, postal_code, subtotal, shipping_fee, discount_total, grand_total,
                    total_amount, payment_method, order_status, currency, payment_status, delivery_address_json, notes, created_at
                 ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?, ?, 0.00, ?,
                    ?, ?, 'pending', 'PKR', 'pending', ?, ?, NOW()
                 )";

    $stmtOrder = $pdo->prepare($sqlOrder);
    $stmtOrder->execute([
        $orderNumber,
        $accessToken,
        !empty($idempotencyKey) ? $idempotencyKey : null,
        $authenticatedUserId, // Null for guest, customer ID when authenticated
        $customerName,
        $customerEmail,
        $customerPhone,
        $storedDeliveryAddress,
        $city,
        $province,
        $postalCode,
        $subtotal,
        $shippingFee,
        $grandTotal,
        $grandTotal,
        $paymentMethod,
        $deliveryJson,
        $notes
    ]);

    $orderId = (int)$pdo->lastInsertId();

    // Insert Each Order Item
    $sqlItem = "INSERT INTO order_items (
                    order_id, product_id, product_name, price, quantity, total_price, product_image, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, NOW()
                )";
    $stmtItem = $pdo->prepare($sqlItem);

    foreach ($cartItems as $item) {
        $stmtItem->execute([
            $orderId,
            $item['product_id'],
            $item['name'],
            $item['unit_price'],
            $item['quantity'],
            $item['total_price'],
            $item['image_url']
        ]);
    }

    // Commit Transaction
    $pdo->commit();

    // 6. Clear session cart and idempotency key
    clearCartServer();
    unset($_SESSION['checkout_idempotency_key']);

    // Redirect to Order Confirmation
    header('Location: order-success.php?token=' . urlencode($accessToken));
    exit;

} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Order placement error: ' . $e->getMessage());
    die('We encountered an issue processing your order. Please try again or contact customer support.');
}
