<?php
$file = "resources/views/sales/receipt-pdf.blade.php";
$content = file_get_contents($file);

// Replace the subtotalExclTax logic
$old_logic = <<<'EOT'
    @php
        $computedTax = $sale->items->sum('tax_amount') ?? 0;
        $subtotalExclTax = $sale->total_amount - $computedTax;
    @endphp
EOT;
$new_logic = <<<'EOT'
    @php
        $computedTax = $sale->items->sum('tax_amount') ?? 0;
        $subtotalExclTax = $sale->subtotal;
    @endphp
EOT;
$content = str_replace($old_logic, $new_logic, $content);

file_put_contents($file, $content);
?>
