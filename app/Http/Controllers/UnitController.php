<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UnitController extends Controller
{
    public function index(Request $request)
    {
        $businessId = $request->user()->business_id;
        $search = $request->input('search');

        $units = Unit::where('business_id', $businessId)
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('short_name', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Units/Index', [
            'units' => $units,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Units/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['required', 'string', 'max:50'],
            'allow_decimal' => ['boolean'],
        ]);

        $validated['business_id'] = $request->user()->business_id;

        try {
            Unit::create($validated);
            return redirect()->route('units.index')->with('success', 'Unit created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to create unit. Please try again.');
        }
    }

    public function edit(Request $request, Unit $unit)
    {
        if ($unit->business_id !== $request->user()->business_id) {
            abort(403);
        }

        return Inertia::render('Units/Edit', [
            'unit' => $unit,
        ]);
    }

    public function update(Request $request, Unit $unit)
    {
        if ($unit->business_id !== $request->user()->business_id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['required', 'string', 'max:50'],
            'allow_decimal' => ['boolean'],
        ]);

        try {
            $unit->update($validated);
            return redirect()->route('units.index')->with('success', 'Unit updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update unit. Please try again.');
        }
    }

    public function destroy(Request $request, Unit $unit)
    {
        if ($unit->business_id !== $request->user()->business_id) {
            abort(403);
        }

        try {
            $unit->delete();
            return redirect()->route('units.index')->with('success', 'Unit deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete unit. Please try again.');
        }
    }
}
