<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Download, Filter, Factory, Calendar, TrendingUp } from '@lucide/vue';

const props = defineProps({
    orders: Array,
    summary: Object,
    filters: Object,
});

const form = useForm({
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
    status: props.filters?.status || '',
});

const applyFilters = () => {
    form.get(route('reports.manufacturing'), {
        preserveState: true,
        preserveScroll: true,
    });
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
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
                    <span class="font-bold truncate text-slate-900">Manufacturing Report</span>
                </div>
                
                <a :href="route('reports.pdf', { type: 'manufacturing', start_date: form.start_date, end_date: form.end_date, status: form.status })" target="_blank" class="inline-flex items-center justify-center px-4 py-2 border border-slate-300 rounded-lg shadow-sm text-sm font-bold text-slate-700 bg-white hover:bg-slate-50 transition-colors">
                    <Download class="w-4 h-4 mr-2" />
                    Export PDF
                </a>
            </div>
        </template>

        <div class="max-w-7xl mx-auto space-y-6">
            
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
                    <div class="w-full sm:w-auto flex-1 max-w-xs">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Status</label>
                        <select v-model="form.status" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm font-medium text-slate-700">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <button type="submit" class="w-full sm:w-auto inline-flex items-center justify-center px-6 py-2 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors h-[38px]">
                        <Filter class="w-4 h-4 mr-2" />
                        Generate
                    </button>
                </form>
            </div>

            <!-- Summary Widgets -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 border-l-4 border-l-indigo-500">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Total Orders</p>
                    <p class="text-2xl font-black text-slate-900">{{ summary.total_orders }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 border-l-4 border-l-emerald-500">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Completed Orders</p>
                    <p class="text-2xl font-black text-emerald-600">{{ summary.completed_orders }}</p>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 border-l-4 border-l-amber-500">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Total Produced</p>
                    <p class="text-2xl font-black text-amber-600">{{ summary.total_produced }}</p>
                </div>
            </div>

            <!-- Orders List -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                    <h3 class="text-sm font-bold text-slate-800 flex items-center uppercase tracking-wider">
                        <Factory class="w-4 h-4 mr-2 text-indigo-500" />
                        Manufacturing Orders
                    </h3>
                </div>
                
                <div class="block">
                    <div v-for="order in orders" :key="order.id" class="p-4 sm:p-5 border-b border-slate-100 last:border-0 hover:bg-slate-50 transition-colors">
                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4">
                            <div>
                                <div class="flex items-center gap-3 mb-1">
                                    <span class="text-sm font-bold text-indigo-600">{{ order.order_number }}</span>
                                    <span :class="[
                                        'px-2 py-0.5 text-[10px] font-bold rounded uppercase tracking-wider',
                                        order.status === 'completed' ? 'bg-emerald-100 text-emerald-800' : (order.status === 'cancelled' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800')
                                    ]">{{ order.status }}</span>
                                </div>
                                <div class="text-xs font-medium text-slate-500 flex flex-wrap items-center gap-3">
                                    <span class="flex items-center"><Calendar class="w-3 h-3 mr-1" /> {{ formatDate(order.start_date) }}</span>
                                    <span v-if="order.product" class="text-slate-700 font-bold">Product: {{ order.product.name }}</span>
                                </div>
                            </div>
                            
                            <div class="flex gap-4 sm:gap-6 bg-slate-50 sm:bg-transparent p-3 sm:p-0 rounded-lg border sm:border-0 border-slate-100">
                                <div class="text-right">
                                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Planned Qty</span>
                                    <span class="text-sm font-black text-slate-900">{{ order.planned_quantity }}</span>
                                </div>
                                <div class="text-right">
                                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Actual Qty</span>
                                    <span :class="['text-sm font-bold', order.actual_quantity >= order.planned_quantity ? 'text-emerald-600' : 'text-slate-600']">
                                        {{ order.actual_quantity || 0 }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div v-if="orders.length === 0" class="p-12 text-center text-slate-400">
                        <TrendingUp class="mx-auto h-12 w-12 text-slate-300 mb-3" />
                        <p class="font-medium text-slate-600">No orders found for this period.</p>
                        <p class="text-sm mt-1">Try adjusting your filters.</p>
                    </div>
                </div>
            </div>
            
        </div>
    </AppLayout>
</template>
