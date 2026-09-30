<?php
$file = "app/Http/Controllers/Manufacturing/BillOfMaterialController.php";
$content = file_get_contents($file);

// Add Product update in store method
$old_store = <<<'EOT'
            // Create opening stock entry (with 0 quantity for now) to ensure it exists
            $stock = \App\Models\Stock::firstOrCreate([
                'business_id' => $businessId,
                'branch_id' => $branchId,
                'product_id' => $validated['product_id']
            ], [
                'quantity' => 0,
                'reserved_quantity' => 0,
                'damaged_quantity' => 0,
                'avg_cost' => $totalCost / max($bom->expected_output, 0.01),
                'stock_value' => 0
            ]);

            // Ensure avg_cost is set even if stock existed but was 0
            if ($stock->quantity == 0) {
                $stock->avg_cost = $totalCost / max($bom->expected_output, 0.01);
                $stock->save();
            }
EOT;

$new_store = <<<'EOT'
            $costPerUnit = $totalCost / max($bom->expected_output, 0.01);

            // Update the product's base cost_price
            \App\Models\Product::where('id', $validated['product_id'])
                ->update(['cost_price' => $costPerUnit]);

            // Create opening stock entry (with 0 quantity for now) to ensure it exists
            $stock = \App\Models\Stock::firstOrCreate([
                'business_id' => $businessId,
                'branch_id' => $branchId,
                'product_id' => $validated['product_id']
            ], [
                'quantity' => 0,
                'reserved_quantity' => 0,
                'damaged_quantity' => 0,
                'avg_cost' => $costPerUnit,
                'stock_value' => 0
            ]);

            // Ensure avg_cost is set even if stock existed but was 0
            if ($stock->quantity == 0) {
                $stock->avg_cost = $costPerUnit;
                $stock->save();
            }
EOT;

$content = str_replace($old_store, $new_store, $content);

// Add Product update in update method
$old_update = <<<'EOT'
            // Re-insert new items
            foreach ($validated['items'] as $item) {
                $qty = $item['quantity'];
                $cost = $item['unit_cost'] ?? 0;
                $bom->items()->create([
                    'product_id' => $item['product_id'] ?? null,
                    'item_type' => 'material',
                    'description' => $item['description'] ?? '',
                    'quantity' => $qty,
                    'unit_id' => $item['unit_id'] ?? null,
                    'unit_cost' => $cost,
                    'total_cost' => $item['product_id'] ? ($qty * $cost) : $cost,
                    'is_optional' => $item['is_optional'] ?? false,
                ]);
            }
EOT;

$new_update = <<<'EOT'
            $totalCost = 0;
            // Re-insert new items
            foreach ($validated['items'] as $item) {
                $qty = $item['quantity'];
                $cost = $item['unit_cost'] ?? 0;
                $lineTotal = $item['product_id'] ? ($qty * $cost) : $cost;
                $totalCost += $lineTotal;

                $bom->items()->create([
                    'product_id' => $item['product_id'] ?? null,
                    'item_type' => 'material',
                    'description' => $item['description'] ?? '',
                    'quantity' => $qty,
                    'unit_id' => $item['unit_id'] ?? null,
                    'unit_cost' => $cost,
                    'total_cost' => $lineTotal,
                    'is_optional' => $item['is_optional'] ?? false,
                ]);
            }

            $costPerUnit = $totalCost / max($bom->expected_output, 0.01);

            // Update the product's base cost_price
            \App\Models\Product::where('id', $validated['product_id'])
                ->update(['cost_price' => $costPerUnit]);

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

$content = str_replace($old_update, $new_update, $content);
file_put_contents($file, $content);
echo "Patched controller to update product cost.\n";
?>
