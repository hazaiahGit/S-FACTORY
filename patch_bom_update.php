<?php
$file = "app/Http/Controllers/Manufacturing/BillOfMaterialController.php";
$content = file_get_contents($file);

$old_validation = <<<'EOT'
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'product_id' => 'required|exists:products,id',
            'expected_output' => 'required|numeric|min:0.01',
            'output_unit_id' => 'nullable|exists:units,id',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_id' => 'nullable|exists:units,id',
            'items.*.unit_cost' => 'nullable|numeric|min:0',
        ]);
EOT;

$new_validation = <<<'EOT'
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'product_id' => 'required|exists:products,id',
            'expected_output' => 'required|numeric|min:0.01',
            'output_unit_id' => 'nullable|exists:units,id',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.description' => 'nullable|string|max:200',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_id' => 'nullable|exists:units,id',
            'items.*.unit_cost' => 'nullable|numeric|min:0',
        ]);
EOT;

$content = str_replace($old_validation, $new_validation, $content);

// Also need to fix the update logic to save description correctly
$old_update_loop = <<<'EOT'
            foreach ($validated['items'] as $item) {
                $qty = $item['quantity'];
                $cost = $item['unit_cost'] ?? 0;
                $lineTotal = $qty * $cost;
                $totalCost += $lineTotal;
                
                \App\Models\BomItem::create([
                    'bom_id' => $bom->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $qty,
                    'unit_id' => $item['unit_id'] ?? null,
                    'unit_cost' => $cost,
                    'total_cost' => $lineTotal
                ]);
            }
EOT;

$new_update_loop = <<<'EOT'
            foreach ($validated['items'] as $item) {
                $qty = $item['quantity'];
                $cost = $item['unit_cost'] ?? 0;
                $lineTotal = $qty * $cost;
                $totalCost += $lineTotal;
                
                \App\Models\BomItem::create([
                    'bom_id' => $bom->id,
                    'product_id' => $item['product_id'] ?? null,
                    'item_type' => 'material',
                    'description' => $item['description'] ?? '',
                    'quantity' => $qty,
                    'unit_id' => $item['unit_id'] ?? null,
                    'unit_cost' => $cost,
                    'total_cost' => $lineTotal
                ]);
            }
EOT;

$content = str_replace($old_update_loop, $new_update_loop, $content);

file_put_contents($file, $content);
echo "Patched BOM update validation and loop.\n";
?>
