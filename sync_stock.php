<?php
$stocks = App\Models\Stock::with('product')->get();
foreach($stocks as $stk) {
    if ($stk->product && $stk->product->cost_price > 0) {
        $stk->update([
            'avg_cost' => $stk->product->cost_price,
            'stock_value' => round($stk->quantity * $stk->product->cost_price, 2)
        ]);
        echo "Updated stock for product: " . $stk->product->name . "\n";
    }
}
?>
