<?php
$file = "resources/js/Pages/Manufacturing/BOM/Edit.vue";
$content = file_get_contents($file);

$select_inject = <<<'EOT'
                                    <td class="px-6 py-3">
                                        <select v-if="item.product_id !== null" v-model="item.product_id" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" required>
                                            <option value="" disabled>Select...</option>
                                            <option v-for="prod in materials" :key="prod.id" :value="prod.id">{{ prod.name }}</option>
                                        </select>
                                        <input v-else v-model="item.description" type="text" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm font-bold text-slate-700 bg-slate-50" placeholder="Custom Material/Cost" />
                                    </td>
EOT;

$content = preg_replace('/<td class="px-6 py-3">\s*<select v-model="item.product_id".*?<\/select>\s*<\/td>/s', $select_inject, $content);
file_put_contents($file, $content);
echo "Patched Edit.vue.\n";
?>
