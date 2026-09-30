<?php
$file = "resources/js/Pages/Manufacturing/BOM/Edit.vue";
$content = file_get_contents($file);

$old_tds = <<<'EOT'
                                    <td class="px-6 py-3">
                                        <FormattedNumberInput v-model="item.quantity" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" required />
                                    </td>
                                    <td class="px-6 py-3">
                                        <FormattedNumberInput v-model="item.unit_cost" @input="item.total_cost = item.unit_cost * item.quantity" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" />
                                    </td>
                                    <td class="px-6 py-3 text-right font-bold text-slate-700">
                                        {{ new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(item.quantity * item.unit_cost) }}
                                    </td>
EOT;

$new_tds = <<<'EOT'
                                    <td class="px-6 py-3">
                                        <FormattedNumberInput v-if="item.product_id !== null" v-model="item.quantity" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" required />
                                        <div v-else class="text-slate-400 text-sm font-medium text-center">-</div>
                                    </td>
                                    <td class="px-6 py-3">
                                        <FormattedNumberInput v-if="item.product_id !== null" v-model="item.unit_cost" @input="item.total_cost = item.unit_cost * item.quantity" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" />
                                        <div v-else class="text-slate-400 text-sm font-medium text-center">-</div>
                                    </td>
                                    <td class="px-6 py-3 text-right font-bold text-slate-700">
                                        <span v-if="item.product_id !== null">{{ new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(item.quantity * item.unit_cost) }}</span>
                                        <FormattedNumberInput v-else v-model="item.unit_cost" @input="item.total_cost = item.unit_cost; item.quantity = 1" class="block w-full text-right border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm font-bold text-slate-900 bg-amber-50" placeholder="Total Cost" />
                                    </td>
EOT;

$content = str_replace($old_tds, $new_tds, $content);
file_put_contents($file, $content);
echo "Patched Edit.vue table tds.\n";
?>
