<?php
$file = "app/Services/StockService.php";
$content = file_get_contents($file);

$new_methods = <<<'EOT'
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

EOT;

// Insert before getStock
$content = str_replace("public function getStock", $new_methods . "    public function getStock", $content);

// Also modify getStock to return Available stock rather than Total quantity if needed?
// Wait, getStock is used to check if there is enough stock!
// It currently returns `quantity`. But it SHOULD return `available_quantity`!
$old_getStock = <<<'EOT'
    public function getStock(int $branchId, int $productId): float
    {
        return (float) Stock::where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->value('quantity') ?? 0;
    }
EOT;
$new_getStock = <<<'EOT'
    public function getStock(int $branchId, int $productId): float
    {
        $stock = Stock::where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->first();
            
        return $stock ? $stock->available_quantity : 0;
    }
EOT;
$content = str_replace($old_getStock, $new_getStock, $content);

file_put_contents($file, $content);
?>
