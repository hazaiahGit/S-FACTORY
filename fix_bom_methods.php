<?php
$file = "app/Http/Controllers/Manufacturing/BillOfMaterialController.php";
$content = file_get_contents($file);

$new = <<<'EOT'
    public function edit(BillOfMaterial $bom)
    {
        $businessId = request()->user()->business_id;
        if ($bom->business_id !== $businessId) abort(403);
        // We can build the full edit view later
        return back()->with('error', 'Edit recipe view coming soon!');
    }
    
    public function update(Request $request, BillOfMaterial $bom) {}
    
    public function destroy(BillOfMaterial $bom)
    {
        $businessId = request()->user()->business_id;
        if ($bom->business_id !== $businessId) {
            abort(403);
        }

        // check if used in production orders
        if ($bom->productionOrders()->count() > 0) {
            return back()->with('error', 'Cannot delete this recipe because it is used in production orders.');
        }

        $bom->items()->delete();
        $bom->delete();

        return back()->with('success', 'Recipe (BOM) deleted successfully.');
    }
}
EOT;

// Replace from 'public function edit' to the end of the file.
$content = preg_replace('/public function edit\s*\(BillOfMaterial\s*\$bom\).*$/s', $new, $content);
file_put_contents($file, $content);
echo "Patched methods successfully.\n";
?>
