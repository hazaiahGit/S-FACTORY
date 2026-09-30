<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $businessId = $request->user()->business_id;
        $search = $request->input('search');
        $status = $request->input('status');

        $categoriesQuery = Category::with(['parent'])
            ->withCount(['products', 'children'])
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

        $categories = $categoriesQuery->paginate(15)->withQueryString();

        // Potential parent categories for this tenant (top-level only)
        $parentCategories = Category::where('business_id', $businessId)
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->get(['id', 'name', 'code']);

        return Inertia::render('Categories/Index', [
            'categories' => $categories,
            'parentCategories' => $parentCategories,
            'filters' => $request->only(['search', 'status']),
            'stats' => [
                'total' => Category::where('business_id', $businessId)->count(),
                'active' => Category::where('business_id', $businessId)->where('is_active', true)->count(),
                'subcategories' => Category::where('business_id', $businessId)->whereNotNull('parent_id')->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $businessId = $request->user()->business_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:30'],
            'parent_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where('business_id', $businessId),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'color' => ['nullable', 'string', 'max:30'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $validated['business_id'] = $businessId;
        $validated['slug'] = Str::slug($validated['name']).'-'.Str::lower(Str::random(4));
        $validated['is_active'] = $validated['is_active'] ?? true;

        Category::create($validated);

        return redirect()->back()->with('success', 'Category created successfully.');
    }

    public function update(Request $request, Category $category)
    {
        $businessId = $request->user()->business_id;
        abort_unless($category->business_id === $businessId, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:30'],
            'parent_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where('business_id', $businessId),
                Rule::notIn([$category->id]), // Cannot be parent of itself
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'color' => ['nullable', 'string', 'max:30'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $category->update($validated);

        return redirect()->back()->with('success', 'Category updated successfully.');
    }

    public function toggleStatus(Request $request, Category $category)
    {
        $businessId = $request->user()->business_id;
        abort_unless($category->business_id === $businessId, 403);

        $category->update([
            'is_active' => ! $category->is_active,
        ]);

        $status = $category->is_active ? 'activated' : 'deactivated';

        return redirect()->back()->with('success', "Category {$status} successfully.");
    }

    public function destroy(Request $request, Category $category)
    {
        $businessId = $request->user()->business_id;
        abort_unless($category->business_id === $businessId, 403);

        if ($category->products()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete category that is currently linked to products.');
        }

        if ($category->children()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete category that contains subcategories. Delete or move subcategories first.');
        }

        $category->delete();

        return redirect()->back()->with('success', 'Category deleted successfully.');
    }
}
