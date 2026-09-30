<?php
$content = <<<'EOT'
<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPackage;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PackageController extends Controller
{
    public function index()
    {
        return Inertia::render('System/Packages/Index', [
            'packages' => SubscriptionPackage::latest()->get()
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'max_users' => 'nullable|integer|min:1',
            'max_branches' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        SubscriptionPackage::create($validated);
        return redirect()->back()->with('success', 'Package created successfully.');
    }

    public function update(Request $request, SubscriptionPackage $package)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'duration_days' => 'required|integer|min:1',
            'max_users' => 'nullable|integer|min:1',
            'max_branches' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $package->update($validated);
        return redirect()->back()->with('success', 'Package updated successfully.');
    }

    public function destroy(SubscriptionPackage $package)
    {
        if ($package->businesses()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete package that is assigned to tenants.');
        }
        
        $package->delete();
        return redirect()->back()->with('success', 'Package deleted successfully.');
    }
}
EOT;
file_put_contents('app/Http/Controllers/System/PackageController.php', $content);
echo "Created PackageController.\n";
?>
