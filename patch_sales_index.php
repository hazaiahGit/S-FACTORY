<?php
$file = "resources/js/Pages/Sales/Index.vue";
$content = file_get_contents($file);

$tag_inject = <<<'EOT'
                            <div class="flex flex-col">
                                <Link :href="route('sales.show', sale.id)" class="text-base font-black text-amber-600 hover:text-amber-800 transition-colors">{{ sale.sale_number }}</Link>
                                <div class="text-xs text-slate-400 mt-0.5 font-medium flex items-center gap-2">
                                    {{ formatDate(sale.transaction_date) }}
                                    <span v-if="sale.items?.some(i => i.price_type === 'wholesale')" class="px-1.5 py-0.5 bg-purple-100 text-purple-700 rounded text-[9px] font-bold uppercase tracking-wider">Wholesale</span>
                                </div>
                            </div>
EOT;
$content = preg_replace('/<div class="flex flex-col">\s*<Link :href="route\(\'sales\.show\', sale\.id\)" class="text-base font-black text-amber-600 hover:text-amber-800 transition-colors">{{ sale\.sale_number }}<\/Link>\s*<div class="text-xs text-slate-400 mt-0\.5 font-medium">{{ formatDate\(sale\.transaction_date\) }}<\/div>\s*<\/div>/', $tag_inject, $content);

$table_tag_inject = <<<'EOT'
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <Link :href="route('sales.show', sale.id)" class="text-sm font-black text-amber-600 hover:text-amber-800 transition-colors">{{ sale.sale_number }}</Link>
                                        <div class="text-xs text-slate-400 mt-0.5 font-medium flex items-center gap-2">
                                            {{ formatDate(sale.transaction_date) }}
                                            <span v-if="sale.items?.some(i => i.price_type === 'wholesale')" class="px-1.5 py-0.5 bg-purple-100 text-purple-700 rounded text-[9px] font-bold uppercase tracking-wider">Wholesale</span>
                                        </div>
                                    </td>
EOT;
$content = preg_replace('/<td class="px-6 py-4 whitespace-nowrap">\s*<Link :href="route\(\'sales\.show\', sale\.id\)" class="text-sm font-black text-amber-600 hover:text-amber-800 transition-colors">{{ sale\.sale_number }}<\/Link>\s*<div class="text-xs text-slate-400 mt-0\.5 font-medium">{{ formatDate\(sale\.transaction_date\) }}<\/div>\s*<\/td>/', $table_tag_inject, $content);

file_put_contents($file, $content);
echo "Patched Index.vue.\n";
?>
