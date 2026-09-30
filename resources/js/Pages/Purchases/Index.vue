<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { Search, Plus, Package, FileText, Calendar, Building, DollarSign, Edit, Eye, Filter } from '@lucide/vue';
import { ref, watch } from 'vue';

const debounce = (fn, delay) => {
    let timeoutId;
    return (...args) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn(...args), delay);
    };
};

const props = defineProps({
    purchases: Object,
    filters: Object,
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');

watch([search, status], debounce(([newSearch, newStatus]) => {
    router.get(
        route('purchases.index'),
        { search: newSearch, status: newStatus },
        { preserveState: true, preserveScroll: true, replace: true }
    );
}, 300));

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-TZ', { style: 'currency', currency: 'TZS', maximumFractionDigits: 0 }).format(value || 0);
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
};
</script>

<template>
    <AppLayout title="Purchases & Receiving">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            
            <!-- Header Actions -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Purchases & Receiving</h1>
                    <p class="text-sm text-slate-500 mt-1">Manage purchase orders and stock receiving</p>
                </div>
                <div class="flex w-full sm:w-auto">
                    <Link :href="route('purchases.create')" class="flex-1 sm:flex-none inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-bold text-slate-900 bg-amber-400 hover:bg-amber-500 transition-colors">
                        <Plus class="h-4 w-4 mr-2" />
                        New Purchase
                    </Link>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 mb-6 flex flex-col sm:flex-row gap-4">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <Search class="h-4 w-4 text-slate-400" />
                    </div>
                    <input v-model="search" type="text" placeholder="Search PO number or reference..." class="pl-10 w-full border-slate-300 rounded-lg focus:ring-amber-500 focus:border-amber-500 text-sm" />
                </div>
                <div class="w-full sm:w-48">
                    <select v-model="status" class="w-full border-slate-300 rounded-lg focus:ring-amber-500 focus:border-amber-500 text-sm">
                        <option value="">All Statuses</option>
                        <option value="draft">Draft</option>
                        <option value="ordered">Ordered</option>
                        <option value="partial">Partial</option>
                        <option value="received">Received</option>
                        <option value="returned">Returned</option>
                    </select>
                </div>
            </div>

            <!-- Responsive Card Grid -->
            <div v-if="purchases.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <div v-for="purchase in purchases.data" :key="purchase.id" class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden flex flex-col hover:shadow-md transition-shadow">
                    
                    <!-- Header -->
                    <div class="p-4 border-b border-slate-100 bg-slate-50 flex justify-between items-start">
                        <div>
                            <div class="font-bold text-slate-900 text-lg flex items-center gap-1.5">
                                <FileText class="h-4 w-4 text-indigo-500" />
                                {{ purchase.purchase_number }}
                            </div>
                            <div class="text-xs text-slate-500 mt-0.5 flex items-center gap-1">
                                <Calendar class="h-3 w-3" />
                                {{ formatDate(purchase.transaction_date) }}
                            </div>
                        </div>
                        <span :class="[
                            'px-2.5 py-1 inline-flex text-[10px] uppercase font-bold rounded-lg',
                            purchase.status === 'received' ? 'bg-emerald-100 text-emerald-800' : 
                            purchase.status === 'ordered' ? 'bg-blue-100 text-blue-800' : 'bg-slate-200 text-slate-800'
                        ]">
                            {{ purchase.status }}
                        </span>
                    </div>
                    
                    <!-- Content -->
                    <div class="p-4 flex-1 flex flex-col">
                        
                        <div class="mb-4">
                            <p class="text-xs text-slate-500 font-bold uppercase tracking-wider mb-1">Supplier</p>
                            <p class="text-sm font-semibold text-slate-900 flex items-center gap-1.5">
                                <Building class="h-4 w-4 text-slate-400" />
                                {{ purchase.supplier?.name || 'Walk-in Vendor' }}
                            </p>
                        </div>
                        
                        <!-- Amounts & Payment -->
                        <div class="mt-auto grid grid-cols-2 gap-3 mb-4">
                            <div class="bg-slate-50 rounded-lg p-2.5 border border-slate-100">
                                <p class="text-xs text-slate-500 mb-0.5">Total Amount</p>
                                <p class="text-sm font-bold text-slate-900">{{ formatCurrency(purchase.total_amount) }}</p>
                            </div>
                            <div class="bg-slate-50 rounded-lg p-2.5 border border-slate-100">
                                <p class="text-xs text-slate-500 mb-0.5">Payment</p>
                                <span :class="[
                                    'inline-flex text-xs font-bold rounded',
                                    purchase.payment_status === 'paid' ? 'text-emerald-600' : 
                                    purchase.payment_status === 'partial' ? 'text-amber-600' : 'text-rose-600'
                                ]">
                                    {{ purchase.payment_status.toUpperCase() }}
                                </span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="grid grid-cols-2 gap-2 pt-4 border-t border-slate-100 mt-auto">
                            <Link :href="route('purchases.show', purchase.id)" class="flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                                <Eye class="h-4 w-4 text-slate-500" /> View
                            </Link>
                            <Link v-if="$can('edit purchases')" :href="route('purchases.edit', purchase.id)" class="flex items-center justify-center gap-2 px-3 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                                <Edit class="h-4 w-4 text-slate-500" /> Edit
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="bg-white rounded-xl shadow-sm border border-slate-200 p-12 text-center">
                <Package class="mx-auto h-12 w-12 text-slate-300 mb-4" />
                <h3 class="text-lg font-bold text-slate-900">No purchases found</h3>
                <p class="text-slate-500 mt-1 mb-6">Start by creating a new purchase order.</p>
                <Link :href="route('purchases.create')" class="inline-flex items-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-bold text-slate-900 bg-amber-400 hover:bg-amber-500">
                    <Plus class="h-4 w-4 mr-2" /> New Purchase
                </Link>
            </div>

            <!-- Pagination -->
            <div v-if="purchases.links && purchases.links.length > 3" class="mt-6 flex justify-center">
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <template v-for="(link, i) in purchases.links" :key="i">
                        <Link 
                            v-if="link.url"
                            :href="link.url"
                            v-html="link.label"
                            :class="[
                                link.active ? 'z-10 bg-amber-50 border-amber-500 text-amber-700' : 'bg-white border-slate-300 text-slate-500 hover:bg-slate-50',
                                'relative inline-flex items-center px-4 py-2 border text-sm font-medium',
                                i === 0 ? 'rounded-l-md' : '',
                                i === purchases.links.length - 1 ? 'rounded-r-md' : ''
                            ]"
                        />
                        <span v-else v-html="link.label" class="relative inline-flex items-center px-4 py-2 border border-slate-300 bg-white text-sm font-medium text-slate-300 cursor-not-allowed" :class="[i === 0 ? 'rounded-l-md' : '', i === purchases.links.length - 1 ? 'rounded-r-md' : '']"></span>
                    </template>
                </nav>
            </div>
            
        </div>
    </AppLayout>
</template>

