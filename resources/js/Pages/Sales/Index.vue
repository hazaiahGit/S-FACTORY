<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { 
    Search, 
    Plus, 
    Filter, 
    MoreVertical, 
    Eye,
    ShoppingCart,
    Download,
    ArrowRightLeft, Edit, Trash2
} from '@lucide/vue';
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue';

const props = defineProps({
    sales: Object,
    filters: Object,
});

const date = ref(props.filters?.date || '');
const status = ref(props.filters?.status || '');

// Debounced search/filter
let filterTimeout;
watch([date, status], ([newDate, newStatus]) => {
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => {
        router.get(route('sales.index'), { 
            date: newDate, 
            status: newStatus 
        }, { preserveState: true, replace: true });
    }, 300);
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-TZ', { style: 'currency', currency: 'TZS', maximumFractionDigits: 0 }).format(value);
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
};

const getUnifiedStatus = (sale) => {
    if (sale.status === 'cancelled') return { label: 'Cancelled', color: 'bg-slate-100 text-slate-800' };
    if (sale.status === 'draft') return { label: 'Draft', color: 'bg-slate-100 text-slate-800' };
    if (sale.status === 'on_hold') return { label: 'On Hold', color: 'bg-amber-100 text-amber-800' };
    if (sale.status === 'credit') return { label: 'Credit', color: 'bg-purple-100 text-purple-800' };
    if (sale.status === 'invoiced') return { label: 'Invoiced', color: 'bg-indigo-100 text-indigo-800' };
    if (sale.status === 'paid') return { label: 'Paid', color: 'bg-emerald-100 text-emerald-800' };
    
    // If it's a confirmed sale, the payment status is the most important thing
    if (sale.payment_status === 'paid') return { label: 'Paid', color: 'bg-emerald-100 text-emerald-800' };
    if (sale.payment_status === 'partial') return { label: 'Partial', color: 'bg-amber-100 text-amber-800' };
    if (sale.payment_status === 'unpaid') return { label: 'Unpaid', color: 'bg-rose-100 text-rose-800' };
    if (sale.payment_status === 'overpaid') return { label: 'Overpaid', color: 'bg-blue-100 text-blue-800' };
    
    return { label: sale.status, color: 'bg-sky-100 text-sky-800' };
};
const deleteSale = (id) => {
    if (confirm("Are you sure you want to delete this sale? This action will reverse stock movements.")) {
        router.delete(route('sales.destroy', id), {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <AppLayout>
        <template #header>Sales History</template>

        <div class="space-y-6">
            <!-- Header Actions -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-4 sm:space-y-0">
                <div class="flex-1 w-full sm:w-auto flex flex-col sm:flex-row space-y-3 sm:space-y-0 sm:space-x-3">
                    <input 
                        v-model="date"
                        type="date" 
                        class="block w-full sm:w-48 pl-3 pr-3 py-2 border border-slate-300 rounded-md leading-5 bg-white focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500 sm:text-sm" 
                    />
                    
                    <select v-model="status" class="block w-full sm:w-48 pl-3 pr-10 py-2 text-base border-slate-300 focus:outline-none focus:ring-amber-500 focus:border-amber-500 sm:text-sm rounded-md">
                        <option value="">All Statuses</option>
                        <optgroup label="Payment Status">
                            <option value="paid">Paid</option>
                            <option value="partial">Partial</option>
                            <option value="unpaid">Unpaid</option>
                        </optgroup>
                        <optgroup label="Sale Status">
                            <option value="confirmed">Confirmed</option>
                            <option value="credit">Credit</option>
                            <option value="invoiced">Invoiced</option>
                            <option value="on_hold">On Hold</option>
                            <option value="draft">Draft</option>
                            <option value="cancelled">Cancelled</option>
                        </optgroup>
                    </select>
                </div>
                
                <div class="flex space-x-3 w-full sm:w-auto">
                    <button class="inline-flex items-center px-4 py-2 border border-slate-300 shadow-sm text-sm font-medium rounded-md text-slate-700 bg-white hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
                        <Download class="-ml-1 mr-2 h-4 w-4 text-slate-500" />
                        Export
                    </button>
                    <Link :href="route('sales.create')" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500">
                        <Plus class="-ml-1 mr-2 h-4 w-4" />
                        New Sale (POS)
                    </Link>
                </div>
            </div>

            <!-- Mobile Cards View -->
            <div class="block md:hidden space-y-4">
                <div v-if="sales.data.length === 0" class="bg-white p-10 rounded-xl border border-slate-200 text-center shadow-sm">
                    <ShoppingCart class="mx-auto h-12 w-12 text-slate-300 mb-3" />
                    <p class="text-base font-bold text-slate-900">No sales found</p>
                    <p class="text-sm mt-1 text-slate-500">Adjust your filters or record a new sale.</p>
                </div>

                <div v-else v-for="sale in sales.data" :key="'mobile-'+sale.id" class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200 hover:border-amber-300 transition-all flex flex-col gap-4 relative">
                    <div class="flex justify-between items-start">
                        <div>
                            <Link :href="route('sales.show', sale.id)" class="text-base font-black text-amber-600 hover:text-amber-800 transition-colors">{{ sale.sale_number }}</Link>
                            <div class="text-xs text-slate-400 mt-0.5 font-medium">{{ formatDate(sale.transaction_date) }}</div>
                        </div>
                        <span :class="['px-2.5 py-1 text-[10px] leading-4 font-black rounded-lg uppercase tracking-wide', getUnifiedStatus(sale).color]">
                            {{ getUnifiedStatus(sale).label }}
                        </span>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center flex-shrink-0">
                            <span class="font-bold text-lg">{{ (sale.customer ? sale.customer.name.charAt(0) : 'W').toUpperCase() }}</span>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-bold text-slate-900">{{ sale.customer ? sale.customer.name : 'Walk-in Customer' }}</div>
                            <div v-if="sale.customer" class="text-xs text-slate-500">{{ sale.customer.phone }}</div>
                        </div>
                        <div class="text-xs font-bold text-slate-400 bg-slate-100 px-2 py-1 rounded-md">
                            {{ sale.items?.reduce((sum, i) => sum + parseFloat(i.quantity), 0) || 0 }} items
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 pt-4 border-t border-slate-100">
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Total</p>
                            <p class="text-lg font-black text-slate-900">{{ formatCurrency(sale.total_amount) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Balance</p>
                            <p :class="['text-lg font-black', sale.balance_amount > 0 ? 'text-rose-600' : 'text-emerald-600']">{{ formatCurrency(sale.balance_amount) }}</p>
                        </div>
                    </div>
                    
                    <div v-if="$can('edit sales') || $can('delete sales')" class="flex gap-2 pt-3 border-t border-slate-100 mt-1 justify-end">
                        <Link v-if="['draft', 'on_hold', 'invoiced'].includes(sale.status) && $can('edit sales')" :href="route('sales.edit', sale.id)" class="px-3 py-1.5 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-md transition-colors flex items-center">
                            <Edit class="w-3.5 h-3.5 mr-1" /> Edit
                        </Link>
                        <button v-if="$can('delete sales')" @click="deleteSale(sale.id)" class="px-3 py-1.5 text-xs font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-md transition-colors flex items-center">
                            <Trash2 class="w-3.5 h-3.5 mr-1" /> Delete
                        </button>
                    </div>
                </div>
            </div>

            <!-- Desktop Table View -->
            <div class="hidden md:block bg-white shadow-sm rounded-2xl border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-[11px] font-bold text-slate-400 uppercase tracking-wider">Sale No. / Date</th>
                                <th scope="col" class="px-6 py-4 text-left text-[11px] font-bold text-slate-400 uppercase tracking-wider">Customer</th>
                                <th scope="col" class="px-6 py-4 text-left text-[11px] font-bold text-slate-400 uppercase tracking-wider">Items</th>
                                <th scope="col" class="px-6 py-4 text-right text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Amount</th>
                                <th scope="col" class="px-6 py-4 text-right text-[11px] font-bold text-slate-400 uppercase tracking-wider">Balance Due</th>
                                <th scope="col" class="px-6 py-4 text-center text-[11px] font-bold text-slate-400 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-100">
                            <tr v-for="sale in sales.data" :key="sale.id" class="hover:bg-amber-50/50 transition-colors group">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <Link :href="route('sales.show', sale.id)" class="text-sm font-black text-amber-600 group-hover:text-amber-700 transition-colors">{{ sale.sale_number }}</Link>
                                    <div class="text-xs text-slate-400 font-medium mt-0.5">{{ formatDate(sale.transaction_date) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-slate-800">{{ sale.customer ? sale.customer.name : 'Walk-in Customer' }}</div>
                                    <div v-if="sale.customer" class="text-xs text-slate-400">{{ sale.customer.phone }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-bold bg-slate-100 text-slate-600">
                                        {{ sale.items?.reduce((sum, i) => sum + parseFloat(i.quantity), 0) || 0 }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-sm font-black text-slate-800">{{ formatCurrency(sale.total_amount) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div :class="['text-sm font-black', sale.balance_amount > 0 ? 'text-rose-600' : 'text-emerald-600']">{{ formatCurrency(sale.balance_amount) }}</div>
                                </td>
                                                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span :class="['px-3 py-1 inline-flex text-[11px] leading-4 font-black rounded-lg uppercase tracking-wide', getUnifiedStatus(sale).color]">
                                        {{ getUnifiedStatus(sale).label }}
                                    </span>
                                </td>                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex items-center justify-center gap-2" v-if="$can('edit sales') || $can('delete sales')">
                                        <Link v-if="['draft', 'on_hold', 'invoiced'].includes(sale.status) && $can('edit sales')" :href="route('sales.edit', sale.id)" class="text-slate-400 hover:text-indigo-600 transition-colors p-1" title="Edit">
                                            <Edit class="w-4 h-4" />
                                        </Link>
                                        <button v-if="$can('delete sales')" @click="deleteSale(sale.id)" class="text-slate-400 hover:text-rose-600 transition-colors p-1" title="Delete">
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                    <span v-else class="text-slate-300 text-xs">-</span>
                                </td>
                            </tr>
                            <tr v-if="sales.data.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                                    <ShoppingCart class="mx-auto h-12 w-12 text-slate-200 mb-3" />
                                    <p class="text-base font-bold text-slate-600">No sales found</p>
                                    <p class="text-sm mt-1">Adjust your filters or record a new sale.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div v-if="sales.links && sales.links.length > 3" class="px-6 py-3 border-t border-slate-200 bg-slate-50 flex items-center justify-between">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <Link :href="sales.prev_page_url" class="relative inline-flex items-center px-4 py-2 border border-slate-300 text-sm font-medium rounded-md text-slate-700 bg-white hover:bg-slate-50" :class="{'opacity-50 pointer-events-none': !sales.prev_page_url}">Previous</Link>
                        <Link :href="sales.next_page_url" class="ml-3 relative inline-flex items-center px-4 py-2 border border-slate-300 text-sm font-medium rounded-md text-slate-700 bg-white hover:bg-slate-50" :class="{'opacity-50 pointer-events-none': !sales.next_page_url}">Next</Link>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm text-slate-700">
                                Showing <span class="font-medium">{{ sales.from }}</span> to <span class="font-medium">{{ sales.to }}</span> of <span class="font-medium">{{ sales.total }}</span> results
                            </p>
                        </div>
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <template v-for="(link, i) in sales.links" :key="i">
                                    <Link 
                                        v-if="link.url"
                                        :href="link.url"
                                        v-html="link.label"
                                        :class="[
                                            link.active ? 'z-10 bg-amber-50 border-amber-500 text-amber-600' : 'bg-white border-slate-300 text-slate-500 hover:bg-slate-50',
                                            'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                                            i === 0 ? 'rounded-l-md' : '',
                                            i === sales.links.length - 1 ? 'rounded-r-md' : ''
                                        ]"
                                    />
                                    <span v-else v-html="link.label" class="relative inline-flex items-center px-4 py-2 border border-slate-300 bg-white text-sm font-medium text-slate-300 cursor-not-allowed"></span>
                                </template>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

