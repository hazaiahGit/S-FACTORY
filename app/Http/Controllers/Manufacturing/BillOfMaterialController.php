<?php

namespace App\Http\Controllers\Manufacturing;

use App\Http\Controllers\Controller;
use App\Models\BillOfMaterial;
use App\Models\BomItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Unit;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class BillOfMaterialController extends Controller
{
    public function index(Request $request)
    {
        $businessId = $request->user()->business_id;

        $boms = BillOfMaterial::with(['product:id,name,sku', 'outputUnit:id,name,abbreviation'])->withCount('items')
            ->where('business_id', $businessId)
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Manufacturing/BOM/Index', [
            'boms' => $boms,
            'filters' => $request->only('search'),
        ]);
    }

    public function create(Request $request)
    {
        $businessId = $request->user()->business_id;

        $products = Product::where('business_id', $businessId)
            ->where('is_active', true)
            ->whereIn('product_type', ['manufactured', 'both'])
            ->select('id', 'name', 'sku')
            ->get();

        $materials = Product::where('business_id', $businessId)
            ->where('is_active', true)
            ->whereIn('product_type', ['purchased', 'both'])
            ->select('id', 'name', 'sku', 'cost_price', 'unit_id')
            ->with('unit:id,name,abbreviation')
            ->get();

        $units = Unit::where('business_id', $businessId)->get();
        $categories = Category::where('business_id', $businessId)->get(['id', 'name']);

        return Inertia::render('Manufacturing/BOM/Create', [
            'products' => $products,
            'materials' => $materials,
            'units' => $units,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $businessId = $request->user()->business_id;
        $branchId = $request->user()->branch_id;

        $validated = $request->validate([
            'product_name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'selling_price' => 'required|numeric|min:0',
            'name' => 'nullable|string|max:200',
            'expected_output' => 'required|numeric|min:0.001',
            'output_unit_id' => 'required|exists:units,id',
            'description' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.name' => 'nullable|string|max:200',
            'items.*.item_type' => 'required|string|in:material,labour,overhead',
            'items.*.description' => 'nullable|string|max:200',
            'items.*.quantity' => 'nullable|numeric|min:0',
            'items.*.unit_id' => 'nullable|exists:units,id',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated, $businessId, $branchId) {

            // Calculate Cost Price
            $totalCost = 0;
            foreach ($validated['items'] as $item) {
                $qty = $item['quantity'] ?? 0;
                $cost = $item['unit_cost'] ?? 0;
                $totalCost += ($qty * $cost);
            }
            $costPerUnit = round($totalCost / $validated['expected_output'], 4);

            // Generate SKU for new product
            $sku = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $validated['product_name']), 0, 3)).'-'.mt_rand(1000, 9999);

            // Create Product
            $product = Product::create([
                'business_id' => $businessId,
                'name' => $validated['product_name'],
                'slug' => Str::slug($validated['product_name']).'-'.time(),
                'sku' => $sku,
                'category_id' => $validated['category_id'],
                'unit_id' => $validated['output_unit_id'],
                'product_type' => 'manufactured',
                'purchase_price' => $costPerUnit,
                'cost_price' => $costPerUnit,
                'selling_price' => $validated['selling_price'],
                'is_active' => true,
                'track_stock' => true,
                'tax_applicable' => false,
            ]);

            // Add Opening Stock
            Stock::create([
                'business_id' => $businessId,
                'branch_id' => $branchId,
                'product_id' => $product->id,
                'quantity' => $validated['expected_output'],
                'unit_cost' => $costPerUnit,
                'total_value' => $totalCost,
            ]);

            // Create BOM
            $bom = BillOfMaterial::create([
                'business_id' => $businessId,
                'product_id' => $product->id,
                'name' => $validated['product_name'].' Recipe',
                'version' => '1.0',
                'expected_output' => $validated['expected_output'],
                'output_unit_id' => $validated['output_unit_id'],
                'description' => $validated['description'] ?? null,
                'is_active' => true,
                'is_default' => true,
            ]);

            // Create BOM Items
            foreach ($validated['items'] as $item) {
                $qty = $item['quantity'] ?? 0;
                $cost = $item['unit_cost'] ?? 0;
                $total = $qty * $cost;

                $fullDescription = trim(($item['name'] ?? '').' - '.($item['description'] ?? ''), ' -');

                BomItem::create([
                    'bom_id' => $bom->id,
                    'product_id' => $item['product_id'] ?? null,
                    'item_type' => $item['item_type'],
                    'description' => $fullDescription ?: null,
                    'quantity' => $qty,
                    'unit_id' => $item['unit_id'] ?? null,
                    'unit_cost' => $cost,
                    'total_cost' => $total,
                    'is_optional' => false,
                ]);

                // Deduct materials from stock if they are actual products
                if (! empty($item['product_id']) && $item['item_type'] === 'material') {
                    app(StockService::class)->decrease(
                        $branchId,
                        $item['product_id'],
                        $qty,
                        'production',
                        BillOfMaterial::class,
                        $bom->id,
                        null,
                        'Used in BOM Quick Manufacture',
                        now(),
                        true // allow negative stock so we don't crash if they run out
                    );
                }
            }

            return redirect()->route('products.index')->with('success', 'Product registered, stock created, and BOM saved successfully.');
        });
    }

    // Stub out other methods just in case they are used
    public function show(BillOfMaterial $bom)
    {
        $bom->load(['product', 'outputUnit', 'items.product', 'items.unit']);

        return Inertia::render('Manufacturing/BOM/Show', ['bom' => $bom]);
    }

    public function edit(BillOfMaterial $bom)
    {
        $businessId = request()->user()->business_id;
        if ($bom->business_id !== $businessId) {
            abort(403);
        }

        $bom->load('items');

        $products = Product::where('business_id', $businessId)->active()->get(['id', 'name', 'sku', 'cost_price', 'unit_id']);
        $units = Unit::where('business_id', $businessId)->active()->get(['id', 'name', 'abbreviation']);

        return Inertia::render('Manufacturing/BOM/Edit', [
            'bom' => $bom,
            'products' => $products, // All products for output
            'materials' => $products, // Same for now
            'units' => $units,
        ]);
    }

    public function update(Request $request, BillOfMaterial $bom)
    {
        $businessId = request()->user()->business_id;
        if ($bom->business_id !== $businessId) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'product_id' => 'required|exists:products,id',
            'expected_output' => 'required|numeric|min:0.01',
            'output_unit_id' => 'nullable|exists:units,id',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.description' => 'nullable|string|max:200',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_id' => 'nullable|exists:units,id',
            'items.*.unit_cost' => 'nullable|numeric|min:0',
            'items.*.total_cost' => 'nullable|numeric|min:0',
            'items.*.is_optional' => 'boolean',
        ]);

        DB::transaction(function () use ($validated, $bom) {
            $bom->update([
                'name' => $validated['name'],
                'product_id' => $validated['product_id'],
                'expected_output' => $validated['expected_output'],
                'output_unit_id' => $validated['output_unit_id'] ?? null,
                'description' => $validated['description'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Delete old items
            $bom->items()->delete();

            $totalCost = 0;
            // Re-insert new items
            foreach ($validated['items'] as $item) {
                $qty = $item['quantity'];
                $cost = $item['unit_cost'] ?? 0;
                $lineTotal = $item['product_id'] ? ($qty * $cost) : $cost;
                $totalCost += $lineTotal;

                $bom->items()->create([
                    'product_id' => $item['product_id'] ?? null,
                    'item_type' => 'material',
                    'description' => $item['description'] ?? '',
                    'quantity' => $qty,
                    'unit_id' => $item['unit_id'] ?? null,
                    'unit_cost' => $cost,
                    'total_cost' => $lineTotal,
                    'is_optional' => $item['is_optional'] ?? false,
                ]);
            }

            $costPerUnit = $totalCost / max($bom->expected_output, 0.01);

            // Update the product's base cost_price
            Product::where('id', $validated['product_id'])
                ->update(['cost_price' => $costPerUnit]);

            // Force update all existing stock records to reflect the new recipe cost
            // since the user wants the inventory value strictly tied to the BOM cost.
            $stocks = Stock::where('product_id', $validated['product_id'])->get();
            foreach ($stocks as $stk) {
                $stk->update([
                    'avg_cost' => $costPerUnit,
                    'stock_value' => round((float) $stk->quantity * $costPerUnit, 2),
                ]);
            }
        });

        return redirect()->route('bom.index')->with('success', 'Recipe updated successfully.');
    }

    public function destroy(BillOfMaterial $bom)
    {
        $businessId = request()->user()->business_id;
        if ($bom->business_id !== $businessId) {
            abort(403);
        }

        // check if used in production orders
        if ($bom->productionOrders()->count() > 0) {
            return back()->with('error', 'Cannot delete this recipe because it is used in production orders.');
        }

        $bom->items()->delete();
        $bom->delete();

        return back()->with('success', 'Recipe (BOM) deleted successfully.');
    }
}
