# Zaffran Project Documentation & Architecture Guide

## Phase 1, 2, 3 & 4 Architecture Overview

### 1. Database & Tables (`zaffran_db`)
- `users`: Registered and verified customer accounts (`id`, `name`, `email`, `email_verified_at`, `phone`, `account_status`, `password`, `created_at`, `updated_at`). Passwords are NULL for passwordless email-only OTP accounts.
- `customer_email_otps`: Cryptographically secure verification challenges (`id`, `challenge_token`, `email`, `otp_hash`, `expires_at`, `attempts`, `max_attempts`, `is_used`, `ip_address`, `user_agent`, `purpose`, `last_sent_at`, `created_at`).
- `auth_rate_limits`: Persistent sliding-window rate limiting for IP and email verification requests.
- `orders`: Customer orders permanently recorded on-site (`id`, `order_number`, `access_token`, `idempotency_key`, `user_id`, `customer_name`, `customer_email`, `customer_phone`, `delivery_address`, `city`, `province`, `postal_code`, `subtotal`, `shipping_fee`, `discount_total`, `grand_total`, `total_amount`, `payment_method`, `order_status`, `currency`, `payment_status`, `delivery_address_json`, `notes`, `created_at`, `updated_at`).
- `order_items`: Snapshots of ordered products (`id`, `order_id`, `product_id`, `product_name`, `price`, `quantity`, `total_price`, `product_image`, `created_at`).
- `products`: Product inventory records.
- `categories`: Main product categories (`Perfume`, `Shilajit`, `Zaffran`, `Perfume Oils`).
- `store_settings`: Global configurations (store phone, WhatsApp links, bank accounts, delivery charges, COD enable flag).
- `announcements`: Top announcement banner contents.
- `reviews`: Authentic customer reviews.

### 2. Available Routes
- **Homepage:** `http://localhost/zaffran/index.php`
- **Shop Catalog:** `http://localhost/zaffran/shop.php`
- **Product Details:** `http://localhost/zaffran/product.php?slug=zaffran-saffron`
- **Shopping Bag:** `http://localhost/zaffran/cart.php`
- **Secure Checkout:** `http://localhost/zaffran/checkout.php`
- **Order Placement:** `http://localhost/zaffran/place_order.php` (POST, CSRF-protected, DB transaction)
- **Order Confirmation:** `http://localhost/zaffran/order-success.php?token=<access_token>`
- **Customer Sign In / Register:** `http://localhost/zaffran/login.php`
- **Enter Verification Code:** `http://localhost/zaffran/verify-otp.php`
- **Customer Dashboard:** `http://localhost/zaffran/account.php`
- **Customer Order History:** `http://localhost/zaffran/account/orders.php`
- **Customer Order Details:** `http://localhost/zaffran/account/order-details.php?id=<order_id>`
- **Customer Profile:** `http://localhost/zaffran/account/profile.php`
- **Customer Logout:** `http://localhost/zaffran/logout.php`
- **Cart API:** `http://localhost/zaffran/cart_api.php` (POST, CSRF-protected)

### 3. Passwordless Email-Only OTP Authentication Model
- **No Passwords:** Eliminates credential stuffing, password reuse, and reset links.
- **Keyed Hash Security:** OTPs are hashed using keyed SHA-256 HMAC with `APP_KEY` before database storage; raw OTPs are never stored in plaintext.
- **Challenge Lifecycle:** OTPs expire strictly in 5 minutes (300s), locked after 5 failed attempts, with a 60-second resend cooldown.
- **Rate Limiting:** Sliding-window rate limiting on IP and email addresses via `auth_rate_limits`.
- **Session Protection:** Session ID regeneration upon login and logout with strict HttpOnly session cookies.
- **Strict Authorization:** Order history in dashboard enforces server-side `user_id` ownership checks (zero numeric ID enumeration).
- **Checkout Linkage:** Logged-in customers have delivery details prefilled and their `user_id` atomically linked to orders upon checkout commit. Guest checkout remains completely supported.
