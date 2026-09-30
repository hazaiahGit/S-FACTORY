<?php
$file = "app/Http/Controllers/CustomerController.php";
$content = file_get_contents($file);

$old_destroy = <<<'EOT'
        if ($customer->sales()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete customer with associated sales.');
        }

        try {
            $customer->delete();
            return redirect()->route('customers.index')->with('success', 'Customer deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error deleting customer: ' . $e->getMessage());
        }
EOT;

$new_destroy = <<<'EOT'
        if ($customer->sales()->exists() || $customer->payments()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete customer because they have associated sales or payments.');
        }

        try {
            $customer->delete();
            return redirect()->back()->with('success', 'Customer deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error deleting customer: ' . $e->getMessage());
        }
EOT;

$content = str_replace($old_destroy, $new_destroy, $content);
file_put_contents($file, $content);
echo "Patched CustomerController destroy method.\n";
?>
