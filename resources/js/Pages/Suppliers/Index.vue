<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Trash2, Truck, Plus, Search, Phone, Mail, Building } from '@lucide/vue';

const props = defineProps({
    suppliers: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');

const debounce = (fn, delay) => {
    let timeoutId;
    return (...args) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn(...args), delay);
    };
};

const performSearch = debounce(() => {
    router.get(
        route('suppliers.index'),
        { search: search.value },
        { preserveState: true, preserveScroll: true, replace: true }
    );
}, 300);

watch(search, performSearch);

const deleteSupplier = (id) => {
    if (confirm('Are you sure you want to delete this supplier?')) {
        router.delete(route('suppliers.destroy', id), {
            preserveScroll: true
        });
    }
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-TZ', { style: 'currency', currency: 'TZS', maximumFractionDigits: 0 }).format(value || 0);
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center text-lg sm:text-xl font-bold">
                <Truck class="w-6 h-6 mr-3 text-indigo-500" />
                Suppliers & Vendors
            </div>
        </template>

        <div class="max-w-7xl mx-auto space-y-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="relative w-full max-w-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <Search class="h-5 w-5 text-slate-400" />
                    </div>
                    <input v-model="search" type="text" class="block w-full pl-10 pr-3 py-2 border-slate-200 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" placeholder="Search suppliers..." />
                </div>
                
                <Link :href="route('suppliers.create')" class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-bold text-slate-900 bg-amber-400 hover:bg-amber-500 focus:outline-none transition-colors w-full sm:w-auto">
                    <Plus class="w-4 h-4 mr-2" />
                    New Supplier
                </Link>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="block">
                    <div v-for="supplier in suppliers.data" :key="supplier.id" class="p-4 sm:p-5 border-b border-slate-100 last:border-0 hover:bg-slate-50 transition-colors">
                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                            <div>
                                <Link :href="route('suppliers.show', supplier.id)" class="text-base sm:text-lg font-bold text-indigo-600 hover:text-indigo-800 transition-colors flex items-center">
                                    <Building class="w-4 h-4 mr-2 text-slate-400" />
                                    {{ supplier.name }}
                                </Link>
                                <div class="flex flex-wrap items-center gap-3 mt-1.5 text-xs text-slate-500">
                                    <span v-if="supplier.contact_person" class="font-bold text-slate-700">Contact: {{ supplier.contact_person }}</span>
                                    <span v-if="supplier.phone" class="flex items-center"><Phone class="w-3 h-3 mr-1" /> {{ supplier.phone }}</span>
                                    <span v-if="supplier.email" class="flex items-center"><Mail class="w-3 h-3 mr-1" /> {{ supplier.email }}</span>
                                </div>
                            </div>
                            
                            <div class="flex flex-wrap gap-4 sm:gap-6 bg-slate-50 sm:bg-transparent p-3 sm:p-0 rounded-lg border sm:border-0 border-slate-100 w-full sm:w-auto">
                                <div class="text-left sm:text-right">
                                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Total Purchases</span>
                                    <span class="text-sm font-black text-slate-900">{{ formatCurrency(supplier.purchases_sum_total_amount) }}</span>
                                </div>
                                <div class="text-left sm:text-right">
                                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Total Paid</span>
                                    <span class="text-sm font-bold text-emerald-600">{{ formatCurrency(supplier.payments_sum_amount) }}</span>
                                </div>
                                <div class="text-left sm:text-right">
                                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">We Owe Them</span>
                                    <span :class="['text-sm font-black', (supplier.purchases_sum_total_amount - supplier.payments_sum_amount) > 0 ? 'text-rose-600' : 'text-slate-600']">
                                        {{ formatCurrency(supplier.purchases_sum_total_amount - supplier.payments_sum_amount) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div v-if="suppliers.data.length === 0" class="p-8 sm:p-12 text-center text-slate-400">
                        <Truck class="mx-auto h-12 w-12 text-slate-300 mb-3" />
                        <p class="font-medium text-slate-600">No suppliers found.</p>
                        <p class="text-sm mt-1">Add your first supplier to start receiving stock.</p>
                    </div>
                </div>
                
                <div v-if="suppliers.links && suppliers.data.length > 0" class="px-6 py-4 border-t border-slate-100 bg-slate-50">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <span class="text-sm text-slate-500">Showing {{ suppliers.from }} to {{ suppliers.to }} of {{ suppliers.total }}</span>
                        <div class="flex space-x-1">
                            <template v-for="(link, i) in suppliers.links" :key="i">
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
