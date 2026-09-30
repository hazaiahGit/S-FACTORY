<?php

namespace App\Http\Controllers\Manufacturing;

use App\Http\Controllers\Controller;
use App\Models\BillOfMaterial;
use App\Models\Branch;
use App\Models\ProductionMaterial;
use App\Models\ProductionOrder;
use App\Services\NumberGeneratorService;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ProductionOrderController extends Controller
{
    public function __construct(
        private NumberGeneratorService $numberGenerator,
        private StockService $stockService
    ) {}

    public function index(Request $request)
    {
        $businessId = $request->user()->business_id;

        $orders = ProductionOrder::with(['product:id,name', 'bom:id,name'])
            ->where('business_id', $businessId)
            ->when($request->status, function ($q, $status) {
                $q->where('status', $status);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Manufacturing/Production/Index', [
            'orders' => $orders,
            'filters' => $request->only('status'),
        ]);
    }

    public function create(Request $request)
    {
        $businessId = $request->user()->business_id;

        $boms = BillOfMaterial::with('product')
            ->where('business_id', $businessId)
            ->where('is_active', true)
            ->get();

        $branches = Branch::where('business_id', $businessId)->get();

        return Inertia::render('Manufacturing/Production/Create', [
            'boms' => $boms,
            'branches' => $branches,
            'defaultBranch' => $request->user()->branch_id,
        ]);
    }

    public function store(Request $request)
    {
        $businessId = $request->user()->business_id;

        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'bom_id' => 'required|exists:bill_of_materials,id',
            'planned_quantity' => 'required|numeric|min:0.001',
            'planned_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated, $businessId, $request) {
            $bom = BillOfMaterial::with('items')->findOrFail($validated['bom_id']);

            // Calculate scale factor
            $scale = $validated['planned_quantity'] / $bom->expected_output;

            $order = ProductionOrder::create([
                'business_id' => $businessId,
                'branch_id' => $validated['branch_id'],
                'bom_id' => $bom->id,
                'product_id' => $bom->product_id,
                'user_id' => $request->user()->id,
                'production_number' => $this->numberGenerator->generateProductionNumber($businessId),
                'status' => 'planned',
                'planned_quantity' => $validated['planned_quantity'],
                'planned_date' => $validated['planned_date'],
                'notes' => $validated['notes'] ?? null,
                'total_material_cost' => 0,
                'total_labour_cost' => 0,
                'total_overhead_cost' => 0,
                'total_production_cost' => 0,
            ]);

            $matCost = 0;
            $labCost = 0;
            $ovhCost = 0;

            foreach ($bom->items as $item) {
                $qty = $item->quantity * $scale;
                $totalCost = $qty * $item->unit_cost;

                ProductionMaterial::create([
                    'production_order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'item_type' => $item->item_type,
                    'description' => $item->description,
                    'planned_quantity' => $qty,
                    'unit_cost' => $item->unit_cost,
                    'total_cost' => $totalCost,
                ]);

                if ($item->item_type === 'material') {
                    $matCost += $totalCost;
                }
                if ($item->item_type === 'labour') {
                    $labCost += $totalCost;
                }
                if ($item->item_type === 'overhead') {
                    $ovhCost += $totalCost;
                }
            }

            $order->update([
                'total_material_cost' => $matCost,
                'total_labour_cost' => $labCost,
                'total_overhead_cost' => $ovhCost,
                'total_production_cost' => $matCost + $labCost + $ovhCost,
            ]);

            return redirect()->route('production.show', $order)
                ->with('success', 'Production Order created successfully.');
        });
    }

    public function show(Request $request, ProductionOrder $productionOrder)
    {
        abort_if($productionOrder->business_id !== $request->user()->business_id, 403);

        $productionOrder->load(['product', 'bom', 'materials.product']);

        return Inertia::render('Manufacturing/Production/Show', [
            'order' => $productionOrder,
        ]);
    }

    public function start(Request $request, ProductionOrder $productionOrder)
    {
        abort_if($productionOrder->business_id !== $request->user()->business_id, 403);

        if ($productionOrder->status !== 'planned') {
            return back()->with('error', 'Only planned orders can be started.');
        }

        $productionOrder->update([
            'status' => 'in_progress',
            'start_date' => today(),
        ]);

        return back()->with('success', 'Production started.');
    }

    public function complete(Request $request, ProductionOrder $productionOrder)
    {
        abort_if($productionOrder->business_id !== $request->user()->business_id, 403);

        if ($productionOrder->status !== 'in_progress') {
            return back()->with('error', 'Only in-progress orders can be completed.');
        }

        $validated = $request->validate([
            'actual_quantity' => 'required|numeric|min:0.001',
            'waste_quantity' => 'nullable|numeric|min:0',
            'materials' => 'required|array',
            'materials.*.id' => 'required|exists:production_materials,id',
            'materials.*.actual_quantity' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated, $productionOrder) {
            $matCost = 0;
            $labCost = 0;
            $ovhCost = 0;

            foreach ($validated['materials'] as $matData) {
                $material = ProductionMaterial::find($matData['id']);
                if ($material->production_order_id !== $productionOrder->id) {
                    continue;
                }

                $actualQty = (float) $matData['actual_quantity'];
                $actualCost = $actualQty * $material->unit_cost;

                $material->update([
                    'actual_quantity' => $actualQty,
                    'total_cost' => $actualCost,
                ]);

                if ($material->item_type === 'material') {
                    $matCost += $actualCost;

                    // Deduct stock for raw materials
                    if ($material->product_id) {
                        $this->stockService->decrease(
                            $productionOrder->branch_id,
                            $material->product_id,
                            $actualQty,
                            'production_consumption',
                            ProductionOrder::class,
                            $productionOrder->id,
                            $productionOrder->production_number
                        );
                    }
                }
                if ($material->item_type === 'labour') {
                    $labCost += $actualCost;
                }
                if ($material->item_type === 'overhead') {
                    $ovhCost += $actualCost;
                }
            }

            $totalCost = $matCost + $labCost + $ovhCost;

            $productionOrder->update([
                'status' => 'completed',
                'completion_date' => today(),
                'actual_quantity' => $validated['actual_quantity'],
                'waste_quantity' => $validated['waste_quantity'] ?? 0,
                'total_material_cost' => $matCost,
                'total_labour_cost' => $labCost,
                'total_overhead_cost' => $ovhCost,
                'total_production_cost' => $totalCost,
                'unit_cost' => $totalCost / $validated['actual_quantity'],
            ]);

            // Receive finished goods into stock
            $this->stockService->increase(
                $productionOrder->branch_id,
                $productionOrder->product_id,
                $validated['actual_quantity'],
                $productionOrder->unit_cost,
                'production_output',
                ProductionOrder::class,
                $productionOrder->id,
                $productionOrder->production_number
            );

            return back()->with('success', 'Production completed and stock updated.');
        });
    }
}
