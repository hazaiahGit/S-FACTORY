<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link, usePage } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { 
    Search, 
    ArrowLeft,
    ShoppingCart,
    Trash2,
    Plus,
    Minus,
    User,
    CreditCard,
    Banknote,
    Landmark,
    CheckCircle,
    FileText,
    PauseCircle,
    PenLine,
    Truck,
    Receipt
} from '@lucide/vue';

const props = defineProps({
    products: Array,
    customers: Array,
    defaultBranch: [String, Number]
});

const page = usePage();
const business = page.props.auth.business || {};
const taxEnabled = business.tax_enabled || false;
const taxRate = parseFloat(business.tax_rate || 0);

const searchQuery = ref('');
const priceMode = ref('retail'); // 'retail' or 'wholesale'

const togglePriceMode = (mode) => {
    if (form.items.length > 0 && priceMode.value !== mode) {
        if (confirm(`Switching to ${mode} mode will clear your current cart. Continue?`)) {
            form.items = [];
            priceMode.value = mode;
        }
    } else {
        priceMode.value = mode;
    }
};
const showPaymentModal = ref(false);

const form = useForm({
    branch_id: props.defaultBranch,
    customer_id: '',
    sale_type: 'sale',
    status: 'confirmed',
    fulfillment_status: 'pending',
    transaction_date: new Date().toISOString().split('T')[0],
    discount_amount: 0,
    items: [],
    payments: [],
});

const statusOpts = [
    { value: 'confirmed', label: 'Confirmed', desc: 'Normal sale',    icon: CheckCircle },
    { value: 'credit',    label: 'Credit',    desc: 'Pay later',       icon: CreditCard  },
    { value: 'invoiced',  label: 'Invoiced',  desc: 'Issue invoice',   icon: Receipt     },
    { value: 'on_hold',   label: 'On Hold',   desc: 'Pause for now',   icon: PauseCircle },
    { value: 'draft',     label: 'Draft',     desc: 'Save as draft',   icon: PenLine     },
];

const paymentForm = ref({
    method: 'cash',
    amount: 0,
    reference: '',
});

// Computed properties
const filteredProducts = computed(() => {
    let filtered = props.products;
    
    // Filter out products that don't have a wholesale price if in wholesale mode
    if (priceMode.value === 'wholesale') {
        filtered = filtered.filter(p => parseFloat(p.wholesale_price) > 0);
    }
    
    if (!searchQuery.value) return filtered;
    
    const q = searchQuery.value.toLowerCase();
    return filtered.filter(p => 
        p.name.toLowerCase().includes(q) || 
        (p.sku && p.sku.toLowerCase().includes(q))
    );
});

const subtotal = computed(() => {
    return form.items.reduce((sum, item) => sum + (item.quantity * item.unit_price) - (item.discount_amount || 0), 0);
});

const taxAmount = computed(() => {
    if (!taxEnabled) return 0;
    // Calculate global tax on the subtotal. Alternatively, can be line-item based.
    return subtotal.value * (taxRate / 100);
});

const totalDue = computed(() => {
    return Math.max(0, subtotal.value - (parseFloat(form.discount_amount) || 0) + taxAmount.value);
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-TZ', { style: 'currency', currency: business.currency || 'TZS', maximumFractionDigits: 0 }).format(value);
};

// Actions
const addToCart = (product) => {
    if (product.track_stock && product.current_stock <= 0) return;
    
    const existing = form.items.find(i => i.product_id === product.id && i.price_type === priceMode.value);
    if (existing) {
        existing.quantity += 1;
    } else {
        form.items.push({
            product_id: product.id,
            name: product.name,
            unit_price: priceMode.value === 'wholesale' ? parseFloat(product.wholesale_price) : parseFloat(product.selling_price),
            quantity: 1,
            discount_amount: 0,
            tax_percent: 0,
            price_type: priceMode.value
        });
    }
};

const updateQuantity = (item, delta) => {
    const newQty = item.quantity + delta;
    if (newQty > 0 && newQty <= item.max_qty) {
        item.quantity = newQty;
    }
};

const removeItem = (index) => {
    form.items.splice(index, 1);
};

const openPayment = () => {
    if (form.items.length === 0) return;
    paymentForm.value.amount = form.status === 'credit' || form.status === 'draft' || form.status === 'on_hold' ? 0 : totalDue.value;
    showPaymentModal.value = true;
};

const processCheckout = () => {
    form.payments = [{
        payment_method: paymentForm.value.method,
        amount: paymentForm.value.amount,
        reference: paymentForm.value.reference
    }];
    
    // Apply global tax amount to the first item (or spread it) if the backend expects line-item taxes. 
    // In our backend SaleService, line total is calculated with item tax_amount.
    // For simplicity, we assign the entire tax to the first item so the backend total matches.
    if (form.items.length > 0 && taxEnabled) {
        // Reset all taxes first
        form.items.forEach(i => i.tax_amount = 0);
        form.items[0].tax_amount = taxAmount.value;
    }

    form.post(route('sales.store'), {
        onSuccess: () => {
            showPaymentModal.value = false;
        }
    });
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center">
                <Link :href="route('sales.index')" class="mr-4 text-slate-400 hover:text-slate-600 transition-colors">
                    <ArrowLeft class="w-6 h-6" />
                </Link>
                <span class="font-bold">Point of Sale</span>
            </div>
        </template>

        <div class="flex flex-col lg:flex-row gap-6 h-[calc(100vh-6rem)] -mt-6 -mx-4 sm:-mx-6 px-4 sm:px-6 py-6 bg-slate-100">
            
            <!-- Left: Product Grid -->
            <div class="flex-1 flex flex-col bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                                <!-- Search Bar -->
                <div class="p-5 border-b border-slate-100 bg-white z-10">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-4">
                        <div class="flex bg-slate-100 p-1 rounded-xl w-full sm:w-auto">
                            <button @click="togglePriceMode('retail')" :class="['flex-1 px-4 py-2 rounded-lg text-sm font-bold transition-all', priceMode === 'retail' ? 'bg-white shadow-sm text-indigo-700' : 'text-slate-500 hover:text-slate-700']">Retail Mode</button>
                            <button @click="togglePriceMode('wholesale')" :class="['flex-1 px-4 py-2 rounded-lg text-sm font-bold transition-all', priceMode === 'wholesale' ? 'bg-indigo-600 shadow-sm text-white' : 'text-slate-500 hover:text-slate-700']">Wholesale Mode</button>
                        </div>
                    </div>
                    <div class="relative max-w-2xl">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <Search class="h-5 w-5 text-slate-400" />
                        </div>
                        <input 
                            v-model="searchQuery"
                            type="text" 
                            class="block w-full pl-12 pr-4 py-3.5 border-slate-200 rounded-xl leading-5 bg-slate-50 placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all font-medium" 
                            placeholder="Scan barcode or search products by name, SKU..." 
                        />
                    </div>
                </div>
                
                <!-- Product List -->
                <div class="flex-1 overflow-y-auto p-5 bg-slate-50/50">
                    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-4 sm:gap-5">
                        <div 
                            v-for="product in filteredProducts" 
                            :key="product.id"
                            @click="!product.track_stock || product.current_stock > 0 ? addToCart(product) : null"
                            role="button"
                            tabindex="0"
                            :class="[
                                'relative flex flex-col text-left p-4 sm:p-5 rounded-2xl border transition-all duration-300 group h-full w-full focus:outline-none focus:ring-2 focus:ring-amber-500',
                                product.track_stock && product.current_stock <= 0 
                                    ? 'bg-slate-50 border-slate-200 opacity-60 cursor-not-allowed' 
                                    : 'bg-white border-slate-200 hover:border-amber-400 hover:shadow-lg hover:-translate-y-1 active:scale-95 cursor-pointer'
                            ]"
                        >
                            <!-- Stock Badge (Top Right) -->
                            <div v-if="product.track_stock" class="absolute top-3 right-3">
                                <span :class="['text-[10px] uppercase tracking-wider font-bold px-2 py-1 rounded-md', product.current_stock > 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700']">
                                    {{ product.current_stock }} in stock
                                </span>
                            </div>

                            <div class="flex-1 mt-6">
                                <h3 class="font-bold text-slate-800 text-sm sm:text-base leading-tight group-hover:text-amber-600 transition-colors">{{ product.name }}</h3>
                                <p class="text-xs text-slate-400 mt-1.5 font-medium">{{ product.sku || 'No SKU' }}</p>
                            </div>
                            
                                                        <div class="mt-4 pt-4 border-t border-slate-100 w-full">
                                <span v-if="priceMode === 'wholesale'" class="text-[10px] font-bold text-amber-500 uppercase tracking-wider mb-0.5 block">Wholesale</span>
                                <span class="font-black text-slate-900 text-base sm:text-lg block truncate">{{ formatCurrency(priceMode === 'wholesale' ? product.wholesale_price : product.selling_price) }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Empty State -->
                    <div v-if="filteredProducts.length === 0" class="flex flex-col items-center justify-center h-full text-slate-400">
                        <div class="bg-slate-100 p-6 rounded-full mb-4">
                            <Search class="w-10 h-10 text-slate-300" />
                        </div>
                        <p class="font-medium text-slate-600">No products found</p>
                        <p class="text-sm mt-1">Try a different search term or scan another barcode.</p>
                    </div>
                </div>
            </div>

            <!-- Right: Cart & Checkout -->
            <div class="w-full lg:w-[380px] flex flex-col bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden flex-shrink-0 min-h-0 h-full">

                <!-- MAIN SCROLLABLE AREA (Settings + Cart) -->
                <div class="flex-1 overflow-y-auto sidebar-scroll bg-slate-50/30 flex flex-col">
                    
                    <!-- Settings Section (White background) -->
                    <div class="bg-white border-b border-slate-200 flex-shrink-0">
                        <!-- Customer -->
                        <div class="p-3 border-b border-slate-100">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 block">Customer</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
                                    <User class="h-3.5 w-3.5 text-amber-500" />
                                </div>
                                <select v-model="form.customer_id" class="block w-full pl-8 py-1.5 text-sm border-slate-200 rounded-lg focus:ring-amber-500 focus:border-amber-500 font-medium text-slate-700 bg-slate-50 hover:bg-slate-100 cursor-pointer transition-colors">
                                    <option value="">Walk-in Customer (Guest)</option>
                                    <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
                                </select>
                            </div>
                        </div>

                        <!-- Date (Admin/Manager only) -->
                        <div class="p-3 border-b border-slate-100" v-if="$hasRole('Super Admin') || $hasRole('Manager')">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 block">Transaction Date</label>
                            <input type="date" v-model="form.transaction_date" class="block w-full py-1.5 px-3 text-sm border-slate-200 rounded-lg focus:ring-amber-500 focus:border-amber-500 font-medium text-slate-700 bg-slate-50 hover:bg-slate-100 cursor-pointer transition-colors" />
                        </div>

                        <!-- Sale Status -->
                        <div class="p-3 border-b border-slate-100">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 block">Sale Status</label>
                            <div class="grid grid-cols-3 gap-1">
                                <button v-for="opt in statusOpts" :key="opt.value" type="button" @click="form.status = opt.value"
                                    :class="['flex flex-col items-center gap-1 py-1.5 px-1 rounded-lg border transition-all', form.status === opt.value ? 'border-amber-500 bg-amber-50 shadow-sm' : 'border-slate-100 bg-slate-50 hover:border-slate-200']"
                                >
                                    <component :is="opt.icon" :class="['h-3.5 w-3.5', form.status === opt.value ? 'text-amber-600' : 'text-slate-400']" />
                                    <span class="text-[9px] font-black text-slate-700 leading-tight text-center">{{ opt.label }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Fulfillment -->
                        <div class="p-3">
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 block">Fulfillment</label>
                            <button type="button"
                                @click="form.fulfillment_status = form.fulfillment_status === 'fulfilled' ? 'pending' : 'fulfilled'"
                                :class="['w-full flex items-center gap-2 px-3 py-1.5 rounded-lg border transition-all', form.fulfillment_status === 'fulfilled' ? 'border-teal-500 bg-teal-50' : 'border-slate-100 bg-slate-50 hover:border-slate-200']"
                            >
                                <Truck :class="['h-4 w-4 flex-shrink-0', form.fulfillment_status === 'fulfilled' ? 'text-teal-600' : 'text-slate-400']" />
                                <div class="flex-1 text-left">
                                    <span :class="['text-xs font-black block', form.fulfillment_status === 'fulfilled' ? 'text-teal-700' : 'text-slate-700']">
                                        {{ form.fulfillment_status === 'fulfilled' ? 'Goods Delivered' : 'Not Delivered Yet' }}
                                    </span>
                                </div>
                                <div :class="['w-8 h-4 rounded-full relative flex-shrink-0 transition-colors', form.fulfillment_status === 'fulfilled' ? 'bg-teal-500' : 'bg-slate-200']">
                                    <div :class="['absolute top-0.5 w-3 h-3 bg-white rounded-full shadow transition-all duration-200', form.fulfillment_status === 'fulfilled' ? 'left-4.5' : 'left-0.5']"></div>
                                </div>
                            </button>
                        </div>
                    </div>

                    <!-- Cart Items Section -->
                    <div class="flex-1 p-2 flex flex-col">
                        <div v-if="form.items.length === 0" class="m-auto flex flex-col items-center justify-center text-slate-400 py-10">
                            <ShoppingCart class="w-16 h-16 mb-4 text-slate-200" />
                            <p class="font-medium">Your cart is empty</p>
                            <p class="text-sm mt-1">Add items from the grid to get started</p>
                        </div>
                    
                        <div v-else class="space-y-2 p-3">
                        <div v-for="(item, index) in form.items" :key="index" class="flex flex-col p-4 bg-white border border-slate-200 rounded-xl shadow-sm hover:border-amber-300 transition-colors">
                            <div class="flex justify-between items-start mb-3">
                                <span class="font-bold text-slate-800 text-sm pr-4">{{ item.name }}</span>
                                <button @click="removeItem(index)" class="text-slate-300 hover:text-rose-500 transition-colors">
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </div>
                            
                            <div class="flex items-center justify-between mt-auto">
                                <div class="flex items-center bg-slate-100 rounded-lg p-1">
                                    <button @click="updateQuantity(item, -1)" class="w-7 h-7 flex items-center justify-center text-slate-600 hover:bg-white hover:shadow-sm rounded-md transition-all"><Minus class="w-3 h-3" /></button>
                                    <FormattedNumberInput v-model="item.quantity" class="w-12 text-center text-sm border-none bg-transparent focus:ring-0 p-0 font-bold text-slate-800" />
                                    <button @click="updateQuantity(item, 1)" class="w-7 h-7 flex items-center justify-center text-slate-600 hover:bg-white hover:shadow-sm rounded-md transition-all"><Plus class="w-3 h-3" /></button>
                                </div>
                                <div class="text-right">
                                    <div class="text-xs text-slate-400 mb-0.5">{{ formatCurrency(item.unit_price) }} / ea</div>
                                    <div class="font-bold text-amber-600">{{ formatCurrency((item.quantity * item.unit_price) - item.discount_amount) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                </div> <!-- End of MAIN SCROLLABLE AREA -->

                <!-- Totals & Checkout -->
                <div class="bg-slate-900 text-white rounded-t-2xl shadow-[0_-4px_20px_-10px_rgba(0,0,0,0.3)] mt-auto p-4 sm:p-5 z-10 relative flex-shrink-0">
                    <div v-if="taxEnabled" class="flex justify-between items-center mb-1.5 text-xs text-slate-400">
                        <span>Subtotal</span>
                        <span>{{ formatCurrency(subtotal) }}</span>
                    </div>
                    <div v-if="taxEnabled" class="flex justify-between items-center mb-3 text-xs text-slate-400">
                        <span>VAT ({{ taxRate }}%)</span>
                        <span>{{ formatCurrency(taxAmount) }}</span>
                    </div>
                    <div class="flex justify-between items-center mb-4 border-t border-slate-700 pt-3">
                        <span class="text-slate-300 font-bold text-base">Total Due</span>
                        <span class="font-black text-white text-2xl tracking-tight">{{ formatCurrency(totalDue) }}</span>
                    </div>
                    <button 
                        @click="openPayment" 
                        :disabled="form.items.length === 0"
                        class="w-full flex items-center justify-center px-5 py-3.5 rounded-xl text-base font-bold text-slate-900 bg-amber-400 hover:bg-amber-300 focus:ring-4 focus:ring-amber-500/30 disabled:opacity-50 disabled:bg-slate-700 disabled:text-slate-500 disabled:cursor-not-allowed transition-all transform active:scale-[0.98]"
                    >
                        Charge {{ formatCurrency(totalDue) }}
                        <ShoppingCart class="w-5 h-5 ml-2" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Payment Modal -->
        <div v-if="showPaymentModal" class="fixed inset-0 z-[100] overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" @click="showPaymentModal = false"></div>
                </div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full border border-slate-100">
                    <div class="px-6 pt-6 pb-2">
                        <div class="text-center w-full">
                            <h3 class="text-2xl font-black text-slate-900 mb-1">Payment</h3>
                            <p class="text-slate-500 text-sm mb-6">Select a payment method and enter the amount received.</p>
                            
                            <!-- Error Display -->
                            <div v-if="Object.keys(form.errors).length > 0" class="mb-6 p-4 bg-rose-50 text-rose-700 rounded-xl text-sm border border-rose-100 text-left">
                                <div class="font-bold mb-1">Please fix the following errors:</div>
                                <ul class="list-disc pl-5">
                                    <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
                                </ul>
                            </div>

                            <div class="bg-slate-50 p-6 rounded-2xl border border-slate-100 mb-6 text-center">
                                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Total Amount Due</p>
                                <p class="text-4xl font-black text-slate-900">{{ formatCurrency(totalDue) }}</p>
                            </div>
                            
                            <div class="mb-5 text-left">
                                <label class="block text-sm font-bold text-slate-700 mb-2">Discount Amount</label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-slate-500 font-bold">-</span>
                                    </div>
                                    <FormattedNumberInput v-model="form.discount_amount" class="block w-full pl-8 py-3 text-lg border-slate-200 rounded-xl focus:ring-amber-500 focus:border-amber-500 font-bold text-slate-900 shadow-sm transition-all" placeholder="0" />
                                </div>
                            </div>
                            
                            <div class="space-y-5 text-left">
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Payment Method</label>
                                    <div class="grid grid-cols-3 gap-3">
                                        <button @click="paymentForm.method = 'cash'" :class="['flex flex-col items-center justify-center py-4 px-2 rounded-xl border-2 font-bold transition-all', paymentForm.method === 'cash' ? 'border-slate-900 bg-slate-900 text-white shadow-md' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300']">
                                            <Banknote class="w-6 h-6 mb-2" /> Cash
                                        </button>
                                        <button @click="paymentForm.method = 'mobile_money'" :class="['flex flex-col items-center justify-center py-4 px-2 rounded-xl border-2 font-bold transition-all text-center', paymentForm.method === 'mobile_money' ? 'border-amber-600 bg-amber-600 text-white shadow-md' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300']">
                                            <CreditCard class="w-6 h-6 mb-2" /> Mobile Money
                                        </button>
                                        <button @click="paymentForm.method = 'bank_transfer'" :class="['flex flex-col items-center justify-center py-4 px-2 rounded-xl border-2 font-bold transition-all text-center', paymentForm.method === 'bank_transfer' ? 'border-blue-600 bg-blue-600 text-white shadow-md' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300']">
                                            <Landmark class="w-6 h-6 mb-2" /> Bank Tx
                                        </button>
                                    </div>
                                </div>
                                
                                <div v-if="paymentForm.method === 'mobile_money' || paymentForm.method === 'bank_transfer'">
                                    <label class="block text-sm font-bold text-slate-700 mb-2">
                                        {{ paymentForm.method === 'mobile_money' ? 'Mobile Number / Ref ID' : 'Bank Reference Number' }}
                                    </label>
                                    <div class="relative rounded-xl shadow-sm">
                                        <input v-model="paymentForm.reference" type="text" class="block w-full py-3 px-4 border-2 border-slate-200 rounded-xl focus:ring-0 focus:border-amber-500 font-semibold text-slate-900 transition-colors" placeholder="e.g. 07XXXXXXXX or TX-12345" />
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Amount Received</label>
                                    <div class="relative rounded-xl shadow-sm">
                                        <FormattedNumberInput v-model="paymentForm.amount" class="block w-full py-4 pl-4 pr-4 border-2 border-slate-200 rounded-xl focus:ring-0 focus:border-amber-500 text-2xl font-black text-slate-900 transition-colors" />
                                    </div>
                                    <div v-if="paymentForm.amount > totalDue" class="mt-3 p-3 bg-emerald-50 text-emerald-800 rounded-lg flex justify-between items-center border border-emerald-100">
                                        <span class="font-bold text-sm uppercase tracking-wide">Change Due</span>
                                        <span class="font-black text-lg">{{ formatCurrency(paymentForm.amount - totalDue) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-5 sm:flex sm:flex-row-reverse mt-4 gap-3 border-t border-slate-100 bg-slate-50">
                        <button @click="processCheckout" :disabled="form.processing" type="button" class="w-full sm:w-auto flex-1 inline-flex justify-center items-center rounded-xl border border-transparent px-6 py-3.5 bg-amber-500 text-base font-bold text-slate-900 shadow-sm hover:bg-amber-400 focus:outline-none focus:ring-4 focus:ring-amber-500/30 disabled:opacity-50 transition-all">
                            {{ form.processing ? 'Processing...' : 'Complete Sale' }}
                        </button>
                        <button @click="showPaymentModal = false" type="button" class="mt-3 sm:mt-0 w-full sm:w-auto inline-flex justify-center items-center rounded-xl border-2 border-slate-200 px-6 py-3.5 bg-white text-base font-bold text-slate-700 shadow-sm hover:bg-slate-50 hover:border-slate-300 focus:outline-none transition-all">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
