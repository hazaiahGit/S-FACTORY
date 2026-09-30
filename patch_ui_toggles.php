<?php
$files = [
    "resources/js/Pages/Sales/Create.vue",
    "resources/js/Pages/Sales/Edit.vue"
];

$ui_inject = <<<'EOT'
                <!-- Search Bar -->
                <div class="p-5 border-b border-slate-100 bg-white z-10">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
                        <div class="flex bg-slate-100 p-1 rounded-xl w-full sm:w-auto">
                            <button @click="togglePriceMode('retail')" :class="['flex-1 px-4 py-2 rounded-lg text-sm font-bold transition-all', priceMode === 'retail' ? 'bg-white shadow-sm text-indigo-700' : 'text-slate-500 hover:text-slate-700']">Retail Mode</button>
                            <button @click="togglePriceMode('wholesale')" :class="['flex-1 px-4 py-2 rounded-lg text-sm font-bold transition-all', priceMode === 'wholesale' ? 'bg-indigo-600 shadow-sm text-white' : 'text-slate-500 hover:text-slate-700']">Wholesale Mode</button>
                        </div>
                    </div>
                    <div class="relative max-w-2xl">
EOT;

foreach ($files as $file) {
    $content = file_get_contents($file);
    
    // Inject the toggle above the search input
    $content = preg_replace('/<\!-- Search Bar -->\s*<div class="p-5 border-b border-slate-100 bg-white z-10">\s*<div class="relative max-w-2xl">/s', $ui_inject, $content);
    
    file_put_contents($file, $content);
}
echo "Patched both UI files.\n";
?>
