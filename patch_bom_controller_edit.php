<?php
$file = "app/Http/Controllers/Manufacturing/BillOfMaterialController.php";
$content = file_get_contents($file);

$new_methods = <<<'EOT'
    public function edit(BillOfMaterial $bom)
    {
        $businessId = request()->user()->business_id;
        if ($bom->business_id !== $businessId) abort(403);
        
        $bom->load('items');

        $products = Product::where('business_id', $businessId)->active()->get(['id', 'name', 'sku', 'cost_price', 'unit_id']);
        $units = Unit::where('business_id', $businessId)->active()->get(['id', 'name', 'abbreviation']);

        return Inertia::render('Manufacturing/BOM/Edit', [
            'bom' => $bom,
            'products' => $products, // All products for output
            'materials' => $products, // Same for now
            'units' => $units
        ]);
    }
    
    public function update(Request $request, BillOfMaterial $bom)
    {
        $businessId = request()->user()->business_id;
        if ($bom->business_id !== $businessId) abort(403);

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

        DB::transaction(function () use ($validated, $bom) {
            $bom->update([
                'name' => $validated['name'],
                'product_id' => $validated['product_id'],
                'expected_output' => $validated['expected_output'],
                'output_unit_id' => $validated['output_unit_id'] ?? null,
                'description' => $validated['description'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Delete old items
            $bom->items()->delete();

            // Re-insert new items
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
        });

        return redirect()->route('bom.index')->with('success', 'Recipe updated successfully.');
    }
EOT;

$content = preg_replace('/public function edit\s*\(BillOfMaterial\s*\$bom\).*?public function destroy/s', $new_methods . "\n    public function destroy", $content);
file_put_contents($file, $content);
echo "Patched edit and update methods.\n";
?>
