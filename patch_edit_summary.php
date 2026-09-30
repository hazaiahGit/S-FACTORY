<?php
$file = "resources/js/Pages/Manufacturing/BOM/Edit.vue";
$content = file_get_contents($file);

// Add computed properties for summary
$computed_code = <<<'EOT'
const hasProducts = computed(() => form.items.some(i => i.product_id !== null));

const totalRecipeCost = computed(() => {
    return form.items.reduce((sum, item) => {
        const qty = item.product_id !== null ? Number(item.quantity) : 1;
        const cost = Number(item.unit_cost);
        return sum + (qty * cost);
    }, 0);
});

const costPerUnit = computed(() => {
    const output = Number(form.expected_output);
    if (output <= 0) return 0;
    return totalRecipeCost.value / output;
});
EOT;

$content = str_replace(
    "const hasProducts = computed(() => form.items.some(i => i.product_id !== null));",
    $computed_code,
    $content
);

// Add UI summary below table
$ui_code = <<<'EOT'
                        </table>
                    </div>
                    <!-- Cost Summary -->
                    <div class="p-5 bg-amber-50/50 border-t border-amber-100 flex flex-col sm:flex-row justify-between items-center gap-4">
                        <div class="text-sm">
                            <span class="text-slate-500 font-bold uppercase tracking-wider text-xs">Total Recipe Cost:</span>
                            <span class="ml-2 font-black text-slate-800 text-lg">{{ new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(totalRecipeCost) }}</span>
                        </div>
                        <div class="text-sm">
                            <span class="text-slate-500 font-bold uppercase tracking-wider text-xs">Output:</span>
                            <span class="ml-2 font-bold text-slate-700">{{ form.expected_output }} Units</span>
                        </div>
                        <div class="text-sm bg-emerald-100 px-4 py-2 rounded-lg border border-emerald-200">
                            <span class="text-emerald-800 font-bold uppercase tracking-wider text-xs">Cost Per Unit:</span>
                            <span class="ml-2 font-black text-emerald-900 text-lg">{{ new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(costPerUnit) }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-2 border-t border-slate-200">
EOT;

$content = str_replace(
    "                        </table>\n                    </div>\n                </div>\n\n                <div class=\"flex justify-end pt-2 border-t border-slate-200\">",
    $ui_code,
    $content
);

file_put_contents($file, $content);
echo "Patched Edit.vue summary.\n";
?>
