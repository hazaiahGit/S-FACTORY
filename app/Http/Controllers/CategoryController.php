<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $businessId = $request->user()->business_id;
        $search = $request->input('search');

        $categories = Category::with(['parent', 'children'])
            ->where('business_id', $businessId)
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Categories/Index', [
            'categories' => $categories,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(Request $request)
    {
        $categories = Category::where('business_id', $request->user()->business_id)
            ->whereNull('parent_id')
            ->get(['id', 'name']);

        return Inertia::render('Categories/Create', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'is_active' => ['boolean'],
        ]);

        $validated['business_id'] = $request->user()->business_id;
        $validated['slug'] = Str::slug($validated['name']);

        try {
            Category::create($validated);
            return redirect()->route('categories.index')->with('success', 'Category created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to create category. Please try again.');
        }
    }

    public function edit(Request $request, Category $category)
    {
        if ($category->business_id !== $request->user()->business_id) {
            abort(403);
        }

        $categories = Category::where('business_id', $request->user()->business_id)
            ->whereNull('parent_id')
            ->where('id', '!=', $category->id)
            ->get(['id', 'name']);

        return Inertia::render('Categories/Edit', [
            'category' => $category,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, Category $category)
    {
        if ($category->business_id !== $request->user()->business_id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'exists:categories,id'],
            'is_active' => ['boolean'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        try {
            $category->update($validated);
            return redirect()->route('categories.index')->with('success', 'Category updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update category. Please try again.');
        }
    }

    public function destroy(Request $request, Category $category)
    {
        if ($category->business_id !== $request->user()->business_id) {
            abort(403);
        }

        try {
            if ($category->children()->exists()) {
                return redirect()->back()->with('error', 'Cannot delete category with sub-categories.');
            }
            
            $category->delete();
            return redirect()->route('categories.index')->with('success', 'Category deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete category. Please try again.');
        }
    }
}
