<?php
$file = "app/Http/Controllers/ProductController.php";
$content = file_get_contents($file);

$bad_stock = <<<'EOT'
                \App\Models\Stock::create([
                    'business_id' => $businessId,
                    'branch_id' => $branchId,
                    'product_id' => $product->id,
                    'quantity' => $openingStock,
                ]);
EOT;

$good_stock = <<<'EOT'
                \App\Models\Stock::create([
                    'business_id' => $businessId,
                    'branch_id' => $branchId,
                    'product_id' => $product->id,
                    'quantity' => $openingStock,
                    'avg_cost' => $costPerUnit,
                    'stock_value' => round($openingStock * $costPerUnit, 2),
                ]);
EOT;

$content = str_replace($bad_stock, $good_stock, $content);
file_put_contents($file, $content);

$file2 = "app/Http/Controllers/Manufacturing/BillOfMaterialController.php";
$content2 = file_get_contents($file2);

$bad_stock2 = <<<'EOT'
            // Add Opening Stock
            Stock::create([
                'business_id' => $businessId,
                'branch_id' => $branchId,
                'product_id' => $product->id,
                'quantity' => $validated['expected_output'],
                'unit_cost' => $costPerUnit,
                'total_value' => $totalCost,
            ]);
EOT;

$good_stock2 = <<<'EOT'
            // Add Opening Stock
            Stock::create([
                'business_id' => $businessId,
                'branch_id' => $branchId,
                'product_id' => $product->id,
                'quantity' => $validated['expected_output'],
                'avg_cost' => $costPerUnit,
                'stock_value' => $totalCost,
            ]);

            \App\Models\StockMovement::create([
                'business_id' => $businessId,
                'branch_id' => $branchId,
                'product_id' => $product->id,
                'user_id' => $request->user()->id,
                'movement_type' => 'in',
                'reference_type' => 'opening_balance',
                'quantity_before' => 0,
                'quantity_change' => $validated['expected_output'],
                'quantity_after' => $validated['expected_output'],
                'transaction_date' => today(),
                'unit_cost' => $costPerUnit,
                'total_cost' => $totalCost,
                'notes' => 'Opening stock from initial recipe creation',
            ]);
EOT;

$content2 = str_replace($bad_stock2, $good_stock2, $content2);
file_put_contents($file2, $content2);

echo "Patched both controllers.\n";
?>
