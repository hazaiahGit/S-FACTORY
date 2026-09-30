<?php
$file = "resources/js/Pages/Sales/Index.vue";
$content = file_get_contents($file);

$old_items_mobile = "{{ sale.items?.length || 0 }} items";
$new_items_mobile = "{{ sale.items?.reduce((sum, i) => sum + parseFloat(i.quantity), 0) || 0 }} items";
$content = str_replace($old_items_mobile, $new_items_mobile, $content);

$old_items_desktop = "{{ sale.items?.length || 0 }}";
$new_items_desktop = "{{ sale.items?.reduce((sum, i) => sum + parseFloat(i.quantity), 0) || 0 }}";
$content = str_replace($old_items_desktop, $new_items_desktop, $content);

file_put_contents($file, $content);
echo "Updated Index.vue\n";
?>
