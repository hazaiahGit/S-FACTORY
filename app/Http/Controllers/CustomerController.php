<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $businessId = $request->user()->business_id;

        $customers = Customer::where('business_id', $businessId)
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
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

    public function create()
    {
        return Inertia::render('Customers/Create');
    }

    public function store(Request $request)
    {
        $businessId = $request->user()->business_id;

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'customer_type' => ['nullable', 'string', 'max:20'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'credit_allowed' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['business_id'] = $businessId;
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
            'customer' => $customer,
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

        return Inertia::render('Customers/Edit', [
            'customer' => $customer,
        ]);
    }

    public function update(Request $request, Customer $customer)
    {
        if ($customer->business_id !== $request->user()->business_id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'customer_type' => ['nullable', 'string', 'max:20'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'credit_allowed' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ]);

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

        if ($customer->sales()->exists() || $customer->payments()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete customer because they have associated sales or payments.');
        }

        try {
            $customer->delete();

            return redirect()->back()->with('success', 'Customer deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error deleting customer: '.$e->getMessage());
        }
    }
}
