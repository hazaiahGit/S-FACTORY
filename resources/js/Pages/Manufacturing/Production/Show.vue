<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { ArrowLeft, Play, CheckCircle, Factory, Edit, Printer, Info, Check } from '@lucide/vue';

const props = defineProps({
    order: Object,
});

const isCompleting = ref(false);

const completeForm = useForm({
    actual_quantity: props.order.planned_quantity,
    waste_quantity: props.order.waste_quantity || 0,
    materials: props.order.materials.map(m => ({
        id: m.id,
        actual_quantity: m.planned_quantity
    }))
});

const startProduction = () => {
    if (confirm("Are you sure you want to start production? This will mark the order as in-progress.")) {
        router.post(route('production.start', props.order.id));
    }
};

const completeProduction = () => {
    isCompleting.value = true;
};

const submitComplete = () => {
    completeForm.post(route('production.complete', props.order.id), {
        onSuccess: () => {
            isCompleting.value = false;
        }
    });
};

const approveProduction = () => {
    if (confirm("Approve this completed production?")) {
        router.post(route('production.approve', props.order.id));
    }
};

const formatNumber = (value) => {
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(value);
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-TZ', { style: 'currency', currency: 'TZS', maximumFractionDigits: 0 }).format(value);
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
                    <Link :href="route('production.index')" class="mr-3 sm:mr-4 text-slate-400 hover:text-slate-600 transition-colors">
                        <ArrowLeft class="w-5 h-5 sm:w-6 sm:h-6" />
                    </Link>
                    <span class="font-bold truncate">Production: {{ order.production_number }}</span>
                    
                    <span :class="[
                        'px-2.5 py-1 text-xs font-bold uppercase tracking-wider rounded-full whitespace-nowrap ml-4',
                        order.status === 'completed' || order.status === 'approved' ? 'bg-emerald-100 text-emerald-800' :
                        order.status === 'in_progress' ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-800'
                    ]">
                        {{ order.status.replace('_', ' ') }}
                    </span>
                </div>
                
                <div class="flex gap-2">
                    <button v-if="order.status === 'planned' || order.status === 'draft'" @click="startProduction" class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors w-full sm:w-auto">
                        <Play class="w-4 h-4 mr-2" />
                        Start Production
                    </button>
                    <button v-if="order.status === 'in_progress' && !isCompleting" @click="completeProduction" class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-bold text-slate-900 bg-amber-400 hover:bg-amber-500 transition-colors w-full sm:w-auto">
                        <CheckCircle class="w-4 h-4 mr-2" />
                        Complete Order
                    </button>
                    <!-- <button v-if="order.status === 'completed'" @click="approveProduction" class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 transition-colors w-full sm:w-auto">
                        <Check class="w-4 h-4 mr-2" />
                        Approve Order
                    </button> -->
                </div>
            </div>
        </template>

        <div class="max-w-7xl mx-auto space-y-6">
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Overview Card -->
                <div class="lg:col-span-1 bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden h-fit">
                    <div class="p-5 border-b border-slate-100 bg-slate-50">
                        <h3 class="text-sm font-bold text-slate-800 flex items-center">
                            <Info class="w-4 h-4 mr-2 text-indigo-500" />
                            Order Summary
                        </h3>
                    </div>
                    <div class="p-5 space-y-4">
                        <div>
                            <span class="block text-xs font-bold text-slate-400 uppercase">Product</span>
                            <span class="text-base font-bold text-slate-900">{{ order.product?.name }}</span>
                        </div>
                        <div>
                            <span class="block text-xs font-bold text-slate-400 uppercase">Recipe (BOM)</span>
                            <span class="text-sm font-medium text-slate-700">{{ order.bom?.name }}</span>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4 pt-2 border-t border-slate-50">
                            <div>
                                <span class="block text-xs font-bold text-slate-400 uppercase">Target Qty</span>
                                <span class="text-lg font-black text-slate-900">{{ formatNumber(order.planned_quantity) }}</span>
                            </div>
                            <div v-if="order.status === 'completed' || order.status === 'approved'">
                                <span class="block text-xs font-bold text-slate-400 uppercase">Produced Qty</span>
                                <span class="text-lg font-black text-emerald-600">{{ formatNumber(order.actual_quantity) }}</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 pt-2 border-t border-slate-50">
                            <div>
                                <span class="block text-xs font-bold text-slate-400 uppercase">Planned Date</span>
                                <span class="text-sm font-bold text-slate-700">{{ formatDate(order.planned_date) }}</span>
                            </div>
                            <div v-if="order.start_date">
                                <span class="block text-xs font-bold text-slate-400 uppercase">Started</span>
                                <span class="text-sm font-bold text-slate-700">{{ formatDate(order.start_date) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Materials & Completion -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <div v-if="isCompleting" class="bg-amber-50 rounded-xl shadow-sm border border-amber-200 overflow-hidden">
                        <div class="p-5 border-b border-amber-100 bg-amber-100/50">
                            <h3 class="text-sm font-bold text-amber-900 flex items-center">
                                <CheckCircle class="w-4 h-4 mr-2 text-amber-600" />
                                Record Final Production & Materials Used
                            </h3>
                        </div>
                        <form @submit.prevent="submitComplete" class="p-5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 mb-1 uppercase tracking-wider">Final Output Qty Produced</label>
                                    <FormattedNumberInput v-model="completeForm.actual_quantity" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 font-black text-lg text-emerald-700" required />
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 mb-1 uppercase tracking-wider">Waste / Rejected Qty</label>
                                    <FormattedNumberInput v-model="completeForm.waste_quantity" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 font-bold text-rose-600" />
                                </div>
                            </div>
                            
                            <h4 class="text-xs font-bold text-slate-500 uppercase mb-3">Actual Materials Consumed</h4>
                            <div class="space-y-3 mb-6">
                                <div v-for="(mat, idx) in order.materials" :key="mat.id" class="flex flex-col sm:flex-row sm:items-center justify-between p-3 bg-white border border-slate-200 rounded-lg">
                                    <div class="mb-2 sm:mb-0">
                                        <div class="text-sm font-bold text-slate-900">{{ mat.product?.name || mat.description }}</div>
                                        <div class="text-xs text-slate-500">Planned: {{ formatNumber(mat.planned_quantity) }} ({{ formatCurrency(mat.unit_cost) }} / unit)</div>
                                    </div>
                                    <div class="w-full sm:w-48">
                                        <FormattedNumberInput v-model="completeForm.materials[idx].actual_quantity" class="block w-full border-slate-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm font-bold text-right" required />
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-end space-x-3">
                                <button type="button" @click="isCompleting = false" class="px-4 py-2 border border-slate-300 rounded-lg text-sm font-bold text-slate-700 hover:bg-slate-50 transition-colors">
                                    Cancel
                                </button>
                                <button type="submit" :disabled="completeForm.processing" class="inline-flex items-center px-6 py-2 border border-transparent rounded-lg shadow-sm text-sm font-bold text-slate-900 bg-amber-400 hover:bg-amber-500 transition-colors">
                                    {{ completeForm.processing ? 'Saving...' : 'Confirm & Complete Order' }}
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Materials Card (View Mode) -->
                    <div v-if="!isCompleting" class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="p-5 border-b border-slate-100 bg-slate-50">
                            <h3 class="text-sm font-bold text-slate-800 flex items-center">
                                <Factory class="w-4 h-4 mr-2 text-slate-500" />
                                Material Requirements
                            </h3>
                        </div>
                        <div class="block">
                            <div v-for="mat in order.materials" :key="mat.id" class="p-4 sm:p-5 border-b border-slate-100 last:border-0 hover:bg-slate-50 transition-colors">
                                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4">
                                    <div>
                                        <div class="text-sm font-bold text-slate-900">{{ mat.product?.name || mat.description }}</div>
                                        <span class="inline-flex items-center px-2 py-0.5 mt-1 rounded text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-600">
                                            {{ mat.item_type }}
                                        </span>
                                    </div>
                                    <div class="flex flex-wrap gap-4 sm:gap-6 sm:text-right">
                                        <div>
                                            <span class="block text-[10px] font-bold text-slate-400 uppercase">Unit Cost</span>
                                            <span class="text-sm font-bold text-slate-700">{{ formatCurrency(mat.unit_cost) }}</span>
                                        </div>
                                        <div>
                                            <span class="block text-[10px] font-bold text-slate-400 uppercase">Planned Qty</span>
                                            <span class="text-sm font-black text-slate-900">{{ formatNumber(mat.planned_quantity) }}</span>
                                        </div>
                                        <div v-if="order.status === 'completed' || order.status === 'approved'">
                                            <span class="block text-[10px] font-bold text-slate-400 uppercase">Actual Qty</span>
                                            <span class="text-sm font-black text-indigo-600">{{ formatNumber(mat.actual_quantity) }}</span>
                                        </div>
                                        <div>
                                            <span class="block text-[10px] font-bold text-slate-400 uppercase">Total Cost</span>
                                            <span class="text-sm font-bold text-slate-900">{{ formatCurrency(mat.total_cost || (mat.planned_quantity * mat.unit_cost)) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="p-5 bg-slate-50 border-t border-slate-100">
                            <div class="flex justify-between items-center text-sm font-bold text-slate-800">
                                <span>Estimated Total Production Cost:</span>
                                <span class="text-lg">{{ formatCurrency(order.total_production_cost || order.materials.reduce((s, m) => s + (m.planned_quantity * m.unit_cost), 0)) }}</span>
                            </div>
                            <div v-if="order.status === 'completed' || order.status === 'approved'" class="flex justify-between items-center text-xs font-bold text-slate-500 mt-2">
                                <span>Final Unit Cost (Cost per item):</span>
                                <span class="text-sm text-emerald-600">{{ formatCurrency(order.unit_cost) }}</span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            
        </div>
    </AppLayout>
</template>
