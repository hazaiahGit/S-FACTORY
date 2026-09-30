<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\PurchaseService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PurchaseController extends Controller
{
    protected PurchaseService $purchaseService;

    public function __construct(PurchaseService $purchaseService)
    {
        $this->purchaseService = $purchaseService;
    }

    public function index(Request $request)
    {
        $businessId = request()->user()->business_id;

        $purchases = Purchase::with(['supplier', 'items'])
            ->where('business_id', $businessId)
            ->where('branch_id', request()->user()->active_branch_id)
            ->when($request->date, function ($query, $date) {
                $query->whereDate('created_at', $date);
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Purchases/Index', [
            'purchases' => $purchases,
            'filters' => $request->only(['date', 'status'])
        ]);
    }

    public function create()
    {
        $businessId = request()->user()->business_id;

        $products = Product::where('business_id', $businessId)
            ->get(['id', 'name', 'cost_price', 'sku']);

        $suppliers = Supplier::where('business_id', $businessId)
            ->get(['id', 'name']);

        return Inertia::render('Purchases/Create', [
            'products' => $products,
            'suppliers' => $suppliers,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'reference' => ['nullable', 'string', 'max:255'],
            'transaction_date' => ['nullable', 'date'],
            'transport_cost' => ['nullable', 'numeric', 'min:0'],
            'other_costs' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'payments' => ['nullable', 'array'],
            'payments.*.payment_method' => ['required', 'string'],
            'payments.*.amount' => ['required', 'numeric', 'min:0.01'],
            'payments.*.reference' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $purchase = $this->purchaseService->create($validated);
            
            // Redirect to index instead of show, since we might not have a show page yet
            return redirect()->route('purchases.index')->with('success', 'Purchase created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error creating purchase: ' . $e->getMessage());
        }
    }

    public function show(Purchase $purchase)
    {
        if ($purchase->business_id !== request()->user()->business_id) {
            abort(403);
        }

        $purchase->load(['supplier', 'items.product', 'payments']);

        return Inertia::render('Purchases/Show', [
            'purchase' => $purchase
        ]);
    }

    public function edit(Purchase $purchase)
    {
        if ($purchase->business_id !== request()->user()->business_id) {
            abort(403);
        }

        $businessId = request()->user()->business_id;

        $purchase->load(['items.product']);

        $products = Product::where('business_id', $businessId)->get(['id', 'name', 'cost_price', 'sku']);
        $suppliers = Supplier::where('business_id', $businessId)->get(['id', 'name', 'contact_person']);

        return Inertia::render('Purchases/Edit', [
            'purchase' => $purchase,
            'products' => $products,
            'suppliers' => $suppliers,
        ]);
    }

    public function update(Request $request, Purchase $purchase)
    {
        if ($purchase->business_id !== request()->user()->business_id) {
            abort(403);
        }

        $validated = $request->validate([
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'reference' => ['nullable', 'string', 'max:255'],
            'transaction_date' => ['nullable', 'date'],
            'transport_cost' => ['nullable', 'numeric', 'min:0'],
            'other_costs' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            DB::beginTransaction();
            
            $stockService = app(\App\Services\StockService::class);
            $numberGenerator = app(\App\Services\NumberGeneratorService::class);

            // Revert old items and stock
            foreach ($purchase->items as $oldItem) {
                // Decrease stock
                $stockService->decrease(
                    $purchase->branch_id,
                    $oldItem->product_id,
                    $oldItem->quantity,
                    'adjustment',
                    Purchase::class,
                    $purchase->id,
                    $purchase->purchase_number,
                    'Reverting stock for edit'
                );
                
                // Delete batch
                if ($oldItem->batch_id) {
                    \App\Models\ProductBatch::where('id', $oldItem->batch_id)->delete();
                }
                
                // Delete item
                $oldItem->delete();
            }

            // Recalculate totals
            $subtotal = 0;
            $totalLandedCost = (float)($validated['transport_cost'] ?? 0) + (float)($validated['other_costs'] ?? 0);
            $totalItemCost = 0;

            foreach ($validated['items'] as &$itemData) {
                $qty = (float) $itemData['quantity'];
                $cost = (float) $itemData['unit_cost'];
                $itemData['_line_total'] = $qty * $cost;
                $totalItemCost += $itemData['_line_total'];
            }

            // Create new items and batches
            foreach ($validated['items'] as $itemData) {
                $qty = (float) $itemData['quantity'];
                $unitCost = (float) $itemData['unit_cost'];
                $lineTotal = (float) $itemData['_line_total'];

                // Distribute landed cost
                $landedCostShare = $totalItemCost > 0 ? ($lineTotal / $totalItemCost) * $totalLandedCost : 0;
                $landedUnitCost = $qty > 0 ? ($lineTotal + $landedCostShare) / $qty : $unitCost;

                $batchNumber = $numberGenerator->generateBatchNumber($purchase->business_id);

                $batch = \App\Models\ProductBatch::create([
                    'business_id' => $purchase->business_id,
                    'branch_id' => $purchase->branch_id,
                    'product_id' => $itemData['product_id'],
                    'supplier_id' => $validated['supplier_id'] ?? null,
                    'batch_number' => $batchNumber,
                    'purchase_date' => $validated['transaction_date'] ?? today(),
                    'quantity_received' => $qty,
                    'quantity_remaining' => $qty,
                    'unit_cost' => $unitCost,
                    'total_cost' => $lineTotal,
                    'reference' => $purchase->purchase_number,
                ]);

                \App\Models\PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $itemData['product_id'],
                    'batch_id' => $batch->id,
                    'quantity' => $qty,
                    'received_quantity' => $qty,
                    'unit_cost' => $unitCost,
                    'landed_unit_cost' => round($landedUnitCost, 2),
                    'total_cost' => $lineTotal,
                    'batch_number' => $batchNumber,
                ]);

                $subtotal += $lineTotal;

                // Increase stock
                $stockService->increase(
                    $purchase->branch_id,
                    $itemData['product_id'],
                    $qty,
                    $landedUnitCost,
                    'purchase',
                    Purchase::class,
                    $purchase->id,
                    $purchase->purchase_number,
                    'Stock received from edited purchase'
                );
            }

            $totalAmount = $subtotal + $totalLandedCost;
            
            $purchase->update([
                'supplier_id' => $validated['supplier_id'] ?? null,
                'reference' => $validated['reference'] ?? null,
                'transaction_date' => $validated['transaction_date'] ?? today(),
                'transport_cost' => $validated['transport_cost'] ?? 0,
                'other_costs' => $validated['other_costs'] ?? 0,
                'subtotal' => $subtotal,
                'total_amount' => $totalAmount,
                'balance_amount' => max(0, $totalAmount - $purchase->paid_amount),
                'notes' => $validated['notes'] ?? null,
            ]);

            DB::commit();
            return redirect()->route('purchases.show', $purchase)->with('success', 'Purchase order updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error updating purchase: ' . $e->getMessage());
        }
    }

    public function destroy(Purchase $purchase)
    {
        if ($purchase->business_id !== request()->user()->business_id) {
            abort(403);
        }

        try {
            DB::beginTransaction();
            
            $this->purchaseService->deletePurchase($purchase);

            DB::commit();
            return redirect()->route('purchases.index')->with('success', 'Purchase deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error deleting purchase: ' . $e->getMessage());
        }
    }
}
