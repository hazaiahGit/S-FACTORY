<?php
$file = "app/Services/SaleService.php";
$content = file_get_contents($file);

// 1. In create()
$content = preg_replace(
    '/\/\/ Deduct stock for actual sales \(not quotations\).*?negative_stock_allowed \?\? false\s*\);\s*}/s',
    <<<'EOT'
// Deduct or Reserve stock for actual sales (not quotations)
                if ($data['sale_type'] === 'sale' && $product->track_stock) {
                    if (in_array($data['status'] ?? 'confirmed', ['draft', 'on_hold'])) {
                        $this->stockService->reserve($data['branch_id'], $product->id, $quantity);
                    } else {
                        $this->stockService->decrease(
                            $data['branch_id'],
                            $product->id,
                            $quantity,
                            'sale',
                            Sale::class,
                            $sale->id,
                            $sale->sale_number,
                            null,
                            $data['transaction_date'] ?? today(),
                            $user->business->negative_stock_allowed ?? false
                        );
                    }
                }
EOT,
    $content
);

// 2. In updateSale(), replace reversal
$content = preg_replace(
    '/\/\/ 1\. Reverse stock for existing items.*?\$item->delete\(\);\s*}/s',
    <<<'EOT'
// 1. Reverse stock for existing items
            $wasReserved = in_array($sale->status, ['draft', 'on_hold']);
            foreach ($sale->items as $item) {
                if ($sale->sale_type === 'sale' && $item->product && $item->product->track_stock) {
                    if ($wasReserved) {
                        $this->stockService->releaseReservation($sale->branch_id, $item->product_id, $item->quantity);
                    } else {
                        $this->stockService->increase(
                            $sale->branch_id,
                            $item->product_id,
                            $item->quantity,
                            $item->unit_cost,
                            'sale_edited',
                            \App\Models\Sale::class,
                            $sale->id,
                            $sale->sale_number,
                            'Reversing stock before update'
                        );
                    }
                }
                $item->delete();
            }
EOT,
    $content
);

// 3. In updateSale(), replace deduction
$content = preg_replace(
    '/\/\/ Deduct stock for actual sales\s+if \(\$data\[\'sale_type\'\].*?negative_stock_allowed \?\? false\s*\);\s*}/s',
    <<<'EOT'
// Deduct or Reserve stock
                if ($data['sale_type'] === 'sale' && $product->track_stock) {
                    if (in_array($data['status'] ?? 'confirmed', ['draft', 'on_hold'])) {
                        $this->stockService->reserve($sale->branch_id, $product->id, $quantity);
                    } else {
                        $this->stockService->decrease(
                            $sale->branch_id,
                            $product->id,
                            $quantity,
                            'sale',
                            \App\Models\Sale::class,
                            $sale->id,
                            $sale->sale_number,
                            null,
                            $data['transaction_date'] ?? today(),
                            $user->business->negative_stock_allowed ?? false
                        );
                    }
                }
EOT,
    $content
);

// 4. In deleteSale() - already did it successfully via fix_delete_sale_reservation.php?
// Let me verify if it was done.

file_put_contents($file, $content);
echo "Patched SaleService successfully.\n";
?>
