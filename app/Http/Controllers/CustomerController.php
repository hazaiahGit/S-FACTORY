<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $businessId = $user->business_id;
        $activeBranchId = $user->active_branch_id;
        $isAdmin = $user->isTenantAdmin() || $user->hasRole(['Super Admin', 'Admin']);

        $query = Customer::where('business_id', $businessId);

        // Branch scoping: non-admin sees strictly their branch; admin sees selected branch or all
        if (! $isAdmin) {
            $query->where('branch_id', $user->branch_id);
        } elseif ($activeBranchId) {
            $query->where(function ($q) use ($activeBranchId) {
                $q->where('branch_id', $activeBranchId)
                    ->orWhereNull('branch_id');
            });
        }

        $customers = $query
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->with('branch:id,name')
            ->withSum('sales', 'total_amount')
            ->withSum('payments', 'amount')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Customers/Index', [
            'customers' => $customers,
            'filters' => $request->only(['search']),
        ]);
    }

    public function create(Request $request)
    {
        $user = $request->user();
        $isAdmin = $user->isTenantAdmin() || $user->hasRole(['Super Admin', 'Admin']);
        $branches = $isAdmin ? Branch::where('business_id', $user->business_id)->get(['id', 'name']) : [];

        return Inertia::render('Customers/Create', [
            'branches' => $branches,
            'defaultBranchId' => $user->active_branch_id ?? $user->branch_id,
            'isAdmin' => $isAdmin,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $businessId = $user->business_id;
        $isAdmin = $user->isTenantAdmin() || $user->hasRole(['Super Admin', 'Admin']);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'customer_type' => ['nullable', 'string', 'max:20'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'credit_allowed' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['business_id'] = $businessId;
        // Non-admin can NEVER assign a branch other than their own assigned branch
        if (! $isAdmin) {
            $validated['branch_id'] = $user->branch_id;
        } else {
            $validated['branch_id'] = $validated['branch_id'] ?? $user->active_branch_id ?? $user->branch_id;
        }
        $validated['is_active'] = true;

        try {
            Customer::create($validated);

            return redirect()->route('customers.index')->with('success', 'Customer created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error creating customer: '.$e->getMessage());
        }
    }

    public function show(Customer $customer, Request $request)
    {
        if ($customer->business_id !== $request->user()->business_id) {
            abort(403);
        }

        $user = $request->user();
        if (! $user->isTenantAdmin() && ! $user->hasRole(['Super Admin', 'Admin']) && $customer->branch_id && $customer->branch_id !== $user->branch_id) {
            abort(403, 'Unauthorized to view customer from another branch.');
        }

        $sales = $customer->sales()
            ->latest()
            ->paginate(10, ['*'], 'sales_page')
            ->withQueryString();

        $payments = $customer->payments()
            ->with('sale')
            ->latest()
            ->paginate(10, ['*'], 'payments_page')
            ->withQueryString();

        $totalSales = $customer->sales()->sum('total_amount');
        $totalPaid = $customer->payments()->sum('amount');
        $balance = $totalSales - $totalPaid;

        return Inertia::render('Customers/Show', [
            'customer' => $customer->load('branch:id,name'),
            'sales' => $sales,
            'payments' => $payments,
            'summary' => [
                'total_sales' => $totalSales,
                'total_paid' => $totalPaid,
                'balance' => $balance,
            ],
        ]);
    }

    public function edit(Customer $customer, Request $request)
    {
        if ($customer->business_id !== $request->user()->business_id) {
            abort(403);
        }

        $user = $request->user();
        $isAdmin = $user->isTenantAdmin() || $user->hasRole(['Super Admin', 'Admin']);
        if (! $isAdmin && $customer->branch_id && $customer->branch_id !== $user->branch_id) {
            abort(403, 'Unauthorized to edit customer from another branch.');
        }

        $branches = $isAdmin ? Branch::where('business_id', $user->business_id)->get(['id', 'name']) : [];

        return Inertia::render('Customers/Edit', [
            'customer' => $customer,
            'branches' => $branches,
            'isAdmin' => $isAdmin,
        ]);
    }

    public function update(Request $request, Customer $customer)
    {
        if ($customer->business_id !== $request->user()->business_id) {
            abort(403);
        }

        $user = $request->user();
        $isAdmin = $user->isTenantAdmin() || $user->hasRole(['Super Admin', 'Admin']);
        if (! $isAdmin && $customer->branch_id && $customer->branch_id !== $user->branch_id) {
            abort(403, 'Unauthorized to update customer from another branch.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'customer_type' => ['nullable', 'string', 'max:20'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'credit_allowed' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        if (! $isAdmin) {
            unset($validated['branch_id']);
        }

        try {
            $customer->update($validated);

            return redirect()->route('customers.index')->with('success', 'Customer updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error updating customer: '.$e->getMessage());
        }
    }

    public function destroy(Customer $customer, Request $request)
    {
        if ($customer->business_id !== $request->user()->business_id) {
            abort(403);
        }

        $user = $request->user();
        if (! $user->isTenantAdmin() && ! $user->hasRole(['Super Admin', 'Admin']) && $customer->branch_id && $customer->branch_id !== $user->branch_id) {
            abort(403, 'Unauthorized to delete customer from another branch.');
        }

        if ($customer->sales()->exists() || $customer->payments()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete customer because they have associated sales or payments.');
        }

        try {
            $customer->delete();

            return redirect()->route('customers.index')->with('success', 'Customer deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error deleting customer: '.$e->getMessage());
        }
    }
}
