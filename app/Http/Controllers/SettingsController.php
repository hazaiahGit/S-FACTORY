<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $business = $request->user()->business;

        return Inertia::render('Settings/Index', [
            'business' => $business,
        ]);
    }

    public function updateBusiness(Request $request)
    {
        $business = $request->user()->business;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'tax_number' => 'nullable|string|max:50',
            'tax_enabled' => 'boolean',
            'tax_inclusive' => 'boolean',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'currency' => 'required|string|max:10',
            'currency_symbol' => 'required|string|max:10',
        ]);

        $business->update($validated);

        return back()->with('success', 'Business settings updated successfully.');
    }
}
