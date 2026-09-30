<?php
$file = 'resources/js/Pages/Sales/Index.vue';
$content = file_get_contents($file);

// Mobile View
$oldMobile = <<<EOT
                    <div v-if="\$can('delete sales')" class="flex gap-2 pt-3 border-t border-slate-100 mt-1 justify-end">
                        <button @click="deleteSale(sale.id)" class="px-3 py-1.5 text-xs font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-md transition-colors flex items-center">
                            <Trash2 class="w-3.5 h-3.5 mr-1" /> Delete
                        </button>
                    </div>
EOT;

$newMobile = <<<EOT
                    <div v-if="\$can('edit sales') || \$can('delete sales')" class="flex gap-2 pt-3 border-t border-slate-100 mt-1 justify-end">
                        <Link v-if="['draft', 'on_hold', 'invoiced'].includes(sale.status) && \$can('edit sales')" :href="route('sales.edit', sale.id)" class="px-3 py-1.5 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-md transition-colors flex items-center">
                            <Edit class="w-3.5 h-3.5 mr-1" /> Edit
                        </Link>
                        <button v-if="\$can('delete sales')" @click="deleteSale(sale.id)" class="px-3 py-1.5 text-xs font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-md transition-colors flex items-center">
                            <Trash2 class="w-3.5 h-3.5 mr-1" /> Delete
                        </button>
                    </div>
EOT;
$content = str_replace($oldMobile, $newMobile, $content);

// Desktop View
$oldDesktop = <<<EOT
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center gap-2" v-if="\$can('delete sales')">
                                        <button @click="deleteSale(sale.id)" class="text-slate-400 hover:text-rose-600 transition-colors p-1" title="Delete">
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                    <span v-else class="text-slate-300 text-xs">-</span>
                                </td>
EOT;

$newDesktop = <<<EOT
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center gap-2" v-if="\$can('edit sales') || \$can('delete sales')">
                                        <Link v-if="['draft', 'on_hold', 'invoiced'].includes(sale.status) && \$can('edit sales')" :href="route('sales.edit', sale.id)" class="text-slate-400 hover:text-indigo-600 transition-colors p-1" title="Edit">
                                            <Edit class="w-4 h-4" />
                                        </Link>
                                        <button v-if="\$can('delete sales')" @click="deleteSale(sale.id)" class="text-slate-400 hover:text-rose-600 transition-colors p-1" title="Delete">
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                    <span v-else class="text-slate-300 text-xs">-</span>
                                </td>
EOT;
$content = str_replace($oldDesktop, $newDesktop, $content);

file_put_contents($file, $content);
echo "Updated Index.vue\n";
?>
