<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\Target;
use App\Models\User;
use App\Services\TargetService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TargetController extends Controller
{
    public function __construct(private TargetService $targetService) {}

    public function index(Request $request)
    {
        $businessId = $request->user()->business_id;

        $targets = Target::with(['branch', 'user', 'product', 'category', 'customer'])
            ->where('business_id', $businessId)
            ->where(function ($q) {
                $q->where('branch_id', request()->user()->active_branch_id)->orWhereNull('branch_id');
            })
            ->latest()
            ->get()
            ->map(function ($target) {
                $progress = $this->targetService->calculateProgress($target);
                $target->progress = $progress;

                return $target;
            });

        return Inertia::render('Targets/Index', [
            'targets' => $targets,
        ]);
    }

    public function create(Request $request)
    {
        $businessId = $request->user()->business_id;

        return Inertia::render('Targets/Create', [
            'branches' => Branch::where('business_id', $businessId)->get(['id', 'name']),
            'users' => User::where('business_id', $businessId)->get(['id', 'name']),
            'products' => Product::where('business_id', $businessId)->get(['id', 'name']),
            'categories' => Category::where('business_id', $businessId)->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'target_type' => 'required|string|in:sales,production,profit,purchase,collection,customer,product,category,expense,custom',
            'target_value' => 'required|numeric|min:0',
            'measurement_unit' => 'required|string|in:amount,quantity,count',
            'period_type' => 'required|string|in:daily,weekly,monthly,quarterly,yearly,custom',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',

            // Scope
            'branch_id' => 'nullable|exists:branches,id',
            'user_id' => 'nullable|exists:users,id',
            'product_id' => 'nullable|exists:products,id',
            'category_id' => 'nullable|exists:categories,id',
            'customer_id' => 'nullable|exists:customers,id',

            'description' => 'nullable|string',
        ]);

        $validated['business_id'] = $request->user()->business_id;

        Target::create($validated);

        return redirect()->route('targets.index')->with('success', 'Target created successfully.');
    }

    public function show(Target $target, Request $request)
    {
        abort_if($target->business_id !== $request->user()->business_id, 403);

        $target->load(['branch', 'user', 'product', 'category', 'customer']);
        $progress = $this->targetService->calculateProgress($target);
        $target->progress = $progress;

        return Inertia::render('Targets/Show', [
            'target' => $target,
        ]);
    }

    public function edit(Target $target, Request $request)
    {
        abort_if($target->business_id !== $request->user()->business_id, 403);
        $businessId = $request->user()->business_id;

        return Inertia::render('Targets/Edit', [
            'target' => $target,
            'branches' => Branch::where('business_id', $businessId)->get(['id', 'name']),
            'users' => User::where('business_id', $businessId)->get(['id', 'name']),
            'products' => Product::where('business_id', $businessId)->get(['id', 'name']),
            'categories' => Category::where('business_id', $businessId)->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Target $target)
    {
        abort_if($target->business_id !== $request->user()->business_id, 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'target_type' => 'required|string|in:sales,production,profit,purchase,collection,customer,product,category,expense,custom',
            'target_value' => 'required|numeric|min:0',
            'measurement_unit' => 'required|string|in:amount,quantity,count',
            'period_type' => 'required|string|in:daily,weekly,monthly,quarterly,yearly,custom',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'branch_id' => 'nullable|exists:branches,id',
            'user_id' => 'nullable|exists:users,id',
            'product_id' => 'nullable|exists:products,id',
            'category_id' => 'nullable|exists:categories,id',
            'customer_id' => 'nullable|exists:customers,id',
            'description' => 'nullable|string',
        ]);

        $target->update($validated);

        return redirect()->route('targets.index')->with('success', 'Target updated successfully.');
    }

    public function destroy(Target $target, Request $request)
    {
        abort_if($target->business_id !== $request->user()->business_id, 403);
        $target->delete();

        return redirect()->route('targets.index')->with('success', 'Target removed.');
    }
}
