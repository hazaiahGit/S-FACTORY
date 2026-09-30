<?php
$file = "app/Http/Controllers/Manufacturing/BillOfMaterialController.php";
$content = file_get_contents($file);

$old_block = <<<'EOT'
            // Also proactively update the stock avg_cost if the stock is currently empty
            $stock = \App\Models\Stock::where([
                'business_id' => request()->user()->business_id,
                'branch_id' => request()->user()->branch_id ?? 1,
                'product_id' => $validated['product_id']
            ])->first();

            if ($stock && $stock->quantity == 0) {
                $stock->avg_cost = $costPerUnit;
                $stock->save();
            }
EOT;

$new_block = <<<'EOT'
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

$content = str_replace($old_block, $new_block, $content);
file_put_contents($file, $content);
echo "Patched correctly.\n";
?>
