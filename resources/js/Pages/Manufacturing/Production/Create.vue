<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { ArrowLeft, Save, Factory, CheckSquare } from '@lucide/vue';

const props = defineProps({
    boms: Array,
    branches: Array,
    defaultBranch: Number,
});

const form = useForm({
    branch_id: props.defaultBranch || (props.branches.length > 0 ? props.branches[0].id : ''),
    bom_id: '',
    planned_quantity: 1,
    planned_date: new Date().toISOString().split('T')[0],
    notes: '',
});

const selectedBom = computed(() => {
    if (!form.bom_id) return null;
    return props.boms.find(b => b.id === form.bom_id);
});

const submit = () => {
    form.post(route('production.store'));
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center text-lg sm:text-xl">
                <Link :href="route('production.index')" class="mr-3 sm:mr-4 text-slate-400 hover:text-slate-600 transition-colors">
                    <ArrowLeft class="w-5 h-5 sm:w-6 sm:h-6" />
                </Link>
                <span class="font-bold truncate">Plan Production Order</span>
            </div>
        </template>

        <div class="max-w-4xl mx-auto">
            <form @submit.prevent="submit" class="space-y-6">
                
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-5 sm:p-6 border-b border-slate-100 bg-slate-50 flex items-center">
                        <Factory class="w-5 h-5 mr-2 text-amber-500" />
                        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Production Setup</h3>
                    </div>
                    
                    <div class="p-5 sm:p-6 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Production Branch</label>
                                <select v-model="form.branch_id" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm bg-slate-50" required>
                                    <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Recipe / Bill of Materials</label>
                                <select v-model="form.bom_id" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" required>
                                    <option value="" disabled>-- Select a Recipe --</option>
                                    <option v-for="bom in boms" :key="bom.id" :value="bom.id">
                                        {{ bom.name }} (Output: {{ bom.expected_output }})
                                    </option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6" v-if="selectedBom">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Planned Date</label>
                                <input v-model="form.planned_date" type="date" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" required />
                            </div>
                            
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Target Quantity to Produce</label>
                                <FormattedNumberInput v-model="form.planned_quantity" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm font-bold text-indigo-600" required />
                            </div>
                        </div>
                        
                        <div v-if="selectedBom" class="bg-indigo-50 p-4 rounded-lg border border-indigo-100 flex items-start">
                            <CheckSquare class="w-5 h-5 text-indigo-500 mr-3 flex-shrink-0 mt-0.5" />
                            <div>
                                <h4 class="text-sm font-bold text-indigo-900 mb-1">Production Preview</h4>
                                <p class="text-xs text-indigo-700">
                                    You are planning to produce <span class="font-bold">{{ form.planned_quantity }}</span> units of 
                                    <span class="font-bold">{{ selectedBom.product?.name }}</span>. 
                                    The system will automatically scale the raw materials required based on the {{ selectedBom.name }} recipe (Base output: {{ selectedBom.expected_output }}).
                                </p>
                            </div>
                        </div>

                        <div v-if="selectedBom">
                            <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Notes / Instructions</label>
                            <textarea v-model="form.notes" rows="3" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" placeholder="Any special instructions for the production floor..."></textarea>
                        </div>
                    </div>
                    
                    <div class="p-5 sm:p-6 bg-slate-50 border-t border-slate-100 flex justify-end">
                        <button type="submit" :disabled="form.processing || !form.bom_id" class="inline-flex items-center px-6 py-3 border border-transparent rounded-lg shadow-sm text-base font-bold text-slate-900 bg-amber-400 hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 disabled:opacity-50 transition-colors">
                            <Save class="w-5 h-5 mr-2" />
                            {{ form.processing ? 'Saving...' : 'Plan Production' }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
