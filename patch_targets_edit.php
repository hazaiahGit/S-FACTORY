<?php
$file = "resources/js/Pages/Targets/Edit.vue";
$content = file_get_contents($file);

$old_props = <<<'EOT'
const props = defineProps({
    branches: Array,
    users: Array,
    products: Array,
    categories: Array,
});

const form = useForm({
    name: '',
    target_type: 'sales',
    target_value: '',
    measurement_unit: 'amount',
    period_type: 'monthly',
    start_date: '',
    end_date: '',
    branch_id: '',
    user_id: '',
    product_id: '',
    category_id: '',
    description: '',
});
EOT;

$new_props = <<<'EOT'
const props = defineProps({
    target: Object,
    branches: Array,
    users: Array,
    products: Array,
    categories: Array,
});

const form = useForm({
    name: props.target.name,
    target_type: props.target.target_type,
    target_value: props.target.target_value,
    measurement_unit: props.target.measurement_unit,
    period_type: props.target.period_type,
    start_date: props.target.start_date,
    end_date: props.target.end_date,
    branch_id: props.target.branch_id || '',
    user_id: props.target.user_id || '',
    product_id: props.target.product_id || '',
    category_id: props.target.category_id || '',
    description: props.target.description || '',
});
EOT;

$content = str_replace($old_props, $new_props, $content);

// Update title and route
$content = str_replace("Create New Target", "Edit Target", $content);
$content = str_replace("form.post(route('targets.store'))", "form.put(route('targets.update', props.target.id))", $content);
$content = str_replace("Create Target", "Save Changes", $content);

file_put_contents($file, $content);
echo "Patched Edit.vue.\n";
?>
