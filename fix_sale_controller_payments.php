<?php
$file = "app/Http/Controllers/SaleController.php";
$content = file_get_contents($file);

$old_validation = <<<'EOT'
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);
EOT;
$new_validation = <<<'EOT'
            'discount_amount' => 'nullable|numeric|min:0',
            'payments' => 'nullable|array',
            'payments.*.payment_method' => 'required|string',
            'payments.*.amount' => 'required|numeric|min:0',
            'payments.*.reference' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);
EOT;
$content = str_replace($old_validation, $new_validation, $content);
file_put_contents($file, $content);
?>
