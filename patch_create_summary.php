<?php
$file = "resources/js/Pages/Manufacturing/BOM/Create.vue";
$content = file_get_contents($file);

// Ensure computed is imported
if (strpos($content, "import { ref, computed } from 'vue';") === false) {
    $content = str_replace(
        "import { ref } from 'vue';",
        "import { ref, computed } from 'vue';",
        $content
    );
}

// Add computed properties
$computed_code = <<<'EOT'
const customItemDesc = ref('');
const customItemCost = ref(0);

const totalRecipeCost = computed(() => {
    return form.items.reduce((sum, item) => {
        return sum + Number(item.total_cost);
    }, 0);
});

const costPerUnit = computed(() => {
    const output = Number(form.expected_output);
    if (output <= 0) return 0;
    return totalRecipeCost.value / output;
});
EOT;

$content = str_replace(
    "const customItemDesc = ref('');\nconst customItemCost = ref(0);",
    $computed_code,
    $content
);

// Add UI summary
$ui_code = <<<'EOT'
                        <div v-if="form.items.length === 0" class="p-8 sm:p-12 text-center text-slate-400">
                            <List class="mx-auto h-12 w-12 text-slate-300 mb-3" />
                            <p class="text-sm font-medium text-slate-500">No materials or costs added.</p>
                            <p class="text-xs text-slate-400 mt-1">Use the controls above to build your recipe.</p>
                        </div>
                    </div>

                    <!-- Cost Summary -->
                    <div class="p-5 bg-amber-50/50 border-t border-amber-100 flex flex-col sm:flex-row justify-between items-center gap-4">
                        <div class="text-sm">
                            <span class="text-slate-500 font-bold uppercase tracking-wider text-xs">Total Recipe Cost:</span>
                            <span class="ml-2 font-black text-slate-800 text-lg">{{ new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(totalRecipeCost) }}</span>
                        </div>
                        <div class="text-sm">
                            <span class="text-slate-500 font-bold uppercase tracking-wider text-xs">Output:</span>
                            <span class="ml-2 font-bold text-slate-700">{{ form.expected_output || 0 }} Units</span>
                        </div>
                        <div class="text-sm bg-emerald-100 px-4 py-2 rounded-lg border border-emerald-200">
                            <span class="text-emerald-800 font-bold uppercase tracking-wider text-xs">Cost Per Unit:</span>
                            <span class="ml-2 font-black text-emerald-900 text-lg">{{ new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(costPerUnit) }}</span>
                        </div>
                    </div>

                    <div class="p-5 sm:p-6 bg-slate-50 border-t border-slate-100 flex justify-end">
EOT;

$content = str_replace(
    "                        <div v-if=\"form.items.length === 0\" class=\"p-8 sm:p-12 text-center text-slate-400\">\n                            <List class=\"mx-auto h-12 w-12 text-slate-300 mb-3\" />\n                            <p class=\"text-sm font-medium text-slate-500\">No materials or costs added.</p>\n                            <p class=\"text-xs text-slate-400 mt-1\">Use the controls above to build your recipe.</p>\n                        </div>\n                    </div>\n\n                    <div class=\"p-5 sm:p-6 bg-slate-50 border-t border-slate-100 flex justify-end\">",
    $ui_code,
    $content
);

file_put_contents($file, $content);
echo "Patched Create.vue summary.\n";
?>
