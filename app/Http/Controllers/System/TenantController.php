<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Business;
use App\Models\SubscriptionPackage;
use App\Models\User;
use App\Services\TenantCatalogSeederService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;

class TenantController extends Controller
{
    public function index()
    {
        return Inertia::render('System/Tenants/Index', [
            'tenants' => Business::with('subscriptionPackage')->withCount(['users', 'branches'])->latest()->get(),
            'packages' => SubscriptionPackage::where('is_active', true)->get(['id', 'name', 'price', 'duration_days']),
            'stats' => [
                'total_tenants' => Business::count(),
                'active_tenants' => Business::where('subscription_status', 'active')->where('is_active', true)->count(),
                'suspended_tenants' => Business::where('subscription_status', 'suspended')->orWhere('is_active', false)->count(),
                'total_users' => User::whereNotNull('business_id')->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            // Tenant/Business fields
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'subscription_package_id' => 'nullable|exists:subscription_packages,id',
            // Admin User fields
            'admin_name' => 'required|string|max:255',
            'admin_email' => 'required|string|email|max:255|unique:users,email',
            'admin_password' => 'required|string|min:8',
        ]);

        DB::transaction(function () use ($validated) {
            $durationDays = 30;
            if (! empty($validated['subscription_package_id'])) {
                $pkg = SubscriptionPackage::find($validated['subscription_package_id']);
                if ($pkg && $pkg->duration_days) {
                    $durationDays = $pkg->duration_days;
                }
            }

            // Create Tenant
            $tenant = Business::create([
                'name' => $validated['name'],
                'slug' => Str::slug($validated['name']).'-'.Str::lower(Str::random(4)),
                'code' => strtoupper(Str::random(4)),
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'address' => $validated['address'],
                'subscription_package_id' => $validated['subscription_package_id'],
                'subscription_status' => 'active',
                'subscription_ends_at' => now()->addDays($durationDays),
            ]);

            // Create Main Branch for Tenant
            $branch = $tenant->branches()->create([
                'name' => 'Main Branch',
                'code' => 'MAIN',
                'is_main' => true,
                'is_active' => true,
            ]);

            // Create Admin User
            $user = User::create([
                'name' => $validated['admin_name'],
                'email' => $validated['admin_email'],
                'password' => Hash::make($validated['admin_password']),
                'business_id' => $tenant->id,
                'branch_id' => $branch->id,
                'job_title' => 'Tenant Administrator',
                'is_active' => true,
            ]);

            $user->branches()->attach($branch->id, ['is_primary' => true]);

            // Assign Super Admin role to user
            $user->assignRole('Super Admin');

            // Provision Default Catalog Taxonomies (Categories, Units, Product Types)
            TenantCatalogSeederService::seedTenantDefaults($tenant);
        });

        return redirect()->back()->with('success', 'Tenant and Admin created successfully.');
    }

    public function updateSubscription(Request $request, Business $tenant)
    {
        $validated = $request->validate([
            'subscription_package_id' => 'nullable|exists:subscription_packages,id',
            'subscription_status' => 'required|string|in:active,suspended,expired',
            'subscription_ends_at' => 'nullable|date',
        ]);

        $tenant->update($validated);

        return redirect()->back()->with('success', 'Tenant subscription updated.');
    }

    public function activate(Business $tenant)
    {
        $tenant->update([
            'is_active' => true,
            'subscription_status' => 'active',
        ]);

        return redirect()->back()->with('success', 'Tenant '.$tenant->name.' activated successfully.');
    }

    public function suspend(Business $tenant)
    {
        $tenant->update([
            'is_active' => false,
            'subscription_status' => 'suspended',
        ]);

        return redirect()->back()->with('success', 'Tenant '.$tenant->name.' suspended successfully.');
    }

    public function destroy(Business $tenant)
    {
        // For safety, soft delete or disable
        $tenant->update(['is_active' => false, 'subscription_status' => 'suspended']);

        return redirect()->back()->with('success', 'Tenant deactivated.');
    }
}
