<?php

namespace App\Http\Controllers;

use App\Models\ProductType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProductTypeController extends Controller
{
    public function index(Request $request): Response
    {
        $businessId = $request->user()->business_id;
        $search = $request->input('search');
        $status = $request->input('status');

        $typesQuery = ProductType::withCount('products')
            ->where('business_id', $businessId)
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($status !== null && $status !== '', function ($query) use ($status) {
                if ($status === 'active') {
                    $query->where('is_active', true);
                } elseif ($status === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->latest();

        $productTypes = $typesQuery->paginate(15)->withQueryString();

        return Inertia::render('ProductTypes/Index', [
            'productTypes' => $productTypes,
            'filters' => $request->only(['search', 'status']),
            'stats' => [
                'total' => ProductType::where('business_id', $businessId)->count(),
                'active' => ProductType::where('business_id', $businessId)->where('is_active', true)->count(),
                'manufacturable' => ProductType::where('business_id', $businessId)->where('is_manufactured', true)->count(),
                'salable' => ProductType::where('business_id', $businessId)->where('is_sold', true)->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $businessId = $request->user()->business_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('product_types')->where('business_id', $businessId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_sold' => ['boolean'],
            'is_purchased' => ['boolean'],
            'is_manufactured' => ['boolean'],
            'track_stock' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $validated['business_id'] = $businessId;
        $validated['code'] = !empty($validated['code'])
            ? Str::slug($validated['code'], '_')
            : Str::slug($validated['name'], '_');

        // Check code uniqueness after slugging
        $existing = ProductType::where('business_id', $businessId)
            ->where('code', $validated['code'])
            ->exists();
        if ($existing) {
            $validated['code'] .= '_' . Str::lower(Str::random(3));
        }

        $validated['is_sold'] = $validated['is_sold'] ?? true;
        $validated['is_purchased'] = $validated['is_purchased'] ?? true;
        $validated['is_manufactured'] = $validated['is_manufactured'] ?? false;
        $validated['track_stock'] = $validated['track_stock'] ?? true;
        $validated['is_active'] = $validated['is_active'] ?? true;

        ProductType::create($validated);

        return redirect()->back()->with('success', 'Product Type created successfully.');
    }

    public function update(Request $request, ProductType $productType)
    {
        $businessId = $request->user()->business_id;
        abort_unless($productType->business_id === $businessId, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('product_types')->where('business_id', $businessId)->ignore($productType->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_sold' => ['boolean'],
            'is_purchased' => ['boolean'],
            'is_manufactured' => ['boolean'],
            'track_stock' => ['boolean'],
            'is_active' => ['boolean'],
        ]);

        $productType->update($validated);

        return redirect()->back()->with('success', 'Product Type updated successfully.');
    }

    public function toggleStatus(Request $request, ProductType $productType)
    {
        $businessId = $request->user()->business_id;
        abort_unless($productType->business_id === $businessId, 403);

        $productType->update([
            'is_active' => !$productType->is_active,
        ]);

        $status = $productType->is_active ? 'activated' : 'deactivated';
        return redirect()->back()->with('success', "Product Type {$status} successfully.");
    }

    public function destroy(Request $request, ProductType $productType)
    {
        $businessId = $request->user()->business_id;
        abort_unless($productType->business_id === $businessId, 403);

        if ($productType->is_default) {
            return redirect()->back()->with('error', 'Cannot delete default system product type.');
        }

        if ($productType->products()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete product type currently assigned to products.');
        }

        $productType->delete();

        return redirect()->back()->with('success', 'Product Type deleted successfully.');
    }
}
