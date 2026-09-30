<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { Target, Plus, TrendingUp, AlertTriangle, CheckCircle2, Factory, ShoppingCart, Users, Wallet, ChevronRight, Edit2, Trash2 } from '@lucide/vue';
import { computed } from 'vue';

const props = defineProps({
    targets: Array
});

const deleteTarget = (id) => {
    if (confirm('Are you sure you want to delete this target?')) {
        router.delete(route('targets.destroy', id), {
            preserveScroll: true
        });
    }
};

const formatNumber = (value, unit) => {
    if (unit === 'amount') {
        return new Intl.NumberFormat('en-TZ', { style: 'currency', currency: 'TZS', maximumFractionDigits: 0 }).format(value);
    }
    return new Intl.NumberFormat('en-US').format(value);
};

const getStatusColor = (status) => {
    switch (status) {
        case 'ON TRACK': return 'bg-blue-100 text-blue-800 border-blue-200';
        case 'BEHIND TARGET': return 'bg-amber-100 text-amber-800 border-amber-200';
        case 'TARGET ACHIEVED': return 'bg-emerald-100 text-emerald-800 border-emerald-200';
        case 'EXCEEDED': return 'bg-purple-100 text-purple-800 border-purple-200';
        case 'NOT STARTED': return 'bg-slate-100 text-slate-800 border-slate-200';
        default: return 'bg-slate-100 text-slate-800';
    }
};

const getProgressBarColor = (status) => {
    switch (status) {
        case 'ON TRACK': return 'bg-blue-500';
        case 'BEHIND TARGET': return 'bg-amber-500';
        case 'TARGET ACHIEVED': return 'bg-emerald-500';
        case 'EXCEEDED': return 'bg-purple-500';
        default: return 'bg-slate-300';
    }
};

const getIconForType = (type) => {
    switch(type) {
        case 'sales': return ShoppingCart;
        case 'production': return Factory;
        case 'customer': return Users;
        case 'profit': return TrendingUp;
        case 'expense': return Wallet;
        default: return Target;
    }
};
</script>

<template>
    <AppLayout>
        <template #header>Performance Targets</template>

        <div class="space-y-6">
            <div class="flex justify-between items-center bg-white p-4 rounded-xl shadow-sm border border-slate-200">
                <div>
                    <h2 class="text-lg font-bold text-slate-800">Business Goals</h2>
                    <p class="text-sm text-slate-500">Track and manage your targets in real time.</p>
                </div>
                <Link :href="route('targets.create')" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-amber-600 hover:bg-amber-700">
                    <Plus class="-ml-1 mr-2 h-4 w-4" />
                    New Target
                </Link>
            </div>

            <!-- Target Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="target in targets" :key="target.id" class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden hover:shadow-md transition-shadow relative">
                    <!-- Status Badge -->
                    <div class="absolute top-4 right-4">
                        <span :class="['px-2.5 py-1 text-xs font-bold rounded-full border', getStatusColor(target.progress.status)]">
                            {{ target.progress.status }}
                        </span>
                    </div>

                    <div class="p-6">
                        <div class="flex items-center mb-4">
                            <div class="p-3 rounded-lg bg-slate-50 border border-slate-100 mr-4">
                                <component :is="getIconForType(target.target_type)" class="w-6 h-6 text-slate-600" />
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-800 truncate pr-20" :title="target.name">{{ target.name }}</h3>
                                <p class="text-xs text-slate-500 capitalize font-medium">{{ target.target_type }} Target</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <!-- Progress Bar -->
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span class="font-semibold text-slate-700">{{ target.progress.achievement_percent }}% Achieved</span>
                                    <span class="text-slate-500">{{ formatNumber(target.progress.actual, target.measurement_unit) }} / {{ formatNumber(target.progress.target, target.measurement_unit) }}</span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden">
                                    <div 
                                        :class="['h-2.5 rounded-full', getProgressBarColor(target.progress.status)]" 
                                        :style="{ width: Math.min(target.progress.achievement_percent, 100) + '%' }"
                                    ></div>
                                </div>
                                <div class="mt-1 flex justify-between text-xs">
                                    <span class="text-slate-500">{{ target.progress.time_elapsed_percent }}% of time elapsed</span>
                                    <span v-if="target.progress.remaining > 0" class="text-amber-600 font-medium">{{ formatNumber(target.progress.remaining, target.measurement_unit) }} remaining</span>
                                    <span v-else class="text-emerald-600 font-medium">Completed</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4 pt-4 border-t border-slate-100">
                                <div>
                                    <p class="text-xs text-slate-400 font-medium">Scope</p>
                                    <p class="text-sm font-semibold text-slate-700 truncate" :title="target.branch?.name || 'Entire Business'">
                                        {{ target.branch?.name || 'Entire Business' }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-400 font-medium">Deadline</p>
                                    <p class="text-sm font-semibold text-slate-700">
                                        {{ new Date(target.end_date).toLocaleDateString() }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Footer Actions -->
                    <div class="px-6 py-3 bg-slate-50 border-t border-slate-100 flex justify-between items-center">
                        <span class="text-xs font-medium text-slate-500 truncate mr-2" title="Required Pace">
                            Req: {{ formatNumber(target.progress.daily_required, target.measurement_unit) }}/day
                        </span>
                        <div class="flex items-center space-x-1 shrink-0">
                            <Link :href="route('targets.edit', target.id)" class="text-slate-400 hover:text-indigo-600 p-1.5 rounded hover:bg-indigo-50 transition-colors" title="Edit">
                                <Edit2 class="w-4 h-4" />
                            </Link>
                            <button @click="deleteTarget(target.id)" class="text-slate-400 hover:text-rose-600 p-1.5 rounded hover:bg-rose-50 transition-colors" title="Delete">
                                <Trash2 class="w-4 h-4" />
                            </button>
                            <Link :href="route('targets.show', target.id)" class="text-amber-500 hover:text-amber-700 p-1.5 rounded hover:bg-amber-50 transition-colors ml-1" title="View Details">
                                <ChevronRight class="w-4 h-4" />
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="targets.length === 0" class="col-span-full py-12 text-center bg-white rounded-xl border border-slate-200 border-dashed">
                    <Target class="w-12 h-12 text-slate-300 mx-auto mb-3" />
                    <h3 class="text-lg font-medium text-slate-900">No targets defined</h3>
                    <p class="mt-1 text-sm text-slate-500">Get started by creating your first business goal.</p>
                    <div class="mt-6">
                        <Link :href="route('targets.create')" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-amber-600 hover:bg-amber-700">
                            <Plus class="-ml-1 mr-2 h-4 w-4" />
                            Create Target
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
