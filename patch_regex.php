<?php
$file = "app/Http/Controllers/Manufacturing/BillOfMaterialController.php";
$content = file_get_contents($file);

// Let's use preg_replace or find the exact string
$pattern = '/\/\/\s*Also proactively update the stock avg_cost if the stock is currently empty.*?update\(\[\'avg_cost\' => \$costPerUnit\]\);/s';

$replacement = <<<'EOT'
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

$new_content = preg_replace($pattern, $replacement, $content);
file_put_contents($file, $new_content);
echo "Patched using regex.\n";
?>
