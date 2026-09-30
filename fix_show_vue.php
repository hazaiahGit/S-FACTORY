<?php
$file = "resources/js/Pages/Sales/Show.vue";
$content = file_get_contents($file);

$old_subtotal = "sale.total_amount - sale.items.reduce((sum, item) => sum + Number(item.tax_amount || 0), 0)";
$new_subtotal = "sale.subtotal";

$content = str_replace($old_subtotal, $new_subtotal, $content);
file_put_contents($file, $content);
?>
