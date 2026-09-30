<?php
$file = "app/Http/Controllers/Manufacturing/BillOfMaterialController.php";
$content = file_get_contents($file);

// Replace in store() and update()
$old_stock_update = <<<'EOT'
            // Also proactively update the stock avg_cost if the stock is currently empty
            $stock = \App\Models\Stock::where([
                'product_id' => $validated['product_id'],
                'business_id' => auth()->user()->business_id
            ])->where('quantity', '<=', 0)->update(['avg_cost' => $costPerUnit]);
EOT;

$new_stock_update = <<<'EOT'
            // Force update all existing stock records to reflect the new recipe cost 
            // since the user wants the inventory value strictly tied to the BOM cost.
            $stocks = \App\Models\Stock::where('product_id', $validated['product_id'])->get();
            foreach ($stocks as $stk) {
                $stk->update([
                    'avg_cost' => $costPerUnit,
                    'stock_value' => round((float)$stk->quantity * $costPerUnit, 2)
                ]);
            }
EOT;

$content = str_replace($old_stock_update, $new_stock_update, $content);
file_put_contents($file, $content);
echo "Patched BillOfMaterialController.\n";
?>
