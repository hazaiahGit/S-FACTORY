<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import { ArrowRightLeft, Plus, MapPin, Truck } from '@lucide/vue';

const props = defineProps({
    transfers: Object,
});

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center text-lg sm:text-xl font-bold">
                <ArrowRightLeft class="w-6 h-6 mr-3 text-amber-500" />
                Stock Transfers
            </div>
        </template>

        <div class="max-w-7xl mx-auto space-y-6">
            <div class="flex justify-end mb-4">
                <Link :href="route('stock-transfers.create')" class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-bold text-slate-900 bg-amber-400 hover:bg-amber-500 transition-colors">
                    <Plus class="w-4 h-4 mr-2" />
                    New Transfer Request
                </Link>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="block">
                    <div v-for="transfer in transfers.data" :key="transfer.id" class="p-4 sm:p-5 border-b border-slate-100 last:border-0 hover:bg-slate-50 transition-colors">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <Link :href="route('stock-transfers.show', transfer.id)" class="text-sm sm:text-lg font-bold text-indigo-600 hover:text-indigo-800 transition-colors">
                                    {{ transfer.transfer_number }}
                                </Link>
                                <div class="text-xs text-slate-500 mt-1">Requested: {{ formatDate(transfer.requested_date) }}</div>
                            </div>
                            <span :class="[
                                'px-2.5 py-1 text-[10px] sm:text-xs font-bold uppercase tracking-wider rounded-full whitespace-nowrap ml-2',
                                transfer.status === 'received' ? 'bg-emerald-100 text-emerald-800' :
                                transfer.status === 'dispatched' ? 'bg-blue-100 text-blue-800' :
                                transfer.status === 'approved' ? 'bg-indigo-100 text-indigo-800' : 'bg-amber-100 text-amber-800'
                            ]">
                                {{ transfer.status }}
                            </span>
                        </div>

                        <div class="flex flex-col sm:flex-row items-center justify-between bg-slate-50 p-4 rounded-lg border border-slate-100 relative">
                            <div class="text-center sm:text-left flex-1 w-full">
                                <span class="block text-[10px] font-bold text-slate-400 uppercase mb-1">From Branch</span>
                                <span class="text-sm font-bold text-slate-900 flex items-center justify-center sm:justify-start">
                                    <MapPin class="w-3.5 h-3.5 text-slate-400 mr-1" />
                                    {{ transfer.from_branch?.name }}
                                </span>
                            </div>
                            
                            <div class="flex items-center justify-center py-2 sm:py-0 w-full sm:w-auto px-4">
                                <div class="h-px bg-slate-300 w-full sm:w-16 hidden sm:block"></div>
                                <Truck class="w-5 h-5 text-indigo-400 mx-2 flex-shrink-0" />
                                <div class="h-px bg-slate-300 w-full sm:w-16 hidden sm:block"></div>
                            </div>
                            
                            <div class="text-center sm:text-right flex-1 w-full">
                                <span class="block text-[10px] font-bold text-slate-400 uppercase mb-1">To Branch</span>
                                <span class="text-sm font-bold text-slate-900 flex items-center justify-center sm:justify-end">
                                    <MapPin class="w-3.5 h-3.5 text-emerald-500 mr-1" />
                                    {{ transfer.to_branch?.name }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div v-if="transfers.data.length === 0" class="p-8 sm:p-12 text-center text-slate-400">
                        <ArrowRightLeft class="mx-auto h-12 w-12 text-slate-300 mb-3" />
                        <p class="font-medium text-slate-600">No stock transfers found.</p>
                        <p class="text-sm mt-1">Start by requesting a transfer from another branch.</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
