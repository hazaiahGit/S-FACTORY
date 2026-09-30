<?php
$files = [
    'resources/js/Pages/Sales/Create.vue',
    'resources/js/Pages/Sales/Edit.vue'
];

foreach ($files as $file) {
    $content = file_get_contents($file);
    
    // 1. Update form definition
    if (strpos($file, 'Create.vue') !== false) {
        $content = preg_replace('/items: \[\],/', "discount_amount: 0,\n    items: [],", $content);
    } else {
        // Edit.vue
        $content = preg_replace('/discount_percent: props\.sale\.discount_percent \|\| 0,/', "discount_percent: props.sale.discount_percent || 0,\n    discount_amount: props.sale.discount_amount || 0,", $content);
    }

    // 2. Update totalDue computed property
    $totalDue_old = <<<'EOT'
const totalDue = computed(() => {
    return subtotal.value + taxAmount.value;
});
EOT;
    $totalDue_new = <<<'EOT'
const totalDue = computed(() => {
    return Math.max(0, subtotal.value - (parseFloat(form.discount_amount) || 0) + taxAmount.value);
});
EOT;
    $content = str_replace($totalDue_old, $totalDue_new, $content);

    // 3. Add Discount Amount field in Payment Modal
    $modal_search = <<<'EOT'
                            <div class="bg-slate-50 p-6 rounded-2xl border border-slate-100 mb-6 text-center">
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Total Amount Due</p>
                                <p class="text-4xl font-black text-slate-900">{{ formatCurrency(totalDue) }}</p>
                            </div>
EOT;
    $modal_replace = <<<'EOT'
                            <div class="bg-slate-50 p-6 rounded-2xl border border-slate-100 mb-6 text-center">
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Total Amount Due</p>
                                <p class="text-4xl font-black text-slate-900">{{ formatCurrency(totalDue) }}</p>
                            </div>
                            
                            <div class="mb-5 text-left">
                                <label class="block text-sm font-bold text-slate-700 mb-2">Discount Amount</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-slate-500 font-bold">-</span>
                                    </div>
                                    <input type="number" min="0" v-model="form.discount_amount" class="block w-full pl-8 py-3 text-lg border-slate-200 rounded-xl focus:ring-amber-500 focus:border-amber-500 font-bold text-slate-900 shadow-sm transition-all" placeholder="0" />
                                </div>
                            </div>
EOT;
    $content = str_replace($modal_search, $modal_replace, $content);
    
    file_put_contents($file, $content);
}
?>
