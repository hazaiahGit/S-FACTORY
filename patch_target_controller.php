<?php
$file = "app/Http/Controllers/TargetController.php";
$content = file_get_contents($file);

$insert_methods = <<<'EOT'
    public function edit(Target $target, Request $request)
    {
        abort_if($target->business_id !== $request->user()->business_id, 403);
        $businessId = $request->user()->business_id;

        return Inertia::render('Targets/Edit', [
            'target' => $target,
            'branches' => Branch::where('business_id', $businessId)->get(['id', 'name']),
            'users' => User::where('business_id', $businessId)->get(['id', 'name']),
            'products' => Product::where('business_id', $businessId)->get(['id', 'name']),
            'categories' => Category::where('business_id', $businessId)->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Target $target)
    {
        abort_if($target->business_id !== $request->user()->business_id, 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'target_type' => 'required|string|in:sales,production,profit,purchase,collection,customer,product,category,expense,custom',
            'target_value' => 'required|numeric|min:0',
            'measurement_unit' => 'required|string|in:amount,quantity,count',
            'period_type' => 'required|string|in:daily,weekly,monthly,quarterly,yearly,custom',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'branch_id' => 'nullable|exists:branches,id',
            'user_id' => 'nullable|exists:users,id',
            'product_id' => 'nullable|exists:products,id',
            'category_id' => 'nullable|exists:categories,id',
            'customer_id' => 'nullable|exists:customers,id',
            'description' => 'nullable|string',
        ]);

        $target->update($validated);

        return redirect()->route('targets.index')->with('success', 'Target updated successfully.');
    }

    public function destroy(Target $target, Request $request)
EOT;

$content = str_replace('    public function destroy(Target $target, Request $request)', $insert_methods, $content);
file_put_contents($file, $content);
echo "Patched TargetController.\n";
?>
