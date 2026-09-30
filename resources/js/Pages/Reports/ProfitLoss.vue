<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Download, Filter } from '@lucide/vue';

const props = defineProps({
    summary: Object,
    filters: Object,
});

const form = useForm({
    start_date: props.filters.start_date,
    end_date: props.filters.end_date,
});

const applyFilters = () => {
    form.get(route('reports.profit-loss'), {
        preserveState: true,
        preserveScroll: true,
    });
};

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
                    <span class="font-bold truncate text-slate-900">Profit & Loss Report</span>
                </div>
                
                <a :href="route('reports.pdf', { type: 'profit_loss', start_date: form.start_date, end_date: form.end_date })" target="_blank" class="inline-flex items-center justify-center px-4 py-2 border border-slate-300 rounded-lg shadow-sm text-sm font-bold text-slate-700 bg-white hover:bg-slate-50 transition-colors">
                    <Download class="w-4 h-4 mr-2" />
                    Export PDF
                </a>
            </div>
        </template>

        <div class="max-w-4xl mx-auto space-y-6">
            
            <!-- Filters -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5">
                <form @submit.prevent="applyFilters" class="flex flex-col sm:flex-row gap-4 items-end">
                    <div class="w-full sm:w-auto flex-1 max-w-xs">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Start Date</label>
                        <input type="date" v-model="form.start_date" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm font-medium text-slate-700" />
                    </div>
                    <div class="w-full sm:w-auto flex-1 max-w-xs">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">End Date</label>
                        <input type="date" v-model="form.end_date" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm font-medium text-slate-700" />
                    </div>
                    <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-2 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors h-[38px]">
                        <Filter class="w-4 h-4 mr-2" />
                        Generate
                    </button>
                </form>
            </div>

            <!-- Statement -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-6 border-b border-slate-100 bg-slate-50 text-center">
                    <h3 class="text-xl font-black text-slate-800 uppercase tracking-wider">Statement of Comprehensive Income</h3>
                    <p class="text-sm font-medium text-slate-500 mt-2">
                        For the period: <span class="font-bold text-slate-700">{{ form.start_date ? new Date(form.start_date).toLocaleDateString() : 'Beginning' }}</span> to <span class="font-bold text-slate-700">{{ form.end_date ? new Date(form.end_date).toLocaleDateString() : 'Present' }}</span>
                    </p>
                </div>
                
                <div class="p-8 space-y-8">
                    <!-- Income Section -->
                    <div>
                        <h4 class="text-sm font-bold text-indigo-600 uppercase tracking-wider border-b-2 border-indigo-100 pb-2 mb-4">Income</h4>
                        <div class="space-y-1">
                            <div class="flex justify-between items-center py-2 px-4 hover:bg-slate-50 rounded-lg transition-colors">
                                <span class="text-sm font-medium text-slate-600">Total Revenue</span>
                                <span class="text-sm font-bold text-slate-900">{{ formatCurrency(summary.total_revenue) }}</span>
                            </div>
                            <div class="flex justify-between items-center py-2 px-4 hover:bg-slate-50 rounded-lg transition-colors">
                                <span class="text-sm font-medium text-slate-600">Cost of Goods Sold (COGS)</span>
                                <span class="text-sm font-bold text-rose-600">({{ formatCurrency(summary.cogs) }})</span>
                            </div>
                            <div class="flex justify-between items-center py-3 px-4 mt-4 bg-slate-100 rounded-lg border border-slate-200 shadow-sm">
                                <span class="text-sm font-black text-slate-800 uppercase tracking-wider">Gross Profit</span>
                                <span class="text-base font-black text-slate-900">{{ formatCurrency(summary.gross_profit) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Expenses Section -->
                    <div>
                        <h4 class="text-sm font-bold text-rose-600 uppercase tracking-wider border-b-2 border-rose-100 pb-2 mb-4">Operating Expenses</h4>
                        <div class="space-y-1">
                            <div class="flex justify-between items-center py-2 px-4 hover:bg-slate-50 rounded-lg transition-colors">
                                <span class="text-sm font-medium text-slate-600">Total Operating Expenses</span>
                                <span class="text-sm font-bold text-rose-600">({{ formatCurrency(summary.total_expenses) }})</span>
                            </div>
                        </div>
                    </div>

                    <!-- Net Profit -->
                    <div class="pt-6 border-t-2 border-slate-100">
                        <div :class="['flex justify-between items-center py-5 px-6 rounded-2xl border-2 shadow-sm', summary.net_profit >= 0 ? 'bg-emerald-50 border-emerald-200' : 'bg-rose-50 border-rose-200']">
                            <span :class="['text-lg font-black uppercase tracking-wider', summary.net_profit >= 0 ? 'text-emerald-800' : 'text-rose-800']">Net Profit / (Loss)</span>
                            <span :class="['text-3xl font-black', summary.net_profit >= 0 ? 'text-emerald-600' : 'text-rose-600']">{{ formatCurrency(summary.net_profit) }}</span>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
    </AppLayout>
</template>
