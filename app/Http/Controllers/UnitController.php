<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UnitController extends Controller
{
    public function index(Request $request): Response
    {
        $businessId = $request->user()->business_id;
        $search = $request->input('search');
        $type = $request->input('type');
        $status = $request->input('status');

        $unitsQuery = Unit::withCount('products')
            ->where('business_id', $businessId)
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('abbreviation', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($type, function ($query, $type) {
                $query->where('type', $type);
            })
            ->when($status !== null && $status !== '', function ($query) use ($status) {
                if ($status === 'active') {
                    $query->where('is_active', true);
                } elseif ($status === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->latest();

        $units = $unitsQuery->paginate(15)->withQueryString();

        return Inertia::render('Units/Index', [
            'units' => $units,
            'filters' => $request->only(['search', 'type', 'status']),
            'stats' => [
                'total' => Unit::where('business_id', $businessId)->count(),
                'active' => Unit::where('business_id', $businessId)->where('is_active', true)->count(),
                'discrete' => Unit::where('business_id', $businessId)->where('allow_decimal', false)->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $businessId = $request->user()->business_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'abbreviation' => [
                'required',
                'string',
                'max:20',
                Rule::unique('units')->where('business_id', $businessId),
            ],
            'type' => ['required', 'string', 'in:quantity,weight,volume,length,area'],
            'allow_decimal' => ['boolean'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
        ]);

        $validated['business_id'] = $businessId;
        $validated['is_active'] = $validated['is_active'] ?? true;
        $validated['allow_decimal'] = $validated['allow_decimal'] ?? true;

        Unit::create($validated);

        return redirect()->back()->with('success', 'Unit of Measure created successfully.');
    }

    public function update(Request $request, Unit $unit)
    {
        $businessId = $request->user()->business_id;
        abort_unless($unit->business_id === $businessId, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'abbreviation' => [
                'required',
                'string',
                'max:20',
                Rule::unique('units')->where('business_id', $businessId)->ignore($unit->id),
            ],
            'type' => ['required', 'string', 'in:quantity,weight,volume,length,area'],
            'allow_decimal' => ['boolean'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
        ]);

        $unit->update($validated);

        return redirect()->back()->with('success', 'Unit of Measure updated successfully.');
    }

    public function toggleStatus(Request $request, Unit $unit)
    {
        $businessId = $request->user()->business_id;
        abort_unless($unit->business_id === $businessId, 403);

        $unit->update([
            'is_active' => ! $unit->is_active,
        ]);

        $status = $unit->is_active ? 'activated' : 'deactivated';

        return redirect()->back()->with('success', "Unit {$status} successfully.");
    }

    public function destroy(Request $request, Unit $unit)
    {
        $businessId = $request->user()->business_id;
        abort_unless($unit->business_id === $businessId, 403);

        if ($unit->products()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete Unit of Measure currently assigned to products.');
        }

        $unit->delete();

        return redirect()->back()->with('success', 'Unit of Measure deleted successfully.');
    }
}
