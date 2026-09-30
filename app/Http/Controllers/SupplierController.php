<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $businessId = $request->user()->business_id;

        $suppliers = Supplier::where('business_id', $businessId)
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('contact_person', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->withSum('purchases', 'total_amount')
            ->withSum('payments', 'amount')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Suppliers/Index', [
            'suppliers' => $suppliers,
            'filters' => $request->only(['search'])
        ]);
    }

    public function create()
    {
        return Inertia::render('Suppliers/Create');
    }

    public function store(Request $request)
    {
        $businessId = $request->user()->business_id;

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

        $validated['business_id'] = $businessId;
        $validated['is_active'] = true;

        try {
            Supplier::create($validated);
            return redirect()->route('suppliers.index')->with('success', 'Supplier created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error creating supplier: ' . $e->getMessage());
        }
    }

    public function show(Supplier $supplier, Request $request)
    {
        if ($supplier->business_id !== $request->user()->business_id) {
            abort(403);
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
            'supplier' => $supplier,
            'purchases' => $purchases,
            'payments' => $payments,
            'summary' => [
                'total_purchases' => $totalPurchases,
                'total_paid' => $totalPaid,
                'balance' => $balance,
            ]
        ]);
    }

    public function edit(Supplier $supplier, Request $request)
    {
        if ($supplier->business_id !== $request->user()->business_id) {
            abort(403);
        }

        return Inertia::render('Suppliers/Edit', [
            'supplier' => $supplier
        ]);
    }

    public function update(Request $request, Supplier $supplier)
    {
        if ($supplier->business_id !== $request->user()->business_id) {
            abort(403);
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
            return redirect()->back()->with('error', 'Error updating supplier: ' . $e->getMessage());
        }
    }

    public function destroy(Supplier $supplier, Request $request)
    {
        if ($supplier->business_id !== $request->user()->business_id) {
            abort(403);
        }

        if ($supplier->purchases()->exists() || $supplier->payments()->exists() || $supplier->products()->exists() || $supplier->batches()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete supplier because they have associated purchases, payments, products, or stock batches.');
        }

        try {
            $supplier->delete();
            return redirect()->back()->with('success', 'Supplier deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error deleting supplier: ' . $e->getMessage());
        }
    }
}
