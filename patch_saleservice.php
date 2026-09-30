<?php
$file = "app/Services/SaleService.php";
$content = file_get_contents($file);

// 1. In create(), replace the decrease logic
$old_create_stock = <<<'EOT'
                // Deduct stock for actual sales
                if ($data['sale_type'] === 'sale' && $product->track_stock) {
                    $this->stockService->decrease(
                        $data['branch_id'],
                        $product->id,
                        $quantity,
                        'sale',
                        \App\Models\Sale::class,
                        $sale->id,
                        $sale->sale_number,
                        'Product sold'
                    );
                }
EOT;
$new_create_stock = <<<'EOT'
                // Deduct or Reserve stock for actual sales
                if ($data['sale_type'] === 'sale' && $product->track_stock) {
                    if (in_array($data['status'] ?? 'confirmed', ['draft', 'on_hold'])) {
                        $this->stockService->reserve($data['branch_id'], $product->id, $quantity);
                    } else {
                        $this->stockService->decrease(
                            $data['branch_id'],
                            $product->id,
                            $quantity,
                            'sale',
                            \App\Models\Sale::class,
                            $sale->id,
                            $sale->sale_number,
                            'Product sold'
                        );
                    }
                }
EOT;
$content = str_replace($old_create_stock, $new_create_stock, $content);


// 2. In updateSale(), replace the reversal logic
$old_update_reversal = <<<'EOT'
            // 1. Reverse stock for existing items
            foreach ($sale->items as $item) {
                if ($sale->sale_type === 'sale' && $item->product && $item->product->track_stock) {
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
                $item->delete();
            }
EOT;
$new_update_reversal = <<<'EOT'
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
EOT;
$content = str_replace($old_update_reversal, $new_update_reversal, $content);

// 3. In updateSale(), replace the deduction logic
$old_update_deduction = <<<'EOT'
                // Deduct stock
                if ($data['sale_type'] === 'sale' && $product->track_stock) {
                    $this->stockService->decrease(
                        $sale->branch_id,
                        $product->id,
                        $quantity,
                        'sale',
                        \App\Models\Sale::class,
                        $sale->id,
                        $sale->sale_number,
                        'Product sold (edited)'
                    );
                }
EOT;
$new_update_deduction = <<<'EOT'
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
                            'Product sold (edited)'
                        );
                    }
                }
EOT;
$content = str_replace($old_update_deduction, $new_update_deduction, $content);


// 4. In deleteSale(), replace the reversal logic
$old_delete_reversal = <<<'EOT'
            // Reverse stock
            foreach ($sale->items as $item) {
                if ($sale->sale_type === 'sale' && $item->product && $item->product->track_stock) {
                    $this->stockService->increase(
                        $sale->branch_id,
                        $item->product_id,
                        $item->quantity,
                        $item->unit_cost,
                        'sale_deleted',
                        \App\Models\Sale::class,
                        $sale->id,
                        $sale->sale_number,
                        'Sale deleted'
                    );
                }
            }
EOT;
$new_delete_reversal = <<<'EOT'
            // Reverse stock
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
                            'sale_deleted',
                            \App\Models\Sale::class,
                            $sale->id,
                            $sale->sale_number,
                            'Sale deleted'
                        );
                    }
                }
            }
EOT;
$content = str_replace($old_delete_reversal, $new_delete_reversal, $content);

file_put_contents($file, $content);
?>
