<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { 
    ArrowLeft,
    Plus,
    Trash2,
    Truck,
    Receipt,
    Save,
    Calculator,
    CreditCard
} from '@lucide/vue';

const props = defineProps({
    products: Array,
    suppliers: Array,
});

const page = usePage();
const business = page.props.auth.business || {};
const branch = page.props.auth.branch || { id: 1 }; // Fallback

const form = useForm({
    branch_id: branch.id,
    supplier_id: '',
    reference: '',
    transaction_date: new Date().toISOString().split('T')[0],
    transport_cost: 0,
    other_costs: 0,
    notes: '',
    items: [],
    // Simple payment handler
    amount_paid: 0,
    payment_method: 'cash',
    payments: []
});

const selectedProduct = ref('');

// Computed
const subtotal = computed(() => {
    return form.items.reduce((sum, item) => sum + (Number(item.quantity) * Number(item.unit_cost)), 0);
});

const totalCosts = computed(() => {
    return (Number(form.transport_cost) || 0) + (Number(form.other_costs) || 0);
});

const totalAmount = computed(() => {
    return subtotal.value + totalCosts.value;
});

let isAmountManuallyEdited = false;
watch(totalAmount, (newTotal) => {
    if (!isAmountManuallyEdited) {
        form.amount_paid = newTotal;
    }
});

const balanceDue = computed(() => {
    return Math.max(0, totalAmount.value - (Number(form.amount_paid) || 0));
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-TZ', { style: 'currency', currency: business.currency || 'TZS', maximumFractionDigits: 0 }).format(value);
};

// Actions
const addItem = () => {
    if (!selectedProduct.value) return;
    
    const product = props.products.find(p => p.id === selectedProduct.value);
    if (!product) return;

    const existing = form.items.find(i => i.product_id === product.id);
    if (existing) {
        existing.quantity++;
    } else {
        form.items.push({
            product_id: product.id,
            name: product.name,
            sku: product.sku,
            quantity: 1,
            unit_cost: Number(product.cost_price || 0),
        });
    }
    
    selectedProduct.value = '';
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const submit = () => {
    if (form.items.length === 0) {
        alert("Please add at least one product to the purchase order.");
        return;
    }
    
    // Build payments array if amount_paid > 0
    if (Number(form.amount_paid) > 0) {
        form.payments = [{
            payment_method: form.payment_method,
            amount: Number(form.amount_paid)
        }];
    } else {
        form.payments = [];
    }

    form.post(route('purchases.store'));
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center text-lg sm:text-xl">
                <Link :href="route('purchases.index')" class="mr-3 sm:mr-4 text-slate-400 hover:text-slate-600 transition-colors">
                    <ArrowLeft class="w-5 h-5 sm:w-6 sm:h-6" />
                </Link>
                <span class="font-bold truncate">Receive Stock</span>
            </div>
        </template>

        <div class="max-w-7xl mx-auto space-y-6">
            <form @submit.prevent="submit" class="flex flex-col lg:flex-row gap-6">
                <!-- Left: Form Fields & Items -->
                <div class="flex-1 space-y-6 min-w-0">
                    <!-- Basic Details -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 sm:p-6">
                        <h3 class="text-xs sm:text-sm font-bold text-slate-400 uppercase tracking-wider mb-4 border-b border-slate-100 pb-2">Purchase Details</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">
                            <div>
                                <label class="block text-xs sm:text-sm font-bold text-slate-700 mb-1">Supplier / Vendor</label>
                                <select v-model="form.supplier_id" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm bg-slate-50">
                                    <option value="">Walk-in Vendor</option>
                                    <option v-for="s in suppliers" :key="s.id" :value="s.id">{{ s.name }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs sm:text-sm font-bold text-slate-700 mb-1">Invoice No. (Ref)</label>
                                <input v-model="form.reference" type="text" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm bg-slate-50" placeholder="e.g. INV-001" />
                            </div>
                            <div class="sm:col-span-2 lg:col-span-1">
                                <label class="block text-xs sm:text-sm font-bold text-slate-700 mb-1">Received Date</label>
                                <input v-model="form.transaction_date" type="date" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm bg-slate-50" />
                            </div>
                        </div>
                    </div>

                    <!-- Items Section -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="p-4 sm:p-6 border-b border-slate-100 flex flex-col sm:flex-row sm:items-end justify-between gap-4 bg-slate-50/50">
                            <div class="flex-1 w-full max-w-md">
                                <label class="block text-xs sm:text-sm font-bold text-slate-700 mb-1">Add Product to Order</label>
                                <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-2">
                                    <select v-model="selectedProduct" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm font-medium">
                                        <option value="">-- Select Product --</option>
                                        <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }} {{ p.sku ? `(${p.sku})` : '' }}</option>
                                    </select>
                                    <button type="button" @click="addItem" class="inline-flex justify-center items-center px-4 py-2 sm:py-0 border border-transparent shadow-sm text-sm font-bold rounded-lg text-slate-900 bg-amber-400 hover:bg-amber-500 focus:outline-none transition-colors w-full sm:w-auto h-10">
                                        Add
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Card View for Items (Default for all screen sizes) -->
                        <div class="block">
                            <div v-for="(item, index) in form.items" :key="index" class="p-4 sm:p-5 border-b border-slate-100 last:border-0 relative hover:bg-slate-50 transition-colors">
                                <button type="button" @click="removeItem(index)" class="absolute top-4 sm:top-5 right-4 sm:right-5 text-rose-400 hover:text-rose-600 bg-rose-50 p-2 rounded-lg transition-colors">
                                    <Trash2 class="w-4 h-4 sm:w-5 sm:h-5" />
                                </button>
                                <div class="pr-12 mb-4">
                                    <div class="text-sm sm:text-base font-bold text-slate-900">{{ item.name }}</div>
                                    <div class="text-xs text-slate-500">{{ item.sku }}</div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[10px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Qty Received</label>
                                        <FormattedNumberInput v-model="item.quantity" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm font-bold" required />
                                    </div>
                                    <div>
                                        <label class="block text-[10px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Unit Cost ({{ business.currency_symbol || 'TSh' }})</label>
                                        <FormattedNumberInput v-model="item.unit_cost" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm font-bold" required />
                                    </div>
                                </div>
                                <div class="mt-4 flex justify-between items-center bg-slate-100/50 p-3 rounded-lg text-sm font-bold border border-slate-100">
                                    <span class="text-slate-500 uppercase tracking-wider text-xs">Total</span>
                                    <span class="text-slate-900 text-base">{{ formatCurrency(item.quantity * item.unit_cost) }}</span>
                                </div>
                            </div>
                            <div v-if="form.items.length === 0" class="p-8 sm:p-12 text-center text-slate-400">
                                <Receipt class="mx-auto h-12 w-12 text-slate-300 mb-3" />
                                <p class="text-sm font-medium text-slate-500">No items added to this order yet.</p>
                                <p class="text-xs text-slate-400 mt-1">Select a product from the dropdown above to begin.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Totals, Costs & Payment -->
                <div class="w-full lg:w-96 space-y-6 flex-shrink-0">
                    
                    <!-- Landed Costs -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="p-4 sm:p-5 border-b border-slate-100 bg-slate-50">
                            <h3 class="text-sm font-bold text-slate-800 flex items-center">
                                <Truck class="w-4 h-4 mr-2 text-amber-500" />
                                Landed Costs
                            </h3>
                            <p class="text-xs text-slate-500 mt-1">Distributed across received items to calculate the true WAC.</p>
                        </div>
                        <div class="p-4 sm:p-5 space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1 uppercase tracking-wider">Transport / Freight</label>
                                <div class="relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-slate-500 sm:text-sm">{{ business.currency_symbol || 'TSh' }}</span>
                                    </div>
                                    <FormattedNumberInput v-model="form.transport_cost" class="block w-full pl-12 border-slate-300 rounded-lg focus:ring-amber-500 focus:border-amber-500 sm:text-sm font-bold" />
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1 uppercase tracking-wider">Other Costs (Duties, Labor)</label>
                                <div class="relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-slate-500 sm:text-sm">{{ business.currency_symbol || 'TSh' }}</span>
                                    </div>
                                    <FormattedNumberInput v-model="form.other_costs" class="block w-full pl-12 border-slate-300 rounded-lg focus:ring-amber-500 focus:border-amber-500 sm:text-sm font-bold" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="p-4 sm:p-5 border-b border-slate-100 bg-slate-50">
                            <h3 class="text-sm font-bold text-slate-800 flex items-center">
                                <CreditCard class="w-4 h-4 mr-2 text-emerald-500" />
                                Supplier Payment
                            </h3>
                        </div>
                        <div class="p-4 sm:p-5 space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1 uppercase tracking-wider">Amount Paid Now</label>
                                <div class="relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-slate-500 sm:text-sm">{{ business.currency_symbol || 'TSh' }}</span>
                                    </div>
                                    <FormattedNumberInput v-model="form.amount_paid" @input="isAmountManuallyEdited = true" :max="totalAmount" class="block w-full pl-12 border-slate-300 rounded-lg focus:ring-amber-500 focus:border-amber-500 sm:text-sm font-bold text-emerald-600" />
                                </div>
                            </div>
                            <div v-if="form.amount_paid > 0">
                                <label class="block text-xs font-bold text-slate-600 mb-1 uppercase tracking-wider">Payment Method</label>
                                <select v-model="form.payment_method" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm">
                                    <option value="cash">Cash</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="mobile_money">Mobile Money</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="bg-slate-900 rounded-xl shadow-xl text-white overflow-hidden relative">
                        <div class="absolute top-0 right-0 -mr-8 -mt-8 opacity-10 pointer-events-none">
                            <Calculator class="w-32 h-32" />
                        </div>
                        
                        <div class="p-5 sm:p-6 relative z-10">
                            <div class="space-y-3 mb-6">
                                <div class="flex justify-between items-center text-sm text-slate-400">
                                    <span>Items Subtotal</span>
                                    <span>{{ formatCurrency(subtotal) }}</span>
                                </div>
                                <div class="flex justify-between items-center text-sm text-slate-400">
                                    <span>Landed Costs</span>
                                    <span>{{ formatCurrency(totalCosts) }}</span>
                                </div>
                            </div>
                            <div class="flex justify-between items-end border-t border-slate-700 pt-4 mb-3">
                                <span class="text-slate-300 font-bold">Total Value</span>
                                <span class="font-black text-2xl sm:text-3xl tracking-tight text-amber-400">{{ formatCurrency(totalAmount) }}</span>
                            </div>
                            <div class="flex justify-between items-end mb-6 text-sm">
                                <span class="text-slate-400">Balance Due</span>
                                <span class="font-bold text-rose-400">{{ formatCurrency(balanceDue) }}</span>
                            </div>
                            
                            <div class="flex justify-end">
                                <button type="submit" :disabled="form.processing || form.items.length === 0" class="inline-flex justify-center items-center px-6 py-2 border border-transparent shadow-sm text-sm font-bold rounded-lg text-slate-900 bg-amber-400 hover:bg-amber-500 focus:outline-none transition-colors w-full sm:w-auto h-10 disabled:opacity-50">
                                    <Save class="w-4 h-4 mr-2" />
                                    {{ form.processing ? 'Saving...' : 'Save Purchase' }}
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="hidden lg:block">
                        <label class="block text-xs font-bold text-slate-600 mb-1 uppercase tracking-wider">Purchase Notes</label>
                        <textarea v-model="form.notes" rows="3" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" placeholder="Any internal notes..."></textarea>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
