<?php
require_once __DIR__ . '/config/database.php';
initSession();

echo "--- RUNNING PHASE 2 AUTOMATED TEST SUITE ---\n";

// 1. CSRF Token Tests
$csrf = getCsrfToken();
$validCsrf = verifyCsrfToken($csrf);
$invalidCsrf = !verifyCsrfToken('fake_token_123');
echo "1. Valid CSRF verification: " . ($validCsrf ? "PASSED" : "FAILED") . "\n";
echo "2. Reject invalid CSRF: " . ($invalidCsrf ? "PASSED" : "FAILED") . "\n";

// 2. Add-to-Cart Validation Tests
$res1 = addToCartServer(1, 2);
echo "3. Add valid product (ID 1, Qty 2): " . ($res1['success'] ? "PASSED" : "FAILED") . " (Count: " . getCartCount() . ")\n";

$res2 = addToCartServer(9999, 1);
echo "4. Reject nonexistent product (ID 9999): " . (!$res2['success'] ? "PASSED" : "FAILED") . " (" . $res2['message'] . ")\n";

$res3 = addToCartServer(1, -3);
echo "5. Reject invalid negative quantity: " . (!$res3['success'] ? "PASSED" : "FAILED") . " (" . $res3['message'] . ")\n";

// 3. Merging Quantities
$res4 = addToCartServer(1, 1);
echo "6. Merge quantities for same product: " . ($res4['success'] && getCartCount() === 3 ? "PASSED" : "FAILED") . " (New count: " . getCartCount() . ")\n";

// 4. Cart Verification & Subtotal Calculation from Real DB Prices
$verified = getVerifiedCart();
$subtotalExpected = 7990 * 3; // 3 * sale price of Zaffran
$subtotalMatches = ((float)$verified['subtotal'] === (float)$subtotalExpected);
echo "7. Real DB subtotal calculation: " . ($subtotalMatches ? "PASSED" : "FAILED") . " (Expected: Rs. {$subtotalExpected}, Got: Rs. {$verified['subtotal']})\n";

// 5. Sale Price System Math Verification
$pricingWithSale = calculateProductPricing(8900.00, 7990.00);
$pricingWithoutSale = calculateProductPricing(8500.00, null);
$pricingWithInvalidHigherSale = calculateProductPricing(8500.00, 9500.00);

echo "8. Sale discount percentage calculation: " . (($pricingWithSale['has_discount'] && $pricingWithSale['discount_percent'] === 10) ? "PASSED" : "FAILED") . " (Got: {$pricingWithSale['discount_percent']}%)\n";
echo "9. Regular pricing when no sale: " . ((!$pricingWithoutSale['has_discount'] && $pricingWithoutSale['effective_price'] == 8500.00) ? "PASSED" : "FAILED") . "\n";
echo "10. Invalidate higher sale price: " . ((!$pricingWithInvalidHigherSale['has_discount'] && $pricingWithInvalidHigherSale['effective_price'] == 8500.00) ? "PASSED" : "FAILED") . "\n";

// 6. Category Query Verification
$cats = getCategoriesWithCounts();
echo "11. Four active categories: " . (count($cats) === 4 ? "PASSED" : "FAILED") . " (Found: " . count($cats) . " categories)\n";
foreach ($cats as $c) {
    echo "    - Category '{$c['name']}' ({$c['slug']}): {$c['product_count']} active products\n";
}

// 7. Catalog Filtering Verification
$perfumeList = getProductsCatalog('perfume');
$shilajitList = getProductsCatalog('shilajit');
$zaffranList = getProductsCatalog('zaffran');
$oilsList = getProductsCatalog('perfume-oils');

echo "12. Filter perfume category: " . (count($perfumeList) === 1 ? "PASSED" : "FAILED") . "\n";
echo "13. Filter shilajit category: " . (count($shilajitList) === 1 ? "PASSED" : "FAILED") . "\n";
echo "14. Filter zaffran category: " . (count($zaffranList) === 1 ? "PASSED" : "FAILED") . "\n";
echo "15. Filter empty perfume oils: " . (count($oilsList) === 0 ? "PASSED" : "FAILED") . "\n";

// 8. Search Query Verification
$searchResults = getProductsCatalog(null, 'saffron');
echo "16. Search query 'saffron': " . (count($searchResults) >= 1 ? "PASSED" : "FAILED") . " (Found: " . count($searchResults) . ")\n";

// 9. Allowlisted Sorting
$sortAsc = getProductsCatalog(null, null, 'price_asc');
$sortDesc = getProductsCatalog(null, null, 'price_desc');
$firstAsc = calculateProductPricing($sortAsc[0]['price'], $sortAsc[0]['sale_price'])['effective_price'];
$firstDesc = calculateProductPricing($sortDesc[0]['price'], $sortDesc[0]['sale_price'])['effective_price'];
echo "17. Price sort low to high: " . ($firstAsc <= 7990 ? "PASSED" : "FAILED") . " (Lowest price: Rs. {$firstAsc})\n";
echo "18. Price sort high to low: " . ($firstDesc >= 14500 ? "PASSED" : "FAILED") . " (Highest price: Rs. {$firstDesc})\n";

echo "--- COMPLETED TEST SUITE ---\n";
