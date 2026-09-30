<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { ArrowLeft, Save, BookOpen, Plus, Trash2, List } from '@lucide/vue';

const props = defineProps({
    products: Array,
    materials: Array,
    units: Array,
    categories: Array,
});

const form = useForm({
    product_name: '',
    category_id: '',
    selling_price: 0,
    name: '',
    expected_output: 1,
    output_unit_id: '',
    description: '',
    is_active: true,
    items: [],
});

const customItemName = ref('');
const customItemDesc = ref('');
const customItemCost = ref(0);

const totalRecipeCost = computed(() => {
    return form.items.reduce((sum, item) => {
        return sum + Number(item.total_cost);
    }, 0);
});

const costPerUnit = computed(() => {
    const output = Number(form.expected_output);
    if (output <= 0) return 0;
    return totalRecipeCost.value / output;
});

const addCustomMaterial = () => {
    if (!customItemName.value) {
        alert("Please provide a name for the material.");
        return;
    }
    
    form.items.push({
        item_type: 'material',
        product_id: null,
        name: customItemName.value,
        description: customItemDesc.value,
        quantity: 1,
        unit_cost: Number(customItemCost.value || 0),
        total_cost: Number(customItemCost.value || 0),
    });
    
    customItemName.value = '';
    customItemDesc.value = '';
    customItemCost.value = 0;
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const submit = () => {
    if (form.items.length === 0) {
        alert("Please add at least one material or cost item to the recipe.");
        return;
    }
    
    // Auto-calculate total costs before submit
    form.items.forEach(item => {
        item.total_cost = Number(item.quantity) * Number(item.unit_cost);
    });

    form.post(route('bom.store'));
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center text-lg sm:text-xl">
                <Link :href="route('bom.index')" class="mr-3 sm:mr-4 text-slate-400 hover:text-slate-600 transition-colors">
                    <ArrowLeft class="w-5 h-5 sm:w-6 sm:h-6" />
                </Link>
                <span class="font-bold truncate">Create Recipe (BOM)</span>
            </div>
        </template>

        <div class="max-w-5xl mx-auto space-y-6">
            <form @submit.prevent="submit" class="space-y-6">
                
                <!-- Main Details -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-5 border-b border-slate-100 bg-slate-50">
                        <h3 class="text-sm font-bold text-slate-800 flex items-center">
                            <BookOpen class="w-4 h-4 mr-2 text-indigo-500" />
                            Recipe Details
                        </h3>
                    </div>
                    <div class="p-5 sm:p-6 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">New Finished Product Name</label>
                                <input v-model="form.product_name" type="text" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" placeholder="e.g. Standard Concrete Block" required />
                                <p class="text-[10px] text-slate-400 mt-1">This product will be created and added to the POS.</p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Product Category</label>
                                <select v-model="form.category_id" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" required>
                                    <option value="" disabled>-- Select Category --</option>
                                    <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Expected Output Qty</label>
                                <FormattedNumberInput v-model="form.expected_output" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm font-bold text-indigo-600" required />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Output Unit</label>
                                <select v-model="form.output_unit_id" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" required>
                                    <option value="" disabled>Select Unit</option>
                                    <option v-for="u in units" :key="u.id" :value="u.id">{{ u.name }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Selling Price (Per Unit)</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-slate-500 sm:text-sm">TSh</span>
                                    </div>
                                    <FormattedNumberInput v-model="form.selling_price" class="block w-full pl-12 border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm font-bold text-emerald-600" required />
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Calculated Unit Cost</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-slate-500 sm:text-sm">TSh</span>
                                    </div>
                                    <input :value="((form.items.reduce((sum, item) => sum + item.total_cost, 0)) / form.expected_output).toFixed(2)" type="number" disabled class="block w-full pl-12 border-slate-200 rounded-lg shadow-sm bg-slate-50 text-slate-500 sm:text-sm font-bold" />
                                </div>
                                <p class="text-[10px] text-slate-400 mt-1">Based on materials / output qty.</p>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Description</label>
                            <textarea v-model="form.description" rows="2" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" placeholder="Brief description of this recipe..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Materials List -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-5 border-b border-slate-100 bg-slate-50">
                        <h3 class="text-sm font-bold text-slate-800 flex items-center">
                            <List class="w-4 h-4 mr-2 text-emerald-500" />
                            Ingredients & Costs per Batch
                        </h3>
                    </div>
                    
                                        <!-- Add Custom Material -->
                    <div class="p-5 border-b border-slate-100 bg-slate-50/50">
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Add Material / Cost</label>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <input v-model="customItemName" type="text" placeholder="Material Name (e.g. Flour)" class="block w-full sm:w-1/3 border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm" />
                            <input v-model="customItemDesc" type="text" placeholder="Description / Qty (e.g. 10 kg)" class="block w-full sm:w-1/3 border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm" />
                            <FormattedNumberInput v-model="customItemCost" placeholder="Total Cost" class="block w-full sm:w-1/4 border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm" />
                            <button type="button" @click="addCustomMaterial" class="inline-flex justify-center items-center px-4 py-2 border border-transparent shadow-sm text-sm font-bold rounded-lg text-slate-900 bg-emerald-100 hover:bg-emerald-200 focus:outline-none transition-colors whitespace-nowrap">
                                <Plus class="w-4 h-4 mr-1" /> Add
                            </button>
                        </div>
                    </div>

                                        <!-- Items Card List -->
                    <div class="block">
                        <div v-for="(item, index) in form.items" :key="index" class="p-4 sm:p-5 border-b border-slate-100 last:border-0 relative hover:bg-slate-50 transition-colors">
                            <button type="button" @click="removeItem(index)" class="absolute top-4 sm:top-5 right-4 sm:right-5 text-rose-400 hover:text-rose-600 bg-rose-50 p-2 rounded-lg transition-colors">
                                <Trash2 class="w-4 h-4 sm:w-5 sm:h-5" />
                            </button>
                            <div class="pr-12">
                                <div class="text-sm sm:text-base font-bold text-slate-900">{{ item.name }}</div>
                                <div class="text-sm text-slate-500 mt-1">{{ item.description }}</div>
                            </div>
                            <div class="mt-4 flex justify-between items-center bg-slate-100/50 p-3 rounded-lg text-sm font-bold border border-slate-100">
                                <span class="text-slate-500 uppercase tracking-wider text-xs">Line Cost Estimate</span>
                                <span class="text-slate-900">{{ new Intl.NumberFormat('en-TZ', { style: 'currency', currency: 'TZS', maximumFractionDigits: 0 }).format(item.total_cost) }}</span>
                            </div>
                        </div>
                        <div v-if="form.items.length === 0" class="p-8 sm:p-12 text-center text-slate-400">
                            <List class="mx-auto h-12 w-12 text-slate-300 mb-3" />
                            <p class="text-sm font-medium text-slate-500">No materials or costs added.</p>
                            <p class="text-xs text-slate-400 mt-1">Use the controls above to build your recipe.</p>
                        </div>
                    </div>

                    <!-- Cost Summary -->
                    <div class="p-5 bg-amber-50/50 border-t border-amber-100 flex flex-col sm:flex-row justify-between items-center gap-4">
                        <div class="text-sm">
                            <span class="text-slate-500 font-bold uppercase tracking-wider text-xs">Total Recipe Cost:</span>
                            <span class="ml-2 font-black text-slate-800 text-lg">{{ new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(totalRecipeCost) }}</span>
                        </div>
                        <div class="text-sm">
                            <span class="text-slate-500 font-bold uppercase tracking-wider text-xs">Output:</span>
                            <span class="ml-2 font-bold text-slate-700">{{ form.expected_output || 0 }} Units</span>
                        </div>
                        <div class="text-sm bg-emerald-100 px-4 py-2 rounded-lg border border-emerald-200">
                            <span class="text-emerald-800 font-bold uppercase tracking-wider text-xs">Cost Per Unit:</span>
                            <span class="ml-2 font-black text-emerald-900 text-lg">{{ new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(costPerUnit) }}</span>
                        </div>
                    </div>

                    <div class="p-5 sm:p-6 bg-slate-50 border-t border-slate-100 flex justify-end">
                        <button type="submit" :disabled="form.processing || form.items.length === 0" class="inline-flex items-center px-6 py-3 border border-transparent rounded-lg shadow-sm text-base font-bold text-slate-900 bg-amber-400 hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 disabled:opacity-50 transition-colors">
                            <Save class="w-5 h-5 mr-2" />
                            {{ form.processing ? 'Saving...' : 'Save Recipe' }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>




