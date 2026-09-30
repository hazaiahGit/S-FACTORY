<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $businessId = $request->user()->business_id;
        
        $branches = Branch::where('business_id', $businessId)
            ->latest()
            ->get();
            
        return Inertia::render('Branches/Index', [
            'branches' => $branches
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'type' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'is_main' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $validated['business_id'] = $request->user()->business_id;

        Branch::create($validated);

        return redirect()->back()->with('success', 'Branch created successfully.');
    }

    public function update(Request $request, Branch $branch)
    {
        if ($branch->business_id !== $request->user()->business_id) abort(403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'type' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'is_main' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $branch->update($validated);

        return redirect()->back()->with('success', 'Branch updated successfully.');
    }

    public function destroy(Branch $branch, Request $request)
    {
        if ($branch->business_id !== $request->user()->business_id) abort(403);
        
        if ($branch->is_main) {
            return redirect()->back()->with('error', 'Cannot delete the main branch.');
        }
        
        if ($branch->sales()->exists() || $branch->purchases()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete branch because it has associated transactions.');
        }

        $branch->delete();
        return redirect()->back()->with('success', 'Branch deleted successfully.');
    }
}