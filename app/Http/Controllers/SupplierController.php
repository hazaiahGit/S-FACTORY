<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $businessId = $user->business_id;
        $activeBranchId = $user->active_branch_id;

        $query = Supplier::where('business_id', $businessId);

        // Branch scoping: non-admin sees only their branch; admin sees selected branch or all
        if ($activeBranchId) {
            $query->where(function ($q) use ($activeBranchId) {
                $q->where('branch_id', $activeBranchId)
                    ->orWhereNull('branch_id');
            });
        }

        $suppliers = $query
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('contact_person', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->with('branch:id,name')
            ->withSum('purchases', 'total_amount')
            ->withSum('payments', 'amount')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Suppliers/Index', [
            'suppliers' => $suppliers,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Suppliers/Create');
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $businessId = $user->business_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'tax_number' => ['nullable', 'string', 'max:50'],
            'payment_terms' => ['nullable', 'string', 'max:50'],
            'credit_days' => ['nullable', 'integer', 'min:0'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['business_id'] = $businessId;
        // Tag with the branch:
        $validated['branch_id'] = $validated['branch_id'] ?? $user->active_branch_id ?? $user->branch_id;
        $validated['is_active'] = true;

        try {
            Supplier::create($validated);

            return redirect()->route('suppliers.index')->with('success', 'Supplier created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error creating supplier: '.$e->getMessage());
        }
    }

    public function show(Supplier $supplier, Request $request)
    {
        if ($supplier->business_id !== $request->user()->business_id) {
            abort(403);
        }

        $user = $request->user();
        if (! $user->isTenantAdmin() && ! $user->hasRole(['Super Admin', 'Admin']) && $supplier->branch_id && $supplier->branch_id !== $user->branch_id) {
            abort(403, 'Unauthorized to view supplier from another branch.');
        }

        $purchases = $supplier->purchases()
            ->latest()
            ->paginate(10, ['*'], 'purchases_page')
            ->withQueryString();

        $payments = $supplier->payments()
            ->latest()
            ->paginate(10, ['*'], 'payments_page')
            ->withQueryString();

        $totalPurchases = $supplier->purchases()->sum('total_amount');
        $totalPaid = $supplier->payments()->sum('amount');
        $balance = $totalPurchases - $totalPaid;

        return Inertia::render('Suppliers/Show', [
            'supplier' => $supplier->load('branch:id,name'),
            'purchases' => $purchases,
            'payments' => $payments,
            'summary' => [
                'total_purchases' => $totalPurchases,
                'total_paid' => $totalPaid,
                'balance' => $balance,
            ],
        ]);
    }

    public function edit(Supplier $supplier, Request $request)
    {
        if ($supplier->business_id !== $request->user()->business_id) {
            abort(403);
        }

        $user = $request->user();
        if (! $user->isTenantAdmin() && ! $user->hasRole(['Super Admin', 'Admin']) && $supplier->branch_id && $supplier->branch_id !== $user->branch_id) {
            abort(403, 'Unauthorized to edit supplier from another branch.');
        }

        return Inertia::render('Suppliers/Edit', [
            'supplier' => $supplier,
        ]);
    }

    public function update(Request $request, Supplier $supplier)
    {
        if ($supplier->business_id !== $request->user()->business_id) {
            abort(403);
        }

        $user = $request->user();
        if (! $user->isTenantAdmin() && ! $user->hasRole(['Super Admin', 'Admin']) && $supplier->branch_id && $supplier->branch_id !== $user->branch_id) {
            abort(403, 'Unauthorized to update supplier from another branch.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'tax_number' => ['nullable', 'string', 'max:50'],
            'payment_terms' => ['nullable', 'string', 'max:50'],
            'credit_days' => ['nullable', 'integer', 'min:0'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        try {
            $supplier->update($validated);

            return redirect()->route('suppliers.index')->with('success', 'Supplier updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error updating supplier: '.$e->getMessage());
        }
    }

    public function destroy(Supplier $supplier, Request $request)
    {
        if ($supplier->business_id !== $request->user()->business_id) {
            abort(403);
        }

        $user = $request->user();
        if (! $user->isTenantAdmin() && ! $user->hasRole(['Super Admin', 'Admin']) && $supplier->branch_id && $supplier->branch_id !== $user->branch_id) {
            abort(403, 'Unauthorized to delete supplier from another branch.');
        }

        if ($supplier->purchases()->exists() || $supplier->payments()->exists() || $supplier->products()->exists() || $supplier->batches()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete supplier because they have associated purchases, payments, products, or stock batches.');
        }

        try {
            $supplier->delete();

            return redirect()->route('suppliers.index')->with('success', 'Supplier deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error deleting supplier: '.$e->getMessage());
        }
    }
}
