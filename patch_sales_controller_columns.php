<?php
$file = "app/Http/Controllers/SaleController.php";
$content = file_get_contents($file);

// Replace get() array for products
$content = str_replace(
    "->get(['id', 'name', 'selling_price', 'sku', 'product_type', 'track_stock'])",
    "->get(['id', 'name', 'selling_price', 'wholesale_price', 'sku', 'product_type', 'track_stock'])",
    $content
);

file_put_contents($file, $content);
echo "Patched SaleController for wholesale_price.\n";
?>
