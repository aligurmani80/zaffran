-- Backup before Announcements and WhatsApp Proofs upgrade
-- Date: 2026-10-22 09:16:55

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

