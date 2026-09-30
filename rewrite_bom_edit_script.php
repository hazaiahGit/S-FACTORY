<?php
$file = "resources/js/Pages/Manufacturing/BOM/Edit.vue";
$content = file_get_contents($file);

$script_block = <<<'EOT'
<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { ArrowLeft, Save, BookOpen, Plus, Trash2, List } from '@lucide/vue';

const props = defineProps({
    products: Array,
    materials: Array,
    units: Array,
    bom: Object,
});

const form = useForm({
    name: props.bom.name,
    product_id: props.bom.product_id,
    expected_output: props.bom.expected_output,
    output_unit_id: props.bom.output_unit_id,
    description: props.bom.description,
    is_active: props.bom.is_active,
    items: props.bom.items.map(i => ({
        product_id: i.product_id,
        quantity: i.quantity,
        unit_id: i.unit_id,
        unit_cost: i.unit_cost,
        total_cost: i.total_cost,
        is_optional: i.is_optional,
    })),
});

const submit = () => {
    form.put(route('bom.update', props.bom.id));
};

const addMaterial = () => {
    form.items.push({
        product_id: '',
        quantity: 1,
        unit_id: '',
        unit_cost: 0,
        total_cost: 0,
        is_optional: false,
    });
};

const removeMaterial = (index) => {
    form.items.splice(index, 1);
};
</script>
EOT;

$content = preg_replace('/<script setup>.*?<\/script>/s', $script_block, $content);
file_put_contents($file, $content);
?>
