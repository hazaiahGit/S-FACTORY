<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseCategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $businessId = $request->user()->business_id;
        $search = $request->input('search');
        $status = $request->input('status');

        $categoriesQuery = ExpenseCategory::withCount('expenses')
            ->withSum('expenses', 'amount')
            ->where('business_id', $businessId)
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
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

        return Inertia::render('ExpenseCategories/Index', [
            'categories' => $categories,
            'filters' => $request->only(['search', 'status']),
            'stats' => [
                'total' => ExpenseCategory::where('business_id', $businessId)->count(),
                'active' => ExpenseCategory::where('business_id', $businessId)->where('is_active', true)->count(),
                'total_amount' => (float) Expense::where('business_id', $businessId)->whereNotNull('expense_category_id')->sum('amount'),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $businessId = $request->user()->business_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['business_id'] = $businessId;
        $validated['color'] = $validated['color'] ?? '#f43f5e';
        $validated['is_active'] = $validated['is_active'] ?? true;

        try {
            ExpenseCategory::create($validated);

            return redirect()->back()->with('success', 'Expense category created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error creating expense category: '.$e->getMessage());
        }
    }

    public function update(Request $request, ExpenseCategory $expenseCategory): RedirectResponse
    {
        if ($expenseCategory->business_id !== $request->user()->business_id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        try {
            $expenseCategory->update($validated);

            return redirect()->back()->with('success', 'Expense category updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error updating expense category: '.$e->getMessage());
        }
    }

    public function destroy(Request $request, ExpenseCategory $expenseCategory): RedirectResponse
    {
        if ($expenseCategory->business_id !== $request->user()->business_id) {
            abort(403);
        }

        if ($expenseCategory->expenses()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete this category because it has associated expenses. You can deactivate it instead.');
        }

        try {
            $expenseCategory->delete();

            return redirect()->back()->with('success', 'Expense category deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error deleting expense category: '.$e->getMessage());
        }
    }
}
