<?php
$file = "app/Services/SaleService.php";
$content = file_get_contents($file);

$old_delete_reversal = <<<'EOT'
            // Reverse stock for all items
            foreach ($sale->items as $item) {
                // If stock tracking is enabled for product, reverse it
                if ($item->product && $item->product->track_stock) {
                    $this->stockService->increase(
                        $sale->branch_id,
                        $item->product_id,
                        $item->quantity,
                        $item->unit_cost, // Restore at original cost
                        'sale_deleted',
                        \App\Models\Sale::class,
                        $sale->id,
                        $sale->sale_number,
                        'Reversing sale deletion'
                    );
                }
            }
EOT;

$new_delete_reversal = <<<'EOT'
            // Reverse stock for all items
            $wasReserved = in_array($sale->status, ['draft', 'on_hold']);
            foreach ($sale->items as $item) {
                // If stock tracking is enabled for product, reverse it
                if ($item->product && $item->product->track_stock) {
                    if ($wasReserved) {
                        $this->stockService->releaseReservation($sale->branch_id, $item->product_id, $item->quantity);
                    } else {
                        $this->stockService->increase(
                            $sale->branch_id,
                            $item->product_id,
                            $item->quantity,
                            $item->unit_cost, // Restore at original cost
                            'sale_deleted',
                            \App\Models\Sale::class,
                            $sale->id,
                            $sale->sale_number,
                            'Reversing sale deletion'
                        );
                    }
                }
            }
EOT;

$content = str_replace($old_delete_reversal, $new_delete_reversal, $content);
file_put_contents($file, $content);
?>
