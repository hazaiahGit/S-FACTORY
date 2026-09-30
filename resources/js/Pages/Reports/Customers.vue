<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, Download, Users, TrendingUp } from '@lucide/vue';

const props = defineProps({
    customers: Array,
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
                    <span class="font-bold truncate text-slate-900">Customers Report</span>
                </div>
                
                <a :href="route('reports.pdf', { type: 'customers' })" target="_blank" class="inline-flex items-center justify-center px-4 py-2 border border-slate-300 rounded-lg shadow-sm text-sm font-bold text-slate-700 bg-white hover:bg-slate-50 transition-colors">
                    <Download class="w-4 h-4 mr-2" />
                    Export PDF
                </a>
            </div>
        </template>

        <div class="max-w-7xl mx-auto space-y-6">
            
            <!-- Summary Widgets -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 border-l-4 border-l-indigo-500">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Total Customers</p>
                    <p class="text-2xl font-black text-slate-900">{{ summary.total_customers }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 border-l-4 border-l-emerald-500">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Total Revenue</p>
                    <p class="text-2xl font-black text-emerald-600">{{ formatCurrency(summary.total_revenue) }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 border-l-4 border-l-rose-500">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Total Debt</p>
                    <p class="text-2xl font-black text-rose-600">{{ formatCurrency(summary.total_debt) }}</p>
                </div>
            </div>

            <!-- Customers List -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                    <h3 class="text-sm font-bold text-slate-800 flex items-center uppercase tracking-wider">
                        <Users class="w-4 h-4 mr-2 text-indigo-500" />
                        Customers List
                    </h3>
                </div>
                
                <div class="block">
                    <div v-for="customer in customers" :key="customer.id" class="p-4 sm:p-5 border-b border-slate-100 last:border-0 hover:bg-slate-50 transition-colors">
                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4">
                            <div>
                                <div class="flex items-center gap-3 mb-1">
                                    <span class="text-sm font-bold text-indigo-600">{{ customer.name }}</span>
                                </div>
                                <div class="text-xs font-medium text-slate-500 flex flex-wrap items-center gap-3">
                                    <span v-if="customer.phone" class="text-slate-700 font-bold">Phone: {{ customer.phone }}</span>
                                    <span v-else class="text-slate-400">No phone</span>
                                </div>
                            </div>
                            
                            <div class="flex gap-4 sm:gap-6 bg-slate-50 sm:bg-transparent p-3 sm:p-0 rounded-lg border sm:border-0 border-slate-100">
                                <div class="text-right">
                                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Revenue</span>
                                    <span class="text-sm font-black text-slate-900">{{ formatCurrency(customer.total_revenue) }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Paid</span>
                                    <span class="text-sm font-black text-emerald-600">{{ formatCurrency(customer.total_paid) }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Debt</span>
                                    <span :class="['text-sm font-bold', customer.debt > 0 ? 'text-rose-600' : 'text-slate-400']">
                                        {{ formatCurrency(customer.debt) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div v-if="customers.length === 0" class="p-12 text-center text-slate-400">
                        <TrendingUp class="mx-auto h-12 w-12 text-slate-300 mb-3" />
                        <p class="font-medium text-slate-600">No customers found.</p>
                    </div>
                </div>
            </div>
            
        </div>
    </AppLayout>
</template>
