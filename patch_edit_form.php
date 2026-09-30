<?php
$file = "resources/js/Pages/Manufacturing/BOM/Edit.vue";
$content = file_get_contents($file);

$old_map = <<<'EOT'
    items: props.bom.items.map(i => ({
        product_id: i.product_id,
        quantity: i.quantity,
        unit_id: i.unit_id,
        unit_cost: i.unit_cost,
        total_cost: i.total_cost,
        is_optional: i.is_optional,
    })),
EOT;

$new_map = <<<'EOT'
    items: props.bom.items.map(i => ({
        product_id: i.product_id,
        description: i.description,
        quantity: i.quantity,
        unit_id: i.unit_id,
        unit_cost: i.unit_cost,
        total_cost: i.total_cost,
        is_optional: i.is_optional,
    })),
EOT;

$content = str_replace($old_map, $new_map, $content);
file_put_contents($file, $content);
echo "Patched Edit.vue form init.\n";
?>
