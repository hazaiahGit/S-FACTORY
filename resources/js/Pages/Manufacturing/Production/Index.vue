<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Factory, Plus, Filter, Search, Play, CheckCircle, Package } from '@lucide/vue';

const props = defineProps({
    orders: Object,
    filters: Object,
});

const page = usePage();
const business = page.props.auth.business || {};

const status = ref(props.filters?.status || '');

const performSearch = () => {
    router.get(
        route('production.index'),
        { status: status.value },
        { preserveState: true, preserveScroll: true, replace: true }
    );
};

watch(status, performSearch);

const formatNumber = (value) => {
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(value);
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center text-lg sm:text-xl font-bold">
                <Factory class="w-6 h-6 mr-3 text-amber-500" />
                Production Orders
            </div>
        </template>

        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Header Actions -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="relative w-full max-w-sm">
                    <select v-model="status" class="block w-full pl-3 pr-10 py-2 border-slate-200 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm text-slate-700 bg-white">
                        <option value="">All Statuses</option>
                        <option value="draft">Draft / Planned</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed / Approved</option>
                    </select>
                </div>
                
                <Link :href="route('production.create')" class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-bold text-slate-900 bg-amber-400 hover:bg-amber-500 focus:outline-none transition-colors w-full sm:w-auto">
                    <Plus class="w-4 h-4 mr-2" />
                    New Production Order
                </Link>
            </div>

            <!-- Card View (Used for both Desktop & Mobile) -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="block">
                    <div v-for="order in orders.data" :key="order.id" class="p-4 sm:p-5 border-b border-slate-100 last:border-0 relative hover:bg-slate-50 transition-colors">
                        
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <!-- <Link :href="route('production.show', order.id)" class="text-sm sm:text-lg font-bold text-indigo-600 hover:text-indigo-800 transition-colors">
                                    {{ order.production_number }}
                                </Link> -->
                                <span class="text-sm sm:text-lg font-bold text-slate-900">{{ order.production_number }}</span>
                                <div class="text-sm font-bold text-slate-900 mt-1">{{ order.product?.name }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">Recipe: {{ order.bom?.name }}</div>
                            </div>
                            <span :class="[
                                'px-2.5 py-1 text-xs font-bold uppercase tracking-wider rounded-full whitespace-nowrap ml-2',
                                order.status === 'completed' || order.status === 'approved' ? 'bg-emerald-100 text-emerald-800' :
                                order.status === 'in_progress' ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-800'
                            ]">
                                {{ order.status.replace('_', ' ') }}
                            </span>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 bg-slate-50 p-3 rounded-lg border border-slate-100">
                            <div>
                                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Planned Date</span>
                                <span class="text-sm font-bold text-slate-700">{{ formatDate(order.planned_date) }}</span>
                            </div>
                            <div>
                                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Target Qty</span>
                                <span class="text-sm font-black text-slate-900">{{ formatNumber(order.planned_quantity) }}</span>
                            </div>
                            <div v-if="order.status === 'completed' || order.status === 'approved'">
                                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Actual Qty</span>
                                <span class="text-sm font-black text-emerald-600">{{ formatNumber(order.actual_quantity) }}</span>
                            </div>
                            <div v-else>
                                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Action</span>
                                <span class="text-xs font-bold text-amber-600 flex items-center">
                                    <Play class="w-3.5 h-3.5 mr-1" v-if="order.status === 'planned' || order.status === 'draft'" />
                                    <CheckCircle class="w-3.5 h-3.5 mr-1" v-if="order.status === 'in_progress'" />
                                    {{ order.status === 'in_progress' ? 'Complete Now' : 'Start Production' }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div v-if="orders.data.length === 0" class="p-8 sm:p-12 text-center text-slate-400">
                        <Factory class="mx-auto h-12 w-12 text-slate-300 mb-3" />
                        <p class="font-medium text-slate-600">No production orders found.</p>
                        <p class="text-sm mt-1">Start by creating a new production order for your factory.</p>
                    </div>
                </div>
                
                <!-- Pagination -->
                <div v-if="orders.links && orders.data.length > 0" class="px-6 py-4 border-t border-slate-100 bg-slate-50">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <span class="text-sm text-slate-500">Showing {{ orders.from }} to {{ orders.to }} of {{ orders.total }}</span>
                        <div class="flex space-x-1">
                            <template v-for="(link, i) in orders.links" :key="i">
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
