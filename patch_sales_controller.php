<?php
$file = "app/Http/Controllers/SaleController.php";
$content = file_get_contents($file);

// Add items.*.price_type to store validation
$store_inject = <<<'EOT'
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.price_type' => 'nullable|string|in:retail,wholesale',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
EOT;
$content = preg_replace('/\'items\.\*\.product_id\' => \'required\|exists:products,id\',\s*\'items\.\*\.quantity\' => \'required\|numeric\|min:0\.01\',\s*\'items\.\*\.unit_price\' => \'required\|numeric\|min:0\',\s*\'items\.\*\.discount_amount\' => \'nullable\|numeric\|min:0\',/', $store_inject, $content);

file_put_contents($file, $content);
echo "Patched SaleController.\n";
?>
