<?php
$controllerFile = "app/Http/Controllers/SaleController.php";
$content = file_get_contents($controllerFile);

$editMethod = <<<'EOT'
    public function edit(\App\Models\Sale $sale)
    {
        if ($sale->business_id !== request()->user()->business_id) {
            abort(403);
        }

        if (!in_array($sale->status, ['draft', 'on_hold', 'invoiced'])) {
            abort(403, 'Only sales in draft, on hold, or invoiced status can be edited.');
        }

        $businessId = request()->user()->business_id;
        $branchId = request()->user()->branch_id;

        $sale->load(['items.product']);

        // Fetch products with their active stock for the current branch
        $products = \App\Models\Product::with(['stock' => function ($q) use ($branchId) {
            $q->where('branch_id', $branchId);
        }])
            ->where('business_id', $businessId)
            ->where('is_active', true)
            ->get(['id', 'name', 'selling_price', 'sku', 'product_type', 'track_stock'])
            ->map(function ($product) {
                $stockItem = $product->stock->first();
                $product->current_stock = $stockItem ? (float) $stockItem->quantity : 0;
                return $product;
            });

        $customers = \App\Models\Customer::where('business_id', $businessId)->get(['id', 'name', 'phone']);

        return \Inertia\Inertia::render('Sales/Edit', [
            'sale' => $sale,
            'products' => $products,
            'customers' => $customers,
        ]);
    }
EOT;

$updateMethod = <<<'EOT'
    public function update(\Illuminate\Http\Request $request, \App\Models\Sale $sale)
    {
        if ($sale->business_id !== request()->user()->business_id) {
            abort(403);
        }

        if (!in_array($sale->status, ['draft', 'on_hold', 'invoiced'])) {
            abort(403, 'Only sales in draft, on hold, or invoiced status can be edited.');
        }

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'customer_id' => 'nullable|exists:customers,id',
            'sale_type' => 'required|string|in:sale,quotation,proforma',
            'status' => 'required|string|in:draft,confirmed,invoiced,credit,on_hold,cancelled',
            'fulfillment_status' => 'nullable|string|in:pending,processing,ready,partial,fulfilled,delivered,returned,cancelled',
            'transaction_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string',
        ]);

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();
            $this->saleService->updateSale($sale, $validated);
            \Illuminate\Support\Facades\DB::commit();

            return redirect()->route('sales.show', $sale)->with('success', 'Sale updated successfully.');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return redirect()->back()->with('error', 'Error updating sale: '.$e->getMessage());
        }
    }
EOT;

$content = preg_replace('/public function edit\(Sale \$sale\).*?public function update\(Request \$request, Sale \$sale\).*?}\s*\n/s', $editMethod . "\n\n" . $updateMethod . "\n\n", $content);

file_put_contents($controllerFile, $content);
echo "Updated SaleController\n";
?>
