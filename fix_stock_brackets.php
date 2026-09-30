<?php
$file = "app/Http/Controllers/StockController.php";
$content = file_get_contents($file);

$content = str_replace(
    "        ]);\n        public function destroyMovement",
    "        ]);\n    }\n\n    public function destroyMovement",
    $content
);
file_put_contents($file, $content);
echo "Fixed StockController brackets.\n";
?>
