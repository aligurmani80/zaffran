-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: zaffran_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `admin_audit_logs`
--

DROP TABLE IF EXISTS `admin_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_audit_logs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` int(10) unsigned DEFAULT NULL,
  `admin_email` varchar(191) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` varchar(50) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_entity` (`entity_type`,`entity_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admin_audit_logs`
--

LOCK TABLES `admin_audit_logs` WRITE;
/*!40000 ALTER TABLE `admin_audit_logs` DISABLE KEYS */;
INSERT INTO `admin_audit_logs` VALUES (3,1,'admin@zaffran.local','admin_login_success','admin','1','Admin logged in successfully from IP ::1','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/155.0.0.0 Safari/537.36','2026-10-22 12:07:04');
/*!40000 ALTER TABLE `admin_audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `admins`
--

DROP TABLE IF EXISTS `admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admins` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `email` varchar(191) NOT NULL,
  `role` enum('super_admin','order_manager','staff') NOT NULL DEFAULT 'super_admin',
  `status` enum('active','suspended') NOT NULL DEFAULT 'active',
  `password_hash` varchar(255) NOT NULL,
  `last_login_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `admins`
--

LOCK TABLES `admins` WRITE;
/*!40000 ALTER TABLE `admins` DISABLE KEYS */;
INSERT INTO `admins` VALUES (1,'admin','admin@zaffran.local','super_admin','active','$2y$10$JEXxGiYoP7c.0NkoGC..WeuLuXrCv.ZJAdxPt7MogtwnMmFL58Sp6','2026-10-22 12:07:04','2026-10-17 23:58:10','2026-10-22 12:07:04');
/*!40000 ALTER TABLE `admins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `announcements`
--

DROP TABLE IF EXISTS `announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcements` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `link_url` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `bg_color` varchar(20) DEFAULT '#d4af37',
  `text_color` varchar(20) DEFAULT '#000000',
  `display_type` enum('scrolling','static') DEFAULT 'scrolling',
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `announcements`
--

LOCK TABLES `announcements` WRITE;
/*!40000 ALTER TABLE `announcements` DISABLE KEYS */;
INSERT INTO `announcements` VALUES (1,'Special Harvest Offer','Special Offer: 100% Pure Himalayan Shilajit, Imperial Zaffran & Luxury Perfumes. Free Nationwide Delivery on Orders over Rs. 5,000!',NULL,1,0,'2026-10-18 00:04:15','#d4af37','#000000','scrolling','2026-10-18 00:04:15');
/*!40000 ALTER TABLE `announcements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `auth_rate_limits`
--

DROP TABLE IF EXISTS `auth_rate_limits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `auth_rate_limits` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `rate_key` varchar(100) NOT NULL,
  `attempts` int(10) unsigned NOT NULL DEFAULT 1,
  `last_attempt` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `rate_key` (`rate_key`),
  KEY `idx_rate_key` (`rate_key`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `auth_rate_limits`
--

LOCK TABLES `auth_rate_limits` WRITE;
/*!40000 ALTER TABLE `auth_rate_limits` DISABLE KEYS */;
/*!40000 ALTER TABLE `auth_rate_limits` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Perfume','perfume','Artisanal Extrait de Parfum blends combining natural aged woods, amber and floral absolutes.','assets/images/categories/cat_perfume.jpg',1,1,'2026-10-21 23:53:42'),(2,'Shilajit','shilajit','High-altitude Himalayan gold-grade resin rich in natural ionic minerals and fulvic acid.','assets/images/categories/cat_shilajit.jpg',2,1,'2026-10-21 23:53:42'),(3,'Zaffran','zaffran','Grade-1 Super Negin Kashmiri whole stigmas prized for intense coloring and potent aroma.','assets/images/categories/cat_zaffran.jpg',3,1,'2026-10-21 23:53:42'),(4,'Perfume Oils','perfume-oils','Undiluted fragrance oil concentrates and wholesale essences to start your own perfume label.','assets/images/categories/cat_oils.jpg',4,1,'2026-10-21 23:53:42');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_addresses`
--

DROP TABLE IF EXISTS `customer_addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_addresses`
--

LOCK TABLES `customer_addresses` WRITE;
/*!40000 ALTER TABLE `customer_addresses` DISABLE KEYS */;
INSERT INTO `customer_addresses` VALUES (6,9,'Test Auditor','03009999999','Punjab','Kot Addu','Railway Colony','Street 4, House 12',NULL,NULL,NULL,1,'2026-10-18 00:57:04','2026-10-18 00:57:04'),(9,12,'Ali Boss','03001234567','Punjab','Lahore','Suite 401','Gulberg III, Main Boulevard','54000',NULL,NULL,1,'2026-10-18 01:12:38','2026-10-18 01:12:38');
/*!40000 ALTER TABLE `customer_addresses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_email_otps`
--

DROP TABLE IF EXISTS `customer_email_otps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_email_otps`
--

LOCK TABLES `customer_email_otps` WRITE;
/*!40000 ALTER TABLE `customer_email_otps` DISABLE KEYS */;
INSERT INTO `customer_email_otps` VALUES (16,'c8507c4ae35319dfd2ad3dc3817cd0f4bce5f76b38e9c1bc79bddce8a4e55f51','aligurmanri80@gmail.com','cf738fe95d0424962010b6b636236dc8dbd63da65abf48ba58e9170c77cf2df6','2026-10-21 22:18:27',0,5,1,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','auth','2026-10-21 22:13:27','2026-10-21 22:13:27'),(17,'6893177d722696a5d5486d372041fde3ebe05e2b332a07a2bc173b41cdfc76b5','aligurmanri80@gmail.com','fb64798dc82589609981de1f113ebe5d162d641ea9f7de6673705052f3f754a6','2026-10-21 22:20:56',1,5,1,'::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/154.0.0.0 Safari/537.36','auth','2026-10-21 22:15:56','2026-10-21 22:15:56');
/*!40000 ALTER TABLE `customer_email_otps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hero_slides`
--

DROP TABLE IF EXISTS `hero_slides`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hero_slides` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `small_badge` varchar(100) DEFAULT NULL,
  `title_prefix` varchar(150) DEFAULT NULL,
  `animated_word` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `desktop_image` varchar(255) NOT NULL,
  `mobile_image` varchar(255) DEFAULT NULL,
  `primary_cta_label` varchar(100) NOT NULL DEFAULT 'Explore Collection',
  `primary_cta_url` varchar(255) NOT NULL DEFAULT 'shop.php',
  `secondary_cta_label` varchar(100) DEFAULT NULL,
  `secondary_cta_url` varchar(255) DEFAULT NULL,
  `guarantee_text` varchar(255) DEFAULT NULL,
  `slide_class` varchar(50) DEFAULT 'slide-perfume',
  `display_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hero_slides`
--

LOCK TABLES `hero_slides` WRITE;
/*!40000 ALTER TABLE `hero_slides` DISABLE KEYS */;
INSERT INTO `hero_slides` VALUES (1,'Artisanal Haute Parfumerie','Discover Rare & Captivating','Perfumes','Experience slow-matured fragrances crafted with pure natural essences, rare Cambodian oud, Damascus rose, and rich botanical accords designed for timeless sillage.','assets/images/hero/hero_perfume.jpg',NULL,'Explore Fragrances','shop.php?category=perfume','Perfume Oil Supply','category.php?slug=perfume-oils','100% Pure Essential Extrait Concentrations','slide-perfume',1,1,'2026-10-22 01:24:16','2026-10-22 02:32:17'),(2,'Wild Himalayan Harvest','100% Pure & Potent','Shilajit','Gold Grade Himalayan resin harvested from 16,000+ ft altitudes. Naturally rich in fulvic acid, bioactive plant minerals, and adaptogenic vitality for authentic daily wellness.','assets/images/hero/hero_shilajit.jpg',NULL,'View Pure Shilajit','shop.php?category=shilajit','Our Sourcing Story','#about','Lab-Tested For Heavy Metals & Purity','slide-shilajit',2,1,'2026-10-22 01:24:16','2026-10-22 02:32:17'),(3,'Imperial Super Negin','Hand-Harvested Royal','Zaffran','Deep ruby-red stigmas celebrated for unrivaled safranal aroma and crocin coloring power. Unadulterated, whole-thread saffron dried at low temperatures to lock in medicinal essence.','assets/images/hero/hero_zaffran.jpg',NULL,'Shop Royal Zaffran','shop.php?category=zaffran','Explore Collections','shop.php','Zero Stems & Pure Filament Guarantee','slide-zaffran',3,1,'2026-10-22 01:24:16','2026-10-22 02:32:17');
/*!40000 ALTER TABLE `hero_slides` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (3,4,1,'Zaffran (Saffron)',8900.00,1,8900.00,'assets/zaffran.jpg','2026-10-18 00:22:34'),(4,4,2,'Shilajit',8500.00,1,8500.00,'assets/shilajit.jpg','2026-10-18 00:22:34'),(10,10,1,'Zaffran (Saffron)',8900.00,1,8900.00,'assets/zaffran.jpg','2026-10-18 01:12:38');
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_notes`
--

DROP TABLE IF EXISTS `order_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `order_notes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `order_id` int(10) unsigned NOT NULL,
  `admin_id` int(10) unsigned DEFAULT NULL,
  `admin_name` varchar(150) DEFAULT NULL,
  `note` text NOT NULL,
  `is_customer_visible` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_notes`
--

LOCK TABLES `order_notes` WRITE;
/*!40000 ALTER TABLE `order_notes` DISABLE KEYS */;
/*!40000 ALTER TABLE `order_notes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (4,'ORD-VERIF-1792264954',NULL,NULL,3,'Ali Boss','boss@zaffran.local','03009999999',NULL,NULL,NULL,NULL,17400.00,0.00,0.00,17400.00,17400.00,'cod','completed','PKR','paid','{\"full_name\":\"Ali Boss\",\"phone\":\"03009999999\",\"city\":\"Kot Addu\",\"province\":\"Punjab\",\"street_address\":\"Circular Road Villa 1\"}',NULL,'2026-10-18 00:22:34','2026-10-22 00:07:30'),(10,'ORD-20261017-DA0500',NULL,NULL,12,'Ali Boss','ali_checkout_test@example.com','03001234567',NULL,NULL,NULL,NULL,8900.00,0.00,0.00,8900.00,8900.00,'cod','pending','PKR','pending','{\"first_name\":\"Ali\",\"last_name\":\"Boss\",\"full_name\":\"Ali Boss\",\"phone\":\"03001234567\",\"address\":\"Gulberg III, Main Boulevard\",\"apartment\":\"Suite 401\",\"city\":\"Lahore\",\"postal_code\":\"54000\",\"province\":\"Punjab\",\"country\":\"Pakistan\",\"billing_address\":\"Same as shipping\"}','Cash on Delivery order.','2026-10-18 01:12:38','2026-10-22 00:07:30');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_gallery`
--

DROP TABLE IF EXISTS `product_gallery`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `product_gallery` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` int(10) unsigned NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_gallery`
--

LOCK TABLES `product_gallery` WRITE;
/*!40000 ALTER TABLE `product_gallery` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_gallery` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
  `track_stock` tinyint(1) NOT NULL DEFAULT 0,
  `sku` varchar(64) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,'Zaffran (Saffron)','zaffran-saffron','Pure & Natural','zaffran','Pure & Natural','Premium Quality Super Negin Zaffran',NULL,8900.00,7990.00,'assets/images/products/zaffran_clean.jpg',1,1,50,0,'ZAF-001','2026-10-17 23:36:29'),(2,'Shilajit','shilajit-resin','Pure & Natural','shilajit','Pure & Natural','Premium Quality Gold Grade Himalayan Shilajit Resin',NULL,8500.00,NULL,'assets/images/products/shilajit_clean.jpg',1,1,50,0,'SHI-001','2026-10-17 23:36:29'),(3,'Perfumes','luxury-perfumes','Luxury Scents','perfume','Luxury Scents','Premium Luxury Sovereign Oud & Scent Fragrances',NULL,14500.00,NULL,'assets/images/products/perfume_clean.jpg',1,1,50,0,'PRF-001','2026-10-17 23:36:29');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `reviews` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `customer_name` varchar(150) NOT NULL,
  `rating` int(11) NOT NULL DEFAULT 5,
  `review_text` text NOT NULL,
  `is_approved` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `store_settings`
--

DROP TABLE IF EXISTS `store_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `store_settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `store_settings`
--

LOCK TABLES `store_settings` WRITE;
/*!40000 ALTER TABLE `store_settings` DISABLE KEYS */;
INSERT INTO `store_settings` VALUES ('bank_account_number','02100108923451','2026-10-18 01:08:30','2026-10-18 01:08:30'),('bank_account_title','Zaffran & Oud Luxury Store','2026-10-18 01:08:30','2026-10-18 01:08:30'),('bank_iban','PK45MEZN0002100108923451','2026-10-18 01:08:30','2026-10-18 01:08:30'),('bank_name','Meezan Bank Limited','2026-10-18 01:08:30','2026-10-18 01:08:30'),('default_delivery_charges','250','2026-10-18 00:04:15','2026-10-22 00:07:30'),('enable_bank_transfer','1','2026-10-18 01:08:30','2026-10-18 01:08:30'),('enable_cod','1','2026-10-18 00:04:15','2026-10-18 00:04:15'),('facebook_url','https://facebook.com','2026-10-18 00:04:15','2026-10-18 00:04:15'),('favicon_url','assets/images/logo.jpg','2026-10-22 01:24:16','2026-10-22 01:24:16'),('free_delivery_threshold','5000','2026-10-18 00:04:15','2026-10-18 00:04:15'),('instagram_url','https://instagram.com','2026-10-18 00:04:15','2026-10-18 00:04:15'),('perfume_oil_supply_desc','We supply pure, undiluted fragrance oil compounds and formulation concentrates to independent boutique perfumers, luxury soap makers, and emerging artisanal fragrance houses across Pakistan.','2026-10-22 01:24:16','2026-10-22 01:24:16'),('perfume_oil_supply_image','assets/images/business/perfume_oils_business.jpg','2026-10-22 01:24:16','2026-10-22 02:32:17'),('perfume_oil_supply_phone','0304-1202111','2026-10-22 01:24:16','2026-10-22 01:24:16'),('perfume_oil_supply_title','Bulk Artisanal Perfume Oil Concentrates','2026-10-22 01:24:16','2026-10-22 01:24:16'),('perfume_oil_supply_visible','1','2026-10-22 01:24:16','2026-10-22 01:24:16'),('perfume_oil_supply_whatsapp','03041202111','2026-10-22 01:24:16','2026-10-22 01:24:16'),('site_description','Zaffran crafts ultra-pure organic Himalayan Shilajit, Imperial Super Negin Saffron, and artisanal French-oriental perfumes.','2026-10-22 01:24:16','2026-10-22 01:24:16'),('site_tagline','Pure Botanicals, Artisanal Fragrances & Essence Oils','2026-10-22 01:24:16','2026-10-22 01:24:16'),('store_address','Circular Road, Kot Addu, Punjab, Pakistan','2026-10-18 00:04:15','2026-10-18 00:04:15'),('store_email','sales@zaffran.local','2026-10-18 00:04:15','2026-10-18 00:04:15'),('store_logo','logo.jpg','2026-10-18 00:04:15','2026-10-18 00:04:15'),('store_name','Zaffran, Shilajit & Perfumes','2026-10-18 00:04:14','2026-10-18 00:04:14'),('store_phone','0304-1202111','2026-10-18 00:04:15','2026-10-18 00:04:15'),('tiktok_url','https://tiktok.com','2026-10-18 00:04:15','2026-10-18 00:04:15'),('whatsapp_contact_link','https://wa.me/923041202111','2026-10-18 00:04:15','2026-10-18 00:04:15'),('whatsapp_number','03041202111','2026-10-18 00:04:15','2026-10-18 00:04:15');
/*!40000 ALTER TABLE `store_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (3,'ali gurmanri','aligurmanri80@gmail.com','2026-10-21 22:16:32','03111730444','active','$2y$10$frjg0CSvFrkk03TwLvRszOGh336F6cIYwu4JOyWQKYcW.R7MAHGJu',NULL,'2026-10-18 00:00:44','2026-10-22 01:16:32'),(9,'Test Auditor','otp_audit_1792267023@zaffrantest.com',NULL,'','active','$2y$10$7pOUZ9LUANPAIdjZCfqsMufwjOBUm6TUEve8UX4bXU3wbwwYAu4CS',NULL,'2026-10-18 00:57:04','2026-10-18 00:57:04'),(12,'Ali Boss','ali_checkout_test@example.com','2026-10-18 01:12:38','03001234567','active','$2y$10$EccFoj9NZ2hEPyzAtoO4UesI6M3kv/7t1sm4tGD7laUegbruRRQnG',NULL,'2026-10-18 01:12:38','2026-10-18 01:12:38');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `whatsapp_proofs`
--

DROP TABLE IF EXISTS `whatsapp_proofs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `whatsapp_proofs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `image_path` varchar(255) NOT NULL,
  `customer_display_name` varchar(150) DEFAULT NULL,
  `product_id` int(10) unsigned DEFAULT NULL,
  `rating` int(11) DEFAULT NULL,
  `verified_buyer` tinyint(1) NOT NULL DEFAULT 1,
  `caption` text DEFAULT NULL,
  `is_published` tinyint(1) NOT NULL DEFAULT 1,
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `idx_published_order` (`is_published`,`display_order`),
  CONSTRAINT `whatsapp_proofs_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `whatsapp_proofs`
--

LOCK TABLES `whatsapp_proofs` WRITE;
/*!40000 ALTER TABLE `whatsapp_proofs` DISABLE KEYS */;
INSERT INTO `whatsapp_proofs` VALUES (1,'uploads/whatsapp_proofs/proof_zaffran_sample.jpg','Malik Jahangir (Lahore)',1,NULL,1,'Received pure Kashmiri Super Negin Zaffran. The color, intense aroma, and filament length are 100% authentic. Superb packaging!',0,1,'2026-10-18 00:03:47','2026-10-22 12:28:38'),(2,'uploads/whatsapp_proofs/proof_shilajit_sample.jpg','Dr. Arshad (Islamabad)',2,NULL,1,'Gold Grade Himalayan Shilajit passed all warm water purity tests. Clean earthy mineral taste, feeling energized within days.',0,2,'2026-10-18 00:03:47','2026-10-22 12:28:38'),(3,'uploads/whatsapp_proofs/proof_perfume_sample.jpg','Capt. Bilal (Karachi)',3,NULL,1,'The Sovereign Oud fragrance projection is majestic. Lasts more than 24 hours on clothes. Will definitely reorder.',0,3,'2026-10-18 00:03:47','2026-10-22 12:28:38');
/*!40000 ALTER TABLE `whatsapp_proofs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'zaffran_db'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-10-22 15:02:07
