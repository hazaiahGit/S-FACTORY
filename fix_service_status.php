<?php
$file = "app/Services/SaleService.php";
$content = file_get_contents($file);

$new_method = <<<'EOT'
    /**
     * Update sale status and handle stock transitions.
     */
    public function updateSaleStatus(\App\Models\Sale $sale, string $newStatus): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($sale, $newStatus) {
            $oldStatus = $sale->status;
            if ($oldStatus === $newStatus) return;

            $reservedStatuses = ['draft', 'on_hold'];
            $wasReserved = in_array($oldStatus, $reservedStatuses);
            $isReserved = in_array($newStatus, $reservedStatuses);

            $sale->load('items.product');

            foreach ($sale->items as $item) {
                if ($sale->sale_type === 'sale' && $item->product && $item->product->track_stock) {
                    
                    if ($wasReserved && !$isReserved) {
                        // Transitioning out of reserved (e.g. on_hold -> confirmed OR on_hold -> cancelled)
                        $this->stockService->releaseReservation($sale->branch_id, $item->product_id, $item->quantity);
                        
                        if ($newStatus !== 'cancelled') {
                            // If it's becoming a finalized sale, we must now permanently deduct it
                            $this->stockService->decrease(
                                $sale->branch_id,
                                $item->product_id,
                                $item->quantity,
                                'sale',
                                \App\Models\Sale::class,
                                $sale->id,
                                $sale->sale_number,
                                'Product sold (status changed from reserved to ' . $newStatus . ')'
                            );
                        }
                    } elseif (!$wasReserved && $isReserved) {
                        // Transitioning into reserved (e.g. confirmed -> on_hold)
                        if ($oldStatus !== 'cancelled') {
                            // It was previously deducted, so we must restore physical stock first
                            $this->stockService->increase(
                                $sale->branch_id,
                                $item->product_id,
                                $item->quantity,
                                $item->unit_cost,
                                'sale_status_changed',
                                \App\Models\Sale::class,
                                $sale->id,
                                $sale->sale_number,
                                'Restored stock (status changed to reserved)'
                            );
                        }
                        // Now reserve it
                        $this->stockService->reserve($sale->branch_id, $item->product_id, $item->quantity);
                    } elseif (!$wasReserved && !$isReserved) {
                        // e.g. confirmed -> cancelled
                        if ($newStatus === 'cancelled' && $oldStatus !== 'cancelled') {
                            $this->stockService->increase(
                                $sale->branch_id,
                                $item->product_id,
                                $item->quantity,
                                $item->unit_cost,
                                'sale_cancelled',
                                \App\Models\Sale::class,
                                $sale->id,
                                $sale->sale_number,
                                'Sale cancelled'
                            );
                        } elseif ($oldStatus === 'cancelled' && $newStatus !== 'cancelled') {
                            $this->stockService->decrease(
                                $sale->branch_id,
                                $item->product_id,
                                $item->quantity,
                                'sale',
                                \App\Models\Sale::class,
                                $sale->id,
                                $sale->sale_number,
                                'Sale un-cancelled'
                            );
                        }
                    }
                    // If $wasReserved && $isReserved (e.g. draft -> on_hold), do nothing to stock.
                }
            }

            $sale->update(['status' => $newStatus]);
        });
    }

EOT;

$content = str_replace("public function deleteSale", $new_method . "    public function deleteSale", $content);
file_put_contents($file, $content);
?>
