<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { ArrowLeft, Save, Plus, Trash2, ArrowRightLeft, MapPin } from '@lucide/vue';

const props = defineProps({
    branches: Array,
    products: Array,
    currentBranchId: Number,
});

const form = useForm({
    to_branch_id: '',
    notes: '',
    items: [],
});

const selectedProduct = ref('');

const addItem = () => {
    if (!selectedProduct.value) return;
    
    const prod = props.products.find(p => p.id === selectedProduct.value);
    if (!prod) return;
    
    if (form.items.some(i => i.product_id === prod.id)) {
        alert("This product is already in the transfer list.");
        return;
    }
    
    form.items.push({
        product_id: prod.id,
        name: prod.name,
        sku: prod.sku,
        quantity: 1,
    });
    
    selectedProduct.value = '';
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const submit = () => {
    if (form.items.length === 0) {
        alert("Please add at least one product to transfer.");
        return;
    }
    form.post(route('stock-transfers.store'));
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center text-lg sm:text-xl">
                <Link :href="route('stock-transfers.index')" class="mr-3 sm:mr-4 text-slate-400 hover:text-slate-600 transition-colors">
                    <ArrowLeft class="w-5 h-5 sm:w-6 sm:h-6" />
                </Link>
                <span class="font-bold truncate">Request Stock Transfer</span>
            </div>
        </template>

        <div class="max-w-4xl mx-auto space-y-6">
            <form @submit.prevent="submit" class="space-y-6">
                
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-5 sm:p-6 border-b border-slate-100 bg-slate-50 flex items-center">
                        <MapPin class="w-5 h-5 mr-2 text-indigo-500" />
                        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Destination Setup</h3>
                    </div>
                    
                    <div class="p-5 sm:p-6 space-y-6">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Transfer To (Destination Branch)</label>
                            <select v-model="form.to_branch_id" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm font-bold text-slate-900 bg-slate-50" required>
                                <option value="" disabled>-- Select Destination Branch --</option>
                                <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Reason / Notes</label>
                            <textarea v-model="form.notes" rows="2" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Why is this transfer needed?"></textarea>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-5 border-b border-slate-100 bg-slate-50">
                        <h3 class="text-sm font-bold text-slate-800 flex items-center">
                            <ArrowRightLeft class="w-4 h-4 mr-2 text-emerald-500" />
                            Products to Transfer
                        </h3>
                    </div>
                    
                    <div class="p-5 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row gap-3">
                        <select v-model="selectedProduct" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                            <option value="">-- Add Product to List --</option>
                            <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} ({{ p.sku }})</option>
                        </select>
                        <button type="button" @click="addItem" class="inline-flex justify-center items-center px-6 py-2 border border-transparent shadow-sm text-sm font-bold rounded-lg text-slate-900 bg-indigo-100 hover:bg-indigo-200 transition-colors whitespace-nowrap">
                            <Plus class="w-4 h-4 mr-1" /> Add
                        </button>
                    </div>

                    <div class="block">
                        <div v-for="(item, index) in form.items" :key="index" class="p-4 sm:p-5 border-b border-slate-100 last:border-0 relative hover:bg-slate-50 transition-colors">
                            <button type="button" @click="removeItem(index)" class="absolute top-4 sm:top-5 right-4 sm:right-5 text-rose-400 hover:text-rose-600 bg-rose-50 p-2 rounded-lg transition-colors">
                                <Trash2 class="w-4 h-4 sm:w-5 sm:h-5" />
                            </button>
                            <div class="pr-12 mb-4">
                                <div class="text-sm sm:text-base font-bold text-slate-900">{{ item.name }}</div>
                                <div class="text-xs text-slate-500">{{ item.sku }}</div>
                            </div>
                            <div>
                                <label class="block text-[10px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Transfer Quantity</label>
                                <FormattedNumberInput v-model="item.quantity" class="block w-full sm:w-1/2 border-slate-300 rounded-lg shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm font-bold" required />
                            </div>
                        </div>
                        
                        <div v-if="form.items.length === 0" class="p-8 sm:p-12 text-center text-slate-400">
                            <ArrowRightLeft class="mx-auto h-12 w-12 text-slate-300 mb-3" />
                            <p class="text-sm font-medium text-slate-500">No products added.</p>
                        </div>
                    </div>

                    <div class="p-5 sm:p-6 bg-slate-50 border-t border-slate-100 flex justify-end">
                        <button type="submit" :disabled="form.processing || form.items.length === 0" class="inline-flex items-center px-6 py-3 border border-transparent rounded-lg shadow-sm text-base font-bold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none disabled:opacity-50 transition-colors">
                            <Save class="w-5 h-5 mr-2" />
                            {{ form.processing ? 'Submitting...' : 'Submit Transfer Request' }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
