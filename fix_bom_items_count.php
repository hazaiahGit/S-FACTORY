<?php
$file = "app/Http/Controllers/Manufacturing/BillOfMaterialController.php";
$content = file_get_contents($file);
$content = str_replace("BillOfMaterial::with(['product:id,name,sku', 'outputUnit:id,name,abbreviation'])", "BillOfMaterial::with(['product:id,name,sku', 'outputUnit:id,name,abbreviation'])->withCount('items')", $content);
file_put_contents($file, $content);
?>
