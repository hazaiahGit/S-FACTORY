<?php
$file = "resources/js/Pages/Customers/Index.vue";
$content = file_get_contents($file);

// Import Trash2
$content = str_replace(
    "import { Users, Plus, Search, Phone, Mail, MapPin } from '@lucide/vue';",
    "import { Users, Plus, Search, Phone, Mail, MapPin, Trash2 } from '@lucide/vue';",
    $content
);

// Add delete method
$delete_method = <<<'EOT'
const deleteCustomer = (id) => {
    if (confirm('Are you sure you want to delete this customer?')) {
        router.delete(route('customers.destroy', id), {
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

// Add delete button in the UI. We can place it at the far right.
$old_ui = <<<'EOT'
                                    <div class="text-left sm:text-right">
                                        <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Balance Due</span>
                                        <span :class="['text-sm font-black', (customer.sales_sum_total_amount - customer.payments_sum_amount) > 0 ? 'text-rose-600' : 'text-slate-600']">
                                            {{ formatCurrency(customer.sales_sum_total_amount - customer.payments_sum_amount) }}
                                        </span>
                                    </div>
                                </div>
EOT;

$new_ui = <<<'EOT'
                                    <div class="text-left sm:text-right">
                                        <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Balance Due</span>
                                        <span :class="['text-sm font-black', (customer.sales_sum_total_amount - customer.payments_sum_amount) > 0 ? 'text-rose-600' : 'text-slate-600']">
                                            {{ formatCurrency(customer.sales_sum_total_amount - customer.payments_sum_amount) }}
                                        </span>
                                    </div>
                                    <div class="flex items-center ml-2 border-l border-slate-100 pl-4">
                                        <button @click="deleteCustomer(customer.id)" class="text-rose-400 hover:text-rose-600 transition-colors p-2 hover:bg-rose-50 rounded-lg" title="Delete Customer">
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>
EOT;

$content = str_replace($old_ui, $new_ui, $content);
file_put_contents($file, $content);
echo "Patched Customers/Index.vue.\n";
?>
