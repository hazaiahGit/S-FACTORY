<?php
$file = "app/Http/Controllers/Manufacturing/BillOfMaterialController.php";
$content = file_get_contents($file);

// Replace Validation
$old_val = <<<'EOT'
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
            'items.*.total_cost' => 'nullable|numeric|min:0',
            'items.*.is_optional' => 'boolean',
        ]);
EOT;

$new_val = <<<'EOT'
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
            'items.*.total_cost' => 'nullable|numeric|min:0',
            'items.*.is_optional' => 'boolean',
        ]);
EOT;

$content = str_replace($old_val, $new_val, $content);

// Replace Loop
$old_loop = <<<'EOT'
            foreach ($validated['items'] as $item) {
                $bom->items()->create([
                    'product_id' => $item['product_id'],
                    'item_type' => 'material',
                    'quantity' => $item['quantity'],
                    'unit_id' => $item['unit_id'] ?? null,
                    'unit_cost' => $item['unit_cost'] ?? 0,
                    'total_cost' => $item['total_cost'] ?? 0,
                    'is_optional' => $item['is_optional'] ?? false,
                ]);
            }
EOT;

$new_loop = <<<'EOT'
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

$content = str_replace($old_loop, $new_loop, $content);
file_put_contents($file, $content);
echo "Patched controller successfully.\n";
?>
