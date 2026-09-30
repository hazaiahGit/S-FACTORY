<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import FormattedNumberInput from '@/Components/FormattedNumberInput.vue';
import { useForm, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { ArrowLeft, Save, Plus, Trash2, ArrowRightLeft, MapPin, Zap } from '@lucide/vue';

const props = defineProps({
    branches: Array,
    products: Array,
    currentBranchId: [Number, String],
    isTenantAdmin: Boolean,
});

const form = useForm({
    from_branch_id: props.currentBranchId || (props.branches?.[0]?.id ?? ''),
    to_branch_id: '',
    notes: '',
    auto_complete: true,
    items: [],
});

const availableDestBranches = computed(() => {
    return (props.branches || []).filter(b => b.id != form.from_branch_id);
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
    if (!form.from_branch_id) {
        alert("Please select the source branch.");
        return;
    }
    if (!form.to_branch_id) {
        alert("Please select the destination branch.");
        return;
    }
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
                <span class="font-bold truncate">Create Stock Transfer</span>
            </div>
        </template>

        <div class="max-w-4xl mx-auto space-y-6">
            <form @submit.prevent="submit" class="space-y-6">
                
                <!-- Branch Route Setup -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-5 sm:p-6 border-b border-slate-100 bg-slate-50 flex items-center">
                        <MapPin class="w-5 h-5 mr-2 text-indigo-500" />
                        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Transfer Route</h3>
                    </div>
                    
                    <div class="p-5 sm:p-6 space-y-6">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Source Branch -->
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Transfer From (Source Branch)</label>
                                <select
                                    v-if="isTenantAdmin"
                                    v-model="form.from_branch_id"
                                    class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm font-bold text-slate-900 bg-slate-50"
                                    required
                                >
                                    <option value="" disabled>-- Select Source Branch --</option>
                                    <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                                </select>
                                <div
                                    v-else
                                    class="w-full px-3 py-2 bg-slate-100 border border-slate-200 rounded-lg text-sm font-bold text-slate-700 flex items-center"
                                >
                                    <MapPin class="w-4 h-4 text-amber-500 mr-2" />
                                    <span>{{ branches.find(b => b.id == form.from_branch_id)?.name || $page.props.auth.branch?.name || 'Your Branch' }}</span>
                                </div>
                            </div>

                            <!-- Destination Branch -->
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Transfer To (Destination Branch)</label>
                                <select
                                    v-model="form.to_branch_id"
                                    class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm font-bold text-slate-900 bg-slate-50"
                                    required
                                >
                                    <option value="" disabled>-- Select Destination Branch --</option>
                                    <option v-for="b in availableDestBranches" :key="b.id" :value="b.id">{{ b.name }}</option>
                                </select>
                            </div>
                        </div>

                        <!-- Auto-Complete Option -->
                        <div class="p-4 bg-amber-50/70 rounded-xl border border-amber-200 flex items-start gap-3">
                            <input
                                id="auto_complete"
                                type="checkbox"
                                v-model="form.auto_complete"
                                class="mt-1 h-4 w-4 rounded border-slate-300 text-amber-500 focus:ring-amber-400 cursor-pointer"
                            />
                            <label for="auto_complete" class="cursor-pointer">
                                <span class="block text-sm font-bold text-slate-900 flex items-center">
                                    <Zap class="w-4 h-4 mr-1.5 text-amber-600 inline" />
                                    Move Stock Immediately (Instant Transfer)
                                </span>
                                <span class="block text-xs text-slate-600 mt-0.5">
                                    Automatically deducts quantity from source branch and adds it directly to destination branch stock now. Uncheck if you want a multi-stage request & approval workflow.
                                </span>
                            </label>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Reason / Notes</label>
                            <textarea v-model="form.notes" rows="2" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" placeholder="Why is this transfer needed? (Optional)"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Products to Transfer -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-5 border-b border-slate-100 bg-slate-50">
                        <h3 class="text-sm font-bold text-slate-800 flex items-center">
                            <ArrowRightLeft class="w-4 h-4 mr-2 text-emerald-500" />
                            Products to Transfer
                        </h3>
                    </div>
                    
                    <div class="p-5 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row gap-3">
                        <select v-model="selectedProduct" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm">
                            <option value="">-- Add Product to List --</option>
                            <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} ({{ p.sku }})</option>
                        </select>
                        <button type="button" @click="addItem" class="inline-flex justify-center items-center px-6 py-2 border border-transparent shadow-sm text-sm font-bold rounded-lg text-slate-900 bg-amber-400 hover:bg-amber-500 transition-colors whitespace-nowrap">
                            <Plus class="w-4 h-4 mr-1" /> Add Product
                        </button>
                    </div>

                    <div class="block">
                        <div v-for="(item, index) in form.items" :key="index" class="p-4 sm:p-5 border-b border-slate-100 last:border-0 relative hover:bg-slate-50 transition-colors">
                            <button type="button" @click="removeItem(index)" class="absolute top-4 sm:top-5 right-4 sm:right-5 text-rose-400 hover:text-rose-600 bg-rose-50 p-2 rounded-lg transition-colors">
                                <Trash2 class="w-4 h-4 sm:w-5 sm:h-5" />
                            </button>
                            <div class="pr-12 mb-3">
                                <div class="text-sm sm:text-base font-bold text-slate-900">{{ item.name }}</div>
                                <div class="text-xs text-slate-500">SKU: {{ item.sku }}</div>
                            </div>
                            <div>
                                <label class="block text-[10px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Transfer Quantity</label>
                                <FormattedNumberInput v-model="item.quantity" class="block w-full sm:w-1/3 border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm font-bold" required />
                            </div>
                        </div>
                        
                        <div v-if="form.items.length === 0" class="p-8 sm:p-12 text-center text-slate-400">
                            <ArrowRightLeft class="mx-auto h-12 w-12 text-slate-300 mb-3" />
                            <p class="text-sm font-medium text-slate-500">No products added yet.</p>
                            <p class="text-xs text-slate-400 mt-1">Select a product above and click "Add Product".</p>
                        </div>
                    </div>

                    <div class="p-5 sm:p-6 bg-slate-50 border-t border-slate-100 flex justify-end">
                        <button type="submit" :disabled="form.processing || form.items.length === 0" class="inline-flex items-center px-6 py-3 border border-transparent rounded-lg shadow-sm text-base font-bold text-slate-900 bg-amber-400 hover:bg-amber-500 focus:outline-none disabled:opacity-50 transition-colors">
                            <Save class="w-5 h-5 mr-2" />
                            {{ form.processing ? 'Processing...' : (form.auto_complete ? 'Transfer & Move Stock Now' : 'Submit Transfer Request') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
