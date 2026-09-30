<?php
$files = [
    "resources/js/Pages/Sales/Create.vue",
    "resources/js/Pages/Sales/Edit.vue"
];

$card_price_inject = <<<'EOT'
                            <div class="mt-4 pt-4 border-t border-slate-100 w-full">
                                <span v-if="priceMode === 'wholesale'" class="text-[10px] font-bold text-amber-500 uppercase tracking-wider mb-0.5 block">Wholesale</span>
                                <span class="font-black text-slate-900 text-base sm:text-lg block truncate">{{ formatCurrency(priceMode === 'wholesale' ? product.wholesale_price : product.selling_price) }}</span>
                            </div>
EOT;

foreach ($files as $file) {
    $content = file_get_contents($file);
    
    // Replace the price display
    $content = preg_replace('/<div class="mt-4 pt-4 border-t border-slate-100 w-full">\s*<span class="font-black text-slate-900 text-base sm:text-lg block truncate">{{ formatCurrency\(product\.selling_price\) }}<\/span>\s*<\/div>/s', $card_price_inject, $content);
    
    file_put_contents($file, $content);
}
echo "Patched price UI files.\n";
?>
