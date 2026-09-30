<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequest;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Stock;
use App\Models\Supplier;
use App\Services\ProfitCalculationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private ProfitCalculationService $profitService
    ) {}

    public function index(Request $request): Response
    {
        $user = auth()->user();
        $businessId = $user->business_id;
        $branchId = $request->get('branch_id', $user->branch_id);

        // Date range from request or default to today
        $period = $request->get('period', 'today');
        [$from, $to] = $this->getDateRange($period, $request);

        // Build query scopes
        $salesBase = Sale::where('business_id', $businessId)
            ->where('sale_type', 'sale')
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->whereBetween('transaction_date', [$from->format('Y-m-d'), $to->format('Y-m-d')]);

        $purchasesBase = Purchase::where('business_id', $businessId)
            ->whereBetween('transaction_date', [$from->format('Y-m-d'), $to->format('Y-m-d')]);

        $expensesBase = Expense::where('business_id', $businessId)
            ->where('status', 'approved')
            ->whereBetween('expense_date', [$from->format('Y-m-d'), $to->format('Y-m-d')]);

        if ($branchId) {
            $salesBase->where('branch_id', $branchId);
            $purchasesBase->where('branch_id', $branchId);
            $expensesBase->where('branch_id', $branchId);
        }

        // Core KPIs
        $totalRevenue = $salesBase->clone()->sum('total_amount');
        $totalCogs = $salesBase->clone()->sum('cogs');
        $grossProfit = $salesBase->clone()->sum('gross_profit');
        $totalPurchases = $purchasesBase->clone()->sum('total_amount');
        $totalExpenses = $expensesBase->clone()->sum('amount');
        $netProfit = $grossProfit - $totalExpenses;
        $totalSalesCount = $salesBase->clone()->count();

        // Financial data - check permissions
        $canSeeFinancials = $user->canSeeRevenue();
        $canSeeProfit = $user->canSeeProfit();

        // Stock metrics
        $stockQuery = Stock::where('business_id', $businessId);
        if ($branchId) {
            $stockQuery->where('branch_id', $branchId);
        }

        $totalStockValue = $user->canSeeStockValue() ? $stockQuery->clone()->sum('stock_value') : null;

        // Low stock count
        $lowStockCount = Product::where('business_id', $businessId)
            ->where('is_active', true)
            ->where('track_stock', true)
            ->where('min_stock', '>', 0)
            ->whereHas('stock', function ($q) use ($branchId) {
                $q->whereColumn('quantity', '<', 'products.min_stock');
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
            })
            ->count();

        $outOfStockCount = Product::where('business_id', $businessId)
            ->where('is_active', true)
            ->where('track_stock', true)
            ->whereHas('stock', function ($q) use ($branchId) {
                $q->where('quantity', '<=', 0);
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
            })
            ->count();

        // Outstanding debts
        $customerDebt = $user->canSeeCustomerDebt()
            ? Customer::where('business_id', $businessId)->where('current_balance', '>', 0)->sum('current_balance')
            : null;

        $supplierDebt = $user->canSeeSupplierDebt()
            ? Supplier::where('business_id', $businessId)->where('current_balance', '>', 0)->sum('current_balance')
            : null;

        // Chart data - Sales trend (last 7 or 30 days depending on period)
        $chartDays = in_array($period, ['today', 'week']) ? 7 : 30;
        $salesTrend = $this->getSalesTrend($businessId, $branchId, $chartDays);
        $expensesTrend = $this->getExpensesTrend($businessId, $branchId, $chartDays);

        // Top products
        $topProducts = $this->getTopProducts($businessId, $branchId, $from, $to);

        // Recent sales
        $recentSales = Sale::with(['customer', 'user'])
            ->where('business_id', $businessId)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->where('sale_type', 'sale')
            ->latest('transaction_date')
            ->limit(5)
            ->get();

        // Production today
        $productionToday = ProductionOrder::where('business_id', $businessId)
            ->whereDate('created_at', today())
            ->count();

        // Pending approvals count
        $pendingApprovals = ApprovalRequest::where('business_id', $businessId)
            ->where('status', 'pending')
            ->count();

        return Inertia::render('Dashboard', [
            'period' => $period,
            'dateFrom' => $from->format('Y-m-d'),
            'dateTo' => $to->format('Y-m-d'),
            'branchId' => $branchId,
            'permissions' => [
                'canViewProfit' => $canSeeProfit,
                'canViewStockValue' => $user->canSeeStockValue(),
                'canViewReport' => $user->canSeeReport(),
            ],
            'kpis' => [
                'revenue' => $canSeeFinancials ? round($totalRevenue, 2) : null,
                'gross_profit' => $canSeeProfit ? round($grossProfit, 2) : null,
                'net_profit' => $canSeeProfit ? round($netProfit, 2) : null,
                'total_purchases' => $canSeeFinancials ? round($totalPurchases, 2) : null,
                'total_expenses' => $user->canSeeExpenses() ? round($totalExpenses, 2) : null,
                'cogs' => $user->canSeeCost() ? round($totalCogs, 2) : null,
                'total_sales_count' => $totalSalesCount,
                'stock_value' => $totalStockValue ? round($totalStockValue, 2) : null,
                'low_stock_count' => $lowStockCount,
                'out_of_stock_count' => $outOfStockCount,
                'customer_debt' => $customerDebt ? round($customerDebt, 2) : null,
                'supplier_debt' => $supplierDebt ? round($supplierDebt, 2) : null,
                'production_today' => $productionToday,
                'pending_approvals' => $pendingApprovals,
            ],
            'charts' => [
                'sales_trend' => $canSeeProfit
                    ? $salesTrend
                    : array_map(fn ($d) => ['date' => $d['date'], 'revenue' => $d['revenue'], 'profit' => null], $salesTrend),
                'expenses_trend' => $expensesTrend,
                'top_products' => $topProducts,
            ],
            'recent_sales' => $recentSales,
            'branches' => Branch::where('business_id', $businessId)
                ->where('is_active', true)
                ->select('id', 'name', 'code')
                ->get(),
        ]);
    }

    private function getDateRange(string $period, Request $request): array
    {
        return match ($period) {
            'today' => [today(), today()],
            'yesterday' => [Carbon::yesterday(), Carbon::yesterday()],
            'week' => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()],
            'month' => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
            'year' => [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()],
            'custom' => [
                Carbon::parse($request->get('from', today())),
                Carbon::parse($request->get('to', today())),
            ],
            default => [today(), today()],
        };
    }

    private function getSalesTrend(int $businessId, ?int $branchId, int $days): array
    {
        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $query = Sale::where('business_id', $businessId)
                ->where('sale_type', 'sale')
                ->whereDate('transaction_date', $date);
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }
            $data[] = [
                'date' => $date->format('M d'),
                'revenue' => (float) $query->sum('total_amount'),
                'profit' => (float) $query->sum('gross_profit'),
            ];
        }

        return $data;
    }

    private function getExpensesTrend(int $businessId, ?int $branchId, int $days): array
    {
        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $query = Expense::where('business_id', $businessId)
                ->where('status', 'approved')
                ->whereDate('expense_date', $date);
            if ($branchId) {
                $query->where('branch_id', $branchId);
            }
            $data[] = [
                'date' => $date->format('M d'),
                'amount' => (float) $query->sum('amount'),
            ];
        }

        return $data;
    }

    private function getTopProducts(int $businessId, ?int $branchId, $from, $to): array
    {
        $query = SaleItem::join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->where('sales.business_id', $businessId)
            ->where('sales.sale_type', 'sale')
            ->whereNotIn('sales.status', ['cancelled'])
            ->whereBetween('sales.transaction_date', [$from, $to])
            ->selectRaw('products.id, products.name, SUM(sale_items.quantity) as total_qty, SUM(sale_items.total_price) as total_revenue, SUM(sale_items.line_profit) as total_profit')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_revenue')
            ->limit(10);

        if ($branchId) {
            $query->where('sales.branch_id', $branchId);
        }

        return $query->get()->toArray();
    }
}
