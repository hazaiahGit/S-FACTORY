<?php
$file = "resources/js/Pages/Sales/Edit.vue";
$content = file_get_contents($file);

// Add priceMode ref and toggle logic
$ref_inject = <<<'EOT'
const searchQuery = ref('');
const priceMode = ref(props.sale.items.length > 0 ? props.sale.items[0].price_type : 'retail');

const togglePriceMode = (mode) => {
    if (form.items.length > 0 && priceMode.value !== mode) {
        if (confirm(`Switching to ${mode} mode will clear your current cart. Continue?`)) {
            form.items = [];
            priceMode.value = mode;
        }
    } else {
        priceMode.value = mode;
    }
};
EOT;
$content = preg_replace('/const searchQuery = ref\(\'\'\);/', $ref_inject, $content);

// Modify filteredProducts
$filtered_inject = <<<'EOT'
const filteredProducts = computed(() => {
    let filtered = props.products;
    
    if (priceMode.value === 'wholesale') {
        filtered = filtered.filter(p => parseFloat(p.wholesale_price) > 0);
    }
    
    if (!searchQuery.value) return filtered;
    
    const q = searchQuery.value.toLowerCase();
    return filtered.filter(p => 
        p.name.toLowerCase().includes(q) || 
        (p.sku && p.sku.toLowerCase().includes(q))
    );
});
EOT;
$content = preg_replace('/const filteredProducts = computed\(\(\) => \{.*?\}\);/s', $filtered_inject, $content);

// Modify addToCart
$addtocart_inject = <<<'EOT'
const addToCart = (product) => {
    const existing = form.items.find(i => i.product_id === product.id && i.price_type === priceMode.value);
    if (existing) {
        existing.quantity += 1;
    } else {
        form.items.push({
            id: null,
            product_id: product.id,
            name: product.name,
            unit_price: priceMode.value === 'wholesale' ? parseFloat(product.wholesale_price) : parseFloat(product.selling_price),
            quantity: 1,
            discount_amount: 0,
            tax_percent: 0,
            price_type: priceMode.value
        });
    }
};
EOT;
$content = preg_replace('/const addToCart = \(product\) => \{.*?\n\};\n/s', $addtocart_inject . "\n", $content);

// In the UI
$ui_inject = <<<'EOT'
                <!-- Search & Header -->
                <div class="px-5 py-4 border-b border-slate-200 bg-white z-10 shrink-0">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
                        <div class="flex bg-slate-100 p-1 rounded-xl w-full sm:w-auto">
                            <button @click="togglePriceMode('retail')" :class="['flex-1 px-4 py-2 rounded-lg text-sm font-bold transition-all', priceMode === 'retail' ? 'bg-white shadow-sm text-indigo-700' : 'text-slate-500 hover:text-slate-700']">Retail Mode</button>
                            <button @click="togglePriceMode('wholesale')" :class="['flex-1 px-4 py-2 rounded-lg text-sm font-bold transition-all', priceMode === 'wholesale' ? 'bg-indigo-600 shadow-sm text-white' : 'text-slate-500 hover:text-slate-700']">Wholesale Mode</button>
                        </div>
                    </div>
                    <div class="relative w-full">
EOT;
$content = preg_replace('/<\!-- Search & Header -->\s*<div class="px-5 py-4 border-b border-slate-200 bg-white z-10 shrink-0">\s*<div class="relative w-full">/', $ui_inject, $content);

// Price display
$card_price_inject = <<<'EOT'
                                    <div class="mt-auto pt-4 flex items-end justify-between relative z-10">
                                        <div class="flex flex-col">
                                            <span v-if="priceMode === 'wholesale'" class="text-[10px] font-bold text-amber-500 uppercase tracking-wider mb-0.5">Wholesale Price</span>
                                            <span class="text-base sm:text-lg font-black text-slate-800">{{ formatCurrency(priceMode === 'wholesale' ? product.wholesale_price : product.selling_price) }}</span>
                                        </div>
EOT;
$content = preg_replace('/<div class="mt-auto pt-4 flex items-end justify-between relative z-10">\s*<span class="text-base sm:text-lg font-black text-slate-800">{{ formatCurrency\(product.selling_price\) }}<\/span>/', $card_price_inject, $content);

file_put_contents($file, $content);
echo "Patched Edit.vue.\n";
?>
