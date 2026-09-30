<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        $businessId = $request->user()->business_id;
        $search = $request->input('search');

        $brands = Brand::where('business_id', $businessId)
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Brands/Index', [
            'brands' => $brands,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Brands/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $validated['business_id'] = $request->user()->business_id;
        $validated['slug'] = Str::slug($validated['name']);

        try {
            Brand::create($validated);
            return redirect()->route('brands.index')->with('success', 'Brand created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to create brand. Please try again.');
        }
    }

    public function edit(Request $request, Brand $brand)
    {
        if ($brand->business_id !== $request->user()->business_id) {
            abort(403);
        }

        return Inertia::render('Brands/Edit', [
            'brand' => $brand,
        ]);
    }

    public function update(Request $request, Brand $brand)
    {
        if ($brand->business_id !== $request->user()->business_id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        try {
            $brand->update($validated);
            return redirect()->route('brands.index')->with('success', 'Brand updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update brand. Please try again.');
        }
    }

    public function destroy(Request $request, Brand $brand)
    {
        if ($brand->business_id !== $request->user()->business_id) {
            abort(403);
        }

        try {
            $brand->delete();
            return redirect()->route('brands.index')->with('success', 'Brand deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete brand. Please try again.');
        }
    }
}
