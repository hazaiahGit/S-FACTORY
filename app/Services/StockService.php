<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Product;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;

class StockService
{
    /**
     * Increase stock using Weighted Average Cost method.
     * Called when purchasing or receiving production output.
     */
    public function increase(
        int $branchId,
        int $productId,
        float $quantity,
        float $unitCost,
        string $movementType,
        string $referenceType = null,
        int $referenceId = null,
        string $referenceNumber = null,
        string $notes = null,
        \DateTime|string $date = null
    ): Stock {
        return DB::transaction(function () use (
            $branchId, $productId, $quantity, $unitCost,
            $movementType, $referenceType, $referenceId,
            $referenceNumber, $notes, $date
        ) {
            $stock = Stock::firstOrCreate(
                ['branch_id' => $branchId, 'product_id' => $productId],
                [
                    'business_id' => auth()->user()->business_id,
                    'quantity' => 0,
                    'reserved_quantity' => 0,
                    'damaged_quantity' => 0,
                    'avg_cost' => 0,
                    'stock_value' => 0,
                ]
            );

            $prevQty = (float) $stock->quantity;
            $prevAvgCost = (float) $stock->avg_cost;
            $newQty = $prevQty + $quantity;

            // Weighted Average Cost calculation
            if ($newQty > 0) {
                $newAvgCost = (($prevQty * $prevAvgCost) + ($quantity * $unitCost)) / $newQty;
            } else {
                $newAvgCost = $unitCost;
            }

            $stock->update([
                'quantity' => $newQty,
                'avg_cost' => round($newAvgCost, 2),
                'stock_value' => round($newQty * $newAvgCost, 2),
            ]);

            // Update product cost price
            Product::where('id', $productId)->update(['cost_price' => round($newAvgCost, 2)]);

            // Record movement
            StockMovement::create([
                'business_id' => auth()->user()->business_id,
                'branch_id' => $branchId,
                'product_id' => $productId,
                'user_id' => auth()->id(),
                'movement_type' => $movementType,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reference_number' => $referenceNumber,
                'quantity_before' => $prevQty,
                'quantity_change' => $quantity,
                'quantity_after' => $newQty,
                'unit_cost' => $unitCost,
                'total_cost' => $quantity * $unitCost,
                'notes' => $notes,
                'transaction_date' => $date ?? today(),
            ]);

            return $stock->fresh();
        });
    }

    /**
     * Decrease stock. Called when selling, transferring out, adjusting down.
     * Validates sufficient stock unless negative stock is allowed.
     */
    public function decrease(
        int $branchId,
        int $productId,
        float $quantity,
        string $movementType,
        string $referenceType = null,
        int $referenceId = null,
        string $referenceNumber = null,
        string $notes = null,
        \DateTime|string $date = null,
        bool $allowNegative = false
    ): Stock {
        return DB::transaction(function () use (
            $branchId, $productId, $quantity, $movementType,
            $referenceType, $referenceId, $referenceNumber,
            $notes, $date, $allowNegative
        ) {
            $stock = Stock::where('branch_id', $branchId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            if (!$stock) {
                if (!$allowNegative) {
                    throw new \RuntimeException('No stock record found for this product at this branch.');
                }
                $businessId = auth()->user()->business_id;
                $stock = Stock::create([
                    'business_id' => $businessId,
                    'branch_id' => $branchId,
                    'product_id' => $productId,
                    'quantity' => 0,
                    'avg_cost' => 0,
                    'stock_value' => 0,
                ]);
            }

            $prevQty = (float) $stock->quantity;
            if (!$allowNegative && $prevQty < $quantity) {
                $product = Product::find($productId);
                throw new \RuntimeException(
                    "Insufficient stock for '{$product->name}'. Available: {$prevQty}, Required: {$quantity}"
                );
            }

            $newQty = $prevQty - $quantity;
            $avgCost = (float) $stock->avg_cost;

            $stock->update([
                'quantity' => $newQty,
                'stock_value' => round($newQty * $avgCost, 2),
            ]);

            StockMovement::create([
                'business_id' => auth()->user()->business_id,
                'branch_id' => $branchId,
                'product_id' => $productId,
                'user_id' => auth()->id(),
                'movement_type' => $movementType,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reference_number' => $referenceNumber,
                'quantity_before' => $prevQty,
                'quantity_change' => -$quantity,
                'quantity_after' => $newQty,
                'unit_cost' => $avgCost,
                'total_cost' => $quantity * $avgCost,
                'notes' => $notes,
                'transaction_date' => $date ?? today(),
            ]);

            return $stock->fresh();
        });
    }

    /**
     * Get current stock level for a product at a branch.
     */
        /**
     * Reserve stock for an order (e.g. Draft / On Hold).
     */
    public function reserve(int $branchId, int $productId, float $quantity): Stock
    {
        return DB::transaction(function () use ($branchId, $productId, $quantity) {
            $stock = Stock::firstOrCreate(
                ['branch_id' => $branchId, 'product_id' => $productId],
                [
                    'business_id' => auth()->user()->business_id,
                    'quantity' => 0,
                    'reserved_quantity' => 0,
                    'damaged_quantity' => 0,
                    'avg_cost' => 0,
                    'stock_value' => 0,
                ]
            );

            // You could optionally throw an error if $stock->available_quantity < $quantity,
            // but since S-FACTORY allows negative stock settings, we'll just increment the reserve.
            $stock->increment('reserved_quantity', $quantity);

            return $stock->fresh();
        });
    }

    /**
     * Release reserved stock back to available (e.g. Order Cancelled, or Reversing an On Hold order).
     */
    public function releaseReservation(int $branchId, int $productId, float $quantity): Stock
    {
        return DB::transaction(function () use ($branchId, $productId, $quantity) {
            $stock = Stock::where('branch_id', $branchId)->where('product_id', $productId)->first();
            if ($stock) {
                // Ensure we don't go below 0 reserved
                $newReserved = max(0, $stock->reserved_quantity - $quantity);
                $stock->update(['reserved_quantity' => $newReserved]);
            }
            return $stock ?? new Stock();
        });
    }
    public function getStock(int $branchId, int $productId): float
    {
        $stock = Stock::where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->first();
            
        return $stock ? $stock->available_quantity : 0;
    }

    /**
     * Get stock across all branches for a product.
     */
    public function getTotalStock(int $productId): float
    {
        return (float) Stock::where('product_id', $productId)->sum('quantity');
    }

    /**
     * Check if there is enough stock for a sale.
     */
    public function hasEnoughStock(int $branchId, int $productId, float $required): bool
    {
        $available = $this->getStock($branchId, $productId);
        return $available >= $required;
    }

    /**
     * Get products with low stock for a branch.
     */
    public function getLowStockProducts(int $branchId, int $businessId)
    {
        return Stock::with(['product.category', 'product.unit'])
            ->where('branch_id', $branchId)
            ->whereHas('product', function ($q) use ($businessId) {
                $q->where('business_id', $businessId)
                  ->where('is_active', true)
                  ->where('track_stock', true)
                  ->where('min_stock', '>', 0);
            })
            ->get()
            ->filter(function ($s) {
                return $s->quantity <= $s->product->min_stock;
            });
    }

    /**
     * Transfer stock between branches.
     * Source stock is decreased immediately but destination stock increases only on receive.
     */
    public function transferOut(
        int $fromBranchId,
        int $productId,
        float $quantity,
        int $transferId,
        string $referenceNumber
    ): void {
        $this->decrease(
            $fromBranchId,
            $productId,
            $quantity,
            'stock_transfer_out',
            \App\Models\StockTransfer::class,
            $transferId,
            $referenceNumber
        );
    }

    public function transferIn(
        int $toBranchId,
        int $productId,
        float $quantity,
        float $unitCost,
        int $transferId,
        string $referenceNumber
    ): void {
        $this->increase(
            $toBranchId,
            $productId,
            $quantity,
            $unitCost,
            'stock_transfer_in',
            \App\Models\StockTransfer::class,
            $transferId,
            $referenceNumber
        );
    }
}
