<?php
$file = "resources/js/Pages/Manufacturing/BOM/Edit.vue";
$content = file_get_contents($file);

$content = str_replace(
    '{{ (item.quantity * item.unit_cost).toFixed(2) }}',
    "{{ new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(item.quantity * item.unit_cost) }}",
    $content
);

file_put_contents($file, $content);
?>
