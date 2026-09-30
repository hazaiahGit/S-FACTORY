<?php
$file = "app/Http/Controllers/StockController.php";
$content = file_get_contents($file);

$old_movements = <<<'EOT'
            ->when($request->search, function ($query, $search) {
                $query->whereHas('product', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            })
EOT;

$new_movements = <<<'EOT'
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('product', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%")
                           ->orWhere('sku', 'like', "%{$search}%");
                    })
                    ->orWhere('movement_type', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%")
                    ->orWhere('transaction_date', 'like', "%{$search}%")
                    ->orWhere('quantity_change', 'like', "%{$search}%")
                    ->orWhere('quantity_after', 'like', "%{$search}%")
                    ->orWhere('unit_cost', 'like', "%{$search}%");
                });
            })
EOT;
$content = str_replace($old_movements, $new_movements, $content);

$destroy_method = <<<'EOT'
    public function destroyMovement(Request $request, \App\Models\StockMovement $movement)
    {
        $user = $request->user();
        
        // Ensure user has admin rights
        if (!$user->hasRole('Super Admin')) {
            return back()->with('error', 'Only Super Admins can delete audit trails.');
        }

        if ($movement->business_id !== $user->business_id) {
            abort(403);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($movement) {
            // Revert stock quantity
            $stock = \App\Models\Stock::where('product_id', $movement->product_id)
                ->where('branch_id', $movement->branch_id)
                ->first();

            if ($stock) {
                $stock->quantity -= (float) $movement->quantity_change;
                $stock->stock_value = $stock->quantity * $stock->avg_cost;
                $stock->save();
            }

            // Delete the trail record
            $movement->delete();
        });

        return back()->with('success', 'Audit trail deleted and stock reverted successfully.');
    }
}
EOT;

$content = preg_replace('/}\s*}$/', $destroy_method, $content);
file_put_contents($file, $content);
echo "Patched StockController.\n";
?>
