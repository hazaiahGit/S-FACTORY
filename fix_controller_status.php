<?php
$file = "app/Http/Controllers/SaleController.php";
$content = file_get_contents($file);

$old_update_status = <<<'EOT'
    public function updateStatus(Request $request, Sale $sale)
    {
        if ($sale->business_id !== $request->user()->business_id) {
            abort(403);
        }

        $request->validate([
            'status' => 'nullable|in:draft,confirmed,invoiced,credit,on_hold,cancelled,paid',
            'fulfillment_status' => 'nullable|in:pending,processing,ready,partial,fulfilled,delivered,returned,cancelled',
        ]);

        $updates = [];
        if ($request->filled('status')) $updates['status'] = $request->status;
        if ($request->filled('fulfillment_status')) $updates['fulfillment_status'] = $request->fulfillment_status;

        if (!empty($updates)) {
            $sale->update($updates);
        }

        return back()->with('success', 'Sale updated successfully.');
    }
EOT;

$new_update_status = <<<'EOT'
    public function updateStatus(Request $request, Sale $sale)
    {
        if ($sale->business_id !== $request->user()->business_id) {
            abort(403);
        }

        $request->validate([
            'status' => 'nullable|in:draft,confirmed,invoiced,credit,on_hold,cancelled,paid',
            'fulfillment_status' => 'nullable|in:pending,processing,ready,partial,fulfilled,delivered,returned,cancelled',
        ]);

        if ($request->filled('status') && $request->status !== $sale->status) {
            $this->saleService->updateSaleStatus($sale, $request->status);
        }

        if ($request->filled('fulfillment_status') && $request->fulfillment_status !== $sale->fulfillment_status) {
            $sale->update(['fulfillment_status' => $request->fulfillment_status]);
        }

        return back()->with('success', 'Sale updated successfully.');
    }
EOT;

$content = str_replace($old_update_status, $new_update_status, $content);
file_put_contents($file, $content);
?>
