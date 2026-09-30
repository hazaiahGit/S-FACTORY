<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, Box, Download, AlertTriangle } from '@lucide/vue';

const props = defineProps({
    products: Array,
    summary: Object,
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-TZ', { style: 'currency', currency: 'TZS', maximumFractionDigits: 0 }).format(value || 0);
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center text-lg sm:text-xl">
                    <Link :href="route('reports.index')" class="mr-3 sm:mr-4 text-slate-400 hover:text-slate-600 transition-colors">
                        <ArrowLeft class="w-5 h-5 sm:w-6 sm:h-6" />
                    </Link>
                    <span class="font-bold truncate text-slate-900">Inventory Report</span>
                </div>
                
                <a :href="route('reports.pdf', { type: 'inventory' })" target="_blank" class="inline-flex items-center justify-center px-4 py-2 border border-slate-300 rounded-lg shadow-sm text-sm font-bold text-slate-700 bg-white hover:bg-slate-50 transition-colors">
                    <Download class="w-4 h-4 mr-2" />
                    Export PDF
                </a>
            </div>
        </template>

        <div class="max-w-7xl mx-auto space-y-6">
            
            <!-- Summary Widgets -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 border-l-4 border-l-indigo-500">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Total Items</p>
                    <p class="text-2xl font-black text-slate-900">{{ summary.total_items }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 border-l-4 border-l-emerald-500">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Total Stock Value</p>
                    <p class="text-2xl font-black text-emerald-600">{{ formatCurrency(summary.total_stock_value) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 border-l-4 border-l-rose-500">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Low Stock Items</p>
                    <p class="text-2xl font-black text-rose-600">{{ summary.low_stock_items }}</p>
                </div>
            </div>

            <!-- Inventory List -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                    <h3 class="text-sm font-bold text-slate-800 flex items-center uppercase tracking-wider">
                        <Box class="w-4 h-4 mr-2 text-indigo-500" />
                        Current Stock
                    </h3>
                </div>
                
                <div class="block">
                    <div v-for="product in products" :key="product.id" class="p-4 sm:p-5 border-b border-slate-100 last:border-0 hover:bg-slate-50 transition-colors">
                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                            <div>
                                <div class="flex items-center gap-3 mb-1">
                                    <span class="text-sm font-bold text-slate-900">{{ product.name }}</span>
                                    <span v-if="product.sku" class="px-2 py-0.5 text-[10px] font-bold rounded bg-slate-100 text-slate-600 uppercase tracking-wider">{{ product.sku }}</span>
                                </div>
                                <div class="text-xs font-medium text-slate-500 flex flex-wrap items-center gap-3">
                                    <span v-if="product.category" class="text-slate-700">Category: {{ product.category.name }}</span>
                                    <span v-if="product.brand" class="text-slate-700">Brand: {{ product.brand.name }}</span>
                                </div>
                            </div>
                            
                            <div class="flex gap-4 sm:gap-6 bg-slate-50 sm:bg-transparent p-3 sm:p-0 rounded-lg border sm:border-0 border-slate-100">
                                <div class="text-right">
                                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Stock Qty</span>
                                    <div class="flex items-center justify-end gap-1">
                                        <AlertTriangle v-if="product.stock_sum_quantity <= (product.alert_quantity || 0)" class="w-3 h-3 text-rose-500" />
                                        <span :class="['text-sm font-black', product.stock_sum_quantity <= (product.alert_quantity || 0) ? 'text-rose-600' : 'text-slate-900']">
                                            {{ product.stock_sum_quantity || 0 }}
                                        </span>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Stock Value</span>
                                    <span class="text-sm font-bold text-emerald-600">
                                        {{ formatCurrency(product.stock_value) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div v-if="products.length === 0" class="p-12 text-center text-slate-400">
                        <Box class="mx-auto h-12 w-12 text-slate-300 mb-3" />
                        <p class="font-medium text-slate-600">No products found in inventory.</p>
                    </div>
                </div>
            </div>
            
        </div>
    </AppLayout>
</template>
