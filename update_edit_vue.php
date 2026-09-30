<?php
$file = 'resources/js/Pages/Sales/Edit.vue';
$content = file_get_contents($file);

// Add sale prop
$content = preg_replace('/const props = defineProps\({/', "const props = defineProps({\n    sale: Object,", $content);

// Update form initial state
$formSetup = <<<'EOT'
const form = useForm({
    branch_id: props.sale.branch_id,
    customer_id: props.sale.customer_id || '',
    sale_type: props.sale.sale_type,
    status: props.sale.status,
    fulfillment_status: props.sale.fulfillment_status,
    transaction_date: props.sale.transaction_date ? props.sale.transaction_date.split('T')[0] : new Date().toISOString().split('T')[0],
    discount_percent: props.sale.discount_percent || 0,
    items: [],
});
EOT;
$content = preg_replace('/const form = useForm\({.*?}\);/s', $formSetup, $content);

// Add onMounted to populate items
$onMounted = <<<'EOT'
import { onMounted } from 'vue';

onMounted(() => {
    if (props.sale && props.sale.items) {
        props.sale.items.forEach(item => {
            cart.value.push({
                product: item.product || props.products.find(p => p.id === item.product_id),
                quantity: parseFloat(item.quantity),
                unit_price: parseFloat(item.unit_price),
                discount_amount: parseFloat(item.discount_amount || 0)
            });
        });
    }
});
EOT;
$content = str_replace("import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue';", "import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue';\n" . $onMounted, $content);

// Change submission route
$content = str_replace("form.post(route('sales.store'), {", "form.put(route('sales.update', props.sale.id), {", $content);

// Change Title
$content = str_replace("<template #header>Point of Sale</template>", "<template #header>Edit Sale {{ sale.sale_number }}</template>", $content);

file_put_contents($file, $content);
echo "Updated Edit.vue\n";
?>
