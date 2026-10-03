<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
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
    Receipt,
    Package,
    X,
    AlertCircle,
    ChevronUp,
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

// Mobile cart sheet
const mobileCartOpen = ref(false);

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

// Quantity Picker Modal State
const showQuantityModal = ref(false);
const activeProductForModal = ref(null);
const modalQty = ref(1);

const quickPresets = [
    [1, 2, 3, 4, 5, 6],
    [10, 12, 15, 20, 24, 50]
];

const openQuantityModal = (product) => {
    if (product.track_stock && product.current_stock <= 0) return;
    activeProductForModal.value = product;

    const existing = form.items.find(i => i.product_id === product.id && i.price_type === priceMode.value);
    modalQty.value = existing ? existing.quantity : 1;
    showQuantityModal.value = true;
};

const openQuantityModalFromItem = (item) => {
    const product = props.products.find(p => p.id === item.product_id);
    if (product) {
        activeProductForModal.value = product;
        modalQty.value = item.quantity;
        showQuantityModal.value = true;
    }
};

const closeQuantityModal = () => {
    showQuantityModal.value = false;
    activeProductForModal.value = null;
    modalQty.value = 1;
};

const setPresetQty = (qty) => {
    const max = activeProductForModal.value?.track_stock ? activeProductForModal.value.current_stock : 999999;
    modalQty.value = Math.min(qty, max);
};

const incrementModalQty = () => {
    const max = activeProductForModal.value?.track_stock ? activeProductForModal.value.current_stock : 999999;
    const current = parseInt(modalQty.value) || 0;
    if (current < max) {
        modalQty.value = current + 1;
    }
};

const decrementModalQty = () => {
    const current = parseInt(modalQty.value) || 1;
    if (current > 1) {
        modalQty.value = current - 1;
    }
};

const activeProductUnitPrice = computed(() => {
    if (!activeProductForModal.value) return 0;
    return priceMode.value === 'wholesale'
        ? parseFloat(activeProductForModal.value.wholesale_price || 0)
        : parseFloat(activeProductForModal.value.selling_price || 0);
});

const modalTotalAmount = computed(() => {
    return (parseInt(modalQty.value) || 0) * activeProductUnitPrice.value;
});

const confirmAddFromModal = () => {
    if (!activeProductForModal.value) return;
    const qty = Math.max(1, parseInt(modalQty.value) || 1);
    const product = activeProductForModal.value;

    const existing = form.items.find(i => i.product_id === product.id && i.price_type === priceMode.value);
    if (existing) {
        existing.quantity = qty;
    } else {
        form.items.push({
            product_id: product.id,
            name: product.name,
            unit_price: activeProductUnitPrice.value,
            quantity: qty,
            discount_amount: 0,
            tax_percent: 0,
            price_type: priceMode.value,
            max_qty: product.track_stock ? product.current_stock : 999999
        });
    }
    closeQuantityModal();
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
    discount_percent: 0,
    notes: '',
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

const isPaymentAmountManuallyEdited = ref(false);

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
    const rawDiscount = parseFloat(form.discount_amount) || 0;
    const effectiveDiscount = Math.min(rawDiscount, subtotal.value);
    return Math.max(0, subtotal.value - effectiveDiscount + taxAmount.value);
});

const applyQuickDiscount = (percent) => {
    if (percent === 0) {
        form.discount_amount = 0;
    } else {
        form.discount_amount = Math.round(subtotal.value * (percent / 100));
    }
    if (!isPaymentAmountManuallyEdited.value && form.status !== 'credit' && form.status !== 'draft' && form.status !== 'on_hold') {
        paymentForm.value.amount = totalDue.value;
    }
};

// Automatically keep payment amount in sync with total due (accounting for discounts)
watch(totalDue, (newTotal) => {
    if (!isPaymentAmountManuallyEdited.value && form.status !== 'credit' && form.status !== 'draft' && form.status !== 'on_hold') {
        paymentForm.value.amount = newTotal;
    }
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
    isPaymentAmountManuallyEdited.value = false;
    paymentForm.value.amount = form.status === 'credit' || form.status === 'draft' || form.status === 'on_hold' ? 0 : totalDue.value;
    showPaymentModal.value = true;
    mobileCartOpen.value = false;
};

const processCheckout = () => {
    const enteredAmount = Number(paymentForm.value.amount) || 0;
    const finalAmount = form.status === 'credit'
        ? Math.min(enteredAmount, totalDue.value)
        : (paymentForm.value.method === 'cash' ? Math.min(enteredAmount, totalDue.value) : enteredAmount);

    form.payments = [{
        payment_method: paymentForm.value.method,
        amount: finalAmount,
        reference: paymentForm.value.reference
    }];
    
    // Ensure discount is clamped and discount_percent is set
    form.discount_amount = Math.min(parseFloat(form.discount_amount) || 0, subtotal.value);
    form.discount_percent = subtotal.value > 0 ? (form.discount_amount / subtotal.value) * 100 : 0;

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
                <Link :href="route('sales.index')" class="mr-3 sm:mr-4 text-slate-400 hover:text-slate-600 transition-colors">
                    <ArrowLeft class="w-5 h-5 sm:w-6 sm:h-6" />
                </Link>
                <span class="font-bold text-base sm:text-xl">Point of Sale</span>
            </div>
        </template>

        <div class="flex flex-col lg:flex-row gap-4 lg:gap-6 lg:h-[calc(100vh-6rem)] -mt-4 sm:-mt-6 -mx-4 sm:-mx-6 px-3 sm:px-6 py-4 sm:py-6 bg-slate-100">
            
            <!-- Left: Product Grid -->
            <div class="flex-1 flex flex-col bg-white rounded-xl lg:rounded-2xl shadow-sm border border-slate-200 overflow-hidden min-h-0">
                <!-- Search Bar -->
                <div class="p-3 sm:p-5 border-b border-slate-100 bg-white z-10">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 sm:gap-4 mb-3 sm:mb-4">
                        <div class="flex bg-slate-100 p-1 rounded-xl w-full sm:w-auto">
                            <button @click="togglePriceMode('retail')" :class="['flex-1 px-3 sm:px-4 py-2 rounded-lg text-xs sm:text-sm font-bold transition-all', priceMode === 'retail' ? 'bg-white shadow-sm text-indigo-700' : 'text-slate-500 hover:text-slate-700']">Retail</button>
                            <button @click="togglePriceMode('wholesale')" :class="['flex-1 px-3 sm:px-4 py-2 rounded-lg text-xs sm:text-sm font-bold transition-all', priceMode === 'wholesale' ? 'bg-indigo-600 shadow-sm text-white' : 'text-slate-500 hover:text-slate-700']">Wholesale</button>
                        </div>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 sm:pl-4 flex items-center pointer-events-none">
                            <Search class="h-4 w-4 sm:h-5 sm:w-5 text-slate-400" />
                        </div>
                        <input 
                            v-model="searchQuery"
                            type="text" 
                            class="block w-full pl-10 sm:pl-12 pr-4 py-3 sm:py-3.5 border-slate-200 rounded-xl leading-5 bg-slate-50 placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-amber-500 focus:border-amber-500 transition-all font-medium text-sm sm:text-base" 
                            placeholder="Search products..." 
                        />
                    </div>
                </div>
                
                <!-- Product List -->
                <div class="flex-1 overflow-y-auto p-3 sm:p-5 bg-slate-50/50">
                    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-2.5 sm:gap-5">
                        <div 
                            v-for="product in filteredProducts" 
                            :key="product.id"
                            @click="!product.track_stock || product.current_stock > 0 ? openQuantityModal(product) : null"
                            role="button"
                            tabindex="0"
                            :class="[
                                'relative flex flex-col text-left p-3 sm:p-5 rounded-xl sm:rounded-2xl border transition-all duration-200 group h-full w-full focus:outline-none focus:ring-2 focus:ring-amber-500',
                                product.track_stock && product.current_stock <= 0 
                                    ? 'bg-slate-50 border-slate-200 opacity-60 cursor-not-allowed' 
                                    : 'bg-white border-slate-200 hover:border-amber-400 hover:shadow-lg active:scale-[0.97] cursor-pointer'
                            ]"
                        >
                            <!-- Stock Badge -->
                            <div v-if="product.track_stock" class="absolute top-2 right-2 sm:top-3 sm:right-3">
                                <span :class="['text-[8px] sm:text-[10px] uppercase tracking-wider font-bold px-1.5 sm:px-2 py-0.5 sm:py-1 rounded-md', product.current_stock > 0 ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700']">
                                    {{ product.current_stock }} <span class="hidden sm:inline">in stock</span>
                                </span>
                            </div>

                            <div class="flex-1 mt-4 sm:mt-6">
                                <h3 class="font-bold text-slate-800 text-xs sm:text-sm leading-tight group-hover:text-amber-600 transition-colors line-clamp-2">{{ product.name }}</h3>
                                <p class="text-[10px] sm:text-xs text-slate-400 mt-1 font-medium truncate">{{ product.sku || 'No SKU' }}</p>
                            </div>
                            
                            <div class="mt-2 sm:mt-4 pt-2 sm:pt-4 border-t border-slate-100 w-full">
                                <span v-if="priceMode === 'wholesale'" class="text-[8px] sm:text-[10px] font-bold text-amber-500 uppercase tracking-wider mb-0.5 block">Wholesale</span>
                                <span class="font-black text-slate-900 text-sm sm:text-lg block truncate">{{ formatCurrency(priceMode === 'wholesale' ? product.wholesale_price : product.selling_price) }}</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Empty State -->
                    <div v-if="filteredProducts.length === 0" class="flex flex-col items-center justify-center h-full text-slate-400 py-12">
                        <div class="bg-slate-100 p-4 sm:p-6 rounded-full mb-3 sm:mb-4">
                            <Search class="w-8 h-8 sm:w-10 sm:h-10 text-slate-300" />
                        </div>
                        <p class="font-medium text-slate-600 text-sm sm:text-base">No products found</p>
                        <p class="text-xs sm:text-sm mt-1">Try a different search term.</p>
                    </div>
                </div>
            </div>

            <!-- Mobile Floating Cart Button -->
            <button 
                v-if="form.items.length > 0"
                @click="mobileCartOpen = true"
                class="fixed bottom-4 right-4 z-40 lg:hidden flex items-center gap-2 px-5 py-3.5 rounded-full bg-amber-500 text-slate-900 font-black shadow-xl shadow-amber-500/30 active:scale-95 transition-all"
            >
                <ShoppingCart class="w-5 h-5" />
                <span>{{ form.items.length }}</span>
                <span class="text-sm">•</span>
                <span class="text-sm">{{ formatCurrency(totalDue) }}</span>
            </button>

            <!-- Mobile Cart Bottom Sheet Backdrop -->
            <div v-if="mobileCartOpen" @click="mobileCartOpen = false" class="fixed inset-0 z-40 bg-slate-900/70 backdrop-blur-sm lg:hidden transition-opacity"></div>

            <!-- Right: Cart & Checkout (Desktop: sidebar, Mobile: bottom sheet) -->
            <div 
                :class="[
                    'lg:w-[380px] flex flex-col bg-white shadow-sm border border-slate-200 overflow-hidden flex-shrink-0 min-h-0 transition-transform duration-300 ease-out',
                    // Mobile: bottom sheet
                    'fixed inset-x-0 bottom-0 z-50 lg:static lg:z-auto rounded-t-2xl lg:rounded-2xl max-h-[85vh] lg:max-h-none lg:h-full',
                    mobileCartOpen ? 'translate-y-0' : 'translate-y-full lg:translate-y-0'
                ]"
            >
                <!-- Mobile Sheet Handle -->
                <div class="lg:hidden flex justify-center pt-2 pb-1 bg-white">
                    <button @click="mobileCartOpen = false" class="w-10 h-1.5 rounded-full bg-slate-300"></button>
                </div>

                <!-- MAIN SCROLLABLE AREA (Settings + Cart) -->
                <div class="flex-1 overflow-y-auto sidebar-scroll bg-slate-50/30 flex flex-col">
                    
                    <!-- Settings Section -->
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
                            <div class="flex gap-1 overflow-x-auto pb-1 scrollbar-hide">
                                <button v-for="opt in statusOpts" :key="opt.value" type="button" @click="form.status = opt.value"
                                    :class="['flex flex-col items-center gap-1 py-1.5 px-2 sm:px-3 rounded-lg border transition-all flex-shrink-0 min-w-[56px]', form.status === opt.value ? 'border-amber-500 bg-amber-50 shadow-sm' : 'border-slate-100 bg-slate-50 hover:border-slate-200']"
                                >
                                    <component :is="opt.icon" :class="['h-3.5 w-3.5', form.status === opt.value ? 'text-amber-600' : 'text-slate-400']" />
                                    <span class="text-[9px] font-black text-slate-700 leading-tight text-center whitespace-nowrap">{{ opt.label }}</span>
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
                        <div v-if="form.items.length === 0" class="m-auto flex flex-col items-center justify-center text-slate-400 py-8 sm:py-10">
                            <ShoppingCart class="w-12 h-12 sm:w-16 sm:h-16 mb-3 sm:mb-4 text-slate-200" />
                            <p class="font-medium text-sm sm:text-base">Your cart is empty</p>
                            <p class="text-xs sm:text-sm mt-1">Add items from the grid to get started</p>
                        </div>
                    
                        <div v-else class="space-y-2 p-2 sm:p-3">
                            <div v-for="(item, index) in form.items" :key="index" @click="openQuantityModalFromItem(item)" class="flex items-center gap-3 p-3 bg-white border border-slate-200 rounded-xl shadow-sm hover:border-amber-400 hover:shadow-md transition-all cursor-pointer group">
                                <div class="flex-1 min-w-0">
                                    <span class="font-bold text-slate-800 text-sm block truncate group-hover:text-amber-600 transition-colors">{{ item.name }}</span>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-xs text-slate-400">{{ item.quantity }} × {{ formatCurrency(item.unit_price) }}</span>
                                    </div>
                                </div>
                                <span class="font-bold text-amber-600 text-sm whitespace-nowrap">{{ formatCurrency((item.quantity * item.unit_price) - item.discount_amount) }}</span>
                                <button @click.stop="removeItem(index)" class="text-slate-300 hover:text-rose-500 transition-colors p-1 shrink-0" title="Remove item">
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div> <!-- End of MAIN SCROLLABLE AREA -->

                <!-- Totals & Checkout -->
                <div class="bg-slate-900 text-white rounded-t-xl sm:rounded-t-2xl shadow-[0_-4px_20px_-10px_rgba(0,0,0,0.3)] mt-auto p-3 sm:p-5 z-10 relative flex-shrink-0">
                    <div v-if="taxEnabled" class="flex justify-between items-center mb-1.5 text-xs text-slate-400">
                        <span>Subtotal</span>
                        <span>{{ formatCurrency(subtotal) }}</span>
                    </div>
                    <div v-if="taxEnabled" class="flex justify-between items-center mb-3 text-xs text-slate-400">
                        <span>VAT ({{ taxRate }}%)</span>
                        <span>{{ formatCurrency(taxAmount) }}</span>
                    </div>
                    <div v-if="Number(form.discount_amount) > 0" class="flex justify-between items-center mb-1.5 text-xs text-emerald-400 font-semibold">
                        <span>Discount</span>
                        <span>-{{ formatCurrency(form.discount_amount) }}</span>
                    </div>
                    <div class="flex justify-between items-center mb-3 sm:mb-4 border-t border-slate-700 pt-2 sm:pt-3">
                        <span class="text-slate-300 font-bold text-sm sm:text-base">Total Due</span>
                        <span class="font-black text-white text-xl sm:text-2xl tracking-tight">{{ formatCurrency(totalDue) }}</span>
                    </div>
                    <button 
                        @click="openPayment" 
                        :disabled="form.items.length === 0"
                        class="w-full flex items-center justify-center px-4 sm:px-5 py-3 sm:py-3.5 rounded-xl text-sm sm:text-base font-bold text-slate-900 bg-amber-400 hover:bg-amber-300 focus:ring-4 focus:ring-amber-500/30 disabled:opacity-50 disabled:bg-slate-700 disabled:text-slate-500 disabled:cursor-not-allowed transition-all transform active:scale-[0.98]"
                    >
                        Charge {{ formatCurrency(totalDue) }}
                        <ShoppingCart class="w-4 h-4 sm:w-5 sm:h-5 ml-2" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Payment Modal -->
        <div v-if="showPaymentModal" class="fixed inset-0 z-[100] overflow-y-auto">
            <div class="flex items-end sm:items-center justify-center min-h-screen sm:px-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" @click="showPaymentModal = false"></div>
                </div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-t-2xl sm:rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full border border-slate-100 max-h-[95vh] sm:max-h-[90vh] overflow-y-auto">
                    <div class="px-4 sm:px-6 pt-5 sm:pt-6 pb-2">
                        <div class="text-center w-full">
                            <div class="flex items-center justify-between sm:justify-center mb-4 sm:mb-0">
                                <h3 class="text-xl sm:text-2xl font-black text-slate-900">Payment</h3>
                                <button @click="showPaymentModal = false" class="sm:hidden w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center">
                                    <X class="w-4 h-4 text-slate-500" />
                                </button>
                            </div>
                            <p class="text-slate-500 text-xs sm:text-sm mb-4 sm:mb-6">Select payment method and enter amount.</p>
                            
                            <!-- Error Display -->
                            <div v-if="Object.keys(form.errors).length > 0" class="mb-4 sm:mb-6 p-3 sm:p-4 bg-rose-50 text-rose-700 rounded-xl text-sm border border-rose-100 text-left">
                                <div class="font-bold mb-1">Please fix the following errors:</div>
                                <ul class="list-disc pl-5">
                                    <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
                                </ul>
                            </div>

                            <div class="bg-slate-50 p-4 sm:p-6 rounded-2xl border border-slate-100 mb-4 sm:mb-6 text-center">
                                <p class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Total Amount Due</p>
                                <p class="text-3xl sm:text-4xl font-black text-slate-900">{{ formatCurrency(totalDue) }}</p>
                            </div>
                            
                            <div class="mb-4 sm:mb-5 text-left">
                                <div class="flex items-center justify-between mb-2">
                                    <label class="block text-sm font-bold text-slate-700">Discount Amount</label>
                                    <span v-if="subtotal > 0 && Number(form.discount_amount) > 0" class="text-xs font-bold text-emerald-600 bg-emerald-50 border border-emerald-200 px-2 py-0.5 rounded-full">
                                        {{ Math.round((Number(form.discount_amount) / subtotal) * 100) }}% off
                                    </span>
                                </div>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-slate-500 font-bold">-</span>
                                    </div>
                                    <FormattedNumberInput v-model="form.discount_amount" class="block w-full pl-8 py-3 text-lg border-slate-200 rounded-xl focus:ring-amber-500 focus:border-amber-500 font-bold text-slate-900 shadow-sm transition-all" placeholder="0" />
                                </div>
                                <!-- Quick presets -->
                                <div v-if="subtotal > 0" class="flex items-center gap-1.5 mt-2 flex-wrap">
                                    <button
                                        type="button"
                                        @click="applyQuickDiscount(0)"
                                        :class="['px-2.5 py-1 text-xs font-bold rounded-lg transition-all', !form.discount_amount || form.discount_amount == 0 ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200']"
                                    >
                                        0%
                                    </button>
                                    <button
                                        type="button"
                                        @click="applyQuickDiscount(5)"
                                        :class="['px-2.5 py-1 text-xs font-bold rounded-lg transition-all', form.discount_amount == Math.round(subtotal * 0.05) ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-800 hover:bg-amber-100 border border-amber-200']"
                                    >
                                        5%
                                    </button>
                                    <button
                                        type="button"
                                        @click="applyQuickDiscount(10)"
                                        :class="['px-2.5 py-1 text-xs font-bold rounded-lg transition-all', form.discount_amount == Math.round(subtotal * 0.10) ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-800 hover:bg-amber-100 border border-amber-200']"
                                    >
                                        10%
                                    </button>
                                    <button
                                        type="button"
                                        @click="applyQuickDiscount(15)"
                                        :class="['px-2.5 py-1 text-xs font-bold rounded-lg transition-all', form.discount_amount == Math.round(subtotal * 0.15) ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-800 hover:bg-amber-100 border border-amber-200']"
                                    >
                                        15%
                                    </button>
                                    <button
                                        type="button"
                                        @click="applyQuickDiscount(20)"
                                        :class="['px-2.5 py-1 text-xs font-bold rounded-lg transition-all', form.discount_amount == Math.round(subtotal * 0.20) ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-800 hover:bg-amber-100 border border-amber-200']"
                                    >
                                        20%
                                    </button>
                                </div>
                            </div>
                            
                            <div class="space-y-4 sm:space-y-5 text-left">
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">Payment Method</label>
                                    <div class="grid grid-cols-3 gap-2 sm:gap-3">
                                        <button @click="paymentForm.method = 'cash'" :class="['flex flex-col items-center justify-center py-3 sm:py-4 px-2 rounded-xl border-2 font-bold transition-all text-xs sm:text-sm', paymentForm.method === 'cash' ? 'border-slate-900 bg-slate-900 text-white shadow-md' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300']">
                                            <Banknote class="w-5 h-5 sm:w-6 sm:h-6 mb-1.5 sm:mb-2" /> Cash
                                        </button>
                                        <button @click="paymentForm.method = 'mobile_money'" :class="['flex flex-col items-center justify-center py-3 sm:py-4 px-2 rounded-xl border-2 font-bold transition-all text-center text-xs sm:text-sm', paymentForm.method === 'mobile_money' ? 'border-amber-600 bg-amber-600 text-white shadow-md' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300']">
                                            <CreditCard class="w-5 h-5 sm:w-6 sm:h-6 mb-1.5 sm:mb-2" /> Mobile
                                        </button>
                                        <button @click="paymentForm.method = 'bank_transfer'" :class="['flex flex-col items-center justify-center py-3 sm:py-4 px-2 rounded-xl border-2 font-bold transition-all text-center text-xs sm:text-sm', paymentForm.method === 'bank_transfer' ? 'border-blue-600 bg-blue-600 text-white shadow-md' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300']">
                                            <Landmark class="w-5 h-5 sm:w-6 sm:h-6 mb-1.5 sm:mb-2" /> Bank
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
                                
                                <!-- Credit Sale Indicator & Deposit Presets -->
                                <div v-if="form.status === 'credit'" class="p-3 sm:p-4 bg-amber-50/80 rounded-2xl border border-amber-200/80 space-y-2.5">
                                    <div class="flex items-center justify-between flex-wrap gap-1">
                                        <span class="text-[10px] sm:text-xs font-black text-amber-900 uppercase tracking-wider flex items-center gap-1.5">
                                            <CreditCard class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-amber-600" />
                                            Credit Sale (Mkopo)
                                        </span>
                                        <span class="text-[10px] sm:text-xs font-mono font-bold text-amber-900">
                                            Deni: {{ formatCurrency(Math.max(0, totalDue - (Number(paymentForm.amount) || 0))) }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-1.5 flex-wrap">
                                        <button
                                            type="button"
                                            @click="paymentForm.amount = 0"
                                            :class="[
                                                'px-2.5 sm:px-3 py-1.5 text-[10px] sm:text-xs font-bold rounded-xl transition-all',
                                                paymentForm.amount === 0 
                                                    ? 'bg-slate-900 text-white shadow-xs' 
                                                    : 'bg-white border border-amber-200 text-slate-700 hover:bg-amber-100'
                                            ]"
                                        >
                                            Full Credit
                                        </button>
                                        <button
                                            v-if="totalDue > 0"
                                            type="button"
                                            @click="paymentForm.amount = Math.round(totalDue * 0.25)"
                                            :class="[
                                                'px-2.5 sm:px-3 py-1.5 text-[10px] sm:text-xs font-bold rounded-xl transition-all',
                                                paymentForm.amount === Math.round(totalDue * 0.25)
                                                    ? 'bg-amber-500 text-white shadow-xs' 
                                                    : 'bg-white border border-amber-200 text-slate-700 hover:bg-amber-100'
                                            ]"
                                        >
                                            25%
                                        </button>
                                        <button
                                            v-if="totalDue > 0"
                                            type="button"
                                            @click="paymentForm.amount = Math.round(totalDue * 0.5)"
                                            :class="[
                                                'px-2.5 sm:px-3 py-1.5 text-[10px] sm:text-xs font-bold rounded-xl transition-all',
                                                paymentForm.amount === Math.round(totalDue * 0.5)
                                                    ? 'bg-amber-500 text-white shadow-xs' 
                                                    : 'bg-white border border-amber-200 text-slate-700 hover:bg-amber-100'
                                            ]"
                                        >
                                            50%
                                        </button>
                                        <button
                                            v-if="totalDue > 0"
                                            type="button"
                                            @click="paymentForm.amount = totalDue"
                                            :class="[
                                                'px-2.5 sm:px-3 py-1.5 text-[10px] sm:text-xs font-bold rounded-xl transition-all',
                                                paymentForm.amount === totalDue
                                                    ? 'bg-emerald-600 text-white shadow-xs' 
                                                    : 'bg-white border border-amber-200 text-slate-700 hover:bg-amber-100'
                                            ]"
                                        >
                                            Full Paid
                                        </button>
                                    </div>
                                    <p v-if="!form.customer_id" class="text-[10px] sm:text-[11px] text-amber-800 font-medium flex items-center gap-1.5 pt-1">
                                        <AlertCircle class="w-3.5 h-3.5 shrink-0 text-amber-600" />
                                        Mteja hajachaguliwa. Unashauriwa kuchagua mteja ili deni liingie kwenye rekodi zake.
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-2">
                                        {{ form.status === 'credit' ? 'Down Payment (TZS)' : 'Amount Received' }}
                                    </label>
                                    <div class="relative rounded-xl shadow-sm">
                                        <FormattedNumberInput v-model="paymentForm.amount" @input="isPaymentAmountManuallyEdited = true" class="block w-full py-3.5 sm:py-4 pl-4 pr-4 border-2 border-slate-200 rounded-xl focus:ring-0 focus:border-amber-500 text-xl sm:text-2xl font-black text-slate-900 transition-colors" />
                                    </div>
                                    <div v-if="paymentForm.amount > totalDue" class="mt-3 p-3 bg-emerald-50 text-emerald-800 rounded-lg flex justify-between items-center border border-emerald-100">
                                        <span class="font-bold text-xs sm:text-sm uppercase tracking-wide">Change Due</span>
                                        <span class="font-black text-base sm:text-lg">{{ formatCurrency(paymentForm.amount - totalDue) }}</span>
                                    </div>
                                    <div v-else-if="form.status === 'credit' && paymentForm.amount < totalDue" class="mt-3 p-3 bg-rose-50 text-rose-800 rounded-lg flex justify-between items-center border border-rose-100">
                                        <span class="font-bold text-[10px] sm:text-xs uppercase tracking-wide">Remaining Credit</span>
                                        <span class="font-black text-sm sm:text-base font-mono">{{ formatCurrency(totalDue - (Number(paymentForm.amount) || 0)) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="px-4 sm:px-6 py-4 sm:py-5 flex flex-col-reverse sm:flex-row sm:flex-row-reverse mt-2 sm:mt-4 gap-2 sm:gap-3 border-t border-slate-100 bg-slate-50">
                        <button @click="processCheckout" :disabled="form.processing" type="button" class="w-full sm:w-auto sm:flex-1 inline-flex justify-center items-center rounded-xl border border-transparent px-6 py-3.5 bg-amber-500 text-sm sm:text-base font-bold text-slate-900 shadow-sm hover:bg-amber-400 focus:outline-none focus:ring-4 focus:ring-amber-500/30 disabled:opacity-50 transition-all">
                            {{ form.processing ? 'Processing...' : 'Complete Sale' }}
                        </button>
                        <button @click="showPaymentModal = false" type="button" class="w-full sm:w-auto inline-flex justify-center items-center rounded-xl border-2 border-slate-200 px-6 py-3.5 bg-white text-sm sm:text-base font-bold text-slate-700 shadow-sm hover:bg-slate-50 hover:border-slate-300 focus:outline-none transition-all">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quantity Picker Modal -->
        <div v-if="showQuantityModal" class="fixed inset-0 z-[100] overflow-y-auto">
            <div class="flex items-end sm:items-center justify-center min-h-screen sm:px-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-slate-900/70 backdrop-blur-sm transition-opacity" @click="closeQuantityModal"></div>
                </div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div
                    v-if="activeProductForModal"
                    class="inline-block align-bottom bg-white rounded-t-3xl sm:rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md w-full border border-slate-100 p-4 sm:p-6 space-y-4 sm:space-y-5"
                >
                    <!-- Header -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3 min-w-0">
                            <div class="w-11 h-11 sm:w-14 sm:h-14 rounded-xl sm:rounded-2xl bg-amber-50/70 border border-amber-200/80 flex items-center justify-center p-2 sm:p-2.5 shrink-0 shadow-xs">
                                <img
                                    v-if="activeProductForModal.image"
                                    :src="activeProductForModal.image"
                                    class="w-full h-full object-cover rounded-lg sm:rounded-xl"
                                    alt="Product"
                                />
                                <Package v-else class="w-5 h-5 sm:w-7 sm:h-7 text-amber-500" />
                            </div>
                            <div class="min-w-0">
                                <h2 class="text-base sm:text-xl font-black text-slate-900 leading-snug uppercase truncate">
                                    {{ activeProductForModal.name }}
                                </h2>
                                <span class="inline-flex items-center px-2.5 sm:px-3 py-0.5 rounded-full text-[10px] sm:text-xs font-bold bg-emerald-50 text-emerald-600 border border-emerald-200/80 mt-1">
                                    {{ formatCurrency(activeProductUnitPrice) }}
                                </span>
                            </div>
                        </div>
                        <button
                            @click="closeQuantityModal"
                            class="w-8 h-8 sm:w-9 sm:h-9 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-400 hover:text-slate-600 flex items-center justify-center transition-colors shrink-0"
                            title="Close"
                        >
                            <X class="w-4 h-4 sm:w-5 sm:h-5" />
                        </button>
                    </div>

                    <!-- Quantity Stepper Box -->
                    <div class="bg-slate-50/80 border border-slate-200/90 rounded-2xl sm:rounded-3xl p-4 sm:p-5 text-center">
                        <span class="text-[10px] sm:text-[11px] font-black tracking-widest text-slate-400 uppercase mb-2 sm:mb-3 block">
                            QUANTITY (IDADI)
                        </span>
                        <div class="flex items-center justify-center gap-3 sm:gap-4">
                            <button
                                type="button"
                                @click="decrementModalQty"
                                class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl sm:rounded-2xl bg-white border-2 border-slate-200 text-2xl font-bold text-slate-700 flex items-center justify-center hover:bg-slate-50 active:scale-95 transition-all shadow-xs"
                            >
                                <Minus class="w-5 h-5 sm:w-6 sm:h-6 stroke-[2.5]" />
                            </button>
                            <div class="border-2 border-amber-500 rounded-xl sm:rounded-2xl w-28 sm:w-36 h-14 sm:h-16 flex items-center justify-center bg-white shadow-xs">
                                <input
                                    v-model.number="modalQty"
                                    type="number"
                                    min="1"
                                    :max="activeProductForModal.track_stock ? activeProductForModal.current_stock : 999999"
                                    class="w-full text-center text-2xl sm:text-3xl font-black text-slate-900 font-mono border-none focus:ring-0 p-0 bg-transparent"
                                />
                            </div>
                            <button
                                type="button"
                                @click="incrementModalQty"
                                class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl sm:rounded-2xl bg-amber-500 hover:bg-amber-600 text-white text-2xl font-bold flex items-center justify-center active:scale-95 transition-all shadow-lg shadow-amber-500/25"
                            >
                                <Plus class="w-5 h-5 sm:w-6 sm:h-6 stroke-[2.5]" />
                            </button>
                        </div>
                    </div>

                    <!-- Quick Presets -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-[10px] sm:text-[11px] font-black tracking-wider text-slate-400 uppercase px-1">
                            <span>QUICK PRESETS</span>
                            <span>ONE-TAP</span>
                        </div>
                        <div class="space-y-1.5 sm:space-y-2">
                            <div v-for="(row, rIdx) in quickPresets" :key="rIdx" class="grid grid-cols-6 gap-1.5 sm:gap-2">
                                <button
                                    v-for="preset in row"
                                    :key="preset"
                                    type="button"
                                    @click="setPresetQty(preset)"
                                    :class="[
                                        'py-2 sm:py-2.5 rounded-lg sm:rounded-xl text-xs sm:text-sm font-bold transition-all active:scale-95',
                                        modalQty === preset
                                            ? 'bg-slate-900 text-white shadow-xs'
                                            : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50'
                                    ]"
                                >
                                    {{ preset }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Total Jumla Bar -->
                    <div class="bg-slate-900 text-white rounded-xl sm:rounded-2xl p-3 sm:p-4 flex items-center justify-between shadow-md">
                        <div>
                            <span class="text-[9px] sm:text-[10px] font-black tracking-wider text-slate-400 uppercase block">
                                TOTAL (JUMLA)
                            </span>
                            <span class="text-[10px] sm:text-xs text-slate-300 font-medium mt-0.5 block">
                                {{ formatCurrency(activeProductUnitPrice) }} × {{ modalQty || 0 }}
                            </span>
                        </div>
                        <span class="text-xl sm:text-2xl font-black text-emerald-400 font-mono">
                            {{ formatCurrency(modalTotalAmount) }}
                        </span>
                    </div>

                    <!-- Actions Footer -->
                    <div class="flex items-center gap-2 sm:gap-3 pt-1">
                        <button
                            type="button"
                            @click="closeQuantityModal"
                            class="px-4 sm:px-6 py-3 sm:py-3.5 rounded-xl sm:rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs sm:text-sm uppercase tracking-wider transition-colors"
                        >
                            CANCEL
                        </button>
                        <button
                            type="button"
                            @click="confirmAddFromModal"
                            class="flex-1 py-3 sm:py-3.5 rounded-xl sm:rounded-2xl bg-amber-500 hover:bg-amber-600 text-white font-black text-xs sm:text-sm uppercase tracking-wider shadow-lg shadow-amber-500/30 active:scale-95 transition-all flex items-center justify-center gap-2"
                        >
                            <CheckCircle class="w-4 h-4 sm:w-5 sm:h-5 stroke-[2.5]" />
                            <span>ADD TO BILL</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
