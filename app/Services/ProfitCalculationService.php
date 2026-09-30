<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\Stock;
use App\Models\Supplier;

class ProfitCalculationService
{
    /**
     * Calculate Profit & Loss for a given period and branch.
     * Returns a structured array suitable for the P&L report.
     */
    public function calculate(
        int $businessId,
        \DateTime|string $from,
        \DateTime|string $to,
        ?int $branchId = null
    ): array {
        $salesQuery = Sale::where('business_id', $businessId)
            ->where('sale_type', 'sale')
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->whereBetween('transaction_date', [$from, $to]);

        $expenseQuery = Expense::where('business_id', $businessId)
            ->where('status', 'approved')
            ->whereBetween('expense_date', [$from, $to]);

        if ($branchId) {
            $salesQuery->where('branch_id', $branchId);
            $expenseQuery->where('branch_id', $branchId);
        }

        $revenue = (float) $salesQuery->sum('total_amount');
        $cogs = (float) $salesQuery->sum('cogs');
        $grossProfit = (float) $salesQuery->sum('gross_profit');
        $totalExpenses = (float) $expenseQuery->sum('amount');
        $netProfit = $grossProfit - $totalExpenses;

        $grossMargin = $revenue > 0 ? ($grossProfit / $revenue) * 100 : 0;
        $netMargin = $revenue > 0 ? ($netProfit / $revenue) * 100 : 0;

        return [
            'period_from' => $from,
            'period_to' => $to,
            'revenue' => round($revenue, 2),
            'cogs' => round($cogs, 2),
            'gross_profit' => round($grossProfit, 2),
            'gross_margin_percent' => round($grossMargin, 2),
            'total_expenses' => round($totalExpenses, 2),
            'net_profit' => round($netProfit, 2),
            'net_margin_percent' => round($netMargin, 2),
            'is_profit' => $netProfit >= 0,
        ];
    }

    /**
     * Calculate today's dashboard summary.
     */
    public function todaySummary(int $businessId, ?int $branchId = null): array
    {
        $today = today();

        return $this->calculate($businessId, $today, $today, $branchId);
    }

    /**
     * Get total outstanding customer debt.
     */
    public function totalCustomerDebt(int $businessId, ?int $branchId = null): float
    {
        return (float) Customer::where('business_id', $businessId)
            ->where('current_balance', '>', 0)
            ->sum('current_balance');
    }

    /**
     * Get total supplier debt (what we owe).
     */
    public function totalSupplierDebt(int $businessId): float
    {
        return (float) Supplier::where('business_id', $businessId)
            ->where('current_balance', '>', 0)
            ->sum('current_balance');
    }

    /**
     * Get total stock value across all branches.
     */
    public function totalStockValue(int $businessId, ?int $branchId = null): float
    {
        $query = Stock::where('business_id', $businessId);
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return (float) $query->sum('stock_value');
    }

    /**
     * Sales breakdown by payment method.
     */
    public function salesByPaymentMethod(int $businessId, $from, $to, ?int $branchId = null): array
    {
        $query = SalePayment::where('business_id', $businessId)
            ->whereBetween('payment_date', [$from, $to]);
        if ($branchId) {
            $query->whereHas('sale', fn ($q) => $q->where('branch_id', $branchId));
        }

        return $query->selectRaw('payment_method, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('payment_method')
            ->get()
            ->toArray();
    }
}
