<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Sale;
use App\Models\Purchase;
use App\Models\Expense;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\ProductionOrder;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    public function index(Request $request): Response
    {
        $businessId = $request->user()->business_id;
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $monthlySales = Sale::where('business_id', $businessId)
            ->where('sale_type', 'sale')
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('total_amount');

        $monthlyPurchases = Purchase::where('business_id', $businessId)
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->sum('total_amount');

        $monthlyExpenses = Expense::where('business_id', $businessId)
            ->where('status', 'approved')
            ->whereBetween('expense_date', [$startOfMonth, $endOfMonth])
            ->sum('amount');

        return Inertia::render('Reports/Index', [
            'metrics' => [
                'monthly_sales' => $monthlySales,
                'monthly_purchases' => $monthlyPurchases,
                'monthly_expenses' => $monthlyExpenses,
                'month_name' => now()->format('F Y')
            ]
        ]);
    }

    protected function getSalesData(Request $request) {
        $businessId = $request->user()->business_id;
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

        $salesQuery = Sale::with(['customer', 'user', 'branch'])
            ->where('business_id', $businessId)
            ->where('sale_type', 'sale')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->latest('transaction_date');

        if ($request->filled('status')) {
            $salesQuery->where('status', $request->status);
        }

        $sales = $salesQuery->get();
        $summary = [
            'total_revenue' => $sales->whereNotIn('status', ['cancelled'])->sum('total_amount'),
            'total_collected' => $sales->whereNotIn('status', ['cancelled'])->sum('paid_amount'),
            'total_outstanding' => $sales->whereNotIn('status', ['cancelled'])->sum('balance_amount'),
            'total_transactions' => $sales->whereNotIn('status', ['cancelled'])->count(),
        ];
        return compact('sales', 'summary', 'startDate', 'endDate');
    }

    public function sales(Request $request): Response
    {
        $data = $this->getSalesData($request);
        return Inertia::render('Reports/Sales', [
            'sales' => $data['sales'],
            'summary' => $data['summary'],
            'filters' => ['start_date' => $data['startDate'], 'end_date' => $data['endDate'], 'status' => $request->input('status', '')]
        ]);
    }

    protected function getPurchasesData(Request $request) {
        $businessId = $request->user()->business_id;
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

        $query = Purchase::with(['supplier', 'user', 'branch'])
            ->where('business_id', $businessId)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->latest('transaction_date');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $purchases = $query->get();
        $summary = [
            'total_purchases' => $purchases->whereNotIn('status', ['cancelled'])->sum('total_amount'),
            'total_paid' => $purchases->whereNotIn('status', ['cancelled'])->sum('paid_amount'),
            'total_outstanding' => $purchases->whereNotIn('status', ['cancelled'])->sum('balance_amount'),
            'total_transactions' => $purchases->whereNotIn('status', ['cancelled'])->count(),
        ];
        return compact('purchases', 'summary', 'startDate', 'endDate');
    }

    public function purchases(Request $request): Response
    {
        $data = $this->getPurchasesData($request);
        return Inertia::render('Reports/Purchases', [
            'purchases' => $data['purchases'],
            'summary' => $data['summary'],
            'filters' => ['start_date' => $data['startDate'], 'end_date' => $data['endDate'], 'status' => $request->input('status', '')]
        ]);
    }

    protected function getInventoryData(Request $request) {
        $businessId = $request->user()->business_id;
        $products = Product::with(['category', 'brand'])
            ->where('business_id', $businessId)
            ->where('track_stock', true)
            ->withSum('stock', 'quantity')
            ->get()
            ->map(function ($product) {
                $qty = $product->stock_sum_quantity ?? 0;
                $value = $qty * ($product->cost_price ?? 0);
                $product->stock_value = $value;
                return $product;
            });

        $summary = [
            'total_items' => $products->count(),
            'total_stock_value' => $products->sum('stock_value'),
            'low_stock_items' => $products->filter(function($p) { return ($p->stock_sum_quantity ?? 0) <= $p->min_stock; })->count(),
        ];
        return compact('products', 'summary');
    }

    public function inventory(Request $request): Response
    {
        $data = $this->getInventoryData($request);
        return Inertia::render('Reports/Inventory', $data);
    }

    protected function getProfitLossData(Request $request) {
        $businessId = $request->user()->business_id;
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

        $sales = Sale::where('business_id', $businessId)
            ->where('sale_type', 'sale')
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->get();
            
        $totalRevenue = $sales->sum('total_amount');
        $totalCogs = $sales->sum('cogs');
        $grossProfit = $totalRevenue - $totalCogs;

        $expenses = Expense::where('business_id', $businessId)
            ->where('status', 'approved')
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->get();
            
        $totalExpenses = $expenses->sum('amount');
        $netProfit = $grossProfit - $totalExpenses;

        $summary = [
            'total_revenue' => $totalRevenue,
            'cogs' => $totalCogs,
            'gross_profit' => $grossProfit,
            'total_expenses' => $totalExpenses,
            'net_profit' => $netProfit,
        ];
        return compact('summary', 'startDate', 'endDate');
    }

    public function profitLoss(Request $request): Response
    {
        $data = $this->getProfitLossData($request);
        return Inertia::render('Reports/ProfitLoss', [
            'summary' => $data['summary'],
            'filters' => ['start_date' => $data['startDate'], 'end_date' => $data['endDate']]
        ]);
    }

    protected function getExpensesData(Request $request) {
        $businessId = $request->user()->business_id;
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

        $query = Expense::with(['category', 'branch', 'user'])
            ->where('business_id', $businessId)
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->latest('expense_date');

        if ($request->filled('category_id')) {
            $query->where('expense_category_id', $request->category_id);
        }

        $expenses = $query->get();
        $summary = [
            'total_expenses' => $expenses->where('status', 'approved')->sum('amount'),
            'total_transactions' => $expenses->where('status', 'approved')->count(),
        ];
        $categories = \App\Models\ExpenseCategory::where('business_id', $businessId)->get();
        return compact('expenses', 'summary', 'categories', 'startDate', 'endDate');
    }

    public function expenses(Request $request): Response
    {
        $data = $this->getExpensesData($request);
        return Inertia::render('Reports/Expenses', [
            'expenses' => $data['expenses'],
            'categories' => $data['categories'],
            'summary' => $data['summary'],
            'filters' => ['start_date' => $data['startDate'], 'end_date' => $data['endDate'], 'category_id' => $request->input('category_id', '')]
        ]);
    }

    protected function getCustomersData(Request $request) {
        $businessId = $request->user()->business_id;
        $customers = Customer::where('business_id', $businessId)
            ->withSum('sales', 'total_amount')
            ->withSum('sales', 'paid_amount')
            ->get()
            ->map(function ($c) {
                $c->total_revenue = $c->sales_sum_total_amount ?? 0;
                $c->total_paid = $c->sales_sum_paid_amount ?? 0;
                $c->debt = max(0, $c->total_revenue - $c->total_paid);
                return $c;
            })
            ->sortByDesc('total_revenue')
            ->values();

        $summary = [
            'total_customers' => $customers->count(),
            'total_revenue' => $customers->sum('total_revenue'),
            'total_debt' => $customers->sum('debt'),
        ];
        return compact('customers', 'summary');
    }

    public function customers(Request $request): Response
    {
        $data = $this->getCustomersData($request);
        return Inertia::render('Reports/Customers', $data);
    }

    public function suppliers(Request $request): Response
    {
        return Inertia::render('Reports/ComingSoon', ['title' => 'Suppliers Report']);
    }

    protected function getManufacturingData(Request $request) {
        $businessId = $request->user()->business_id;
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

        $query = ProductionOrder::with(['product', 'branch', 'user'])
            ->where('business_id', $businessId)
            ->whereBetween('start_date', [$startDate, $endDate])
            ->latest('start_date');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->get();
        $summary = [
            'total_orders' => $orders->count(),
            'completed_orders' => $orders->where('status', 'completed')->count(),
            'total_produced' => $orders->where('status', 'completed')->sum('actual_quantity'),
        ];
        return compact('orders', 'summary', 'startDate', 'endDate');
    }

    public function manufacturing(Request $request): Response
    {
        $data = $this->getManufacturingData($request);
        return Inertia::render('Reports/Manufacturing', [
            'orders' => $data['orders'],
            'summary' => $data['summary'],
            'filters' => ['start_date' => $data['startDate'], 'end_date' => $data['endDate'], 'status' => $request->input('status', '')]
        ]);
    }

    public function branches(Request $request): Response
    {
        return Inertia::render('Reports/ComingSoon', ['title' => 'Branch Performance']);
    }

    public function export(Request $request, $type)
    {
        abort(501, 'Export not implemented yet.');
    }

    public function pdf(Request $request, $type)
    {
        $business = $request->user()->business;
        
        switch ($type) {
            case 'sales':
                $data = $this->getSalesData($request);
                $data['business'] = $business;
                $data['filters'] = ['start_date' => $data['startDate'], 'end_date' => $data['endDate'], 'status' => $request->input('status', '')];
                return Pdf::loadView('reports.sales-pdf', $data)->download('Sales-Report-' . now()->format('Ymd') . '.pdf');
                
            case 'purchases':
                $data = $this->getPurchasesData($request);
                $data['business'] = $business;
                $data['filters'] = ['start_date' => $data['startDate'], 'end_date' => $data['endDate'], 'status' => $request->input('status', '')];
                return Pdf::loadView('reports.purchases-pdf', $data)->download('Purchases-Report-' . now()->format('Ymd') . '.pdf');

            case 'inventory':
                $data = $this->getInventoryData($request);
                $data['business'] = $business;
                $data['filters'] = ['start_date' => now()->format('Y-m-d'), 'end_date' => now()->format('Y-m-d')]; // current day
                return Pdf::loadView('reports.inventory-pdf', $data)->download('Inventory-Report-' . now()->format('Ymd') . '.pdf');

            case 'profit_loss':
                $data = $this->getProfitLossData($request);
                $data['business'] = $business;
                $data['filters'] = ['start_date' => $data['startDate'], 'end_date' => $data['endDate']];
                return Pdf::loadView('reports.profit_loss-pdf', $data)->download('ProfitLoss-Report-' . now()->format('Ymd') . '.pdf');

            case 'expenses':
                $data = $this->getExpensesData($request);
                $data['business'] = $business;
                $data['filters'] = ['start_date' => $data['startDate'], 'end_date' => $data['endDate'], 'category_id' => $request->input('category_id', '')];
                return Pdf::loadView('reports.expenses-pdf', $data)->download('Expenses-Report-' . now()->format('Ymd') . '.pdf');

            case 'customers':
                $data = $this->getCustomersData($request);
                $data['business'] = $business;
                $data['filters'] = ['start_date' => '', 'end_date' => ''];
                return Pdf::loadView('reports.customers-pdf', $data)->download('Customers-Report-' . now()->format('Ymd') . '.pdf');

            case 'manufacturing':
                $data = $this->getManufacturingData($request);
                $data['business'] = $business;
                $data['filters'] = ['start_date' => $data['startDate'], 'end_date' => $data['endDate'], 'status' => $request->input('status', '')];
                return Pdf::loadView('reports.manufacturing-pdf', $data)->download('Manufacturing-Report-' . now()->format('Ymd') . '.pdf');
                
            default:
                abort(501, 'PDF Generation for ' . $type . ' not implemented yet.');
        }
    }
}
