<?php
$stocks = App\Models\Stock::where('avg_cost', 0)->where('quantity', '>', 0)->get();
foreach($stocks as $stock) {
    $product = App\Models\Product::find($stock->product_id);
    if ($product && $product->cost_price > 0) {
        $stock->avg_cost = $product->cost_price;
        $stock->stock_value = $stock->quantity * $product->cost_price;
        $stock->save();
        echo "Fixed stock for product: " . $product->name . "\n";
    }
}
echo "Done.";
?>
