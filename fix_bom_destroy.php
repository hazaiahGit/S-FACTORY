<?php
$file = "app/Http/Controllers/Manufacturing/BillOfMaterialController.php";
$content = file_get_contents($file);

$old = <<<'EOT'
    public function edit(BillOfMaterial $bom) {}
    public function update(Request $request, BillOfMaterial $bom) {}
    public function destroy(BillOfMaterial $bom) {}
EOT;

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
            return back()->with('error', 'Cannot delete this recipe because it is used in production orders. Set it to inactive instead.');
        }

        $bom->items()->delete();
        $bom->delete();

        return back()->with('success', 'Recipe (BOM) deleted successfully.');
    }
EOT;

$content = str_replace($old, $new, $content);
file_put_contents($file, $content);
?>
