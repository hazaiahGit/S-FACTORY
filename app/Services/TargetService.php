<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\ProductionOrder;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Target;
use Carbon\Carbon;

class TargetService
{
    /**
     * Calculate comprehensive progress for a target.
     */
    public function calculateProgress(Target $target): array
    {
        $actual = $this->getActualValue($target);
        $targetValue = (float) $target->target_value;

        // Prevent division by zero
        if ($targetValue <= 0) {
            $targetValue = 1;
        }

        $achievementPercent = ($actual / $targetValue) * 100;
        $remaining = max(0, $targetValue - $actual);

        // Time elapsed calculation
        $start = Carbon::parse($target->start_date)->startOfDay();
        $end = Carbon::parse($target->end_date)->endOfDay();
        $now = now();

        if ($now < $start) {
            $timeElapsedPercent = 0;
            $daysTotal = $start->diffInDays($end) + 1;
            $daysRemaining = $daysTotal;
        } elseif ($now > $end) {
            $timeElapsedPercent = 100;
            $daysTotal = $start->diffInDays($end) + 1;
            $daysRemaining = 0;
        } else {
            $totalSeconds = $end->timestamp - $start->timestamp;
            $elapsedSeconds = $now->timestamp - $start->timestamp;
            $timeElapsedPercent = ($elapsedSeconds / $totalSeconds) * 100;

            $daysTotal = $start->diffInDays($end) + 1;
            $daysRemaining = $now->diffInDays($end) + 1;
        }

        // Expected progress
        $expectedActual = ($timeElapsedPercent / 100) * $targetValue;
        $dailyRequired = $daysRemaining > 0 ? ($remaining / $daysRemaining) : 0;
        $projectedTotal = $timeElapsedPercent > 0 ? ($actual / ($timeElapsedPercent / 100)) : 0;

        $variance = $actual - $expectedActual;

        return [
            'actual' => round($actual, 2),
            'target' => round($targetValue, 2),
            'remaining' => round($remaining, 2),
            'achievement_percent' => round($achievementPercent, 2),
            'time_elapsed_percent' => round($timeElapsedPercent, 2),
            'daily_required' => round($dailyRequired, 2),
            'projected_total' => round($projectedTotal, 2),
            'variance' => round($variance, 2),
            'status' => $this->determineStatus($actual, $targetValue, $achievementPercent, $timeElapsedPercent),
        ];
    }

    /**
     * Get the actual achieved value based on target type and scope.
     */
    private function getActualValue(Target $target): float
    {
        $query = null;
        $sumColumn = 'total_amount';

        switch ($target->target_type) {
            case 'sales':
                $query = Sale::where('business_id', $target->business_id)
                    ->where('sale_type', 'sale')
                    ->whereNotIn('status', ['cancelled', 'draft']);
                break;

            case 'profit':
                $query = Sale::where('business_id', $target->business_id)
                    ->where('sale_type', 'sale')
                    ->whereNotIn('status', ['cancelled', 'draft']);
                $sumColumn = 'gross_profit';
                break;

            case 'production':
                $query = ProductionOrder::where('business_id', $target->business_id)
                    ->where('status', 'completed');
                $sumColumn = 'actual_quantity';
                break;

            case 'purchase':
                $query = Purchase::where('business_id', $target->business_id)
                    ->whereNotIn('status', ['cancelled', 'draft']);
                break;

            case 'collection':
                $query = SalePayment::where('business_id', $target->business_id);
                $sumColumn = 'amount';
                break;

            case 'expense':
                $query = Expense::where('business_id', $target->business_id)
                    ->where('status', 'approved');
                $sumColumn = 'amount';
                break;

            case 'product':
            case 'category':
                // For specific products or categories, we must join sale_items
                $query = SaleItem::join('sales', 'sales.id', '=', 'sale_items.sale_id')
                    ->where('sales.business_id', $target->business_id)
                    ->where('sales.sale_type', 'sale')
                    ->whereNotIn('sales.status', ['cancelled']);

                if ($target->target_type === 'category') {
                    $query->join('products', 'products.id', '=', 'sale_items.product_id');
                }

                // Are we measuring count (qty) or amount (revenue)?
                $sumColumn = $target->measurement_unit === 'quantity' ? 'sale_items.quantity' : 'sale_items.total_price';
                break;

            case 'customer':
                $query = Customer::where('business_id', $target->business_id);
                // Customers acquired
                $sumColumn = 'count';
                break;

            default:
                return 0;
        }

        // Apply Scope Filters
        if ($target->branch_id) {
            if (in_array($target->target_type, ['product', 'category'])) {
                $query->where('sales.branch_id', $target->branch_id);
            } elseif ($target->target_type === 'collection') {
                $query->whereHas('sale', fn ($q) => $q->where('branch_id', $target->branch_id));
            } elseif ($target->target_type !== 'customer') {
                $query->where('branch_id', $target->branch_id);
            }
        }

        if ($target->user_id) {
            if (in_array($target->target_type, ['product', 'category'])) {
                $query->where('sales.user_id', $target->user_id);
            } else {
                $query->where('user_id', $target->user_id);
            }
        }

        if ($target->product_id && in_array($target->target_type, ['sales', 'profit', 'product'])) {
            if (in_array($target->target_type, ['product', 'category'])) {
                $query->where('sale_items.product_id', $target->product_id);
            } elseif ($target->target_type === 'production') {
                $query->where('product_id', $target->product_id);
            }
        }

        if ($target->category_id && $target->target_type === 'category') {
            $query->where('products.category_id', $target->category_id);
        }

        if ($target->customer_id) {
            if (in_array($target->target_type, ['product', 'category'])) {
                $query->where('sales.customer_id', $target->customer_id);
            } else {
                $query->where('customer_id', $target->customer_id);
            }
        }

        // Apply Date Filters
        $dateColumn = match ($target->target_type) {
            'collection' => 'payment_date',
            'expense' => 'expense_date',
            'customer' => 'created_at',
            'production' => 'completion_date',
            'product', 'category' => 'sales.transaction_date',
            default => 'transaction_date',
        };

        if ($sumColumn === 'count') {
            return (float) $query->whereBetween($dateColumn, [
                $target->start_date->format('Y-m-d 00:00:00'),
                $target->end_date->format('Y-m-d 23:59:59'),
            ])->count();
        }

        return (float) $query->whereBetween($dateColumn, [
            $target->start_date->format('Y-m-d'),
            $target->end_date->format('Y-m-d'),
        ])->sum($sumColumn);
    }

    /**
     * Determine the status text based on progress.
     */
    private function determineStatus(float $actual, float $target, float $achievementPercent, float $timeElapsedPercent): string
    {
        if ($actual <= 0 && $timeElapsedPercent < 100) {
            return 'NOT STARTED';
        }

        if ($actual > $target) {
            return 'EXCEEDED';
        }

        if ($actual == $target || $achievementPercent >= 100) {
            return 'TARGET ACHIEVED';
        }

        // If it's an expense target, logic is reversed (lower is better)
        // We will assume normal positive targets here for simplicity.
        // If needed, Expense logic could be injected.

        // Allowed 5% buffer for being "On Track"
        if ($achievementPercent >= ($timeElapsedPercent - 5)) {
            return 'ON TRACK';
        }

        return 'BEHIND TARGET';
    }
}
