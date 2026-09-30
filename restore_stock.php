<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$product = App\Models\Product::where('sku', 'MISU-8415')->first();
if ($product && $product->opening_stock > 0) {
    App\Models\Stock::create([
        'business_id' => $product->business_id,
        'branch_id' => App\Models\User::first()->branch_id,
        'product_id' => $product->id,
        'quantity' => $product->opening_stock,
        'avg_cost' => $product->cost_price,
        'stock_value' => $product->opening_stock * $product->cost_price,
    ]);
    echo 'Restored stock for ' . $product->name;
}
