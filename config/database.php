<?php
/**
 * Database Connection & Environment Loader
 * Zaffran E-Commerce - Phase 3 Enhanced
 */

if (!defined('ZAFFRAN_APP')) {
    define('ZAFFRAN_APP', true);
}

// Function to load .env file
function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv("$name=$value");
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
}

// Load .env
$envPath = dirname(__DIR__) . '/.env';
loadEnv($envPath);

function env($key, $default = null) {
    $val = getenv($key);
    if ($val === false) {
        $val = $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }
    return $val;
}

// Database Connection (PDO Singleton)
function getDbConnection() {
    static $pdo = null;
    if ($pdo === null) {
        $host = env('DB_HOST', 'localhost');
        $port = env('DB_PORT', '3306');
        $dbName = env('DB_NAME', 'zaffran_db');
        $user = env('DB_USER', 'root');
        $pass = env('DB_PASS', '');
        $charset = env('DB_CHARSET', 'utf8mb4');

        $dsn = "mysql:host={$host};port={$port};dbname={$dbName};charset={$charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            return null;
        }
    }
    return $pdo;
}

// Fetch store settings from DB
function getStoreSettings() {
    static $settings = null;
    if ($settings !== null) {
        return $settings;
    }
    
    $settings = [
        'store_name' => 'Zaffran',
        'store_phone' => '0304-1202111',
        'store_email' => 'sales@zaffran.local',
        'store_address' => 'Circular Road, Kot Addu, Punjab, Pakistan',
        'whatsapp_number' => '03041202111',
        'whatsapp_contact_link' => 'https://wa.me/923041202111',
        'facebook_url' => 'https://facebook.com',
        'instagram_url' => 'https://instagram.com',
        'tiktok_url' => 'https://tiktok.com',
        'free_delivery_threshold' => '5000',
        'default_delivery_charges' => '250',
        'enable_cod' => '1'
    ];

    $pdo = getDbConnection();
    if ($pdo) {
        try {
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM store_settings");
            while ($row = $stmt->fetch()) {
                if ($row['setting_value'] !== null && $row['setting_value'] !== '') {
                    $settings[$row['setting_key']] = $row['setting_value'];
                }
            }
        } catch (Exception $e) {
            error_log("Failed to load store settings: " . $e->getMessage());
        }
    }
    return $settings;
}

// Fetch announcements from DB
function getAnnouncements() {
    $pdo = getDbConnection();
    if (!$pdo) {
        return [];
    }

    try {
        $stmt = $pdo->query("SELECT id, title, message, link_url as link, bg_color, text_color, display_type FROM announcements WHERE is_active = 1 ORDER BY display_order ASC, id DESC");
        $dbAnnouncements = $stmt->fetchAll();
        return $dbAnnouncements ?: [];
    } catch (Exception $e) {
        error_log("Failed to load announcements: " . $e->getMessage());
        return [];
    }
}

// Fetch published authentic WhatsApp review screenshots from DB
function getPublishedWhatsAppProofs() {
    $pdo = getDbConnection();
    if (!$pdo) {
        return [];
    }

    try {
        $stmt = $pdo->query("SELECT wp.*, p.name as product_name FROM whatsapp_proofs wp LEFT JOIN products p ON wp.product_id = p.id WHERE wp.is_published = 1 ORDER BY wp.display_order ASC, wp.id DESC");
        return $stmt->fetchAll() ?: [];
    } catch (Exception $e) {
        error_log("Failed to load WhatsApp proofs: " . $e->getMessage());
        return [];
    }
}

// Fetch All Active Categories with verified product counts
function getCategoriesWithCounts() {
    $pdo = getDbConnection();
    if (!$pdo) {
        return [
            ['id' => 1, 'name' => 'Perfume', 'slug' => 'perfume', 'description' => 'Artisanal Extrait de Parfum blends combining natural aged woods, amber and floral absolutes.', 'image_url' => 'assets/images/categories/cat_perfume.svg', 'product_count' => 0],
            ['id' => 2, 'name' => 'Shilajit', 'slug' => 'shilajit', 'description' => 'High-altitude Himalayan gold-grade resin rich in natural ionic minerals and fulvic acid.', 'image_url' => 'assets/images/categories/cat_shilajit.svg', 'product_count' => 0],
            ['id' => 3, 'name' => 'Zaffran', 'slug' => 'zaffran', 'description' => 'Grade-1 Super Negin Kashmiri whole stigmas prized for intense coloring and potent aroma.', 'image_url' => 'assets/images/categories/cat_zaffran.svg', 'product_count' => 0],
            ['id' => 4, 'name' => 'Perfume Oils', 'slug' => 'perfume-oils', 'description' => 'Undiluted fragrance oil concentrates and wholesale essences to start your own perfume label.', 'image_url' => 'assets/images/categories/cat_oils.svg', 'product_count' => 0]
        ];
    }

    try {
        $sql = "SELECT c.id, c.name, c.slug, c.description, c.image_url, COUNT(p.id) AS product_count 
                FROM categories c 
                LEFT JOIN products p ON (p.category_slug = c.slug AND p.is_active = 1) 
                WHERE c.is_active = 1 
                GROUP BY c.id 
                ORDER BY c.display_order ASC, c.id ASC";
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Failed to load categories: " . $e->getMessage());
        return [];
    }
}

// Fetch single category by slug
function getCategoryBySlug($slug) {
    $pdo = getDbConnection();
    if (!$pdo) return null;

    $stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

// Price calculation helper: returns formatted array with discount percentage
function calculateProductPricing($price, $salePrice = null) {
    $reg = (float)$price;
    $sale = ($salePrice !== null && $salePrice !== '') ? (float)$salePrice : null;

    $hasDiscount = false;
    $discountPercent = 0;
    $effectivePrice = $reg;

    if ($sale !== null && $sale > 0 && $sale < $reg) {
        $hasDiscount = true;
        $effectivePrice = $sale;
        $discountPercent = (int)round((($reg - $sale) / $reg) * 100);
    }

    return [
        'regular_price' => $reg,
        'sale_price' => $sale,
        'has_discount' => $hasDiscount,
        'discount_percent' => $discountPercent,
        'effective_price' => $effectivePrice,
        'formatted_effective' => 'Rs. ' . number_format($effectivePrice, 0),
        'formatted_regular' => 'Rs. ' . number_format($reg, 0),
    ];
}

// Calculate Verified Shipping Fee based on Store Settings
function calculateShippingFee($subtotal) {
    $settings = getStoreSettings();
    $subtotal = (float)$subtotal;

    // Check if flat rate is configured
    if (!isset($settings['default_delivery_charges']) || $settings['default_delivery_charges'] === '') {
        return [
            'is_configured' => false,
            'fee' => 0.0,
            'message' => 'Delivery charges have not been configured by the store.'
        ];
    }

    $defaultCharge = (float)$settings['default_delivery_charges'];
    $freeThreshold = isset($settings['free_delivery_threshold']) ? (float)$settings['free_delivery_threshold'] : 0.0;

    $isFree = ($freeThreshold > 0 && $subtotal >= $freeThreshold);
    $fee = $isFree ? 0.0 : $defaultCharge;

    return [
        'is_configured' => true,
        'fee' => $fee,
        'is_free' => $isFree,
        'threshold' => $freeThreshold,
        'default_fee' => $defaultCharge,
        'formatted_fee' => $isFree ? 'Free Delivery' : 'Rs. ' . number_format($fee, 0)
    ];
}

// Fetch Featured Products from DB
function getFeaturedProducts() {
    $pdo = getDbConnection();
    if (!$pdo) {
        return [];
    }

    try {
        $stmt = $pdo->query("SELECT id, name, slug, category, category_slug, badge, short_desc, price, sale_price, stock, image_url, created_at 
                             FROM products 
                             WHERE is_active = 1 AND is_featured = 1 
                             ORDER BY id ASC");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Failed to load products: " . $e->getMessage());
        return [];
    }
}

// Fetch Products with Flexible Filters, Search, and Allowlisted Sorting
function getProductsCatalog($categorySlug = null, $searchQuery = null, $sortOption = 'default') {
    $pdo = getDbConnection();
    if (!$pdo) {
        return [];
    }

    $where = ["is_active = 1"];
    $params = [];

    if (!empty($categorySlug) && $categorySlug !== 'all') {
        $where[] = "category_slug = ?";
        $params[] = $categorySlug;
    }

    if (!empty($searchQuery)) {
        $where[] = "(name LIKE ? OR short_desc LIKE ? OR category LIKE ?)";
        $term = '%' . trim($searchQuery) . '%';
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
    }

    $orderBy = match ($sortOption) {
        'price_asc' => "COALESCE(NULLIF(sale_price, 0), price) ASC",
        'price_desc' => "COALESCE(NULLIF(sale_price, 0), price) DESC",
        'name_asc' => "name ASC",
        'newest' => "created_at DESC, id DESC",
        default => "id ASC"
    };

    $sql = "SELECT id, name, slug, category, category_slug, badge, short_desc, price, sale_price, stock, image_url, created_at 
            FROM products 
            WHERE " . implode(' AND ', $where) . " 
            ORDER BY {$orderBy}";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Catalog query error: " . $e->getMessage());
        return [];
    }
}

// Fetch Single Product by Slug or ID
function getProductBySlug($slug) {
    $pdo = getDbConnection();
    if (!$pdo) return null;

    $stmt = $pdo->prepare("SELECT id, name, slug, category, category_slug, badge, short_desc, long_desc, price, sale_price, stock, sku, image_url, created_at 
                           FROM products 
                           WHERE slug = ? AND is_active = 1 
                           LIMIT 1");
    $stmt->execute([$slug]);
    return $stmt->fetch() ?: null;
}

function getProductById($id) {
    $pdo = getDbConnection();
    if (!$pdo) return null;

    $stmt = $pdo->prepare("SELECT id, name, slug, category, category_slug, badge, short_desc, long_desc, price, sale_price, stock, sku, image_url, created_at 
                           FROM products 
                           WHERE id = ? AND is_active = 1 
                           LIMIT 1");
    $stmt->execute([(int)$id]);
    return $stmt->fetch() ?: null;
}

// Fetch Related Products
function getRelatedProducts($currentProductId, $categorySlug, $limit = 3) {
    $pdo = getDbConnection();
    if (!$pdo) return [];

    try {
        $stmt = $pdo->prepare("SELECT id, name, slug, category, category_slug, badge, short_desc, price, sale_price, stock, image_url 
                               FROM products 
                               WHERE is_active = 1 AND id != ? 
                               ORDER BY (category_slug = ?) DESC, id ASC 
                               LIMIT ?");
        $stmt->bindValue(1, (int)$currentProductId, PDO::PARAM_INT);
        $stmt->bindValue(2, $categorySlug, PDO::PARAM_STR);
        $stmt->bindValue(3, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("Failed to load related products: " . $e->getMessage());
        return [];
    }
}

// Helper to resolve product thumbnail
function resolveProductImage($product) {
    if (!empty($product['image_url']) && file_exists(dirname(__DIR__) . '/' . $product['image_url'])) {
        return $product['image_url'];
    }

    $catSlug = $product['category_slug'] ?? '';
    if ($catSlug === 'shilajit' || stripos($product['name'] ?? '', 'shilajit') !== false) {
        return 'assets/images/hero/hero_shilajit.jpg';
    }
    if ($catSlug === 'zaffran' || stripos($product['name'] ?? '', 'zaffran') !== false) {
        return 'assets/images/hero/hero_zaffran.jpg';
    }
    if ($catSlug === 'perfume-oils' || stripos($product['name'] ?? '', 'oil') !== false) {
        return 'assets/images/categories/cat_oils.jpg';
    }
    return 'assets/images/hero/hero_perfume.jpg';
}

// ==========================================================================
// SESSION & SECURE CART OPERATIONS
// ==========================================================================

function initSession() {
    if (session_status() === PHP_SESSION_NONE) {
        $sessionLifetime = (int) env('SESSION_LIFETIME', 86400);
        ini_set('session.gc_maxlifetime', $sessionLifetime);
        session_set_cookie_params([
            'lifetime' => $sessionLifetime,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_name(env('SESSION_NAME', 'zaffran_session'));
        session_start();
    }
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

function getCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Server-side Cart Operation: Add item with DB verification
function addToCartServer($productId, $quantity = 1) {
    initSession();
    $productId = (int)$productId;
    $quantity = (int)$quantity;

    if ($productId <= 0 || $quantity <= 0) {
        return ['success' => false, 'message' => 'Invalid product or quantity specified.'];
    }

    $product = getProductById($productId);
    if (!$product) {
        return ['success' => false, 'message' => 'Product not found or unavailable.'];
    }

    // Merge or add item
    if (isset($_SESSION['cart'][$productId])) {
        $newQty = $_SESSION['cart'][$productId]['quantity'] + $quantity;
        if ($newQty > 50) $newQty = 50;
        $_SESSION['cart'][$productId]['quantity'] = $newQty;
    } else {
        if ($quantity > 50) $quantity = 50;
        $_SESSION['cart'][$productId] = [
            'product_id' => $productId,
            'quantity' => $quantity,
            'added_at' => time()
        ];
    }

    return [
        'success' => true,
        'message' => htmlspecialchars($product['name']) . ' added to your shopping bag.',
        'cart_count' => getCartCount(),
        'product' => [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => (float)($product['sale_price'] ?? $product['price'])
        ]
    ];
}

// Server-side Cart Operation: Update quantity
function updateCartQuantityServer($productId, $quantity) {
    initSession();
    $productId = (int)$productId;
    $quantity = (int)$quantity;

    if ($productId <= 0) {
        return ['success' => false, 'message' => 'Invalid product specified.'];
    }

    if ($quantity <= 0) {
        // Remove item if quantity set to 0 or negative
        return removeFromCartServer($productId);
    }

    if ($quantity > 50) {
        $quantity = 50;
    }

    $product = getProductById($productId);
    if (!$product) {
        unset($_SESSION['cart'][$productId]);
        return ['success' => false, 'message' => 'Product no longer available.'];
    }

    $_SESSION['cart'][$productId] = [
        'product_id' => $productId,
        'quantity' => $quantity,
        'added_at' => time()
    ];

    $verifiedCart = getVerifiedCart();
    return [
        'success' => true,
        'message' => 'Quantity updated successfully.',
        'cart_count' => getCartCount(),
        'cart' => $verifiedCart
    ];
}

// Server-side Cart Operation: Remove item
function removeFromCartServer($productId) {
    initSession();
    $productId = (int)$productId;

    if (isset($_SESSION['cart'][$productId])) {
        unset($_SESSION['cart'][$productId]);
    }

    $verifiedCart = getVerifiedCart();
    return [
        'success' => true,
        'message' => 'Item removed from shopping bag.',
        'cart_count' => getCartCount(),
        'cart' => $verifiedCart
    ];
}

// Read verified cart contents with real-time DB prices
function getVerifiedCart() {
    initSession();
    $cart = $_SESSION['cart'] ?? [];
    $items = [];
    $subtotal = 0.0;
    $totalCount = 0;

    if (empty($cart)) {
        return [
            'items' => [],
            'subtotal' => 0.0,
            'formatted_subtotal' => 'Rs. 0',
            'total_count' => 0,
            'shipping' => calculateShippingFee(0.0),
            'grand_total' => 0.0,
            'formatted_grand_total' => 'Rs. 0'
        ];
    }

    foreach ($cart as $productId => $entry) {
        $product = getProductById($productId);
        if (!$product) {
            unset($_SESSION['cart'][$productId]);
            continue;
        }

        $qty = (int)($entry['quantity'] ?? 1);
        if ($qty <= 0) $qty = 1;
        if ($qty > 50) $qty = 50;

        $pricing = calculateProductPricing($product['price'], $product['sale_price'] ?? null);
        $itemTotal = $pricing['effective_price'] * $qty;

        $items[] = [
            'product_id' => $product['id'],
            'name' => $product['name'],
            'slug' => $product['slug'],
            'image_url' => resolveProductImage($product),
            'category' => $product['category'],
            'quantity' => $qty,
            'unit_price' => $pricing['effective_price'],
            'regular_price' => $pricing['regular_price'],
            'has_discount' => $pricing['has_discount'],
            'discount_percent' => $pricing['discount_percent'],
            'total_price' => $itemTotal,
            'formatted_unit_price' => $pricing['formatted_effective'],
            'formatted_total_price' => 'Rs. ' . number_format($itemTotal, 0)
        ];

        $subtotal += $itemTotal;
        $totalCount += $qty;
    }

    $shipping = calculateShippingFee($subtotal);
    $grandTotal = $subtotal + ($shipping['is_configured'] ? $shipping['fee'] : 0.0);

    return [
        'items' => $items,
        'subtotal' => $subtotal,
        'formatted_subtotal' => 'Rs. ' . number_format($subtotal, 0),
        'total_count' => $totalCount,
        'shipping' => $shipping,
        'grand_total' => $grandTotal,
        'formatted_grand_total' => 'Rs. ' . number_format($grandTotal, 0)
    ];
}

function getCartCount() {
    initSession();
    $count = 0;
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $count += is_array($item) ? ($item['quantity'] ?? 1) : (int)$item;
        }
    }
    return $count;
}

// Clear cart on successful order placement
function clearCartServer() {
    initSession();
    $_SESSION['cart'] = [];
}

// ==========================================================================
// ORDER RETRIEVAL HELPERS
// ==========================================================================

// Fetch Active Hero Slides dynamically from DB
function getHeroSlides() {
    $pdo = getDbConnection();
    if ($pdo) {
        try {
            $stmt = $pdo->query("SELECT * FROM hero_slides WHERE is_active = 1 ORDER BY display_order ASC, id ASC");
            $slides = $stmt->fetchAll();
            if (!empty($slides)) {
                return $slides;
            }
        } catch (Exception $e) {
            error_log("Failed to fetch hero slides: " . $e->getMessage());
        }
    }

    // Default Fallback Slides preserving 100% of Phase 1 signature design
    return [
        [
            'id' => 1,
            'slide_class' => 'slide-perfume',
            'desktop_image' => 'assets/images/hero/hero_perfume.svg',
            'small_badge' => 'Artisanal Haute Parfumerie',
            'title_prefix' => 'Discover Rare & Captivating',
            'animated_word' => 'Perfumes',
            'description' => 'Experience slow-matured fragrances crafted with pure natural essences, rare Cambodian oud, Damascus rose, and rich botanical accords designed for timeless sillage.',
            'primary_cta_label' => 'Explore Fragrances',
            'primary_cta_url' => 'shop.php?category=perfume',
            'secondary_cta_label' => 'Perfume Oil Supply',
            'secondary_cta_url' => 'category.php?slug=perfume-oils',
            'guarantee_text' => '100% Pure Essential Extrait Concentrations'
        ],
        [
            'id' => 2,
            'slide_class' => 'slide-shilajit',
            'desktop_image' => 'assets/images/hero/hero_shilajit.svg',
            'small_badge' => 'Wild Himalayan Harvest',
            'title_prefix' => '100% Pure & Potent',
            'animated_word' => 'Shilajit',
            'description' => 'Gold Grade Himalayan resin harvested from 16,000+ ft altitudes. Naturally rich in fulvic acid, bioactive plant minerals, and adaptogenic vitality for authentic daily wellness.',
            'primary_cta_label' => 'View Pure Shilajit',
            'primary_cta_url' => 'shop.php?category=shilajit',
            'secondary_cta_label' => 'Our Sourcing Story',
            'secondary_cta_url' => '#about',
            'guarantee_text' => 'Lab-Tested For Heavy Metals & Purity'
        ],
        [
            'id' => 3,
            'slide_class' => 'slide-zaffran',
            'desktop_image' => 'assets/images/hero/hero_zaffran.svg',
            'small_badge' => 'Imperial Super Negin',
            'title_prefix' => 'Hand-Harvested Royal',
            'animated_word' => 'Zaffran',
            'description' => 'Deep ruby-red stigmas celebrated for unrivaled safranal aroma and crocin coloring power. Unadulterated, whole-thread saffron dried at low temperatures to lock in medicinal essence.',
            'primary_cta_label' => 'Shop Royal Zaffran',
            'primary_cta_url' => 'shop.php?category=zaffran',
            'secondary_cta_label' => 'Explore Collections',
            'secondary_cta_url' => 'shop.php',
            'guarantee_text' => 'Zero Stems & Pure Filament Guarantee'
        ]
    ];
}

function getOrderByAccessToken($accessToken) {
    $pdo = getDbConnection();
    if (!$pdo || empty($accessToken)) return null;

    $stmt = $pdo->prepare("SELECT * FROM orders WHERE access_token = ? LIMIT 1");
    $stmt->execute([$accessToken]);
    $order = $stmt->fetch();
    if (!$order) return null;

    // Fetch order items
    $stmtItems = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ? ORDER BY id ASC");
    $stmtItems->execute([$order['id']]);
    $order['items'] = $stmtItems->fetchAll();

    return $order;
}
