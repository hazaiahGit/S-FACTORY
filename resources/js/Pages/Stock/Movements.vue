<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router, usePage, useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Search, ArrowLeft, ArrowDownRight, ArrowUpRight, FileText, Trash2 } from '@lucide/vue';

const props = defineProps({
    movements: Object,
    filters: Object,
});

const page = usePage();
const business = page.props.auth.business || {};

const search = ref(props.filters?.search || '');

const deleteForm = useForm({});
const deleteMovement = (id) => {
    if (confirm('Are you sure you want to delete this audit trail? This will instantly reverse the stock change!')) {
        deleteForm.delete(route('inventory.movements.destroy', id), {
            preserveScroll: true
        });
    }
};

const debounce = (fn, delay) => {
    let timeoutId;
    return (...args) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn(...args), delay);
    };
};

const performSearch = debounce(() => {
    router.get(
        route('inventory.movements'),
        { search: search.value },
        { preserveState: true, preserveScroll: true, replace: true }
    );
}, 300);

watch(search, performSearch);

const formatCurrency = (value) => {
    if (!value) return '-';
    return new Intl.NumberFormat('en-TZ', { style: 'currency', currency: business.currency || 'TZS', maximumFractionDigits: 0 }).format(value);
};

const formatNumber = (value) => {
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(value);
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center text-lg sm:text-xl">
                <Link :href="route('inventory.index')" class="mr-3 sm:mr-4 text-slate-400 hover:text-slate-600 transition-colors">
                    <ArrowLeft class="w-5 h-5 sm:w-6 sm:h-6" />
                </Link>
                <span class="font-bold truncate">Stock Movements (Audit Trail)</span>
            </div>
        </template>

        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Header Actions -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="relative w-full max-w-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <Search class="h-5 w-5 text-slate-400" />
                    </div>
                    <input v-model="search" type="text" class="block w-full pl-10 pr-3 py-2 border-slate-200 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" placeholder="Search by product, REF, date, QTY, change..." />
                </div>
            </div>

            <!-- Table Container -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <!-- Mobile View -->
                <div class="block md:hidden">
                    <div v-for="mv in movements.data" :key="mv.id" class="p-4 border-b border-slate-100 last:border-0 relative">
                        <div class="flex justify-between items-start mb-2">
                            <div class="pr-2">
                                <div class="text-sm font-bold text-slate-900">{{ mv.product?.name }}</div>
                                <div class="text-xs text-slate-500">
                                    <span v-if="mv.product?.sku">{{ mv.product?.sku }} &bull; </span>
                                    {{ formatDate(mv.created_at) }}
                                </div>
                            </div>
                            <span :class="[
                                'px-2 py-1 text-[10px] font-bold uppercase rounded-full whitespace-nowrap',
                                Number(mv.quantity_change) > 0 ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'
                            ]">
                                {{ mv.movement_type.replace('_', ' ') }}
                            </span>
                        </div>
                        
                        <div class="flex items-center justify-between bg-slate-50 p-3 rounded-lg border border-slate-100 mb-2 mt-3">
                            <div class="text-center">
                                <div class="text-[10px] text-slate-400 font-bold uppercase mb-1">Before</div>
                                <div class="font-bold text-slate-600">{{ formatNumber(mv.quantity_before) }}</div>
                            </div>
                            
                            <div class="flex flex-col items-center justify-center px-4">
                                <ArrowDownRight v-if="Number(mv.quantity_change) > 0" class="w-5 h-5 text-emerald-500 mb-1" />
                                <ArrowUpRight v-else class="w-5 h-5 text-rose-500 mb-1" />
                                <div :class="['font-black', Number(mv.quantity_change) > 0 ? 'text-emerald-600' : 'text-rose-600']">
                                    {{ Number(mv.quantity_change) > 0 ? '+' : '' }}{{ formatNumber(mv.quantity_change) }}
                                </div>
                            </div>
                            
                            <div class="text-center">
                                <div class="text-[10px] text-slate-400 font-bold uppercase mb-1">After</div>
                                <div class="font-bold text-slate-900">{{ formatNumber(mv.quantity_after) }}</div>
                            </div>
                        </div>

                        <div class="flex justify-between items-end text-xs text-slate-500 mt-2">
                            <div>
                                <span v-if="mv.reference_number" class="block">Ref: <span class="font-medium text-slate-700">{{ mv.reference_number }}</span></span>
                                <span v-if="mv.user" class="block">By: <span class="font-medium text-slate-700">{{ mv.user.name }}</span></span>
                            </div>
                                                        <div class="text-right flex flex-col items-end gap-2">
                                <div>
                                    <span class="block">Unit Cost</span>
                                    <span class="font-bold text-slate-700">{{ formatCurrency(mv.unit_cost) }}</span>
                                </div>
                                <button v-if="$page.props.auth.roles?.includes('Super Admin')" @click="deleteMovement(mv.id)" class="text-rose-400 hover:text-rose-600 bg-rose-50 p-2 rounded-lg transition-colors flex items-center justify-center mt-2" title="Delete & Revert Stock">
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div v-if="movements.data.length === 0" class="p-8 text-center text-slate-400">
                        <FileText class="mx-auto h-12 w-12 text-slate-300 mb-3" />
                        <p class="font-medium text-slate-600">No stock movements found.</p>
                    </div>
                </div>

                <!-- Desktop Table View -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Date</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Product</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Type / Ref</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Qty Before</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Change</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Qty After</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Unit Cost</th><th scope="col" class="px-4 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider w-10"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-100">
                            <tr v-for="mv in movements.data" :key="mv.id" class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                    {{ formatDate(mv.created_at) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-bold text-slate-900">{{ mv.product?.name }}</div>
                                    <div class="text-xs text-slate-500">{{ mv.product?.sku }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-800 uppercase tracking-wider">
                                        {{ mv.movement_type.replace('_', ' ') }}
                                    </span>
                                    <div v-if="mv.reference_number" class="text-xs font-medium text-slate-500 mt-1">{{ mv.reference_number }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-slate-600">
                                    {{ formatNumber(mv.quantity_before) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right font-bold">
                                    <span :class="Number(mv.quantity_change) > 0 ? 'text-emerald-600' : 'text-rose-600'">
                                        {{ Number(mv.quantity_change) > 0 ? '+' : '' }}{{ formatNumber(mv.quantity_change) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-slate-900">
                                    {{ formatNumber(mv.quantity_after) }}
                                </td>
                                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-slate-600">
                                    {{ formatCurrency(mv.unit_cost) }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-right text-sm">
                                    <button v-if="$page.props.auth.roles?.includes('Super Admin')" @click="deleteMovement(mv.id)" class="text-rose-400 hover:text-rose-600 transition-colors p-1" title="Delete & Revert Stock">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="movements.data.length === 0">
                                <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                                    <FileText class="mx-auto h-12 w-12 text-slate-300 mb-3" />
                                    <p class="font-medium text-slate-600">No stock movements found.</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div v-if="movements.links && movements.data.length > 0" class="px-6 py-4 border-t border-slate-100 bg-slate-50">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <span class="text-sm text-slate-500">Showing {{ movements.from }} to {{ movements.to }} of {{ movements.total }}</span>
                        <div class="flex space-x-1">
                            <template v-for="(link, i) in movements.links" :key="i">
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
