<?php
$file = "resources/js/Pages/Sales/Show.vue";
$content = file_get_contents($file);

$tag_inject = <<<'EOT'
                        <h1 class="text-2xl font-bold text-slate-900 flex items-center flex-wrap gap-2">
                            Sale #{{ sale.sale_number }}
                            <span v-if="sale.items?.some(i => i.price_type === 'wholesale')" class="px-2 py-1 bg-purple-100 text-purple-700 rounded-lg text-xs font-bold uppercase tracking-wider ml-2">Wholesale</span>
EOT;

$content = preg_replace('/<h1 class="text-2xl font-bold text-slate-900 flex items-center flex-wrap gap-2">\s*Sale #\{\{ sale\.sale_number \}\}/', $tag_inject, $content);

file_put_contents($file, $content);
echo "Patched Show.vue.\n";
?>
