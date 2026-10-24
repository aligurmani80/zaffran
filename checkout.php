<?php
/**
 * Secure on-site checkout.
 * Guest checkout is supported; verified customers are prefilled via email OTP.
 */

define('ZAFFRAN_APP', true);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
initSession();

$verifiedCart = getVerifiedCart();
$items = $verifiedCart['items'];
$subtotal = $verifiedCart['subtotal'];
$totalCount = $verifiedCart['total_count'];
$shipping = $verifiedCart['shipping'];
$grandTotal = $verifiedCart['grand_total'];
$storeSettings = getStoreSettings();

if (empty($items)) {
    header('Location: cart.php');
    exit;
}

$isAuthenticated = CustomerAuth::isAuthenticated();
$customer = $isAuthenticated ? CustomerAuth::getCustomer() : null;

if (empty($_SESSION['checkout_idempotency_key'])) {
    $_SESSION['checkout_idempotency_key'] = bin2hex(random_bytes(24));
}
$idempotencyKey = $_SESSION['checkout_idempotency_key'];

$codEnabled = ($storeSettings['enable_cod'] ?? '1') === '1';
$bankEnabled = ($storeSettings['enable_bank_transfer'] ?? '0') === '1';
$bankConfigured = !empty($storeSettings['bank_name']) && !empty($storeSettings['bank_account_number']);
$bankEnabled = $bankEnabled && $bankConfigured;
$paymentAvailable = $codEnabled || $bankEnabled;

$pageTitle = 'Secure Checkout | Zaffran';
$pageDescription = 'Complete your Zaffran order on our secure website with Cash on Delivery or manual bank transfer.';

require_once __DIR__ . '/includes/header.php';
?>

<section class="checkout-intro">
  <div class="container checkout-intro-inner">
    <a href="index.php" class="checkout-logo" aria-label="Zaffran home">
      <span class="checkout-logo-mark" aria-hidden="true">Z</span>
      <span class="checkout-logo-text">ZAFFRAN<small>Botanicals &amp; Parfumerie</small></span>
    </a>
    <h1 class="checkout-heading">Secure Checkout</h1>
    <p class="checkout-subheading">Complete your order directly on our website. Your details are encrypted in transit and stored securely.</p>
    <div class="checkout-steps-mini" aria-hidden="true">
      <span class="is-complete">Cart</span>
      <span class="is-current">Information</span>
      <span>Confirmation</span>
    </div>
  </div>
</section>

<main id="main-content" class="checkout-page-section">
  <div class="container">

    <form action="place_order.php" method="POST" id="checkout-form" class="checkout-grid" novalidate>
      <input type="hidden" name="csrf_token" id="checkout-csrf" value="<?php echo htmlspecialchars(getCsrfToken()); ?>">
      <input type="hidden" name="idempotency_key" value="<?php echo htmlspecialchars($idempotencyKey); ?>">

      <div class="checkout-form-panel">

        <?php if ($isAuthenticated && $customer): ?>
          <div class="checkout-verified-banner">
            <div class="checkout-verified-icon" aria-hidden="true">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><polyline points="20 6 9 17 4 12"></polyline></svg>
            </div>
            <div>
              <strong>Signed in as <?php echo htmlspecialchars($customer['email']); ?></strong>
              <span>Your verified account will be linked to this order.</span>
            </div>
            <a href="account.php">Dashboard</a>
          </div>
        <?php endif; ?>

        <!-- 1. CONTACT -->
        <section class="checkout-block" aria-labelledby="checkout-contact-heading">
          <div class="checkout-block-head">
            <h2 id="checkout-contact-heading">Contact Information</h2>
            <span class="checkout-block-note">Steps 1 of 4</span>
          </div>

          <div class="form-group">
            <label for="customer_email" class="form-label">Email Address <span class="required-star">*</span></label>
            <input type="email" id="customer_email" name="customer_email" class="form-control"
                   value="<?php echo htmlspecialchars($customer['email'] ?? ''); ?>"
                   placeholder="name@example.com" required autocomplete="email"
                   <?php echo ($isAuthenticated && $customer) ? 'readonly' : ''; ?>>
            <span class="form-help">Your order receipt and dispatch updates are sent to this address.</span>
          </div>

          <?php if (!$isAuthenticated): ?>
            <div class="checkout-otp-box" id="checkout-otp-box">
              <div class="checkout-otp-head">
                <div>
                  <strong>Have an order history with Zaffran?</strong>
                  <span>Sign in securely with a 6-digit email code — no password needed.</span>
                </div>
                <button type="button" class="checkout-otp-toggle" id="checkout-otp-toggle" aria-expanded="false" aria-controls="checkout-otp-panel">Sign in</button>
              </div>

              <div class="checkout-otp-panel" id="checkout-otp-panel" hidden>
                <div class="checkout-otp-step" id="checkout-otp-step-email">
                  <button type="button" class="btn btn-secondary btn-sm" id="checkout-otp-send">Email me a 6-digit code</button>
                  <p class="checkout-otp-hint">We will use the email address entered above.</p>
                </div>
                <div class="checkout-otp-step" id="checkout-otp-step-code" hidden>
                  <label for="checkout-otp-code" class="form-label">Enter the 6-digit code</label>
                  <div class="checkout-otp-code-row">
                    <input type="text" id="checkout-otp-code" class="form-control checkout-otp-input" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" placeholder="000000">
                    <button type="button" class="btn btn-primary btn-sm" id="checkout-otp-verify">Verify</button>
                  </div>
                  <button type="button" class="checkout-otp-resend" id="checkout-otp-resend" disabled>Resend code (60s)</button>
                </div>
                <div class="checkout-otp-feedback" id="checkout-otp-feedback" role="status" aria-live="polite"></div>
              </div>
            </div>
          <?php endif; ?>

          <div class="form-grid-2col">
            <div class="form-group">
              <label for="customer_name" class="form-label">Full Name <span class="required-star">*</span></label>
              <input type="text" id="customer_name" name="customer_name" class="form-control"
                     value="<?php echo htmlspecialchars($customer['name'] ?? ''); ?>"
                     placeholder="e.g. Malik Jahangir" required autocomplete="name">
            </div>
            <div class="form-group">
              <label for="customer_phone" class="form-label">Mobile Phone <span class="required-star">*</span></label>
              <input type="tel" id="customer_phone" name="customer_phone" class="form-control"
                     value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>"
                     placeholder="0300-1234567" required autocomplete="tel">
              <span class="form-help">Used only for courier delivery updates.</span>
            </div>
          </div>
        </section>

        <!-- 2. DELIVERY -->
        <section class="checkout-block" aria-labelledby="checkout-delivery-heading">
          <div class="checkout-block-head">
            <h2 id="checkout-delivery-heading">Delivery Address</h2>
            <span class="checkout-block-note">Step 2 of 4</span>
          </div>

          <div class="form-group">
            <label for="delivery_address" class="form-label">Street Address, House / Building, Area <span class="required-star">*</span></label>
            <input type="text" id="delivery_address" name="delivery_address" class="form-control"
                   placeholder="House 12, Street 4, Gulberg III" required autocomplete="street-address">
          </div>

          <div class="form-grid-3col">
            <div class="form-group">
              <label for="city" class="form-label">City <span class="required-star">*</span></label>
              <input type="text" id="city" name="city" class="form-control" placeholder="Lahore" required autocomplete="address-level2">
            </div>
            <div class="form-group">
              <label for="province" class="form-label">Province <span class="required-star">*</span></label>
              <select id="province" name="province" class="form-control" required autocomplete="address-level1">
                <option value="" disabled selected>Select province</option>
                <option value="Punjab">Punjab</option>
                <option value="Sindh">Sindh</option>
                <option value="Khyber Pakhtunkhwa">Khyber Pakhtunkhwa</option>
                <option value="Balochistan">Balochistan</option>
                <option value="Islamabad Capital Territory">Islamabad Capital Territory</option>
                <option value="Gilgit-Baltistan">Gilgit-Baltistan</option>
                <option value="Azad Kashmir">Azad Kashmir</option>
              </select>
            </div>
            <div class="form-group">
              <label for="postal_code" class="form-label">Postal Code <span class="optional-tag">Optional</span></label>
              <input type="text" id="postal_code" name="postal_code" class="form-control" placeholder="54000" autocomplete="postal-code">
            </div>
          </div>

          <div class="form-group">
            <label for="notes" class="form-label">Delivery Notes <span class="optional-tag">Optional</span></label>
            <input type="text" id="notes" name="notes" class="form-control" placeholder="Landmark, preferred delivery time, or instructions">
          </div>
        </section>

        <!-- 3. SHIPPING -->
        <section class="checkout-block" aria-labelledby="checkout-shipping-heading">
          <div class="checkout-block-head">
            <h2 id="checkout-shipping-heading">Shipping Method</h2>
            <span class="checkout-block-note">Step 3 of 4</span>
          </div>

          <div class="checkout-shipping-card">
            <div class="checkout-shipping-icon" aria-hidden="true">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
            </div>
            <div class="checkout-shipping-copy">
              <strong>Standard Nationwide Delivery</strong>
              <span>2–4 business days after dispatch · Tracked courier</span>
            </div>
            <div class="checkout-shipping-price">
              <?php if (!empty($shipping['is_free'])): ?>
                <strong>FREE</strong>
                <s>Rs. <?php echo number_format((float)($shipping['default_fee'] ?? ($storeSettings['default_delivery_charges'] ?? 250)), 0); ?></s>
              <?php else: ?>
                <strong><?php echo htmlspecialchars($shipping['formatted_fee']); ?></strong>
              <?php endif; ?>
            </div>
          </div>
        </section>

        <!-- 4. PAYMENT -->
        <section class="checkout-block" aria-labelledby="checkout-payment-heading">
          <div class="checkout-block-head">
            <h2 id="checkout-payment-heading">Payment Method</h2>
            <span class="checkout-block-note">Step 4 of 4</span>
          </div>

          <?php if ($paymentAvailable): ?>
            <div class="payment-options-list">
              <?php if ($codEnabled): ?>
                <label class="payment-option-card selected">
                  <input type="radio" name="payment_method" value="cod" checked class="payment-option-radio">
                  <span class="payment-option-glyph" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2" y="6" width="20" height="12" rx="2"></rect><circle cx="12" cy="12" r="2.5"></circle></svg>
                  </span>
                  <span class="payment-option-details">
                    <strong>Cash on Delivery</strong>
                    <span>Pay the courier in cash when your parcel arrives. No advance payment.</span>
                  </span>
                </label>
              <?php endif; ?>

              <?php if ($bankEnabled): ?>
                <label class="payment-option-card <?php echo !$codEnabled ? 'selected' : ''; ?>">
                  <input type="radio" name="payment_method" value="bank_transfer" <?php echo !$codEnabled ? 'checked' : ''; ?> class="payment-option-radio">
                  <span class="payment-option-glyph" aria-hidden="true">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 10h18"></path><path d="M5 10V20"></path><path d="M19 10V20"></path><path d="M9 10V20"></path><path d="M15 10V20"></path><path d="M2 20h20"></path><path d="M12 2l9 6H3z"></path></svg>
                  </span>
                  <span class="payment-option-details">
                    <strong>Manual Bank Transfer</strong>
                    <span>Transfer to our verified Meezan Bank account. Your order is confirmed after payment verification.</span>
                  </span>
                </label>
              <?php endif; ?>
            </div>

            <?php if ($bankEnabled): ?>
              <div class="bank-transfer-panel" id="bank-transfer-panel" hidden>
                <div class="bank-transfer-heading">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><polyline points="9 12 11 14 15 10"></polyline></svg>
                  <strong>Verified Bank Details</strong>
                </div>
                <dl class="bank-detail-list">
                  <div><dt>Bank</dt><dd><?php echo htmlspecialchars($storeSettings['bank_name'] ?? ''); ?></dd></div>
                  <div><dt>Account Title</dt><dd><?php echo htmlspecialchars($storeSettings['bank_account_title'] ?? ''); ?></dd></div>
                  <div><dt>Account Number</dt><dd><?php echo htmlspecialchars($storeSettings['bank_account_number'] ?? ''); ?></dd></div>
                  <?php if (!empty($storeSettings['bank_iban'])): ?>
                    <div><dt>IBAN</dt><dd><?php echo htmlspecialchars($storeSettings['bank_iban']); ?></dd></div>
                  <?php endif; ?>
                  <div><dt>Amount</dt><dd><strong><?php echo $verifiedCart['formatted_grand_total']; ?></strong></dd></div>
                </dl>
                <p class="bank-transfer-note">
                  After placing your order, transfer the total and share the payment receipt with our support team referencing your order number. We never ask for card details, OTPs or remote access.
                </p>
              </div>
            <?php endif; ?>
          <?php else: ?>
            <div class="checkout-payment-unavailable">
              Online ordering is temporarily paused. Please contact customer support to place your order.
            </div>
          <?php endif; ?>
        </section>

        <!-- 5. BILLING -->
        <section class="checkout-block" aria-labelledby="checkout-billing-heading">
          <div class="checkout-block-head">
            <h2 id="checkout-billing-heading">Billing Address</h2>
          </div>

          <label class="checkout-checkbox-row">
            <input type="checkbox" name="billing_same" id="billing_same" value="1" checked>
            <span>Billing address is the same as my delivery address</span>
          </label>

          <div class="checkout-billing-fields" id="billing-fields" hidden>
            <div class="form-group">
              <label for="billing_name" class="form-label">Billing Name</label>
              <input type="text" id="billing_name" name="billing_name" class="form-control" placeholder="Name on billing record">
            </div>
            <div class="form-group">
              <label for="billing_address" class="form-label">Billing Street Address</label>
              <input type="text" id="billing_address" name="billing_address" class="form-control" placeholder="House, street, area">
            </div>
            <div class="form-group">
              <label for="billing_city" class="form-label">Billing City</label>
              <input type="text" id="billing_city" name="billing_city" class="form-control" placeholder="City">
            </div>
          </div>
        </section>

      </div>

      <!-- ORDER SUMMARY -->
      <aside class="checkout-summary-sidebar" aria-label="Order summary">
        <div class="cart-summary-card checkout-summary-card">

          <div class="checkout-summary-head">
            <h2 class="summary-heading">Your Order</h2>
            <a href="cart.php" class="checkout-edit-cart">Edit bag</a>
          </div>

          <ul class="order-items-preview checkout-items-preview">
            <?php foreach ($items as $item): ?>
              <li class="order-item-preview-row">
                <div class="checkout-item-thumb-wrap">
                  <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="order-item-preview-thumb">
                  <span class="checkout-item-qty-badge"><?php echo (int)$item['quantity']; ?></span>
                </div>
                <div class="order-item-preview-meta">
                  <span class="order-item-preview-title"><?php echo htmlspecialchars($item['name']); ?></span>
                  <span class="order-item-preview-qty"><?php echo $item['formatted_unit_price']; ?> each</span>
                </div>
                <div class="order-item-preview-price"><?php echo $item['formatted_total_price']; ?></div>
              </li>
            <?php endforeach; ?>
          </ul>

          <div class="checkout-totals">
            <div class="summary-row">
              <span>Subtotal · <?php echo (int)$totalCount; ?> item<?php echo $totalCount === 1 ? '' : 's'; ?></span>
              <strong><?php echo $verifiedCart['formatted_subtotal']; ?></strong>
            </div>
            <div class="summary-row">
              <span>Shipping</span>
              <span>
                <?php if (!empty($shipping['is_free'])): ?>
                  <span class="summary-badge-free">FREE</span>
                <?php else: ?>
                  <?php echo htmlspecialchars($shipping['formatted_fee']); ?>
                <?php endif; ?>
              </span>
            </div>
            <div class="summary-row grand-total checkout-grand-total">
              <span>Total</span>
              <strong><?php echo $verifiedCart['formatted_grand_total']; ?></strong>
            </div>
          </div>

          <button type="submit" class="btn btn-primary summary-action-btn checkout-submit-btn" id="place-order-submit-btn" <?php echo !$paymentAvailable ? 'disabled' : ''; ?>>
            <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
            Complete Order
          </button>

          <p class="checkout-submit-note">
            By completing this order you agree to our store policies. Payment is collected only by COD or verified bank transfer — never through WhatsApp.
          </p>

          <div class="checkout-trust-list">
            <div><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg><span>Genuine products, sealed and batch-verified</span></div>
            <div><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg><span>Tracked delivery in 2–4 business days</span></div>
            <div><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg><span>Customer support via phone and WhatsApp</span></div>
          </div>
        </div>
      </aside>

    </form>
  </div>
</main>

<script>
(function () {
  'use strict';

  var form = document.getElementById('checkout-form');
  if (!form) return;

  // Payment selection → reveal bank details
  var bankPanel = document.getElementById('bank-transfer-panel');
  var paymentCards = Array.prototype.slice.call(form.querySelectorAll('.payment-option-card'));

  function syncPaymentCards() {
    paymentCards.forEach(function (card) {
      var radio = card.querySelector('input[type="radio"]');
      card.classList.toggle('selected', !!(radio && radio.checked));
    });
    if (bankPanel) {
      var bankSelected = form.querySelector('input[name="payment_method"][value="bank_transfer"]');
      bankPanel.hidden = !(bankSelected && bankSelected.checked);
    }
  }
  paymentCards.forEach(function (card) {
    var radio = card.querySelector('input[type="radio"]');
    if (radio) radio.addEventListener('change', syncPaymentCards);
  });
  syncPaymentCards();

  // Billing address toggle
  var billingSame = document.getElementById('billing_same');
  var billingFields = document.getElementById('billing-fields');
  function syncBilling() {
    if (!billingFields || !billingSame) return;
    billingFields.hidden = billingSame.checked;
    ['billing_name', 'billing_address', 'billing_city'].forEach(function (id) {
      var field = document.getElementById(id);
      if (field) field.required = !billingSame.checked;
    });
  }
  if (billingSame) billingSame.addEventListener('change', syncBilling);
  syncBilling();

  // Inline email OTP sign-in
  var otpToggle = document.getElementById('checkout-otp-toggle');
  var otpPanel = document.getElementById('checkout-otp-panel');
  var otpSend = document.getElementById('checkout-otp-send');
  var otpResend = document.getElementById('checkout-otp-resend');
  var otpVerify = document.getElementById('checkout-otp-verify');
  var otpCode = document.getElementById('checkout-otp-code');
  var otpFeedback = document.getElementById('checkout-otp-feedback');
  var otpEmailStep = document.getElementById('checkout-otp-step-email');
  var otpCodeStep = document.getElementById('checkout-otp-step-code');
  var emailField = document.getElementById('customer_email');
  var resendTimer = null;

  function setFeedback(message, type) {
    if (!otpFeedback) return;
    otpFeedback.textContent = message || '';
    otpFeedback.className = 'checkout-otp-feedback' + (type ? ' is-' + type : '');
  }

  function startResendCooldown(seconds) {
    if (!otpResend) return;
    var left = seconds;
    otpResend.disabled = true;
    otpResend.textContent = 'Resend code (' + left + 's)';
    if (resendTimer) window.clearInterval(resendTimer);
    resendTimer = window.setInterval(function () {
      left -= 1;
      if (left <= 0) {
        window.clearInterval(resendTimer);
        otpResend.disabled = false;
        otpResend.textContent = 'Resend code';
      } else {
        otpResend.textContent = 'Resend code (' + left + 's)';
      }
    }, 1000);
  }

  function postOtp(action, payload) {
    var csrf = document.getElementById('checkout-csrf');
    var body = new URLSearchParams();
    body.set('action', action);
    body.set('csrf_token', csrf ? csrf.value : '');
    Object.keys(payload || {}).forEach(function (key) { body.set(key, payload[key]); });

    return fetch('checkout_otp.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: body.toString(),
      credentials: 'same-origin'
    }).then(function (response) {
      return response.json().catch(function () {
        return { success: false, message: 'Unexpected response. Please try again.' };
      });
    });
  }

  if (otpToggle && otpPanel) {
    otpToggle.addEventListener('click', function () {
      var open = otpPanel.hidden;
      otpPanel.hidden = !open;
      otpToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      otpToggle.textContent = open ? 'Hide' : 'Sign in';
      if (open) setFeedback('', '');
    });
  }

  function sendCode() {
    var email = emailField ? emailField.value.trim() : '';
    if (!email || email.indexOf('@') === -1) {
      setFeedback('Enter your email address above first.', 'error');
      if (emailField) emailField.focus();
      return;
    }
    if (otpSend) otpSend.disabled = true;
    setFeedback('Sending your secure code…', '');
    postOtp('request', { email: email }).then(function (res) {
      if (otpSend) otpSend.disabled = false;
      if (res.success) {
        if (otpEmailStep) otpEmailStep.hidden = true;
        if (otpCodeStep) otpCodeStep.hidden = false;
        setFeedback(res.message || 'Code sent. Please check your inbox.', 'success');
        startResendCooldown(60);
        if (otpCode) otpCode.focus();
      } else {
        setFeedback(res.message || 'Could not send the code.', 'error');
        if (res.cooldown_seconds) startResendCooldown(res.cooldown_seconds);
      }
    }).catch(function () {
      if (otpSend) otpSend.disabled = false;
      setFeedback('Network error. Please try again.', 'error');
    });
  }

  if (otpSend) otpSend.addEventListener('click', sendCode);
  if (otpResend) otpResend.addEventListener('click', sendCode);

  if (otpVerify) {
    otpVerify.addEventListener('click', function () {
      var code = otpCode ? otpCode.value.trim() : '';
      if (!/^\d{6}$/.test(code)) {
        setFeedback('Enter the complete 6-digit code.', 'error');
        return;
      }
      otpVerify.disabled = true;
      setFeedback('Verifying…', '');
      postOtp('verify', { code: code }).then(function (res) {
        otpVerify.disabled = false;
        if (res.success) {
          setFeedback('Verified. Your account is now linked to this order.', 'success');
          if (res.csrf_token) {
            var csrf = document.getElementById('checkout-csrf');
            if (csrf) csrf.value = res.csrf_token;
          }
          if (res.customer) {
            if (res.customer.name) {
              var nameField = document.getElementById('customer_name');
              if (nameField && !nameField.value) nameField.value = res.customer.name;
            }
            if (res.customer.phone) {
              var phoneField = document.getElementById('customer_phone');
              if (phoneField && !phoneField.value) phoneField.value = res.customer.phone;
            }
          }
          if (emailField) emailField.readOnly = true;
          if (otpCodeStep) otpCodeStep.hidden = true;
          if (resendTimer) window.clearInterval(resendTimer);
          otpToggle.disabled = true;
          otpToggle.textContent = 'Verified';
        } else {
          setFeedback(res.message || 'Verification failed.', 'error');
        }
      }).catch(function () {
        otpVerify.disabled = false;
        setFeedback('Network error. Please try again.', 'error');
      });
    });
  }

  if (otpCode) {
    otpCode.addEventListener('input', function () {
      otpCode.value = otpCode.value.replace(/\D/g, '').slice(0, 6);
      if (otpCode.value.length === 6 && otpVerify) otpVerify.focus();
    });
  }
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
