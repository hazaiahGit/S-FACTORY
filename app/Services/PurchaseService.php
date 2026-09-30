<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\ProductBatch;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchasePayment;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function __construct(
        private StockService $stockService,
        private NumberGeneratorService $numberGenerator
    ) {}

    /**
     * Create a purchase, receive stock, update supplier balance.
     */
    public function create(array $data): Purchase
    {
        return DB::transaction(function () use ($data) {
            $user = auth()->user();
            $businessId = $user->business_id;

            $purchase = Purchase::create([
                'business_id' => $businessId,
                'branch_id' => $data['branch_id'],
                'supplier_id' => $data['supplier_id'] ?? null,
                'user_id' => $user->id,
                'purchase_number' => $this->numberGenerator->generatePurchaseNumber($businessId),
                'reference' => $data['reference'] ?? null,
                'status' => 'received',
                'payment_status' => 'unpaid',
                'transaction_date' => $data['transaction_date'] ?? today(),
                'received_date' => $data['transaction_date'] ?? today(),
                'subtotal' => 0,
                'discount_amount' => $data['discount_amount'] ?? 0,
                'tax_amount' => $data['tax_amount'] ?? 0,
                'transport_cost' => $data['transport_cost'] ?? 0,
                'other_costs' => $data['other_costs'] ?? 0,
                'total_amount' => 0,
                'paid_amount' => 0,
                'balance_amount' => 0,
                'notes' => $data['notes'] ?? null,
            ]);

            $subtotal = 0;
            $totalLandedCost = (float) ($data['transport_cost'] ?? 0) + (float) ($data['other_costs'] ?? 0);
            $totalItemCost = 0;

            // First pass: calculate item totals for landed cost distribution
            foreach ($data['items'] as &$itemData) {
                $qty = (float) $itemData['quantity'];
                $cost = (float) $itemData['unit_cost'];
                $itemData['_line_total'] = $qty * $cost;
                $totalItemCost += $itemData['_line_total'];
            }

            // Second pass: create items with landed cost
            foreach ($data['items'] as $itemData) {
                $qty = (float) $itemData['quantity'];
                $unitCost = (float) $itemData['unit_cost'];
                $lineTotal = (float) $itemData['_line_total'];

                // Distribute landed cost proportionally
                $landedCostShare = $totalItemCost > 0
                    ? ($lineTotal / $totalItemCost) * $totalLandedCost
                    : 0;
                $landedUnitCost = $qty > 0 ? ($lineTotal + $landedCostShare) / $qty : $unitCost;

                // Generate batch number
                $batchNumber = $this->numberGenerator->generateBatchNumber($businessId);

                $batch = ProductBatch::create([
                    'business_id' => $businessId,
                    'branch_id' => $data['branch_id'],
                    'product_id' => $itemData['product_id'],
                    'supplier_id' => $data['supplier_id'] ?? null,
                    'batch_number' => $batchNumber,
                    'purchase_date' => $data['transaction_date'] ?? today(),
                    'expiry_date' => $itemData['expiry_date'] ?? null,
                    'quantity_received' => $qty,
                    'quantity_remaining' => $qty,
                    'unit_cost' => $unitCost,
                    'total_cost' => $lineTotal,
                    'reference' => $purchase->purchase_number,
                ]);

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $itemData['product_id'],
                    'batch_id' => $batch->id,
                    'quantity' => $qty,
                    'received_quantity' => $qty,
                    'unit_cost' => $unitCost,
                    'landed_unit_cost' => round($landedUnitCost, 2),
                    'discount_percent' => $itemData['discount_percent'] ?? 0,
                    'discount_amount' => $itemData['discount_amount'] ?? 0,
                    'tax_percent' => $itemData['tax_percent'] ?? 0,
                    'tax_amount' => $itemData['tax_amount'] ?? 0,
                    'total_cost' => $lineTotal,
                    'batch_number' => $batchNumber,
                    'expiry_date' => $itemData['expiry_date'] ?? null,
                ]);

                $subtotal += $lineTotal;

                // Increase stock
                $this->stockService->increase(
                    $data['branch_id'],
                    $itemData['product_id'],
                    $qty,
                    $landedUnitCost,
                    'purchase',
                    Purchase::class,
                    $purchase->id,
                    $purchase->purchase_number,
                    null,
                    $data['transaction_date'] ?? today()
                );
            }

            $totalAmount = $subtotal + (float) ($data['tax_amount'] ?? 0)
                + (float) ($data['transport_cost'] ?? 0)
                + (float) ($data['other_costs'] ?? 0)
                - (float) ($data['discount_amount'] ?? 0);

            // Process payments
            $paidAmount = 0;
            foreach ($data['payments'] ?? [] as $paymentData) {
                $amount = (float) $paymentData['amount'];
                if ($amount <= 0) {
                    continue;
                }

                PurchasePayment::create([
                    'purchase_id' => $purchase->id,
                    'business_id' => $businessId,
                    'supplier_id' => $data['supplier_id'] ?? null,
                    'user_id' => $user->id,
                    'payment_number' => $this->numberGenerator->generatePaymentNumber('purchase', $businessId),
                    'payment_method' => $paymentData['payment_method'] ?? 'cash',
                    'amount' => $amount,
                    'payment_date' => $data['transaction_date'] ?? today(),
                    'reference' => $paymentData['reference'] ?? null,
                ]);
                $paidAmount += $amount;
            }

            $balanceAmount = $totalAmount - $paidAmount;
            $paymentStatus = $paidAmount >= $totalAmount ? 'paid' : ($paidAmount > 0 ? 'partial' : 'unpaid');

            $purchase->update([
                'subtotal' => $subtotal,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'balance_amount' => $balanceAmount,
                'payment_status' => $paymentStatus,
                'landed_cost' => $totalLandedCost,
            ]);

            // Update supplier balance
            if ($balanceAmount > 0 && $data['supplier_id']) {
                Supplier::where('id', $data['supplier_id'])->increment('current_balance', $balanceAmount);
            }

            AuditLog::record('create', 'purchases', "Created purchase {$purchase->purchase_number}", [], [], $businessId, $purchase);

            return $purchase->load(['items.product', 'supplier', 'payments']);
        });
    }

    /**
     * Record payment for a purchase.
     */
    public function recordPayment(Purchase $purchase, array $paymentData): PurchasePayment
    {
        return DB::transaction(function () use ($purchase, $paymentData) {
            $user = auth()->user();
            $amount = (float) $paymentData['amount'];

            $payment = PurchasePayment::create([
                'purchase_id' => $purchase->id,
                'business_id' => $purchase->business_id,
                'supplier_id' => $purchase->supplier_id,
                'user_id' => $user->id,
                'payment_number' => $this->numberGenerator->generatePaymentNumber('purchase', $purchase->business_id),
                'payment_method' => $paymentData['payment_method'] ?? 'cash',
                'amount' => $amount,
                'payment_date' => $paymentData['payment_date'] ?? today(),
                'reference' => $paymentData['reference'] ?? null,
            ]);

            $newPaid = $purchase->paid_amount + $amount;
            $newBalance = $purchase->total_amount - $newPaid;
            $paymentStatus = $newPaid >= $purchase->total_amount ? 'paid' : ($newPaid > 0 ? 'partial' : 'unpaid');

            $purchase->update([
                'paid_amount' => $newPaid,
                'balance_amount' => $newBalance,
                'payment_status' => $paymentStatus,
            ]);

            // Update supplier balance
            if ($purchase->supplier_id) {
                Supplier::where('id', $purchase->supplier_id)->decrement('current_balance', $amount);
            }

            AuditLog::record('payment', 'purchases', "Payment {$payment->payment_number} for purchase {$purchase->purchase_number}", [], ['amount' => $amount], $purchase->business_id, $purchase);

            return $payment;
        });
    }
}
