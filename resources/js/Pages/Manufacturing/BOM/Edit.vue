<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import InputError from '@/Components/InputError.vue';
import { ArrowLeft, Save, BookOpen, Plus, Trash2, List } from '@lucide/vue';

const props = defineProps({
    products: Array,
    materials: Array,
    units: Array,
    bom: Object,
});

const form = useForm({
    name: props.bom.name,
    product_id: props.bom.product_id,
    expected_output: props.bom.expected_output,
    output_unit_id: props.bom.output_unit_id,
    description: props.bom.description,
    is_active: props.bom.is_active,
    items: props.bom.items.map(i => ({
        product_id: i.product_id,
        description: i.description,
        quantity: i.quantity,
        unit_id: i.unit_id,
        unit_cost: i.unit_cost,
        total_cost: i.total_cost,
        is_optional: i.is_optional,
    })),
});

const submit = () => {
    form.put(route('bom.update', props.bom.id));
};

const addMaterial = () => {
    form.items.push({
        product_id: '',
        description: '',
        quantity: 1,
        unit_cost: 0,
        total_cost: 0,
        item_type: 'material'
    });
};

const addCustomMaterial = () => {
    form.items.push({
        product_id: null,
        description: 'New Custom Cost',
        quantity: 1,
        unit_cost: 0,
        total_cost: 0,
        item_type: 'material'
    });
};

const hasProducts = computed(() => form.items.some(i => i.product_id !== null));

const totalRecipeCost = computed(() => {
    return form.items.reduce((sum, item) => {
        const qty = item.product_id !== null ? Number(item.quantity) : 1;
        const cost = Number(item.unit_cost);
        return sum + (qty * cost);
    }, 0);
});

const costPerUnit = computed(() => {
    const output = Number(form.expected_output);
    if (output <= 0) return 0;
    return totalRecipeCost.value / output;
});

const removeMaterial = (index) => {
    form.items.splice(index, 1);
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center text-lg sm:text-xl">
                <Link :href="route('bom.index')" class="mr-3 sm:mr-4 text-slate-400 hover:text-slate-600 transition-colors">
                    <ArrowLeft class="w-5 h-5 sm:w-6 sm:h-6" />
                </Link>
                <span class="font-bold truncate">Save Changes (BOM)</span>
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
                                <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Recipe Name</label>
                                <input v-model="form.name" type="text" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" placeholder="e.g. Tofali Recipe" required />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Output Product</label>
                                <select v-model="form.product_id" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" required>
                                    <option value="" disabled>-- Select Product --</option>
                                    <option v-for="prod in products" :key="prod.id" :value="prod.id">{{ prod.name }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Expected Output Qty</label>
                                <FormattedNumberInput v-model="form.expected_output" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" required />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Output Unit</label>
                                <select v-model="form.output_unit_id" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm">
                                    <option value="">None</option>
                                    <option v-for="u in units" :key="u.id" :value="u.id">{{ u.name }}</option>
                                </select>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Description / Notes</label>
                            <textarea v-model="form.description" rows="2" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm"></textarea>
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox" v-model="form.is_active" id="is_active" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-slate-300 rounded">
                            <label for="is_active" class="ml-2 block text-sm text-slate-900">Active Recipe</label>
                        </div>
                    </div>
                </div>

                <!-- Materials / Ingredients -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                        <h3 class="text-sm font-bold text-slate-800 flex items-center">
                            <List class="w-4 h-4 mr-2 text-indigo-500" />
                            Materials (Ingredients)
                        </h3>
                                                <div class="flex gap-2">
                            <button type="button" @click="addCustomMaterial" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-bold rounded-lg text-emerald-700 bg-emerald-100 hover:bg-emerald-200 transition-colors">
                                <Plus class="w-3.5 h-3.5 mr-1" />
                                Add Custom Cost
                            </button>
                            <button type="button" @click="addMaterial" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-bold rounded-lg text-indigo-700 bg-indigo-100 hover:bg-indigo-200 transition-colors">
                                <Plus class="w-3.5 h-3.5 mr-1" />
                                Add Product
                            </button>
                        </div>
                    </div>
                    <div class="p-0 overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Material</th>
                                    <th v-if="hasProducts" scope="col" class="px-6 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider w-32">Qty</th>
                                    <th v-if="hasProducts" scope="col" class="px-6 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider w-32">Unit Cost</th>
                                    <th scope="col" class="px-6 py-3 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider w-32">Total</th>
                                    <th scope="col" class="px-6 py-3 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider w-16"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-200">
                                <tr v-for="(item, index) in form.items" :key="index">
                                                                        <td class="px-6 py-3">
                                        <select v-if="item.product_id !== null" v-model="item.product_id" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" required>
                                            <option value="" disabled>Select...</option>
                                            <option v-for="prod in materials" :key="prod.id" :value="prod.id">{{ prod.name }}</option>
                                        </select>
                                        <input v-else v-model="item.description" type="text" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm font-bold text-slate-700 bg-slate-50" placeholder="Custom Material/Cost" />
                                    </td>
                                    <td v-if="hasProducts" class="px-6 py-3">
                                        <FormattedNumberInput v-if="item.product_id !== null" v-model="item.quantity" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" required />
                                        <div v-else class="text-slate-400 text-sm font-medium text-center">-</div>
                                    </td>
                                    <td v-if="hasProducts" class="px-6 py-3">
                                        <FormattedNumberInput v-if="item.product_id !== null" v-model="item.unit_cost" @input="item.total_cost = item.unit_cost * item.quantity" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" />
                                        <div v-else class="text-slate-400 text-sm font-medium text-center">-</div>
                                    </td>
                                    <td class="px-6 py-3 text-right font-bold text-slate-700">
                                        <span v-if="item.product_id !== null">{{ new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(item.quantity * item.unit_cost) }}</span>
                                        <FormattedNumberInput v-else v-model="item.unit_cost" @input="item.total_cost = item.unit_cost; item.quantity = 1" class="block w-full text-right border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm font-bold text-slate-900 bg-amber-50" placeholder="Total Cost" />
                                    </td>
                                    <td class="px-6 py-3 text-right">
                                        <button type="button" @click="removeMaterial(index)" class="text-rose-400 hover:text-rose-600 transition-colors p-1" title="Remove">
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <!-- Cost Summary -->
                    <div class="p-5 bg-amber-50/50 border-t border-amber-100 flex flex-col sm:flex-row justify-between items-center gap-4">
                        <div class="text-sm">
                            <span class="text-slate-500 font-bold uppercase tracking-wider text-xs">Total Recipe Cost:</span>
                            <span class="ml-2 font-black text-slate-800 text-lg">{{ new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(totalRecipeCost) }}</span>
                        </div>
                        <div class="text-sm">
                            <span class="text-slate-500 font-bold uppercase tracking-wider text-xs">Output:</span>
                            <span class="ml-2 font-bold text-slate-700">{{ form.expected_output }} Units</span>
                        </div>
                        <div class="text-sm bg-emerald-100 px-4 py-2 rounded-lg border border-emerald-200">
                            <span class="text-emerald-800 font-bold uppercase tracking-wider text-xs">Cost Per Unit:</span>
                            <span class="ml-2 font-black text-emerald-900 text-lg">{{ new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(costPerUnit) }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-2 border-t border-slate-200">
                    <button type="submit" :disabled="form.processing" class="inline-flex justify-center items-center py-2 px-6 border border-transparent shadow-sm text-sm font-bold rounded-lg text-slate-900 bg-amber-400 hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 disabled:opacity-50 transition-colors">
                        <Save class="w-4 h-4 mr-2" />
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>




