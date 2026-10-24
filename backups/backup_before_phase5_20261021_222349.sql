-- Zaffran DB Backup Before Phase 5
DROP TABLE IF EXISTS admins;
CREATE TABLE `admins` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `email` varchar(191) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO admins (id, username, email, password_hash, created_at) VALUES ('1', 'admin', 'admin@zaffran.local', '$2y$10$JEXxGiYoP7c.0NkoGC..WeuLuXrCv.ZJAdxPt7MogtwnMmFL58Sp6', '2026-10-17 23:58:10');

DROP TABLE IF EXISTS announcements;
CREATE TABLE `announcements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `bg_color` varchar(20) DEFAULT '#d4af37',
  `text_color` varchar(20) DEFAULT '#000000',
  `display_type` enum('scrolling','static') DEFAULT 'scrolling',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO announcements (id, title, message, is_active, created_at, bg_color, text_color, display_type, updated_at) VALUES ('1', 'Special Harvest Offer', 'Special Offer: 100% Pure Himalayan Shilajit, Imperial Zaffran & Luxury Perfumes. Free Nationwide Delivery on Orders over Rs. 5,000!', '1', '2026-10-18 00:04:15', '#d4af37', '#000000', 'scrolling', '2026-10-18 00:04:15');

DROP TABLE IF EXISTS auth_rate_limits;
CREATE TABLE `auth_rate_limits` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rate_key` varchar(100) NOT NULL,
  `attempts` int(10) unsigned NOT NULL DEFAULT 1,
  `last_attempt` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `rate_key` (`rate_key`),
  KEY `idx_rate_key` (`rate_key`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO auth_rate_limits (id, rate_key, attempts, last_attempt) VALUES ('2', 'otp_req_email_bd48339c040b42f17fed11c82137f725', '2', '2026-10-22 00:27:11');
INSERT INTO auth_rate_limits (id, rate_key, attempts, last_attempt) VALUES ('3', 'audit_test_rate_5393a5c3', '2', '2026-10-22 00:27:11');
INSERT INTO auth_rate_limits (id, rate_key, attempts, last_attempt) VALUES ('6', 'otp_req_ip_837ec5754f503cfaaee0929fd48974e7', '2', '2026-10-21 22:15:56');
INSERT INTO auth_rate_limits (id, rate_key, attempts, last_attempt) VALUES ('7', 'otp_req_email_5f1b7576f14d8ee3051f017146cc4a0c', '2', '2026-10-21 22:15:56');

DROP TABLE IF EXISTS categories;
CREATE TABLE `categories` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `display_order` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO categories (id, name, slug, description, image_url, display_order, is_active, created_at) VALUES ('1', 'Perfume', 'perfume', 'Artisanal Extrait de Parfum blends combining natural aged woods, amber and floral absolutes.', 'assets/images/categories/cat_perfume.svg', '1', '1', '2026-10-21 23:53:42');
INSERT INTO categories (id, name, slug, description, image_url, display_order, is_active, created_at) VALUES ('2', 'Shilajit', 'shilajit', 'High-altitude Himalayan gold-grade resin rich in natural ionic minerals and fulvic acid.', 'assets/images/categories/cat_shilajit.svg', '2', '1', '2026-10-21 23:53:42');
INSERT INTO categories (id, name, slug, description, image_url, display_order, is_active, created_at) VALUES ('3', 'Zaffran', 'zaffran', 'Grade-1 Super Negin Kashmiri whole stigmas prized for intense coloring and potent aroma.', 'assets/images/categories/cat_zaffran.svg', '3', '1', '2026-10-21 23:53:42');
INSERT INTO categories (id, name, slug, description, image_url, display_order, is_active, created_at) VALUES ('4', 'Perfume Oils', 'perfume-oils', 'Undiluted fragrance oil concentrates and wholesale essences to start your own perfume label.', 'assets/images/categories/cat_oils.svg', '4', '1', '2026-10-21 23:53:42');

DROP TABLE IF EXISTS customer_addresses;
CREATE TABLE `customer_addresses` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `province` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL,
  `area` varchar(150) NOT NULL,
  `street_address` text NOT NULL,
  `postal_code` varchar(20) DEFAULT NULL,
  `landmark` varchar(150) DEFAULT NULL,
  `delivery_instructions` text DEFAULT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `customer_addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO customer_addresses (id, user_id, full_name, phone, province, city, area, street_address, postal_code, landmark, delivery_instructions, is_default, created_at, updated_at) VALUES ('6', '9', 'Test Auditor', '03009999999', 'Punjab', 'Kot Addu', 'Railway Colony', 'Street 4, House 12', NULL, NULL, NULL, '1', '2026-10-18 00:57:04', '2026-10-18 00:57:04');
INSERT INTO customer_addresses (id, user_id, full_name, phone, province, city, area, street_address, postal_code, landmark, delivery_instructions, is_default, created_at, updated_at) VALUES ('9', '12', 'Ali Boss', '03001234567', 'Punjab', 'Lahore', 'Suite 401', 'Gulberg III, Main Boulevard', '54000', NULL, NULL, '1', '2026-10-18 01:12:38', '2026-10-18 01:12:38');

DROP TABLE IF EXISTS customer_email_otps;
CREATE TABLE `customer_email_otps` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `challenge_token` varchar(64) DEFAULT NULL,
  `email` varchar(191) NOT NULL,
  `otp_hash` varchar(255) NOT NULL,
  `expires_at` datetime NOT NULL,
  `attempts` int(10) unsigned NOT NULL DEFAULT 0,
  `max_attempts` int(10) unsigned NOT NULL DEFAULT 5,
  `is_used` tinyint(1) NOT NULL DEFAULT 0,
  `ip_address` varchar(45) NOT NULL DEFAULT '',
  `user_agent` varchar(255) NOT NULL DEFAULT '',
  `purpose` varchar(32) NOT NULL DEFAULT 'auth',
  `last_sent_at` datetime NOT NULL DEFAULT current_timestamp(),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_challenge_token` (`challenge_token`),
  KEY `idx_email_active` (`email`,`is_used`,`expires_at`),
  KEY `idx_ip` (`ip_address`,`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO customer_email_otps (id, challenge_token, email, otp_hash, expires_at, attempts, max_attempts, is_used, ip_address, user_agent, purpose, last_sent_at, created_at) VALUES ('16', 'c8507c4ae35319dfd2ad3dc3817cd0f4bce5f76b38e9c1bc79bddce8a4e55f51', 'aligurmanri80@gmail.com', 'cf738fe95d0424962010b6b636236dc8dbd63da65abf48ba58e9170c77cf2df6', '2026-10-21 22:18:27', '0', '5', '1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', 'auth', '2026-10-21 22:13:27', '2026-10-21 22:13:27');
INSERT INTO customer_email_otps (id, challenge_token, email, otp_hash, expires_at, attempts, max_attempts, is_used, ip_address, user_agent, purpose, last_sent_at, created_at) VALUES ('17', '6893177d722696a5d5486d372041fde3ebe05e2b332a07a2bc173b41cdfc76b5', 'aligurmanri80@gmail.com', 'fb64798dc82589609981de1f113ebe5d162d641ea9f7de6673705052f3f754a6', '2026-10-21 22:20:56', '1', '5', '1', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36', 'auth', '2026-10-21 22:15:56', '2026-10-21 22:15:56');

DROP TABLE IF EXISTS order_items;
CREATE TABLE `order_items` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL,
  `product_id` int(10) unsigned DEFAULT NULL,
  `product_name` varchar(200) NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `quantity` int(10) unsigned NOT NULL,
  `total_price` decimal(12,2) NOT NULL,
  `product_image` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_order_items_order` (`order_id`),
  CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO order_items (id, order_id, product_id, product_name, price, quantity, total_price, product_image, created_at) VALUES ('3', '4', '1', 'Zaffran (Saffron)', '8900.00', '1', '8900.00', 'assets/zaffran.jpg', '2026-10-18 00:22:34');
INSERT INTO order_items (id, order_id, product_id, product_name, price, quantity, total_price, product_image, created_at) VALUES ('4', '4', '2', 'Shilajit', '8500.00', '1', '8500.00', 'assets/shilajit.jpg', '2026-10-18 00:22:34');
INSERT INTO order_items (id, order_id, product_id, product_name, price, quantity, total_price, product_image, created_at) VALUES ('10', '10', '1', 'Zaffran (Saffron)', '8900.00', '1', '8900.00', 'assets/zaffran.jpg', '2026-10-18 01:12:38');

DROP TABLE IF EXISTS orders;
CREATE TABLE `orders` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_number` varchar(60) NOT NULL,
  `access_token` varchar(64) DEFAULT NULL,
  `idempotency_key` varchar(64) DEFAULT NULL,
  `user_id` int(10) unsigned DEFAULT NULL,
  `customer_name` varchar(150) NOT NULL,
  `customer_email` varchar(191) NOT NULL,
  `customer_phone` varchar(30) NOT NULL,
  `delivery_address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `postal_code` varchar(30) DEFAULT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `shipping_fee` decimal(12,2) NOT NULL DEFAULT 0.00,
  `discount_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(12,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL DEFAULT 'cod',
  `order_status` varchar(50) NOT NULL DEFAULT 'pending',
  `currency` varchar(10) NOT NULL DEFAULT 'PKR',
  `payment_status` varchar(50) NOT NULL DEFAULT 'pending',
  `delivery_address_json` longtext NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_number` (`order_number`),
  UNIQUE KEY `uq_orders_access_token` (`access_token`),
  UNIQUE KEY `uq_orders_idempotency` (`idempotency_key`),
  KEY `idx_order_user` (`user_id`),
  KEY `idx_order_num` (`order_number`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO orders (id, order_number, access_token, idempotency_key, user_id, customer_name, customer_email, customer_phone, delivery_address, city, province, postal_code, subtotal, shipping_fee, discount_total, grand_total, total_amount, payment_method, order_status, currency, payment_status, delivery_address_json, notes, created_at, updated_at) VALUES ('4', 'ORD-VERIF-1792264954', NULL, NULL, '3', 'Ali Boss', 'boss@zaffran.local', '03009999999', NULL, NULL, NULL, NULL, '17400.00', '0.00', '0.00', '17400.00', '17400.00', 'cod', 'completed', 'PKR', 'paid', '{\"full_name\":\"Ali Boss\",\"phone\":\"03009999999\",\"city\":\"Kot Addu\",\"province\":\"Punjab\",\"street_address\":\"Circular Road Villa 1\"}', NULL, '2026-10-18 00:22:34', '2026-10-22 00:07:30');
INSERT INTO orders (id, order_number, access_token, idempotency_key, user_id, customer_name, customer_email, customer_phone, delivery_address, city, province, postal_code, subtotal, shipping_fee, discount_total, grand_total, total_amount, payment_method, order_status, currency, payment_status, delivery_address_json, notes, created_at, updated_at) VALUES ('10', 'ORD-20261017-DA0500', NULL, NULL, '12', 'Ali Boss', 'ali_checkout_test@example.com', '03001234567', NULL, NULL, NULL, NULL, '8900.00', '0.00', '0.00', '8900.00', '8900.00', 'cod', 'pending', 'PKR', 'pending', '{\"first_name\":\"Ali\",\"last_name\":\"Boss\",\"full_name\":\"Ali Boss\",\"phone\":\"03001234567\",\"address\":\"Gulberg III, Main Boulevard\",\"apartment\":\"Suite 401\",\"city\":\"Lahore\",\"postal_code\":\"54000\",\"province\":\"Punjab\",\"country\":\"Pakistan\",\"billing_address\":\"Same as shipping\"}', 'Cash on Delivery order.', '2026-10-18 01:12:38', '2026-10-22 00:07:30');

DROP TABLE IF EXISTS password_resets;
CREATE TABLE `password_resets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(191) NOT NULL,
  `token_hash` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token_hash` (`token_hash`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS products;
CREATE TABLE `products` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `category` varchar(100) NOT NULL,
  `category_slug` varchar(100) NOT NULL DEFAULT 'perfumes',
  `badge` varchar(100) DEFAULT NULL,
  `short_desc` varchar(255) DEFAULT NULL,
  `long_desc` text DEFAULT NULL,
  `price` decimal(12,2) NOT NULL,
  `sale_price` decimal(12,2) DEFAULT NULL,
  `image_url` varchar(255) NOT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `stock` int(10) unsigned NOT NULL DEFAULT 50,
  `sku` varchar(64) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO products (id, name, slug, category, category_slug, badge, short_desc, long_desc, price, sale_price, image_url, is_featured, is_active, stock, sku, created_at) VALUES ('1', 'Zaffran (Saffron)', 'zaffran-saffron', 'Pure & Natural', 'zaffran', 'Pure & Natural', 'Premium Quality Super Negin Zaffran', NULL, '8900.00', '7990.00', 'assets/zaffran.jpg', '1', '1', '50', 'ZAF-001', '2026-10-17 23:36:29');
INSERT INTO products (id, name, slug, category, category_slug, badge, short_desc, long_desc, price, sale_price, image_url, is_featured, is_active, stock, sku, created_at) VALUES ('2', 'Shilajit', 'shilajit-resin', 'Pure & Natural', 'shilajit', 'Pure & Natural', 'Premium Quality Gold Grade Himalayan Shilajit Resin', NULL, '8500.00', NULL, 'assets/shilajit.jpg', '1', '1', '50', 'SHI-001', '2026-10-17 23:36:29');
INSERT INTO products (id, name, slug, category, category_slug, badge, short_desc, long_desc, price, sale_price, image_url, is_featured, is_active, stock, sku, created_at) VALUES ('3', 'Perfumes', 'luxury-perfumes', 'Luxury Scents', 'perfume', 'Luxury Scents', 'Premium Luxury Sovereign Oud & Scent Fragrances', NULL, '14500.00', NULL, 'assets/perfume.jpg', '1', '1', '50', 'PRF-001', '2026-10-17 23:36:29');

DROP TABLE IF EXISTS reviews;
CREATE TABLE `reviews` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_name` varchar(150) NOT NULL,
  `rating` int(11) NOT NULL DEFAULT 5,
  `review_text` text NOT NULL,
  `is_approved` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS store_settings;
CREATE TABLE `store_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
INSERT INTO store_settings (setting_key, setting_value, created_at, updated_at) VALUES ('bank_account_number', '02100108923451', '2026-10-18 01:08:30', '2026-10-18 01:08:30');
INSERT INTO store_settings (setting_key, setting_value, created_at, updated_at) VALUES ('bank_account_title', 'Zaffran & Oud Luxury Store', '2026-10-18 01:08:30', '2026-10-18 01:08:30');
INSERT INTO store_settings (setting_key, setting_value, created_at, updated_at) VALUES ('bank_iban', 'PK45MEZN0002100108923451', '2026-10-18 01:08:30', '2026-10-18 01:08:30');
INSERT INTO store_settings (setting_key, setting_value, created_at, updated_at) VALUES ('bank_name', 'Meezan Bank Limited', '2026-10-18 01:08:30', '2026-10-18 01:08:30');
INSERT INTO store_settings (setting_key, setting_value, created_at, updated_at) VALUES ('default_delivery_charges', '250', '2026-10-18 00:04:15', '2026-10-22 00:07:30');
INSERT INTO store_settings (setting_key, setting_value, created_at, updated_at) VALUES ('enable_bank_transfer', '1', '2026-10-18 01:08:30', '2026-10-18 01:08:30');
INSERT INTO store_settings (setting_key, setting_value, created_at, updated_at) VALUES ('enable_cod', '1', '2026-10-18 00:04:15', '2026-10-18 00:04:15');
INSERT INTO store_settings (setting_key, setting_value, created_at, updated_at) VALUES ('facebook_url', 'https://facebook.com', '2026-10-18 00:04:15', '2026-10-18 00:04:15');
INSERT INTO store_settings (setting_key, setting_value, created_at, updated_at) VALUES ('free_delivery_threshold', '5000', '2026-10-18 00:04:15', '2026-10-18 00:04:15');
INSERT INTO store_settings (setting_key, setting_value, created_at, updated_at) VALUES ('instagram_url', 'https://instagram.com', '2026-10-18 00:04:15', '2026-10-18 00:04:15');
INSERT INTO store_settings (setting_key, setting_value, created_at, updated_at) VALUES ('store_address', 'Circular Road, Kot Addu, Punjab, Pakistan', '2026-10-18 00:04:15', '2026-10-18 00:04:15');
INSERT INTO store_settings (setting_key, setting_value, created_at, updated_at) VALUES ('store_email', 'sales@zaffran.local', '2026-10-18 00:04:15', '2026-10-18 00:04:15');
INSERT INTO store_settings (setting_key, setting_value, created_at, updated_at) VALUES ('store_logo', 'logo.jpg', '2026-10-18 00:04:15', '2026-10-18 00:04:15');
INSERT INTO store_settings (setting_key, setting_value, created_at, updated_at) VALUES ('store_name', 'Zaffran, Shilajit & Perfumes', '2026-10-18 00:04:14', '2026-10-18 00:04:14');
INSERT INTO store_settings (setting_key, setting_value, created_at, updated_at) VALUES ('store_phone', '0304-1202111', '2026-10-18 00:04:15', '2026-10-18 00:04:15');
INSERT INTO store_settings (setting_key, setting_value, created_at, updated_at) VALUES ('tiktok_url', 'https://tiktok.com', '2026-10-18 00:04:15', '2026-10-18 00:04:15');
INSERT INTO store_settings (setting_key, setting_value, created_at, updated_at) VALUES ('whatsapp_contact_link', 'https://wa.me/923041202111', '2026-10-18 00:04:15', '2026-10-18 00:04:15');
INSERT INTO store_settings (setting_key, setting_value, created_at, updated_at) VALUES ('whatsapp_number', '03041202111', '2026-10-18 00:04:15', '2026-10-18 00:04:15');

DROP TABLE IF EXISTS users;
CREATE TABLE `users` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `email` varchar(191) NOT NULL,
  `email_verified_at` datetime DEFAULT NULL,
  `phone` varchar(30) NOT NULL,
  `account_status` enum('active','suspended','pending') NOT NULL DEFAULT 'active',
  `password` varchar(255) DEFAULT NULL,
  `remember_token` varchar(64) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO users (id, name, email, email_verified_at, phone, account_status, password, remember_token, created_at, updated_at) VALUES ('3', 'ali gurmanri', 'aligurmanri80@gmail.com', '2026-10-21 22:16:32', '03111730444', 'active', '$2y$10$frjg0CSvFrkk03TwLvRszOGh336F6cIYwu4JOyWQKYcW.R7MAHGJu', NULL, '2026-10-18 00:00:44', '2026-10-22 01:16:32');
INSERT INTO users (id, name, email, email_verified_at, phone, account_status, password, remember_token, created_at, updated_at) VALUES ('9', 'Test Auditor', 'otp_audit_1792267023@zaffrantest.com', NULL, '', 'active', '$2y$10$7pOUZ9LUANPAIdjZCfqsMufwjOBUm6TUEve8UX4bXU3wbwwYAu4CS', NULL, '2026-10-18 00:57:04', '2026-10-18 00:57:04');
INSERT INTO users (id, name, email, email_verified_at, phone, account_status, password, remember_token, created_at, updated_at) VALUES ('12', 'Ali Boss', 'ali_checkout_test@example.com', '2026-10-18 01:12:38', '03001234567', 'active', '$2y$10$EccFoj9NZ2hEPyzAtoO4UesI6M3kv/7t1sm4tGD7laUegbruRRQnG', NULL, '2026-10-18 01:12:38', '2026-10-18 01:12:38');

DROP TABLE IF EXISTS whatsapp_proofs;
CREATE TABLE `whatsapp_proofs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `image_path` varchar(255) NOT NULL,
  `customer_display_name` varchar(150) DEFAULT NULL,
  `product_id` int(10) unsigned DEFAULT NULL,
  `caption` text DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `idx_published_order` (`is_published`,`display_order`),
  CONSTRAINT `whatsapp_proofs_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO whatsapp_proofs (id, image_path, customer_display_name, product_id, caption, is_published, display_order, created_at, updated_at) VALUES ('1', 'uploads/whatsapp_proofs/proof_zaffran_sample.jpg', 'Malik Jahangir (Lahore)', '1', 'Received pure Kashmiri Super Negin Zaffran. The color, intense aroma, and filament length are 100% authentic. Superb packaging!', '1', '1', '2026-10-18 00:03:47', '2026-10-18 00:03:47');
INSERT INTO whatsapp_proofs (id, image_path, customer_display_name, product_id, caption, is_published, display_order, created_at, updated_at) VALUES ('2', 'uploads/whatsapp_proofs/proof_shilajit_sample.jpg', 'Dr. Arshad (Islamabad)', '2', 'Gold Grade Himalayan Shilajit passed all warm water purity tests. Clean earthy mineral taste, feeling energized within days.', '1', '2', '2026-10-18 00:03:47', '2026-10-18 00:03:47');
INSERT INTO whatsapp_proofs (id, image_path, customer_display_name, product_id, caption, is_published, display_order, created_at, updated_at) VALUES ('3', 'uploads/whatsapp_proofs/proof_perfume_sample.jpg', 'Capt. Bilal (Karachi)', '3', 'The Sovereign Oud fragrance projection is majestic. Lasts more than 24 hours on clothes. Will definitely reorder.', '1', '3', '2026-10-18 00:03:47', '2026-10-18 00:03:47');

