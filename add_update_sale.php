<?php
$serviceFile = "app/Services/SaleService.php";
$content = file_get_contents($serviceFile);

$updateSaleMethod = <<<'EOT'

    public function updateSale(\App\Models\Sale $sale, array $data): \App\Models\Sale
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($sale, $data) {
            $user = auth()->user();
            
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

            // 2. Validate stock for new items
            if ($data['sale_type'] === 'sale') {
                foreach ($data['items'] as $item) {
                    $product = \App\Models\Product::findOrFail($item['product_id']);
                    if ($product->track_stock) {
                        $business = $user->business;
                        if (!$business->negative_stock_allowed) {
                            $available = $this->stockService->getStock($data['branch_id'], $item['product_id']);
                            if ($available < $item['quantity']) {
                                throw new \RuntimeException(
                                    "Insufficient stock for '{$product->name}'. Available: {$available}, Required: {$item['quantity']}"
                                );
                            }
                        }
                    }
                }
            }

            // 3. Re-create items and calculate totals
            $subtotal = 0;
            $totalCogs = 0;
            
            foreach ($data['items'] as $itemData) {
                $product = \App\Models\Product::findOrFail($itemData['product_id']);
                $unitCost = (float) $product->cost_price;
                $unitPrice = (float) $itemData['unit_price'];
                $quantity = (float) $itemData['quantity'];
                $discountAmount = (float) ($itemData['discount_amount'] ?? 0);
                $taxAmount = (float) ($itemData['tax_amount'] ?? 0);
                $lineTotal = ($unitPrice * $quantity) - $discountAmount + $taxAmount;
                $lineCogs = $unitCost * $quantity;
                $lineProfit = $lineTotal - $lineCogs;

                \App\Models\SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'collected_quantity' => $data['sale_type'] === 'sale' ? $quantity : 0,
                    'unit_price' => $unitPrice,
                    'unit_cost' => $unitCost,
                    'price_type' => $itemData['price_type'] ?? 'retail',
                    'discount_percent' => $itemData['discount_percent'] ?? 0,
                    'discount_amount' => $discountAmount,
                    'tax_percent' => $itemData['tax_percent'] ?? 0,
                    'tax_amount' => $taxAmount,
                    'total_price' => $lineTotal,
                    'line_cogs' => $lineCogs,
                    'line_profit' => $lineProfit,
                    'fulfillment_status' => $data['fulfillment_status'] ?? ($data['sale_type'] === 'sale' ? 'fulfilled' : 'pending'),
                    'notes' => $itemData['notes'] ?? null,
                ]);

                $subtotal += $lineTotal;
                $totalCogs += $lineCogs;

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
                        null,
                        $data['transaction_date'] ?? today(),
                        $user->business->negative_stock_allowed ?? false
                    );
                }
            }

            // 4. Update the sale record
            $discountPercent = $data['discount_percent'] ?? 0;
            $discountAmount = ($subtotal * $discountPercent) / 100;
            $taxAmount = 0; // Simple implementation
            $totalAmount = $subtotal - $discountAmount + $taxAmount;
            
            // Assume payments are unchanged, update balance
            $paidAmount = (float) $sale->paid_amount;
            $balanceAmount = $totalAmount - $paidAmount;
            
            $sale->update([
                'branch_id' => $data['branch_id'],
                'customer_id' => $data['customer_id'] ?? null,
                'sale_type' => $data['sale_type'] ?? 'sale',
                'status' => $data['status'] ?? 'confirmed',
                'payment_status' => $this->calculatePaymentStatus($totalAmount, $paidAmount),
                'fulfillment_status' => $data['fulfillment_status'] ?? ($data['sale_type'] === 'sale' ? 'fulfilled' : 'pending'),
                'transaction_date' => $data['transaction_date'] ?? today(),
                'due_date' => $data['due_date'] ?? null,
                'subtotal' => $subtotal,
                'discount_percent' => $discountPercent,
                'discount_amount' => $discountAmount,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'balance_amount' => $balanceAmount,
                'cogs' => $totalCogs,
                'gross_profit' => $totalAmount - $totalCogs,
                'notes' => $data['notes'] ?? null,
            ]);

            return $sale->fresh(['items']);
        });
    }
EOT;

$content = str_replace("public function deleteSale(\App\Models\Sale \$sale): void", $updateSaleMethod . "\n\n    public function deleteSale(\App\Models\Sale \$sale): void", $content);
file_put_contents($serviceFile, $content);
echo "Updated SaleService\n";
?>
