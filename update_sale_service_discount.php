<?php
$file = "app/Services/SaleService.php";
$content = file_get_contents($file);

// In create()
$create_old = <<<'EOT'
            // Calculate totals
            $discountAmount = $subtotal * ((float)($data['discount_percent'] ?? 0) / 100);
            $totalAmount = $subtotal - $discountAmount;
EOT;
$create_new = <<<'EOT'
            // Calculate totals
            $discountAmount = (float)($data['discount_amount'] ?? 0);
            if ($discountAmount == 0 && isset($data['discount_percent']) && $data['discount_percent'] > 0) {
                $discountAmount = $subtotal * ((float)$data['discount_percent'] / 100);
            }
            $discountPercent = $subtotal > 0 ? ($discountAmount / $subtotal) * 100 : 0;
            $totalAmount = $subtotal - $discountAmount;
EOT;
$content = str_replace($create_old, $create_new, $content);

// Update $sale->update(...) inside create()
$update_old = <<<'EOT'
            $sale->update([
                'subtotal' => $subtotal,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
EOT;
$update_new = <<<'EOT'
            $sale->update([
                'subtotal' => $subtotal,
                'discount_percent' => $discountPercent,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
EOT;
$content = str_replace($update_old, $update_new, $content);

// In updateSale()
$update_sale_old = <<<'EOT'
            // 4. Update the sale record
            $discountPercent = $data['discount_percent'] ?? 0;
            $discountAmount = ($subtotal * $discountPercent) / 100;
            $taxAmount = 0; // Simple implementation
            $totalAmount = $subtotal - $discountAmount + $taxAmount;
EOT;
$update_sale_new = <<<'EOT'
            // 4. Update the sale record
            $discountAmount = (float)($data['discount_amount'] ?? 0);
            if ($discountAmount == 0 && isset($data['discount_percent']) && $data['discount_percent'] > 0) {
                $discountAmount = $subtotal * ((float)$data['discount_percent'] / 100);
            }
            $discountPercent = $subtotal > 0 ? ($discountAmount / $subtotal) * 100 : 0;
            $taxAmount = 0; // Simple implementation
            $totalAmount = $subtotal - $discountAmount + $taxAmount;
EOT;
$content = str_replace($update_sale_old, $update_sale_new, $content);

file_put_contents($file, $content);
?>
