<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Customer;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function __construct(
        private StockService $stockService,
        private NumberGeneratorService $numberGenerator
    ) {}

    /**
     * Create a new sale with items and payments.
     * Handles stock deduction, customer balance, profit calculation.
     */
    public function create(array $data): Sale
    {
        return DB::transaction(function () use ($data) {
            $user = auth()->user();
            $businessId = $user->business_id;

            // Validate stock before creating sale
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

            // Create sale
            $sale = Sale::create([
                'business_id' => $businessId,
                'branch_id' => $data['branch_id'],
                'customer_id' => $data['customer_id'] ?? null,
                'user_id' => $user->id,
                'sale_number' => $this->numberGenerator->generateSaleNumber($businessId),
                'sale_type' => $data['sale_type'] ?? 'sale',
                'status' => $data['status'] ?? 'confirmed',
                'payment_status' => $this->determinePaymentStatus($data),
                'fulfillment_status' => $data['fulfillment_status'] ?? ($data['sale_type'] === 'sale' ? 'fulfilled' : 'pending'),
                'transaction_date' => $data['transaction_date'] ?? today(),
                'due_date' => $data['due_date'] ?? null,
                'subtotal' => 0,
                'discount_percent' => $data['discount_percent'] ?? 0,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'total_amount' => 0,
                'paid_amount' => 0,
                'balance_amount' => 0,
                'cogs' => 0,
                'gross_profit' => 0,
                'notes' => $data['notes'] ?? null,
            ]);

            // Create items
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

                SaleItem::create([
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
            }

            // Calculate totals
            $discountAmount = (float)($data['discount_amount'] ?? 0);
            if ($discountAmount == 0 && isset($data['discount_percent']) && $data['discount_percent'] > 0) {
                $discountAmount = $subtotal * ((float)$data['discount_percent'] / 100);
            }
            $discountPercent = $subtotal > 0 ? ($discountAmount / $subtotal) * 100 : 0;
            $totalAmount = $subtotal - $discountAmount;
            $grossProfit = $totalAmount - $totalCogs;

            // Process payments
            $paidAmount = 0;
            foreach ($data['payments'] ?? [] as $paymentData) {
                $amount = (float) $paymentData['amount'];
                if ($amount <= 0) continue;

                SalePayment::create([
                    'sale_id' => $sale->id,
                    'business_id' => $businessId,
                    'customer_id' => $data['customer_id'] ?? null,
                    'user_id' => $user->id,
                    'payment_number' => $this->numberGenerator->generatePaymentNumber('sale', $businessId),
                    'payment_method' => $paymentData['payment_method'] ?? 'cash',
                    'amount' => $amount,
                    'payment_date' => $data['transaction_date'] ?? today(),
                    'reference' => $paymentData['reference'] ?? null,
                ]);
                $paidAmount += $amount;
            }

            $balanceAmount = $totalAmount - $paidAmount;

            $sale->update([
                'subtotal' => $subtotal,
                'discount_percent' => $discountPercent,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'balance_amount' => $balanceAmount,
                'cogs' => $totalCogs,
                'gross_profit' => $grossProfit,
                'payment_status' => $this->calculatePaymentStatus($totalAmount, $paidAmount),
            ]);

            // Update customer balance
            if ($balanceAmount > 0 && $data['customer_id']) {
                Customer::where('id', $data['customer_id'])->increment('current_balance', $balanceAmount);
                Customer::where('id', $data['customer_id'])->increment('total_purchases', $totalAmount);
            }

            AuditLog::record('create', 'sales', "Created sale {$sale->sale_number}", [], $sale->toArray(), $businessId, $sale);

            return $sale->load(['items.product', 'payments', 'customer']);
        });
    }

    /**
     * Record a payment against a sale.
     */
    public function recordPayment(Sale $sale, array $paymentData): SalePayment
    {
        return DB::transaction(function () use ($sale, $paymentData) {
            $user = auth()->user();
            $amount = (float) $paymentData['amount'];

            $payment = SalePayment::create([
                'sale_id' => $sale->id,
                'business_id' => $sale->business_id,
                'customer_id' => $sale->customer_id,
                'user_id' => $user->id,
                'payment_number' => $this->numberGenerator->generatePaymentNumber('sale', $sale->business_id),
                'payment_method' => $paymentData['payment_method'] ?? 'cash',
                'amount' => $amount,
                'payment_date' => $paymentData['payment_date'] ?? today(),
                'reference' => $paymentData['reference'] ?? null,
                'notes' => $paymentData['notes'] ?? null,
            ]);

            $newPaid = $sale->paid_amount + $amount;
            $newBalance = $sale->total_amount - $newPaid;

            $sale->update([
                'paid_amount' => $newPaid,
                'balance_amount' => $newBalance,
                'payment_status' => $this->calculatePaymentStatus($sale->total_amount, $newPaid),
            ]);

            // Update customer balance
            if ($sale->customer_id) {
                Customer::where('id', $sale->customer_id)->decrement('current_balance', $amount);
                Customer::where('id', $sale->customer_id)->increment('total_paid', $amount);
            }

            AuditLog::record('payment', 'sales', "Recorded payment {$payment->payment_number} for sale {$sale->sale_number}", [], ['amount' => $amount], $sale->business_id, $sale);

            return $payment;
        });
    }

    private function determinePaymentStatus(array $data): string
    {
        $totalPayment = collect($data['payments'] ?? [])->sum('amount');
        // We'll calculate proper status after creating totals
        return 'paid'; // Will be recalculated after
    }

    private function calculatePaymentStatus(float $total, float $paid): string
    {
        if ($paid <= 0) return 'unpaid';
        if ($paid >= $total) return 'paid';
        return 'partial';
    }

    
    public function updateSale(\App\Models\Sale $sale, array $data): \App\Models\Sale
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($sale, $data) {
            $user = auth()->user();
            
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
            }

            // 4. Update the sale record
            $discountAmount = (float)($data['discount_amount'] ?? 0);
            if ($discountAmount == 0 && isset($data['discount_percent']) && $data['discount_percent'] > 0) {
                $discountAmount = $subtotal * ((float)$data['discount_percent'] / 100);
            }
            $discountPercent = $subtotal > 0 ? ($discountAmount / $subtotal) * 100 : 0;
            $taxAmount = 0; // Simple implementation
            $totalAmount = $subtotal - $discountAmount + $taxAmount;
            
            // Update payments if provided
            $paidAmount = (float) $sale->paid_amount;
            if (isset($data['payments'])) {
                // Delete old payments
                $sale->payments()->delete();
                $paidAmount = 0;
                $businessId = $user->business_id;
                foreach ($data['payments'] as $paymentData) {
                    $amount = (float) $paymentData['amount'];
                    if ($amount <= 0) continue;
                    
                    \App\Models\SalePayment::create([
                        'sale_id' => $sale->id,
                        'business_id' => $businessId,
                        'customer_id' => $data['customer_id'] ?? null,
                        'user_id' => $user->id,
                        'payment_number' => $this->numberGenerator->generatePaymentNumber('sale', $businessId),
                        'payment_method' => $paymentData['payment_method'] ?? 'cash',
                        'amount' => $amount,
                        'payment_date' => $data['transaction_date'] ?? today(),
                        'reference' => $paymentData['reference'] ?? null,
                    ]);
                    $paidAmount += $amount;
                }
            }
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
    public function deleteSale(\App\Models\Sale $sale): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($sale) {
            $sale->load(['items', 'payments']);

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

            // Payments and items will be deleted automatically if we have soft deletes or cascade
            // But let's explicitly delete them if they don't cascade or soft delete
            foreach ($sale->payments as $payment) {
                $payment->delete();
            }

            foreach ($sale->items as $item) {
                $item->delete();
            }

            $sale->delete();
        });
    }
}
