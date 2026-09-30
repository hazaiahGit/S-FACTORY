<?php
$file = "app/Http/Controllers/SaleController.php";
$content = file_get_contents($file);

// Replace validation rules
$content = str_replace("'discount_percent' => 'nullable|numeric|min:0|max:100',", "'discount_percent' => 'nullable|numeric|min:0|max:100',\n            'discount_amount' => 'nullable|numeric|min:0',", $content);

file_put_contents($file, $content);
?>
