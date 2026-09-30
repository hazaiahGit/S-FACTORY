<?php
$file = "resources/js/Pages/Manufacturing/BOM/Edit.vue";
$content = file_get_contents($file);

// Add computed import if missing, but watch and ref are there. Let's add computed.
$content = str_replace(
    "import { ref, watch } from 'vue';",
    "import { ref, watch, computed } from 'vue';",
    $content
);

// Add computed property
$computed_inject = <<<'EOT'
const addCustomMaterial = () => {
    form.items.push({
        product_id: null,
        description: 'New Custom Cost',
        quantity: 1,
        unit_cost: 0,
        total_cost: 0,
        item_type: 'material'
    });
};

const hasProducts = computed(() => form.items.some(i => i.product_id !== null));
EOT;

$content = str_replace(
    "const addCustomMaterial = () => {\n    form.items.push({\n        product_id: null,\n        description: 'New Custom Cost',\n        quantity: 1,\n        unit_cost: 0,\n        total_cost: 0,\n        item_type: 'material'\n    });\n};",
    $computed_inject,
    $content
);

// Update THs
$old_ths = <<<'EOT'
                                    <th scope="col" class="px-6 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Material</th>
                                    <th scope="col" class="px-6 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider w-32">Qty</th>
                                    <th scope="col" class="px-6 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider w-32">Unit Cost</th>
                                    <th scope="col" class="px-6 py-3 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider w-32">Total</th>
                                    <th scope="col" class="px-6 py-3 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider w-16"></th>
EOT;

$new_ths = <<<'EOT'
                                    <th scope="col" class="px-6 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Material</th>
                                    <th v-if="hasProducts" scope="col" class="px-6 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider w-32">Qty</th>
                                    <th v-if="hasProducts" scope="col" class="px-6 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider w-32">Unit Cost</th>
                                    <th scope="col" class="px-6 py-3 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider w-32">Total</th>
                                    <th scope="col" class="px-6 py-3 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider w-16"></th>
EOT;

$content = str_replace($old_ths, $new_ths, $content);

// Update TDs
$old_tds = <<<'EOT'
                                    <td class="px-6 py-3">
                                        <FormattedNumberInput v-if="item.product_id !== null" v-model="item.quantity" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" required />
                                        <div v-else class="text-slate-400 text-sm font-medium text-center">-</div>
                                    </td>
                                    <td class="px-6 py-3">
                                        <FormattedNumberInput v-if="item.product_id !== null" v-model="item.unit_cost" @input="item.total_cost = item.unit_cost * item.quantity" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" />
                                        <div v-else class="text-slate-400 text-sm font-medium text-center">-</div>
                                    </td>
EOT;

$new_tds = <<<'EOT'
                                    <td v-if="hasProducts" class="px-6 py-3">
                                        <FormattedNumberInput v-if="item.product_id !== null" v-model="item.quantity" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" required />
                                        <div v-else class="text-slate-400 text-sm font-medium text-center">-</div>
                                    </td>
                                    <td v-if="hasProducts" class="px-6 py-3">
                                        <FormattedNumberInput v-if="item.product_id !== null" v-model="item.unit_cost" @input="item.total_cost = item.unit_cost * item.quantity" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" />
                                        <div v-else class="text-slate-400 text-sm font-medium text-center">-</div>
                                    </td>
EOT;

$content = str_replace($old_tds, $new_tds, $content);

file_put_contents($file, $content);
echo "Patched Edit.vue table to hide columns dynamically.\n";
?>
