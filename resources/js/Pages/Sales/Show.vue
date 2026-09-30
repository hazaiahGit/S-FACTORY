<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref } from 'vue';
import { 
    Receipt, 
    User, 
    Calendar, 
    ArrowLeft, 
    Printer,
    CheckCircle,
    Clock,
    XCircle,
    CreditCard,
    ChevronDown,
    RefreshCw,
    Truck,
    Plus,
    Banknote,
    Landmark,
    X
} from '@lucide/vue';
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue';

const props = defineProps({
    sale: Object,
});

// Status definitions
const saleStatusOptions = [
    { value: 'draft',     label: 'Draft',     color: 'text-slate-600',   bg: 'bg-slate-100',   desc: 'Not yet confirmed' },
    { value: 'confirmed', label: 'Confirmed', color: 'text-blue-700',    bg: 'bg-blue-100',    desc: 'Sale confirmed' },
    { value: 'paid',      label: 'Paid',      color: 'text-emerald-700', bg: 'bg-emerald-100', desc: 'Sale fully paid' },
    { value: 'invoiced',  label: 'Invoiced',  color: 'text-indigo-700',  bg: 'bg-indigo-100',  desc: 'Invoice issued to customer' },
    { value: 'credit',    label: 'Credit',    color: 'text-purple-700',  bg: 'bg-purple-100',  desc: 'Sold on credit, payment pending' },
    { value: 'on_hold',   label: 'On Hold',   color: 'text-amber-700',   bg: 'bg-amber-100',   desc: 'Temporarily held' },
    { value: 'cancelled', label: 'Cancelled', color: 'text-rose-700',    bg: 'bg-rose-100',    desc: 'Sale cancelled' },
];

const fulfillmentOptions = [
    { value: 'pending',    label: 'Pending',    color: 'text-slate-600',   bg: 'bg-slate-100',   desc: 'Awaiting fulfillment' },
    { value: 'processing', label: 'Processing', color: 'text-blue-700',    bg: 'bg-blue-100',    desc: 'Being picked/packed' },
    { value: 'ready',      label: 'Ready',      color: 'text-indigo-700',  bg: 'bg-indigo-100',  desc: 'Ready for pickup/delivery' },
    { value: 'partial',    label: 'Partial',    color: 'text-amber-700',   bg: 'bg-amber-100',   desc: 'Partially fulfilled' },
    { value: 'fulfilled',  label: 'Fulfilled',  color: 'text-emerald-700', bg: 'bg-emerald-100', desc: 'Fully handed over' },
    { value: 'delivered',  label: 'Delivered',  color: 'text-teal-700',    bg: 'bg-teal-100',    desc: 'Arrived at destination' },
    { value: 'returned',   label: 'Returned',   color: 'text-rose-700',    bg: 'bg-rose-100',    desc: 'Items returned' },
    { value: 'cancelled',  label: 'Cancelled',  color: 'text-slate-500',   bg: 'bg-slate-100',   desc: 'Delivery cancelled' },
];

const currentStatusInfo = () => {
    return saleStatusOptions.find(s => s.value === props.sale.status) 
        || { label: props.sale.status, color: 'text-slate-600', bg: 'bg-slate-100' };
};

const currentFulfillmentInfo = () => {
    return fulfillmentOptions.find(f => f.value === props.sale.fulfillment_status)
        || { label: props.sale.fulfillment_status || 'Pending', color: 'text-slate-600', bg: 'bg-slate-100' };
};

// Payment Form & Modal
const showRecordPaymentModal = ref(false);

const paymentForm = useForm({
    amount: Number(props.sale.balance_amount) || 0,
    payment_method: 'cash',
    payment_date: new Date().toISOString().split('T')[0],
    reference: '',
    notes: '',
});

const openPaymentModal = () => {
    paymentForm.amount = Number(props.sale.balance_amount) || 0;
    paymentForm.payment_method = 'cash';
    paymentForm.payment_date = new Date().toISOString().split('T')[0];
    paymentForm.reference = '';
    paymentForm.notes = '';
    paymentForm.clearErrors();
    showRecordPaymentModal.value = true;
};

const closePaymentModal = () => {
    showRecordPaymentModal.value = false;
    paymentForm.reset();
    paymentForm.clearErrors();
};

const setFullBalance = () => {
    paymentForm.amount = Number(props.sale.balance_amount) || 0;
};

const setHalfBalance = () => {
    paymentForm.amount = Math.round((Number(props.sale.balance_amount) || 0) / 2);
};

const submitPayment = () => {
    paymentForm.post(route('sales.payment', props.sale.id), {
        preserveScroll: true,
        onSuccess: () => {
            closePaymentModal();
        },
    });
};

const statusForm = useForm({ status: '', fulfillment_status: '' });

const changeStatus = (newStatus) => {
    statusForm.status = newStatus;
    statusForm.patch(route('sales.status', props.sale.id), {
        preserveScroll: true,
    });
};

const changeFulfillment = (newStatus) => {
    statusForm.fulfillment_status = newStatus;
    statusForm.patch(route('sales.status', props.sale.id), {
        preserveScroll: true,
    });
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    return new Date(dateString).toLocaleDateString('en-GB', {
        day: '2-digit', month: 'short', year: 'numeric',
    });
};

const formatTime = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleTimeString('en-GB', {
        hour: '2-digit', minute: '2-digit'
    });
};

const getStatusColor = (status) => {
    const map = {
        draft: 'bg-slate-100 text-slate-800',
        confirmed: 'bg-blue-100 text-blue-800',
        paid: 'bg-emerald-100 text-emerald-800',
        partial: 'bg-amber-100 text-amber-800',
        credit: 'bg-purple-100 text-purple-800',
        cancelled: 'bg-rose-100 text-rose-800',
    };
    return map[status?.toLowerCase()] || 'bg-slate-100 text-slate-800';
};
</script>

<template>
    <Head :title="`Sale ${sale.sale_number}`" />

    <AppLayout>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center">
                    <Link :href="route('sales.index')" class="mr-4 p-2 rounded-full hover:bg-slate-200 transition-colors text-slate-500">
                        <ArrowLeft class="h-5 w-5" />
                    </Link>
                    <div>
                                                <h1 class="text-2xl font-bold text-slate-900 flex items-center flex-wrap gap-2">
                            Sale #{{ sale.sale_number }}
                            <span v-if="sale.items?.some(i => i.price_type === 'wholesale')" class="px-2 py-1 bg-purple-100 text-purple-700 rounded-lg text-xs font-bold uppercase tracking-wider ml-2">Wholesale</span>

                            <!-- Sale Status Dropdown -->
                            <Menu as="div" class="relative inline-block text-left">
                                <MenuButton :class="['inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider border transition-colors', currentStatusInfo().bg, currentStatusInfo().color, 'border-transparent hover:opacity-80']">
                                    {{ currentStatusInfo().label }}
                                    <ChevronDown class="h-3 w-3 ml-1" />
                                </MenuButton>
                                <transition
                                    enter-active-class="transition ease-out duration-100"
                                    enter-from-class="transform opacity-0 scale-95"
                                    enter-to-class="transform opacity-100 scale-100"
                                    leave-active-class="transition ease-in duration-75"
                                    leave-from-class="transform opacity-100 scale-100"
                                    leave-to-class="transform opacity-0 scale-95"
                                >
                                    <MenuItems class="absolute left-0 z-50 mt-2 w-64 origin-top-left rounded-xl bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none overflow-hidden">
                                        <div class="px-4 py-2 bg-slate-50 border-b border-slate-100">
                                            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center">
                                                <RefreshCw class="h-3 w-3 mr-1" /> Change Sale Status
                                            </p>
                                        </div>
                                        <div class="py-1">
                                            <MenuItem v-for="opt in saleStatusOptions" :key="opt.value" v-slot="{ active }">
                                                <button
                                                    @click="changeStatus(opt.value)"
                                                    :disabled="opt.value === sale.status || statusForm.processing"
                                                    :class="[
                                                        'w-full text-left px-4 py-3 flex items-start transition-colors',
                                                        active ? 'bg-slate-50' : '',
                                                        opt.value === sale.status ? 'opacity-50 cursor-not-allowed' : ''
                                                    ]"
                                                >
                                                    <span :class="['mt-0.5 w-2.5 h-2.5 rounded-full mr-3 flex-shrink-0', opt.bg.replace('bg-', 'bg-').replace('-100', '-500')]"></span>
                                                    <div>
                                                        <p class="text-sm font-bold text-slate-800">{{ opt.label }}</p>
                                                        <p class="text-xs text-slate-500">{{ opt.desc }}</p>
                                                    </div>
                                                    <span v-if="opt.value === sale.status" class="ml-auto text-xs text-slate-400 font-medium">Current</span>
                                                </button>
                                            </MenuItem>
                                        </div>
                                    </MenuItems>
                                </transition>
                            </Menu>

                            <!-- Fulfillment Status Dropdown -->
                            <Menu as="div" class="relative inline-block text-left">
                                <MenuButton :class="['inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider border transition-colors', currentFulfillmentInfo().bg, currentFulfillmentInfo().color, 'border-transparent hover:opacity-80']">
                                    <Truck class="h-3 w-3 mr-1" />
                                    {{ currentFulfillmentInfo().label }}
                                    <ChevronDown class="h-3 w-3 ml-1" />
                                </MenuButton>
                                <transition
                                    enter-active-class="transition ease-out duration-100"
                                    enter-from-class="transform opacity-0 scale-95"
                                    enter-to-class="transform opacity-100 scale-100"
                                    leave-active-class="transition ease-in duration-75"
                                    leave-from-class="transform opacity-100 scale-100"
                                    leave-to-class="transform opacity-0 scale-95"
                                >
                                    <MenuItems class="absolute left-0 z-50 mt-2 w-64 origin-top-left rounded-xl bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none overflow-hidden">
                                        <div class="px-4 py-2 bg-slate-50 border-b border-slate-100">
                                            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center">
                                                <RefreshCw class="h-3 w-3 mr-1" /> Update Fulfillment
                                            </p>
                                        </div>
                                        <div class="py-1">
                                            <MenuItem v-for="opt in fulfillmentOptions" :key="opt.value" v-slot="{ active }">
                                                <button
                                                    @click="changeFulfillment(opt.value)"
                                                    :disabled="opt.value === sale.fulfillment_status || statusForm.processing"
                                                    :class="[
                                                        'w-full text-left px-4 py-3 flex items-start transition-colors',
                                                        active ? 'bg-slate-50' : '',
                                                        opt.value === sale.fulfillment_status ? 'opacity-50 cursor-not-allowed' : ''
                                                    ]"
                                                >
                                                    <span :class="['mt-0.5 w-2.5 h-2.5 rounded-full mr-3 flex-shrink-0', opt.bg.replace('bg-', 'bg-').replace('-100', '-500')]"></span>
                                                    <div>
                                                        <p class="text-sm font-bold text-slate-800">{{ opt.label }}</p>
                                                        <p class="text-xs text-slate-500">{{ opt.desc }}</p>
                                                    </div>
                                                    <span v-if="opt.value === sale.fulfillment_status" class="ml-auto text-xs text-slate-400 font-medium">Current</span>
                                                </button>
                                            </MenuItem>
                                        </div>
                                    </MenuItems>
                                </transition>
                            </Menu>
                        </h1>
                        <p class="mt-1 text-sm text-slate-500 flex items-center">
                            <Calendar class="h-4 w-4 mr-1" />
                            {{ formatDate(sale.transaction_date) }} {{ formatTime(sale.created_at) }}
                        </p>
                    </div>
                </div>
                <div class="mt-4 sm:mt-0 flex items-center space-x-3">
                    <button
                        v-if="sale.balance_amount > 0"
                        type="button"
                        @click="openPaymentModal"
                        class="inline-flex items-center justify-center px-4 py-2 rounded-lg shadow-sm text-sm font-bold text-white bg-amber-500 hover:bg-amber-600 transition-all active:scale-95 shadow-amber-500/20"
                    >
                        <CreditCard class="h-4 w-4 mr-2" />
                        Record Payment
                    </button>
                    <a :href="route('sales.print', sale.id)" target="_blank" class="inline-flex items-center justify-center px-4 py-2 border border-slate-300 rounded-lg shadow-sm text-sm font-bold text-slate-700 bg-white hover:bg-slate-50 transition-colors">
                        <Printer class="h-4 w-4 mr-2" />
                        Print Receipt
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column (Items & Payments) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Items Table -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                            <h2 class="text-lg font-bold text-slate-800 flex items-center">
                                <Receipt class="h-5 w-5 mr-2 text-indigo-500" />
                                Order Items
                            </h2>
                        </div>
                        <!-- Mobile Cards -->
                        <div class="block md:hidden border-t border-slate-100 divide-y divide-slate-100 bg-white">
                            <div v-for="item in sale.items" :key="item.id" class="p-4 space-y-3">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="text-sm font-bold text-slate-900">{{ item.product?.name || 'Unknown Product' }}</p>
                                        <p v-if="item.discount_amount > 0" class="text-xs text-rose-500 mt-0.5">
                                            Discount: {{ Number(item.discount_amount).toLocaleString() }} TZS
                                        </p>
                                        <p v-if="item.tax_amount > 0" class="text-xs text-indigo-500 mt-0.5">
                                            + {{ Number(item.tax_amount).toLocaleString() }} TZS VAT
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-bold text-slate-900">{{ Number(item.total_price).toLocaleString() }}</p>
                                    </div>
                                </div>
                                <div class="flex justify-between items-center text-sm bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                                    <div class="flex flex-col">
                                        <span class="text-xs text-slate-500">Qty</span>
                                        <span class="font-medium text-slate-700">{{ Number(item.quantity).toLocaleString() }}</span>
                                    </div>
                                    <div class="flex flex-col text-right">
                                        <span class="text-xs text-slate-500">Price</span>
                                        <span class="font-medium text-slate-700">{{ Number(item.unit_price).toLocaleString() }}</span>
                                    </div>
                                </div>
                            </div>
                            <div v-if="!sale.items || sale.items.length === 0" class="p-6 text-center text-sm text-slate-500 italic">
                                No items found.
                            </div>
                        </div>

                        <!-- Desktop Table -->
                        <div class="hidden md:block overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-white">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase">Item</th>
                                        <th class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase">Qty</th>
                                        <th class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase">Price</th>
                                        <th class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <tr v-for="item in sale.items" :key="item.id" class="hover:bg-slate-50">
                                        <td class="px-6 py-4">
                                            <p class="text-sm font-bold text-slate-900">{{ item.product?.name || 'Unknown Product' }}</p>
                                            <p v-if="item.discount_amount > 0" class="text-xs text-rose-500 mt-0.5">
                                                Discount: {{ Number(item.discount_amount).toLocaleString() }} TZS
                                            </p>
                                            <p v-if="item.tax_amount > 0" class="text-xs text-indigo-500 mt-0.5">
                                                + {{ Number(item.tax_amount).toLocaleString() }} TZS VAT
                                            </p>
                                        </td>
                                        <td class="px-6 py-4 text-right text-sm font-medium text-slate-700">
                                            {{ Number(item.quantity).toLocaleString() }}
                                        </td>
                                        <td class="px-6 py-4 text-right text-sm text-slate-700">
                                            {{ Number(item.unit_price).toLocaleString() }}
                                        </td>
                                        <td class="px-6 py-4 text-right text-sm font-bold text-slate-900">
                                            {{ Number(item.total_price).toLocaleString() }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Payments Table -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                            <h2 class="text-lg font-bold text-slate-800 flex items-center">
                                <CreditCard class="h-5 w-5 mr-2 text-emerald-500" />
                                Payment History
                            </h2>
                            <button
                                v-if="sale.balance_amount > 0"
                                type="button"
                                @click="openPaymentModal"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold text-amber-700 bg-amber-50 border border-amber-200 hover:bg-amber-100 transition-colors"
                            >
                                <Plus class="w-3.5 h-3.5" />
                                Add Payment
                            </button>
                        </div>

                        <!-- Empty Payments State -->
                        <div v-if="!sale.payments || sale.payments.length === 0" class="p-8 text-center text-slate-400">
                            <CreditCard class="w-8 h-8 mx-auto text-slate-300 mb-2" />
                            <p class="font-bold text-slate-700 text-sm">No payments recorded</p>
                            <p class="text-xs text-slate-500 mt-0.5">This sale was placed on credit without upfront payment.</p>
                            <button
                                v-if="sale.balance_amount > 0"
                                type="button"
                                @click="openPaymentModal"
                                class="mt-3 inline-flex items-center gap-1.5 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold rounded-xl shadow-xs transition-colors"
                            >
                                <Plus class="w-3.5 h-3.5" />
                                Record First Payment
                            </button>
                        </div>
                        <template v-else>
                            <!-- Mobile Cards -->
                            <div class="block md:hidden border-t border-slate-100 divide-y divide-slate-100 bg-white">
                                <div v-for="payment in sale.payments" :key="payment.id" class="p-4 flex justify-between items-center">
                                    <div>
                                        <p class="text-sm font-bold text-slate-700 capitalize flex items-center">
                                            {{ payment.payment_method }}
                                        </p>
                                        <div class="flex items-center text-xs text-slate-500 mt-1 space-x-2">
                                            <span>{{ formatDate(payment.payment_date) }}</span>
                                            <span>&bull;</span>
                                            <span class="font-mono text-slate-400">{{ payment.payment_number }}</span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-bold text-emerald-600">
                                            {{ Number(payment.amount).toLocaleString() }} TZS
                                        </p>
                                    </div>
                                </div>
                            </div>

                        <!-- Desktop Table -->
                        <div class="hidden md:block overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-white">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase">Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase">Ref</th>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase">Method</th>
                                        <th class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase">Amount (TZS)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <tr v-for="payment in sale.payments" :key="payment.id">
                                        <td class="px-6 py-4 text-sm text-slate-700">{{ formatDate(payment.payment_date) }}</td>
                                        <td class="px-6 py-4 text-sm font-mono text-slate-500">{{ payment.payment_number }}</td>
                                        <td class="px-6 py-4 text-sm font-bold text-slate-700 capitalize">{{ payment.payment_method }}</td>
                                        <td class="px-6 py-4 text-right text-sm font-bold text-emerald-600">
                                            {{ Number(payment.amount).toLocaleString() }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        </template>
                    </div>
                </div>

                <!-- Right Column (Customer & Summary) -->
                <div class="space-y-6">
                    <!-- Customer Details -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                            <h2 class="text-lg font-bold text-slate-800 flex items-center">
                                <User class="h-5 w-5 mr-2 text-amber-500" />
                                Customer Details
                            </h2>
                        </div>
                        <div class="p-6">
                            <div v-if="sale.customer">
                                <p class="text-base font-bold text-slate-900">{{ sale.customer.name }}</p>
                                <p v-if="sale.customer.phone" class="text-sm text-slate-600 mt-1">{{ sale.customer.phone }}</p>
                                <p v-if="sale.customer.email" class="text-sm text-slate-600 mt-1">{{ sale.customer.email }}</p>
                                
                                <div class="mt-4 pt-4 border-t border-slate-100">
                                    <div class="flex justify-between text-sm">
                                        <span class="text-slate-500">Customer Balance:</span>
                                        <span :class="['font-bold', sale.customer.current_balance > 0 ? 'text-rose-600' : 'text-emerald-600']">
                                            {{ Number(sale.customer.current_balance).toLocaleString() }} TZS
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="text-slate-500 italic text-sm">
                                Walk-in Customer (No details provided)
                            </div>
                        </div>
                    </div>

                    <!-- Order Summary -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                            <h2 class="text-lg font-bold text-slate-800">Order Summary</h2>
                        </div>
                        <div class="p-6 space-y-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-slate-500">Subtotal (Excl. VAT)</span>
                                <span class="font-medium text-slate-900">{{ Number(sale.subtotal).toLocaleString() }} TZS</span>
                            </div>
                            <div v-if="sale.discount_amount > 0" class="flex justify-between text-sm">
                                <span class="text-slate-500">Discount</span>
                                <span class="font-medium text-rose-600">-{{ Number(sale.discount_amount).toLocaleString() }} TZS</span>
                            </div>
                            <div v-if="sale.items.reduce((sum, item) => sum + Number(item.tax_amount || 0), 0) > 0" class="flex justify-between text-sm">
                                <span class="text-slate-500">VAT (18%)</span>
                                <span class="font-medium text-slate-900">{{ Number(sale.items.reduce((sum, item) => sum + Number(item.tax_amount || 0), 0)).toLocaleString() }} TZS</span>
                            </div>
                            
                            <div class="pt-3 border-t border-slate-200">
                                <div class="flex justify-between items-center">
                                    <span class="text-base font-bold text-slate-900">Total Amount</span>
                                    <span class="text-xl font-black text-slate-900">{{ Number(sale.total_amount).toLocaleString() }} TZS</span>
                                </div>
                            </div>

                            <div class="pt-3 border-t border-slate-100 space-y-3">
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-500">Paid Amount</span>
                                    <span class="font-bold text-emerald-600">{{ Number(sale.paid_amount).toLocaleString() }} TZS</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-slate-500">Balance Due</span>
                                    <span :class="['font-bold', sale.balance_amount > 0 ? 'text-rose-600' : 'text-slate-400']">
                                        {{ Number(sale.balance_amount).toLocaleString() }} TZS
                                    </span>
                                </div>

                                <button
                                    v-if="sale.balance_amount > 0"
                                    type="button"
                                    @click="openPaymentModal"
                                    class="w-full mt-2 py-2.5 px-4 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-sm shadow-sm transition-all flex items-center justify-center gap-2 active:scale-98"
                                >
                                    <CreditCard class="w-4 h-4" />
                                    <span>Record Payment (Lipia)</span>
                                </button>

                                <div class="flex justify-between items-center pt-3 border-t border-slate-100">
                                    <span class="text-slate-500 text-sm font-bold uppercase tracking-wider">Payment Status</span>
                                    <span :class="[
                                        'px-3 py-1 rounded-full text-[11px] font-black uppercase tracking-wider',
                                        sale.payment_status === 'paid' ? 'bg-emerald-100 text-emerald-700' : 
                                        sale.payment_status === 'partial' ? 'bg-amber-100 text-amber-700' : 
                                        sale.payment_status === 'overpaid' ? 'bg-blue-100 text-blue-700' : 'bg-rose-100 text-rose-700'
                                    ]">
                                        {{ sale.payment_status }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Record Payment Modal -->
        <div v-if="showRecordPaymentModal" class="fixed inset-0 z-[100] overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 p-4 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-slate-900/70 backdrop-blur-sm transition-opacity" @click="closePaymentModal"></div>
                </div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md w-full border border-slate-100 p-6 space-y-5">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center space-x-3 min-w-0">
                            <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-600 flex items-center justify-center shrink-0">
                                <CreditCard class="w-5 h-5" />
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-lg font-black text-slate-900">Record Payment</h3>
                                <p class="text-xs text-slate-500 font-mono">Invoice #{{ sale.sale_number }}</p>
                            </div>
                        </div>
                        <button @click="closePaymentModal" class="w-8 h-8 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-400 hover:text-slate-600 flex items-center justify-center transition-colors">
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <!-- Balance Banner & Shortcuts -->
                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/80">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Remaining Balance</span>
                            <span class="text-lg font-black text-rose-600 font-mono">{{ Number(sale.balance_amount).toLocaleString() }} TZS</span>
                        </div>
                        <div class="flex items-center gap-2 mt-3">
                            <button
                                type="button"
                                @click="setFullBalance"
                                class="px-2.5 py-1 text-xs font-bold rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 transition-colors shadow-2xs"
                            >
                                Pay Full ({{ Number(sale.balance_amount).toLocaleString() }})
                            </button>
                            <button
                                v-if="sale.balance_amount > 1000"
                                type="button"
                                @click="setHalfBalance"
                                class="px-2.5 py-1 text-xs font-bold rounded-lg bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 transition-colors shadow-2xs"
                            >
                                Pay Half ({{ Number(Math.round(sale.balance_amount / 2)).toLocaleString() }})
                            </button>
                        </div>
                    </div>

                    <form @submit.prevent="submitPayment" class="space-y-4">
                        <!-- Amount Received -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Amount Paying (TZS) *
                            </label>
                            <input
                                v-model.number="paymentForm.amount"
                                type="number"
                                step="any"
                                min="1"
                                :max="sale.balance_amount"
                                class="w-full text-2xl font-black text-slate-900 font-mono px-4 py-3 bg-white border-2 border-slate-200 rounded-2xl focus:border-amber-500 focus:ring-0 transition-colors"
                                required
                            />
                            <p v-if="paymentForm.errors.amount" class="text-xs text-rose-600 mt-1 font-medium">{{ paymentForm.errors.amount }}</p>
                        </div>

                        <!-- Payment Method Selector -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Payment Method *
                            </label>
                            <div class="grid grid-cols-3 gap-2">
                                <button
                                    type="button"
                                    @click="paymentForm.payment_method = 'cash'"
                                    :class="[
                                        'py-2.5 px-2 rounded-xl border-2 flex flex-col items-center justify-center font-bold text-xs transition-all',
                                        paymentForm.payment_method === 'cash'
                                            ? 'border-slate-900 bg-slate-900 text-white shadow-xs'
                                            : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'
                                    ]"
                                >
                                    <Banknote class="w-4 h-4 mb-1" />
                                    Cash
                                </button>
                                <button
                                    type="button"
                                    @click="paymentForm.payment_method = 'mobile_money'"
                                    :class="[
                                        'py-2.5 px-2 rounded-xl border-2 flex flex-col items-center justify-center font-bold text-xs transition-all',
                                        paymentForm.payment_method === 'mobile_money'
                                            ? 'border-amber-600 bg-amber-600 text-white shadow-xs'
                                            : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'
                                    ]"
                                >
                                    <CreditCard class="w-4 h-4 mb-1" />
                                    Mobile Money
                                </button>
                                <button
                                    type="button"
                                    @click="paymentForm.payment_method = 'bank_transfer'"
                                    :class="[
                                        'py-2.5 px-2 rounded-xl border-2 flex flex-col items-center justify-center font-bold text-xs transition-all',
                                        paymentForm.payment_method === 'bank_transfer'
                                            ? 'border-blue-600 bg-blue-600 text-white shadow-xs'
                                            : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'
                                    ]"
                                >
                                    <Landmark class="w-4 h-4 mb-1" />
                                    Bank Tx
                                </button>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <!-- Payment Date -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Date
                                </label>
                                <input
                                    v-model="paymentForm.payment_date"
                                    type="date"
                                    class="w-full text-xs font-medium border border-slate-200 rounded-xl py-2 px-3 focus:border-amber-500 focus:ring-0"
                                />
                            </div>

                            <!-- Reference Number -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                    Ref / Tx ID
                                </label>
                                <input
                                    v-model="paymentForm.reference"
                                    type="text"
                                    placeholder="e.g. MP-12345"
                                    class="w-full text-xs font-mono border border-slate-200 rounded-xl py-2 px-3 focus:border-amber-500 focus:ring-0"
                                />
                            </div>
                        </div>

                        <!-- Notes -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Notes (Optional)
                            </label>
                            <input
                                v-model="paymentForm.notes"
                                type="text"
                                placeholder="Payment remarks..."
                                class="w-full text-xs font-medium border border-slate-200 rounded-xl py-2 px-3 focus:border-amber-500 focus:ring-0"
                            />
                        </div>

                        <!-- Modal Actions -->
                        <div class="flex items-center gap-3 pt-3 border-t border-slate-100">
                            <button
                                type="button"
                                @click="closePaymentModal"
                                class="px-5 py-3 rounded-2xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs uppercase tracking-wider transition-colors"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="paymentForm.processing || paymentForm.amount <= 0"
                                class="flex-1 py-3 rounded-2xl bg-amber-500 hover:bg-amber-600 text-white font-black text-xs uppercase tracking-wider shadow-lg shadow-amber-500/20 active:scale-95 transition-all flex items-center justify-center gap-2 disabled:opacity-50"
                            >
                                <CheckCircle class="w-4 h-4 stroke-[2.5]" />
                                <span>{{ paymentForm.processing ? 'Saving...' : 'Confirm Payment' }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
