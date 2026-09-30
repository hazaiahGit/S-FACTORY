<?php
$file = "resources/js/Pages/Stock/Movements.vue";
$content = file_get_contents($file);

// Add useForm
$content = str_replace(
    "import { Link, router, usePage } from '@inertiajs/vue3';",
    "import { Link, router, usePage, useForm } from '@inertiajs/vue3';",
    $content
);

// Add Trash2 icon
$content = str_replace(
    "import { Search, ArrowLeft, ArrowDownRight, ArrowUpRight, FileText } from '@lucide/vue';",
    "import { Search, ArrowLeft, ArrowDownRight, ArrowUpRight, FileText, Trash2 } from '@lucide/vue';",
    $content
);

// Add deleteMovement method
$method_inject = <<<'EOT'
const search = ref(props.filters?.search || '');

const deleteForm = useForm({});
const deleteMovement = (id) => {
    if (confirm('Are you sure you want to delete this audit trail? This will instantly reverse the stock change!')) {
        deleteForm.delete(route('inventory.movements.destroy', id), {
            preserveScroll: true
        });
    }
};
EOT;
$content = str_replace("const search = ref(props.filters?.search || '');", $method_inject, $content);

// Modify placeholder of search bar
$content = str_replace('placeholder="Search by product..."', 'placeholder="Search by product, REF, date, QTY, change..."', $content);

// Add empty <th> for Action
$content = str_replace(
    '<th scope="col" class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Unit Cost</th>',
    '<th scope="col" class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Unit Cost</th><th scope="col" class="px-4 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider w-10"></th>',
    $content
);

// Add <td> for Action
$td_inject = <<<'EOT'
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    {{ formatCurrency(mv.unit_cost) }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button v-if="$page.props.auth.user.roles?.includes('Super Admin')" @click="deleteMovement(mv.id)" class="text-rose-400 hover:text-rose-600 transition-colors p-1" title="Delete & Revert Stock">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </td>
                            </tr>
EOT;
$content = preg_replace('/<td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">\s*\{\{ formatCurrency\(mv\.unit_cost\) \}\}\s*<\/td>\s*<\/tr>/', $td_inject, $content);

// Also add a delete button in the mobile card view just in case
$mobile_inject = <<<'EOT'
                            <div class="text-right flex flex-col items-end gap-2">
                                <div>
                                    <span class="block">Unit Cost</span>
                                    <span class="font-bold text-slate-700">{{ formatCurrency(mv.unit_cost) }}</span>
                                </div>
                                <button v-if="$page.props.auth.user.roles?.includes('Super Admin')" @click="deleteMovement(mv.id)" class="text-rose-400 hover:text-rose-600 bg-rose-50 p-2 rounded-lg transition-colors flex items-center justify-center mt-2" title="Delete & Revert Stock">
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </div>
EOT;
$content = preg_replace('/<div class="text-right">\s*<span class="block">Unit Cost<\/span>\s*<span class="font-bold text-slate-700">\{\{ formatCurrency\(mv\.unit_cost\) \}\}<\/span>\s*<\/div>/', $mobile_inject, $content);

file_put_contents($file, $content);
echo "Patched Movements.vue.\n";
?>
