<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Target, Trophy, Clock, TrendingUp, Calendar, Trash2, Zap } from '@lucide/vue';
import { computed } from 'vue';

const props = defineProps({
    target: Object,
});

const formatNumber = (value, unit) => {
    if (unit === 'amount') {
        return new Intl.NumberFormat('en-TZ', { style: 'currency', currency: 'TZS', maximumFractionDigits: 0 }).format(value);
    }
    return new Intl.NumberFormat('en-US').format(value) + (unit === 'quantity' ? ' Units' : '');
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
};

const getStatusColor = (status) => {
    const map = {
        'ON TRACK': 'bg-emerald-100 text-emerald-800 border-emerald-200',
        'BEHIND TARGET': 'bg-rose-100 text-rose-800 border-rose-200',
        'TARGET ACHIEVED': 'bg-indigo-100 text-indigo-800 border-indigo-200',
        'EXCEEDED': 'bg-fuchsia-100 text-fuchsia-800 border-fuchsia-200',
        'NOT STARTED': 'bg-slate-100 text-slate-800 border-slate-200',
    };
    return map[status] || map['NOT STARTED'];
};

const getStatusText = (status) => {
    return status;
};

const deleteTarget = () => {
    if (confirm("Are you sure you want to delete this target?")) {
        router.delete(route('targets.destroy', props.target.id));
    }
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center text-lg sm:text-xl">
                    <Link :href="route('targets.index')" class="mr-3 sm:mr-4 text-slate-400 hover:text-slate-600 transition-colors">
                        <ArrowLeft class="w-5 h-5 sm:w-6 sm:h-6" />
                    </Link>
                    <span class="font-bold truncate">Target Details</span>
                </div>
                
                <button @click="deleteTarget" class="inline-flex items-center justify-center px-4 py-2 border border-rose-200 rounded-lg shadow-sm text-sm font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 transition-colors">
                    <Trash2 class="w-4 h-4 mr-2" />
                    Delete Target
                </button>
            </div>
        </template>

        <div class="max-w-5xl mx-auto space-y-6">
            <!-- Header Card -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 sm:p-8 relative overflow-hidden">
                <div class="absolute -right-10 -top-10 text-amber-500 opacity-5">
                    <Target class="w-64 h-64" />
                </div>
                
                <div class="relative z-10 flex flex-col sm:flex-row sm:justify-between gap-6">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <span :class="['px-3 py-1 text-xs font-bold rounded-full border uppercase tracking-wider', getStatusColor(target.progress.status)]">
                                {{ getStatusText(target.progress.status) }}
                            </span>
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">{{ target.target_type }}</span>
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-black text-slate-900 mb-2">{{ target.name }}</h1>
                        <p class="text-slate-500 max-w-xl">{{ target.description || 'No description provided.' }}</p>
                    </div>
                    
                    <div class="text-left sm:text-right">
                        <p class="text-sm font-bold text-slate-500 uppercase tracking-wider mb-1">Target Goal</p>
                        <p class="text-3xl font-black text-amber-500">{{ formatNumber(target.target_value, target.measurement_unit) }}</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Progress Card -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                        <h3 class="text-sm font-bold text-slate-800 flex items-center mb-6 uppercase tracking-wider">
                            <TrendingUp class="w-4 h-4 mr-2 text-indigo-500" />
                            Performance Tracking
                        </h3>
                        
                        <div class="mb-8">
                            <div class="flex justify-between items-end mb-2">
                                <div>
                                    <span class="text-3xl font-black text-indigo-600">{{ formatNumber(target.progress.actual, target.measurement_unit) }}</span>
                                    <span class="text-sm text-slate-500 ml-2 font-medium">Achieved so far</span>
                                </div>
                                <span class="text-lg font-bold text-slate-700">{{ target.progress.achievement_percent.toFixed(1) }}%</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-4 overflow-hidden shadow-inner border border-slate-200/50">
                                <div class="h-4 rounded-full transition-all duration-1000 ease-out relative overflow-hidden bg-gradient-to-r from-amber-400 to-amber-500" :style="`width: ${Math.min(target.progress.achievement_percent, 100)}%`">
                                    <div class="absolute inset-0 bg-white/20" style="background-image: repeating-linear-gradient(45deg, transparent, transparent 10px, rgba(255,255,255,0.1) 10px, rgba(255,255,255,0.1) 20px);"></div>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 border-t border-slate-100 pt-6">
                            <div>
                                <p class="text-xs font-bold text-slate-400 uppercase mb-1">Time Elapsed</p>
                                <p class="text-lg font-bold text-slate-800">{{ target.progress.time_elapsed_percent.toFixed(1) }}%</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-400 uppercase mb-1">Pacing Requirement</p>
                                <p class="text-lg font-bold text-slate-800">{{ formatNumber(target.progress.daily_required, target.measurement_unit) }} / day</p>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-400 uppercase mb-1">Current Status</p>
                                <p :class="['text-lg font-bold', target.progress.status === 'ON TRACK' || target.progress.status === 'TARGET ACHIEVED' || target.progress.status === 'EXCEEDED' ? 'text-emerald-600' : (target.progress.status === 'NOT STARTED' ? 'text-slate-500' : 'text-rose-600')]">
                                    {{ target.progress.status }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Details Card -->
                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="p-5 border-b border-slate-100 bg-slate-50">
                            <h3 class="text-sm font-bold text-slate-800 flex items-center uppercase tracking-wider">
                                <Zap class="w-4 h-4 mr-2 text-amber-500" />
                                Target Scope
                            </h3>
                        </div>
                        <div class="p-5 space-y-4">
                            <div class="flex items-center">
                                <Calendar class="w-4 h-4 text-slate-400 mr-3" />
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Duration ({{ target.period_type }})</p>
                                    <p class="text-sm font-bold text-slate-700">{{ formatDate(target.start_date) }} - {{ formatDate(target.end_date) }}</p>
                                </div>
                            </div>
                            
                            <div v-if="target.branch_id" class="pt-4 border-t border-slate-100">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Branch Focus</p>
                                <p class="text-sm font-bold text-indigo-600">{{ target.branch?.name }}</p>
                            </div>
                            
                            <div v-if="target.user_id" class="pt-4 border-t border-slate-100">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Assigned To</p>
                                <p class="text-sm font-bold text-emerald-600">{{ target.user?.name }}</p>
                            </div>
                            
                            <div v-if="target.product_id" class="pt-4 border-t border-slate-100">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Specific Product</p>
                                <p class="text-sm font-bold text-rose-600">{{ target.product?.name }}</p>
                            </div>
                            
                            <div v-if="target.category_id" class="pt-4 border-t border-slate-100">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Specific Category</p>
                                <p class="text-sm font-bold text-amber-600">{{ target.category?.name }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
