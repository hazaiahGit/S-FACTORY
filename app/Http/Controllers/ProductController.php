<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductType;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $businessId = $request->user()->business_id;

        $search = $request->input('search');
        $categoryId = $request->input('category_id');
        $brandId = $request->input('brand_id');
        $branchId = $request->input('branch_id');

        $user = $request->user();
        $isAdmin = $user->isTenantAdmin() || $user->hasRole(['Super Admin', 'Admin']);
        $activeBranchId = $isAdmin
            ? ($branchId ?? $user->active_branch_id)
            : $user->branch_id;

        $products = Product::with([
            'category',
            'brand',
            'unit',
            'stock' => function ($query) use ($activeBranchId) {
                if ($activeBranchId) {
                    $query->where('branch_id', $activeBranchId);
                }
            },
        ])
            ->withSum(['stock as stock_sum_quantity' => function ($query) use ($activeBranchId) {
                if ($activeBranchId) {
                    $query->where('branch_id', $activeBranchId);
                }
            }], 'quantity')
            ->where('business_id', $businessId)
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->when($categoryId, function ($query, $categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->when($brandId, function ($query, $brandId) {
                $query->where('brand_id', $brandId);
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $categories = Category::where('business_id', $businessId)->get(['id', 'name']);
        $brands = Brand::where('business_id', $businessId)->get(['id', 'name']);
        $branches = $isAdmin ? Branch::where('business_id', $businessId)->get(['id', 'name']) : [];

        return Inertia::render('Products/Index', [
            'products' => $products,
            'categories' => $categories,
            'brands' => $brands,
            'branches' => $branches,
            'filters' => $request->only(['search', 'category_id', 'brand_id', 'branch_id']),
            'activeBranchId' => $activeBranchId,
        ]);
    }

    public function create(Request $request)
    {
        $businessId = $request->user()->business_id;

        return Inertia::render('Products/Create', [
            'categories' => Category::where('business_id', $businessId)->where('is_active', true)->get(['id', 'name']),
            'units' => Unit::where('business_id', $businessId)->where('is_active', true)->get(['id', 'name', 'abbreviation']),
            'productTypes' => ProductType::where('business_id', $businessId)->where('is_active', true)->get(['id', 'name', 'code', 'is_manufactured', 'track_stock', 'is_sold', 'is_purchased']),
        ]);
    }

    public function show(Request $request, Product $product)
    {
        if ($product->business_id !== $request->user()->business_id) {
            abort(403);
        }

        // Redirect to edit page — dedicated Show page can be built later
        return redirect()->route('products.edit', $product);
    }

    public function store(Request $request)
    {
        $businessId = $request->user()->business_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:100', 'unique:products,sku'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'category_id' => ['required', Rule::exists('categories', 'id')->where('business_id', $businessId)],
            'brand_name' => ['nullable', 'string', 'max:100'],
            'unit_id' => ['required', Rule::exists('units', 'id')->where('business_id', $businessId)],
            'product_type_id' => ['nullable', Rule::exists('product_types', 'id')->where('business_id', $businessId)],
            'product_type' => ['nullable', 'string', 'max:50'],
            'total_cost' => ['required', 'numeric', 'min:0'],
            'opening_stock' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'wholesale_price' => ['nullable', 'numeric', 'min:0'],
            'min_selling_price' => ['nullable', 'numeric', 'min:0'],
            'min_stock' => ['nullable', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'track_stock' => ['boolean'],
            'tax_applicable' => ['boolean'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'notes' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        $validated['business_id'] = $businessId;

        // Resolve product_type and product_type_id
        if (! empty($validated['product_type_id'])) {
            $matchedType = ProductType::where('business_id', $businessId)->find($validated['product_type_id']);
            if ($matchedType) {
                $validated['product_type'] = $matchedType->code;
            }
        } elseif (! empty($validated['product_type'])) {
            $matchedType = ProductType::where('business_id', $businessId)->where('code', $validated['product_type'])->first();
            if ($matchedType) {
                $validated['product_type_id'] = $matchedType->id;
            }
        }
        $validated['slug'] = Str::slug($validated['name']).'-'.time();

        // Compute cost per unit from total_cost ÷ opening_stock
        $totalCost = (float) $validated['total_cost'];
        $openingStock = (float) $validated['opening_stock'];
        $costPerUnit = $openingStock > 0 ? round($totalCost / $openingStock, 4) : $totalCost;

        $validated['purchase_price'] = $costPerUnit;
        $validated['cost_price'] = $costPerUnit;
        unset($validated['total_cost']); // not a DB column

        // Find or create brand from free text
        if (! empty($validated['brand_name'])) {
            $brand = Brand::firstOrCreate(
                ['business_id' => $businessId, 'name' => trim($validated['brand_name'])],
                ['slug' => Str::slug($validated['brand_name']).'-'.$businessId, 'is_active' => true]
            );
            $validated['brand_id'] = $brand->id;
        }
        unset($validated['brand_name']);

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time().'_'.Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'.'.$file->getClientOriginalExtension();
            $file->move(public_path('uploads/products'), $filename);
            $validated['image'] = 'uploads/products/'.$filename;
        }

        try {
            DB::beginTransaction();

            $product = Product::create($validated);

            if ($openingStock > 0) {
                $user = $request->user();
                $targetBranchId = ($user->isTenantAdmin() || $user->hasRole(['Super Admin', 'Admin']))
                    ? ($user->active_branch_id ?? $user->branch_id ?? Branch::where('business_id', $businessId)->value('id'))
                    : $user->branch_id;

                Stock::create([
                    'business_id' => $businessId,
                    'branch_id' => $targetBranchId,
                    'product_id' => $product->id,
                    'quantity' => $openingStock,
                    'avg_cost' => $costPerUnit,
                    'stock_value' => round($openingStock * $costPerUnit, 2),
                ]);

                StockMovement::create([
                    'business_id' => $businessId,
                    'branch_id' => $targetBranchId,
                    'product_id' => $product->id,
                    'user_id' => $request->user()->id,
                    'movement_type' => 'in',
                    'reference_type' => 'opening_balance',
                    'quantity_before' => 0,
                    'quantity_change' => $openingStock,
                    'quantity_after' => $openingStock,
                    'transaction_date' => today(),
                    'notes' => 'System opening stock',
                ]);
            }

            DB::commit();

            return redirect()->route('products.index')->with('success', 'Product created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($validated['image']) && file_exists(public_path($validated['image']))) {
                unlink(public_path($validated['image']));
            }

            return redirect()->back()->with('error', 'Failed to create product: '.$e->getMessage())->withInput();
        }
    }

    public function edit(Request $request, Product $product)
    {
        if ($product->business_id !== $request->user()->business_id) {
            abort(403);
        }

        $businessId = $request->user()->business_id;

        return Inertia::render('Products/Edit', [
            'product' => $product,
            'categories' => Category::where('business_id', $businessId)->get(['id', 'name']),
            'brands' => Brand::where('business_id', $businessId)->get(['id', 'name']),
            'units' => Unit::where('business_id', $businessId)->get(['id', 'name', 'abbreviation']),
            'productTypes' => ProductType::where('business_id', $businessId)->get(['id', 'name', 'code', 'is_manufactured', 'track_stock', 'is_sold', 'is_purchased']),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        if ($product->business_id !== $request->user()->business_id) {
            abort(403);
        }

        $businessId = $request->user()->business_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:100', 'unique:products,sku,'.$product->id],
            'barcode' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'category_id' => ['required', Rule::exists('categories', 'id')->where('business_id', $businessId)],
            'brand_name' => ['nullable', 'string', 'max:100'],
            'unit_id' => ['required', Rule::exists('units', 'id')->where('business_id', $businessId)],
            'product_type_id' => ['nullable', Rule::exists('product_types', 'id')->where('business_id', $businessId)],
            'product_type' => ['nullable', 'string', 'max:50'],
            'purchase_price' => ['required', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'wholesale_price' => ['nullable', 'numeric', 'min:0'],
            'min_selling_price' => ['nullable', 'numeric', 'min:0'],
            'min_stock' => ['nullable', 'numeric', 'min:0'],
            'reorder_level' => ['nullable', 'numeric', 'min:0'],
            'track_stock' => ['boolean'],
            'tax_applicable' => ['boolean'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
            'notes' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'max:2048'],
        ]);

        $validated['slug'] = Str::slug($validated['name']).'-'.$product->business_id;

        // Resolve product_type and product_type_id
        if (! empty($validated['product_type_id'])) {
            $matchedType = ProductType::where('business_id', $businessId)->find($validated['product_type_id']);
            if ($matchedType) {
                $validated['product_type'] = $matchedType->code;
            }
        } elseif (! empty($validated['product_type'])) {
            $matchedType = ProductType::where('business_id', $businessId)->where('code', $validated['product_type'])->first();
            if ($matchedType) {
                $validated['product_type_id'] = $matchedType->id;
            }
        }

        // Find or create brand from free text
        if (! empty($validated['brand_name'])) {
            $brand = Brand::firstOrCreate(
                ['business_id' => $product->business_id, 'name' => trim($validated['brand_name'])],
                ['slug' => Str::slug($validated['brand_name']).'-'.$product->business_id, 'is_active' => true]
            );
            $validated['brand_id'] = $brand->id;
        }
        unset($validated['brand_name']);

        if ($request->hasFile('image')) {
            if ($product->image && file_exists(public_path($product->image))) {
                unlink(public_path($product->image));
            } elseif ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }

            $file = $request->file('image');
            $filename = time().'_'.Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)).'.'.$file->getClientOriginalExtension();
            $file->move(public_path('uploads/products'), $filename);
            $validated['image'] = 'uploads/products/'.$filename;
        }

        try {
            DB::beginTransaction();

            $oldPurchasePrice = (float) $product->purchase_price;
            $newPurchasePrice = (float) $validated['purchase_price'];

            $product->update($validated);

            // If the purchase_price (cost) changed, sync cost_price and all stock avg_cost records
            if ($oldPurchasePrice !== $newPurchasePrice) {
                $product->update(['cost_price' => $newPurchasePrice]);

                // Update avg_cost and stock_value in every branch's stock record for this product
                Stock::where('product_id', $product->id)
                    ->each(function ($stock) use ($newPurchasePrice) {
                        $stock->update([
                            'avg_cost' => $newPurchasePrice,
                            'stock_value' => round((float) $stock->quantity * $newPurchasePrice, 2),
                        ]);
                    });
            }

            DB::commit();

            return redirect()->route('products.index')->with('success', 'Product updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            if (isset($validated['image']) && $request->hasFile('image') && file_exists(public_path($validated['image']))) {
                unlink(public_path($validated['image']));
            }

            return redirect()->back()->with('error', 'Failed to update product. Please try again.');
        }
    }

    public function destroy(Request $request, Product $product)
    {
        if ($product->business_id !== $request->user()->business_id) {
            abort(403);
        }

        try {
            DB::beginTransaction();

            if ($product->image && file_exists(public_path($product->image))) {
                unlink(public_path($product->image));
            } elseif ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }

            $product->delete();

            DB::commit();

            return redirect()->route('products.index')->with('success', 'Product deleted successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Failed to delete product. It may be in use.');
        }
    }
}
