<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import { Search, Package, AlertTriangle, ArrowRightLeft, Plus } from '@lucide/vue';

const props = defineProps({
    stocks: Object,
    filters: Object,
});

const page = usePage();
const business = page.props.auth.business || {};

const search = ref(props.filters?.search || '');

// Custom debounce
const debounce = (fn, delay) => {
    let timeoutId;
    return (...args) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn(...args), delay);
    };
};

const performSearch = debounce(() => {
    router.get(
        route('inventory.index'),
        { search: search.value },
        { preserveState: true, preserveScroll: true, replace: true }
    );
}, 300);

watch(search, performSearch);

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-TZ', { style: 'currency', currency: business.currency || 'TZS', maximumFractionDigits: 0 }).format(value);
};

const formatNumber = (value) => {
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(value);
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center">
                <span class="font-bold">Inventory Levels</span>
            </div>
        </template>

        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Header Actions -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="relative w-full max-w-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <Search class="h-5 w-5 text-slate-400" />
                    </div>
                    <input v-model="search" type="text" class="block w-full pl-10 pr-3 py-2 border-slate-200 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" placeholder="Search by product name or SKU..." />
                </div>
                
                <div class="flex flex-wrap gap-2 w-full sm:w-auto">
                    <Link :href="route('inventory.movements')" class="inline-flex items-center justify-center px-4 py-2 border border-slate-300 rounded-lg shadow-sm text-sm font-bold text-slate-700 bg-white hover:bg-slate-50 focus:outline-none transition-colors flex-1 sm:flex-none">
                        <ArrowRightLeft class="w-4 h-4 mr-2 text-slate-400" />
                        Audit Trail
                    </Link>
                    <Link :href="route('purchases.create')" class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-bold text-slate-900 bg-amber-400 hover:bg-amber-500 focus:outline-none transition-colors flex-1 sm:flex-none">
                        <Plus class="w-4 h-4 mr-2" />
                        Receive Stock
                    </Link>
                </div>
            </div>

            <!-- Table Container -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <!-- Mobile Card View -->
                <div class="block md:hidden divide-y divide-slate-100">
                    <div v-for="stock in stocks.data" :key="stock.id" class="p-4 bg-white space-y-4">
                        <div class="flex justify-between items-start gap-2">
                            <div>
                                <div class="text-sm font-bold text-slate-900">{{ stock.product?.name }}</div>
                                <div class="text-xs text-slate-500">{{ stock.product?.sku }}</div>
                            </div>
                            <div>
                                <span v-if="Number(stock.quantity) <= 0" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-medium bg-rose-100 text-rose-800 whitespace-nowrap">
                                    Out of Stock
                                </span>
                                <span v-else-if="Number(stock.quantity) <= Number(stock.product?.reorder_level)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-medium bg-amber-100 text-amber-800 whitespace-nowrap">
                                    Low Stock
                                </span>
                                <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-medium bg-emerald-100 text-emerald-800 whitespace-nowrap">
                                    In Stock
                                </span>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-3">
                            <div class="bg-slate-50 p-2 rounded-lg text-center border border-slate-100">
                                <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">On Hand</span>
                                <span :class="['text-lg font-black', Number(stock.quantity) <= Number(stock.product?.reorder_level) ? 'text-rose-600' : 'text-slate-900']">
                                    {{ formatNumber(stock.quantity) }}
                                </span>
                            </div>
                            <div class="bg-slate-50 p-2 rounded-lg text-center border border-slate-100">
                                <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Reserved</span>
                                <span class="text-lg font-black text-amber-600">{{ formatNumber(stock.reserved_quantity) }}</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3 text-sm pt-2 border-t border-slate-100">
                            <div>
                                <span class="block text-xs text-slate-500 mb-0.5">Avg Cost (WAC)</span>
                                <span class="font-medium text-slate-700">{{ formatCurrency(stock.avg_cost) }}</span>
                            </div>
                            <div class="text-right">
                                <span class="block text-xs text-slate-500 mb-0.5">Total Value</span>
                                <span class="font-bold text-slate-900">{{ formatCurrency(stock.quantity * stock.avg_cost) }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div v-if="stocks.data.length === 0" class="p-8 text-center text-slate-400">
                        <Package class="mx-auto h-12 w-12 text-slate-300 mb-3" />
                        <p class="font-medium text-slate-600">No stock records found.</p>
                        <p class="text-sm mt-1">Start receiving inventory through Purchases.</p>
                    </div>
                </div>

                <!-- Desktop Table View -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Product</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Qty On Hand</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Reserved</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Avg Cost (WAC)</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Total Value</th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-100">
                            <tr v-for="stock in stocks.data" :key="stock.id" class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-slate-900">{{ stock.product?.name }}</div>
                                    <div class="text-xs text-slate-500">{{ stock.product?.sku }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <span :class="['text-sm font-bold', Number(stock.quantity) <= Number(stock.product?.reorder_level) ? 'text-rose-600' : 'text-slate-900']">
                                        {{ formatNumber(stock.quantity) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <span class="text-sm font-medium text-amber-600">{{ formatNumber(stock.reserved_quantity) }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-sm text-slate-700">{{ formatCurrency(stock.avg_cost) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right">
                                    <div class="text-sm font-bold text-slate-900">{{ formatCurrency(stock.quantity * stock.avg_cost) }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span v-if="Number(stock.quantity) <= 0" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-100 text-rose-800">
                                        Out of Stock
                                    </span>
                                    <span v-else-if="Number(stock.quantity) <= Number(stock.product?.reorder_level)" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                        Low Stock
                                    </span>
                                    <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                                        In Stock
                                    </span>
                                </td>
                            </tr>
                            <tr v-if="stocks.data.length === 0">
                                <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                    <Package class="mx-auto h-12 w-12 text-slate-300 mb-3" />
                                    <p class="font-medium text-slate-600">No stock records found.</p>
                                    <p class="text-sm mt-1">Start receiving inventory through Purchases.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div v-if="stocks.links && stocks.data.length > 0" class="px-6 py-4 border-t border-slate-100 bg-slate-50">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <span class="text-sm text-slate-500">Showing {{ stocks.from }} to {{ stocks.to }} of {{ stocks.total }}</span>
                        <div class="flex space-x-1">
                            <template v-for="(link, i) in stocks.links" :key="i">
                                <Link 
                                    v-if="link.url" 
                                    :href="link.url" 
                                    v-html="link.label"
                                    :class="[
                                        'px-3 py-1 text-sm border rounded-md transition-colors',
                                        link.active ? 'bg-amber-500 text-white border-amber-500 font-bold' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'
                                    ]"
                                />
                                <span v-else v-html="link.label" class="px-3 py-1 text-sm border border-slate-100 rounded-md text-slate-400 bg-slate-50 cursor-not-allowed"></span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
