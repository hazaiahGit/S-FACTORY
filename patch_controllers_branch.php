<?php
// Add active_branch_id accessor to User.php
$user_file = 'app/Models/User.php';
$user_content = file_get_contents($user_file);

$accessor = <<<'EOT'
    public function getActiveBranchIdAttribute()
    {
        if ($this->hasRole('Super Admin') || $this->hasRole('Admin')) {
            return session('active_branch_id', $this->branch_id);
        }
        return $this->branch_id;
    }
EOT;

if (strpos($user_content, 'getActiveBranchIdAttribute') === false) {
    $user_content = str_replace(
        "public function recordLogin(string \$ip): void",
        $accessor . "\n\n    public function recordLogin(string \$ip): void",
        $user_content
    );
    file_put_contents($user_file, $user_content);
}

// 1. Patch SaleController
$sale_file = 'app/Http/Controllers/SaleController.php';
$sale_content = file_get_contents($sale_file);
$sale_content = str_replace(
    "->where('business_id', \$businessId)",
    "->where('business_id', \$businessId)\n            ->where('branch_id', request()->user()->active_branch_id)",
    $sale_content
);
// For branch_id in create (already uses request()->user()->branch_id)
$sale_content = str_replace(
    "\$branchId = request()->user()->branch_id;",
    "\$branchId = request()->user()->active_branch_id;",
    $sale_content
);
file_put_contents($sale_file, $sale_content);

// 2. Patch PurchaseController
$purchase_file = 'app/Http/Controllers/PurchaseController.php';
$purchase_content = file_get_contents($purchase_file);
$purchase_content = str_replace(
    "->where('business_id', \$businessId)",
    "->where('business_id', \$businessId)\n            ->where('branch_id', request()->user()->active_branch_id)",
    $purchase_content
);
file_put_contents($purchase_file, $purchase_content);

// 3. Patch TargetController
$target_file = 'app/Http/Controllers/TargetController.php';
$target_content = file_get_contents($target_file);
$target_content = str_replace(
    "->where('business_id', \$businessId)",
    "->where('business_id', \$businessId)\n            ->where(function(\$q) { \$q->where('branch_id', request()->user()->active_branch_id)->orWhereNull('branch_id'); })",
    $target_content
);
file_put_contents($target_file, $target_content);

// 4. Patch ExpenseController
$expense_file = 'app/Http/Controllers/ExpenseController.php';
$expense_content = file_get_contents($expense_file);
$expense_content = str_replace(
    "\$user->branch_id",
    "\$user->active_branch_id",
    $expense_content
);
file_put_contents($expense_file, $expense_content);

// 5. Patch StockController
$stock_file = 'app/Http/Controllers/StockController.php';
$stock_content = file_get_contents($stock_file);
$stock_content = str_replace(
    "\$user->branch_id",
    "\$user->active_branch_id",
    $stock_content
);
file_put_contents($stock_file, $stock_content);

echo "Patched Controllers with active_branch_id.\n";
?>
