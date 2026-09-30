<?php
$sales = \App\Models\Sale::whereIn('status', ['draft', 'on_hold'])->get();
foreach ($sales as $sale) {
    foreach ($sale->items as $item) {
        if ($item->product && $item->product->track_stock) {
            $stock = \App\Models\Stock::where('branch_id', $sale->branch_id)->where('product_id', $item->product_id)->first();
            if ($stock) {
                $stock->quantity += $item->quantity;
                $stock->reserved_quantity += $item->quantity;
                $stock->save();
            }
        }
    }
}
echo "Fixed stock records.\n";
?>
