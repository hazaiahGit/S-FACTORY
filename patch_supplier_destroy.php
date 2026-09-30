<?php
$file = "app/Http/Controllers/SupplierController.php";
$content = file_get_contents($file);

$old_destroy = <<<'EOT'
        if ($supplier->purchases()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete supplier with associated purchases.');
        }

        try {
            $supplier->delete();
            return redirect()->route('suppliers.index')->with('success', 'Supplier deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error deleting supplier: ' . $e->getMessage());
        }
EOT;

$new_destroy = <<<'EOT'
        if ($supplier->purchases()->exists() || $supplier->payments()->exists() || $supplier->products()->exists() || $supplier->batches()->exists()) {
            return redirect()->back()->with('error', 'Cannot delete supplier because they have associated purchases, payments, products, or stock batches.');
        }

        try {
            $supplier->delete();
            return redirect()->back()->with('success', 'Supplier deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error deleting supplier: ' . $e->getMessage());
        }
EOT;

$content = str_replace($old_destroy, $new_destroy, $content);
file_put_contents($file, $content);
echo "Patched SupplierController destroy method.\n";
?>
