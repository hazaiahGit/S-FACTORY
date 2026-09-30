<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Services\SaleService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SaleController extends Controller
{
    protected SaleService $saleService;

    public function __construct(SaleService $saleService)
    {
        $this->saleService = $saleService;
    }

    public function index(Request $request)
    {
        $businessId = request()->user()->business_id;

        $sales = Sale::with(['customer', 'items'])
            ->where('business_id', $businessId)
            ->when(request()->user()->active_branch_id, function ($q, $branchId) {
                $q->where('branch_id', $branchId);
            })
            ->when($request->date, function ($query, $date) {
                $query->whereDate('created_at', $date);
            })
            ->when($request->status, function ($query, $status) {
                if (in_array($status, ['paid', 'partial', 'unpaid', 'overpaid'])) {
                    $query->where('payment_status', $status);
                } else {
                    $query->where('status', $status);
                }
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Sales/Index', [
            'sales' => $sales,
            'filters' => $request->only(['date', 'status']),
        ]);
    }

    public function create()
    {
        $user = request()->user();
        $businessId = $user->business_id;
        $branchId = $user->active_branch_id ?: ($user->branch_id ?: Branch::where('business_id', $businessId)->value('id'));

        // Fetch products with their active stock for the current branch
        $products = Product::with(['stock' => function ($q) use ($branchId) {
            $q->where('branch_id', $branchId);
        }])
            ->where('business_id', $businessId)
            ->where('is_active', true)
            ->get(['id', 'name', 'selling_price', 'wholesale_price', 'sku', 'product_type', 'track_stock'])
            ->map(function ($product) {
                $stockItem = $product->stock->first();
                $product->current_stock = $stockItem ? (float) $stockItem->quantity : 0;

                return $product;
            });

        // Fetch customers according to user branch scoping
        $customers = Customer::where('business_id', $businessId)
            ->when($branchId, function ($q, $bId) {
                $q->where(function ($sub) use ($bId) {
                    $sub->where('branch_id', $bId)->orWhereNull('branch_id');
                });
            })
            ->get(['id', 'name', 'phone', 'customer_type', 'current_balance']);

        return Inertia::render('Sales/Create', [
            'products' => $products,
            'customers' => $customers,
            'defaultBranch' => $branchId,
        ]);
    }

    public function store(Request $request)
    {
        $user = request()->user();
        $businessId = $user->business_id;

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'customer_id' => 'nullable|exists:customers,id',
            'sale_type' => 'required|string|in:sale,quotation,proforma',
            'status' => 'required|string|in:draft,confirmed,invoiced,credit,on_hold,cancelled',
            'fulfillment_status' => 'nullable|string|in:pending,processing,ready,partial,fulfilled,delivered,returned,cancelled',
            'transaction_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.price_type' => 'nullable|string|in:retail,wholesale',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'items.*.tax_amount' => 'nullable|numeric|min:0',
            'payments' => 'nullable|array',
            'payments.*.payment_method' => 'required|string',
            'payments.*.amount' => 'required|numeric|min:0',
            'payments.*.reference' => 'nullable|string|max:100',
        ]);

        // Non-admins can only record sales for their own branch
        if (! $user->isTenantAdmin() && ! $user->hasRole(['Super Admin', 'Admin'])) {
            $validated['branch_id'] = $user->branch_id;
        }

        try {
            DB::beginTransaction();

            $sale = $this->saleService->create($validated);

            DB::commit();

            return redirect()->route('sales.index')->with('success', 'Sale created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Error creating sale: '.$e->getMessage());
        }
    }

    public function show(Sale $sale)
    {
        $user = request()->user();
        if ($sale->business_id !== $user->business_id) {
            abort(403);
        }

        if (! $user->isTenantAdmin() && ! $user->hasRole(['Super Admin', 'Admin']) && $sale->branch_id && $sale->branch_id !== $user->branch_id) {
            abort(403, 'Unauthorized to view sale from another branch.');
        }

        $sale->load(['customer', 'items.product', 'payments']);

        return Inertia::render('Sales/Show', [
            'sale' => $sale,
        ]);
    }

    public function edit(Sale $sale)
    {
        $user = request()->user();
        if ($sale->business_id !== $user->business_id) {
            abort(403);
        }

        if (! $user->isTenantAdmin() && ! $user->hasRole(['Super Admin', 'Admin']) && $sale->branch_id && $sale->branch_id !== $user->branch_id) {
            abort(403, 'Unauthorized to edit sale from another branch.');
        }

        if (! in_array($sale->status, ['draft', 'on_hold', 'invoiced'])) {
            abort(403, 'Only sales in draft, on hold, or invoiced status can be edited.');
        }

        $businessId = $user->business_id;
        $branchId = $user->active_branch_id ?? $user->branch_id;

        $sale->load(['items.product']);

        // Fetch products with their active stock for the current branch
        $products = Product::with(['stock' => function ($q) use ($branchId) {
            $q->where('branch_id', $branchId);
        }])
            ->where('business_id', $businessId)
            ->where('is_active', true)
            ->get(['id', 'name', 'selling_price', 'wholesale_price', 'sku', 'product_type', 'track_stock'])
            ->map(function ($product) {
                $stockItem = $product->stock->first();
                $product->current_stock = $stockItem ? (float) $stockItem->quantity : 0;

                return $product;
            });

        $customers = Customer::where('business_id', $businessId)->get(['id', 'name', 'phone']);

        return Inertia::render('Sales/Edit', [
            'sale' => $sale,
            'products' => $products,
            'customers' => $customers,
        ]);
    }

    public function update(Request $request, Sale $sale)
    {
        if ($sale->business_id !== request()->user()->business_id) {
            abort(403);
        }

        if (! in_array($sale->status, ['draft', 'on_hold', 'invoiced'])) {
            abort(403, 'Only sales in draft, on hold, or invoiced status can be edited.');
        }

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'customer_id' => 'nullable|exists:customers,id',
            'sale_type' => 'required|string|in:sale,quotation,proforma',
            'status' => 'required|string|in:draft,confirmed,invoiced,credit,on_hold,cancelled',
            'fulfillment_status' => 'nullable|string|in:pending,processing,ready,partial,fulfilled,delivered,returned,cancelled',
            'transaction_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.price_type' => 'nullable|string|in:retail,wholesale',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'payments' => 'nullable|array',
            'payments.*.payment_method' => 'required|string',
            'payments.*.amount' => 'required|numeric|min:0',
            'payments.*.reference' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();
            $this->saleService->updateSale($sale, $validated);
            DB::commit();

            return redirect()->route('sales.show', $sale)->with('success', 'Sale updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Error updating sale: '.$e->getMessage());
        }
    }

    public function destroy(Sale $sale)
    {
        if ($sale->business_id !== request()->user()->business_id) {
            abort(403);
        }

        try {
            DB::beginTransaction();

            $this->saleService->deleteSale($sale);

            DB::commit();

            return redirect()->route('sales.index')->with('success', 'Sale deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Error deleting sale: '.$e->getMessage());
        }
    }

    public function updateStatus(Request $request, Sale $sale)
    {
        if ($sale->business_id !== $request->user()->business_id) {
            abort(403);
        }

        $request->validate([
            'status' => 'nullable|in:draft,confirmed,invoiced,credit,on_hold,cancelled,paid',
            'fulfillment_status' => 'nullable|in:pending,processing,ready,partial,fulfilled,delivered,returned,cancelled',
        ]);

        if ($request->filled('status') && $request->status !== $sale->status) {
            $this->saleService->updateSaleStatus($sale, $request->status);
        }

        if ($request->filled('fulfillment_status') && $request->fulfillment_status !== $sale->fulfillment_status) {
            $sale->update(['fulfillment_status' => $request->fulfillment_status]);
        }

        return back()->with('success', 'Sale updated successfully.');
    }

    public function print(Sale $sale)
    {
        if ($sale->business_id !== request()->user()->business_id) {
            abort(403);
        }

        $sale->load(['customer', 'items.product', 'payments', 'business', 'user']);

        $pdf = Pdf::loadView('sales.receipt-pdf', [
            'sale' => $sale,
            'business' => $sale->business,
        ]);

        return $pdf->download('Receipt-'.$sale->sale_number.'.pdf');
    }
}
