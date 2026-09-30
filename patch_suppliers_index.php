<?php
$file = "resources/js/Pages/Suppliers/Index.vue";
$content = file_get_contents($file);

// Add import for Trash2
$content = str_replace(
    "import { Truck, Plus, Search, Phone, Mail, MapPin, Building2 } from '@lucide/vue';",
    "import { Truck, Plus, Search, Phone, Mail, MapPin, Building2, Trash2 } from '@lucide/vue';",
    $content
);
if (strpos($content, 'Trash2') === false) {
    // try fallback
    $content = str_replace(
        "import { Truck",
        "import { Trash2, Truck",
        $content
    );
}

// Add delete method
$delete_method = <<<'EOT'
const deleteSupplier = (id) => {
    if (confirm('Are you sure you want to delete this supplier?')) {
        router.delete(route('suppliers.destroy', id), {
            preserveScroll: true
        });
    }
};

const formatCurrency = (value) => {
EOT;

$content = str_replace(
    "const formatCurrency = (value) => {",
    $delete_method,
    $content
);

// Add UI button
$old_ui = <<<'EOT'
                                    <div class="text-left sm:text-right">
                                        <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">We Owe Them</span>
                                        <span :class="['text-sm font-black', (supplier.purchases_sum_total_amount - supplier.payments_sum_amount) > 0 ? 'text-rose-600' : 'text-slate-600']">
                                            {{ formatCurrency(supplier.purchases_sum_total_amount - supplier.payments_sum_amount) }}
                                        </span>
                                    </div>
                                </div>
EOT;

$new_ui = <<<'EOT'
                                    <div class="text-left sm:text-right">
                                        <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">We Owe Them</span>
                                        <span :class="['text-sm font-black', (supplier.purchases_sum_total_amount - supplier.payments_sum_amount) > 0 ? 'text-rose-600' : 'text-slate-600']">
                                            {{ formatCurrency(supplier.purchases_sum_total_amount - supplier.payments_sum_amount) }}
                                        </span>
                                    </div>
                                    <div class="flex items-center ml-2 border-l border-slate-100 pl-4">
                                        <button @click="deleteSupplier(supplier.id)" class="text-rose-400 hover:text-rose-600 transition-colors p-2 hover:bg-rose-50 rounded-lg" title="Delete Supplier">
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>
EOT;

$content = str_replace($old_ui, $new_ui, $content);
file_put_contents($file, $content);
echo "Patched Suppliers/Index.vue.\n";
?>
