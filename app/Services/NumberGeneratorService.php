<?php

namespace App\Services;

use App\Models\Business;
use Illuminate\Support\Facades\DB;

class NumberGeneratorService
{
    /**
     * Generate a unique sequential number for various document types.
     */
    public function generate(string $type, int $businessId): string
    {
        return DB::transaction(function () use ($type, $businessId) {
            $business = Business::findOrFail($businessId);
            $today = now()->format('Ymd');
            $prefix = $this->getPrefix($type, $business);

            // Get the last number for this type today (or all time)
            $table = $this->getTable($type);
            $column = $this->getColumn($type);

            $lastNumber = DB::table($table)
                ->where('business_id', $businessId)
                ->where($column, 'like', "{$prefix}-{$today}-%")
                ->orderBy($column, 'desc')
                ->value($column);

            if ($lastNumber) {
                $lastSeq = (int) substr($lastNumber, -4);
                $seq = str_pad($lastSeq + 1, 4, '0', STR_PAD_LEFT);
            } else {
                $seq = '0001';
            }

            return "{$prefix}-{$today}-{$seq}";
        });
    }

    public function generateSaleNumber(int $businessId): string
    {
        return $this->generate('sale', $businessId);
    }

    public function generatePurchaseNumber(int $businessId): string
    {
        return $this->generate('purchase', $businessId);
    }

    public function generateExpenseNumber(int $businessId): string
    {
        return $this->generate('expense', $businessId);
    }

    public function generatePaymentNumber(string $type, int $businessId): string
    {
        return $this->generate($type . '_payment', $businessId);
    }

    public function generateBatchNumber(int $businessId, string $productPrefix = null): string
    {
        $business = Business::findOrFail($businessId);
        $prefix = $productPrefix ?? $business->batch_prefix ?? 'HW';
        $today = now()->format('Ymd');

        $lastNumber = DB::table('product_batches')
            ->where('business_id', $businessId)
            ->where('batch_number', 'like', "{$prefix}-{$today}-%")
            ->orderBy('batch_number', 'desc')
            ->value('batch_number');

        if ($lastNumber) {
            $lastSeq = (int) substr($lastNumber, -4);
            $seq = str_pad($lastSeq + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $seq = '0001';
        }

        return "{$prefix}-{$today}-{$seq}";
    }

    public function generateProductionNumber(int $businessId): string
    {
        $today = now()->format('Ymd');

        $lastNumber = DB::table('production_orders')
            ->where('business_id', $businessId)
            ->where('production_number', 'like', "PROD-{$today}-%")
            ->orderBy('production_number', 'desc')
            ->value('production_number');

        if ($lastNumber) {
            $lastSeq = (int) substr($lastNumber, -4);
            $seq = str_pad($lastSeq + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $seq = '0001';
        }

        return "PROD-{$today}-{$seq}";
    }

    public function generateTransferNumber(int $businessId): string
    {
        return $this->generate('transfer', $businessId);
    }

    public function generateStockTakeNumber(int $businessId): string
    {
        return $this->generate('stock_take', $businessId);
    }

    public function generateAdjustmentNumber(int $businessId): string
    {
        return $this->generate('adjustment', $businessId);
    }

    private function getPrefix(string $type, Business $business): string
    {
        return match ($type) {
            'sale' => $business->invoice_prefix ?? 'INV',
            'purchase' => $business->po_prefix ?? 'PO',
            'expense' => 'EXP',
            'sale_payment' => 'RPMT',
            'purchase_payment' => 'PPMT',
            'transfer' => 'TRF',
            'stock_take' => 'STK',
            'adjustment' => 'ADJ',
            default => strtoupper($type),
        };
    }

    private function getTable(string $type): string
    {
        return match ($type) {
            'sale' => 'sales',
            'purchase' => 'purchases',
            'expense' => 'expenses',
            'sale_payment' => 'sale_payments',
            'purchase_payment' => 'purchase_payments',
            'transfer' => 'stock_transfers',
            'stock_take' => 'stock_takes',
            'adjustment' => 'stock_adjustments',
            default => $type . 's',
        };
    }

    private function getColumn(string $type): string
    {
        return match ($type) {
            'sale' => 'sale_number',
            'purchase' => 'purchase_number',
            'expense' => 'expense_number',
            'sale_payment' => 'payment_number',
            'purchase_payment' => 'payment_number',
            'transfer' => 'transfer_number',
            'stock_take' => 'stock_take_number',
            'adjustment' => 'adjustment_number',
            default => $type . '_number',
        };
    }
}
