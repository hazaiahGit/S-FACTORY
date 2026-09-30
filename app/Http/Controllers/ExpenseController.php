<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\NumberGeneratorService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Exception;

class ExpenseController extends Controller
{
    public function __construct(
        private NumberGeneratorService $numberGenerator
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();

        $expenses = Expense::with(['category', 'user', 'branch'])
            ->where('business_id', $user->business_id)
            ->where('branch_id', $user->active_branch_id)
            ->when($request->search, function ($query, $search) {
                $query->where('expense_number', 'like', "%{$search}%")
                      ->orWhere('title', 'like', "%{$search}%")
                      ->orWhere('reference', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Expenses/Index', [
            'expenses' => $expenses,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(Request $request): Response
    {
        $categories = ExpenseCategory::where('business_id', $request->user()->business_id)
            ->where('is_active', true)
            ->get(['id', 'name', 'color']);
            
        return Inertia::render('Expenses/Create', [
            'categories' => $categories
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'title' => 'required|string|max:200',
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
            'payment_method' => 'required|string|max:30',
            'reference' => 'nullable|string|max:100',
            'description' => 'nullable|string',
        ]);

        try {
            $validated['business_id'] = $user->business_id;
            $validated['branch_id'] = $user->active_branch_id;
            $validated['user_id'] = $user->id;
            $validated['expense_number'] = $this->numberGenerator->generateExpenseNumber($user->business_id);
            $validated['status'] = 'approved';

            Expense::create($validated);

            return redirect()->route('expenses.index')->with('success', 'Expense recorded successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to create expense: ' . $e->getMessage());
        }
    }

    public function destroy(Request $request, Expense $expense): RedirectResponse
    {
        $user = $request->user();

        if ($expense->business_id !== $user->business_id) {
            abort(403);
        }

        try {
            $expense->delete();
            return redirect()->route('expenses.index')->with('success', 'Expense deleted successfully.');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete expense: ' . $e->getMessage());
        }
    }
}
