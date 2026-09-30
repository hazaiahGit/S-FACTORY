<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ArrowLeft, User, Phone, Mail, MapPin, Receipt, Wallet, TrendingUp, Edit, ChevronRight, ExternalLink, CreditCard, Trash2 } from '@lucide/vue';

const props = defineProps({
    customer: Object,
    sales: Object,
    payments: Object,
    summary: Object,
});

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-TZ', { style: 'currency', currency: 'TZS', maximumFractionDigits: 0 }).format(value || 0);
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
};

const deleteCustomer = () => {
    if (confirm('Are you sure you want to delete this customer? This cannot be undone.')) {
        router.delete(route('customers.destroy', props.customer.id));
    }
};

const getSaleStatusBadge = (sale) => {
    if (sale.payment_status === 'paid') {
        return { label: 'Paid', color: 'bg-emerald-100 text-emerald-800' };
    }
    if (sale.status === 'credit') {
        return { label: 'Credit', color: 'bg-purple-100 text-purple-800' };
    }
    if (sale.payment_status === 'partial') {
        return { label: 'Partial', color: 'bg-amber-100 text-amber-800' };
    }
    return { label: 'Unpaid', color: 'bg-rose-100 text-rose-800' };
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center text-lg sm:text-xl">
                    <Link :href="route('customers.index')" class="mr-3 sm:mr-4 text-slate-400 hover:text-slate-600 transition-colors">
                        <ArrowLeft class="w-5 h-5 sm:w-6 sm:h-6" />
                    </Link>
                    <span class="font-bold truncate">{{ customer.name }}</span>
                    <span :class="[
                        'px-2.5 py-1 text-[10px] sm:text-xs font-bold uppercase tracking-wider rounded-full whitespace-nowrap ml-4',
                        customer.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'
                    ]">
                        {{ customer.is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <Link :href="route('customers.edit', customer.id)" class="inline-flex items-center justify-center px-4 py-2 border border-slate-300 rounded-lg shadow-sm text-sm font-bold text-slate-700 bg-white hover:bg-slate-50 transition-colors">
                        <Edit class="w-4 h-4 mr-2 text-slate-500" />
                        Edit Details
                    </Link>
                    <button @click="deleteCustomer" class="inline-flex items-center justify-center px-4 py-2 border border-rose-200 rounded-lg shadow-sm text-sm font-bold text-rose-600 bg-white hover:bg-rose-50 transition-colors">
                        <Trash2 class="w-4 h-4 mr-2" />
                        Delete Customer
                    </button>
                </div>
            </div>
        </template>

        <div class="max-w-7xl mx-auto space-y-6">
            
            <!-- Summary Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex items-center">
                    <div class="p-4 bg-indigo-50 rounded-lg mr-4">
                        <TrendingUp class="w-8 h-8 text-indigo-600" />
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Total Sales Vol.</p>
                        <p class="text-2xl font-black text-slate-900">{{ formatCurrency(summary.total_sales) }}</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex items-center">
                    <div class="p-4 bg-emerald-50 rounded-lg mr-4">
                        <Wallet class="w-8 h-8 text-emerald-600" />
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Total Paid</p>
                        <p class="text-2xl font-black text-slate-900">{{ formatCurrency(summary.total_paid) }}</p>
                    </div>
                </div>
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex items-center">
                    <div class="p-4 bg-rose-50 rounded-lg mr-4">
                        <Receipt class="w-8 h-8 text-rose-600" />
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Outstanding Balance</p>
                        <p class="text-2xl font-black text-rose-600">{{ formatCurrency(summary.balance) }}</p>
                        <p v-if="customer.credit_allowed" class="text-xs font-medium text-slate-500 mt-1">Limit: {{ formatCurrency(customer.credit_limit) }}</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Customer Info Profile -->
                <div class="lg:col-span-1 bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden h-fit">
                    <div class="p-5 border-b border-slate-100 bg-slate-50">
                        <h3 class="text-sm font-bold text-slate-800 flex items-center">
                            <User class="w-4 h-4 mr-2 text-indigo-500" />
                            Customer Profile
                        </h3>
                    </div>
                    <div class="p-5 space-y-4">
                        <div v-if="customer.phone" class="flex items-center text-sm">
                            <Phone class="w-4 h-4 text-slate-400 mr-3" />
                            <span class="font-medium text-slate-700">{{ customer.phone }}</span>
                        </div>
                        <div v-if="customer.email" class="flex items-center text-sm">
                            <Mail class="w-4 h-4 text-slate-400 mr-3" />
                            <span class="font-medium text-slate-700">{{ customer.email }}</span>
                        </div>
                        <div v-if="customer.address" class="flex items-start text-sm">
                            <MapPin class="w-4 h-4 text-slate-400 mr-3 mt-0.5" />
                            <span class="font-medium text-slate-700">{{ customer.address }}</span>
                        </div>
                        
                        <div class="pt-4 mt-4 border-t border-slate-100">
                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Type</span>
                            <span class="text-sm font-bold text-slate-700 capitalize">{{ customer.customer_type }}</span>
                        </div>
                        
                        <div v-if="customer.notes" class="pt-4 mt-4 border-t border-slate-100">
                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Internal Notes</span>
                            <p class="text-sm text-slate-600">{{ customer.notes }}</p>
                        </div>
                    </div>
                </div>

                <!-- Ledger / Recent Activity -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Recent Sales -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                            <h3 class="text-sm font-bold text-slate-800 flex items-center">
                                <Receipt class="w-4 h-4 mr-2 text-indigo-500" />
                                Recent Sales
                            </h3>
                            <Link :href="route('sales.index')" class="text-xs font-bold text-amber-600 hover:text-amber-700 flex items-center gap-1">
                                View All
                                <ChevronRight class="w-3.5 h-3.5" />
                            </Link>
                        </div>
                        <div class="divide-y divide-slate-100">
                            <Link 
                                v-for="sale in sales.data" 
                                :key="sale.id" 
                                :href="route('sales.show', sale.id)"
                                class="p-4 sm:p-5 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 hover:bg-amber-50/50 transition-all group block"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 group-hover:bg-amber-500 group-hover:text-white flex items-center justify-center transition-colors shrink-0">
                                        <Receipt class="w-5 h-5" />
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-black text-slate-900 group-hover:text-amber-600 transition-colors">
                                                {{ sale.sale_number }}
                                            </span>
                                            <span :class="['px-2 py-0.5 text-[10px] font-bold rounded-full uppercase tracking-wider', getSaleStatusBadge(sale).color]">
                                                {{ getSaleStatusBadge(sale).label }}
                                            </span>
                                        </div>
                                        <div class="text-xs text-slate-500 mt-0.5">{{ formatDate(sale.transaction_date) }}</div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4 sm:gap-6 bg-slate-50 sm:bg-transparent p-3 sm:p-0 rounded-lg border sm:border-0 border-slate-100 justify-between sm:justify-end">
                                    <div class="text-left sm:text-right">
                                        <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Total</span>
                                        <span class="text-sm font-black text-slate-900">{{ formatCurrency(sale.total_amount) }}</span>
                                    </div>
                                    <div class="text-right">
                                        <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Balance</span>
                                        <span :class="['text-sm font-black', sale.balance_amount > 0 ? 'text-rose-600' : 'text-emerald-600']">
                                            {{ formatCurrency(sale.balance_amount) }}
                                        </span>
                                    </div>
                                    <ChevronRight class="w-4 h-4 text-slate-300 group-hover:text-amber-600 group-hover:translate-x-1 transition-all shrink-0 hidden sm:block" />
                                </div>
                            </Link>
                            <div v-if="sales.data.length === 0" class="p-8 text-center text-sm text-slate-500">
                                No sales recorded for this customer yet.
                            </div>
                        </div>
                    </div>

                    <!-- Recent Payments -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                            <h3 class="text-sm font-bold text-slate-800 flex items-center">
                                <Wallet class="w-4 h-4 mr-2 text-emerald-500" />
                                Recent Payments Received
                            </h3>
                        </div>
                        <div class="divide-y divide-slate-100">
                            <component
                                :is="payment.sale_id ? Link : 'div'"
                                v-for="payment in payments.data" 
                                :key="payment.id"
                                :href="payment.sale_id ? route('sales.show', payment.sale_id) : undefined"
                                :class="[
                                    'p-4 sm:p-5 flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 transition-all block',
                                    payment.sale_id ? 'hover:bg-emerald-50/40 group cursor-pointer' : ''
                                ]"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white flex items-center justify-center transition-colors shrink-0">
                                        <Wallet class="w-5 h-5" />
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-sm font-black text-slate-900 group-hover:text-emerald-700 transition-colors">
                                                {{ payment.payment_number }}
                                            </span>
                                            <span v-if="payment.sale?.sale_number" class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-slate-100 text-slate-600 font-mono">
                                                {{ payment.sale.sale_number }}
                                            </span>
                                        </div>
                                        <div class="text-xs text-slate-500 mt-0.5">
                                            {{ formatDate(payment.payment_date) }} • <span class="capitalize font-medium">{{ payment.payment_method }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4 sm:gap-6 bg-slate-50 sm:bg-transparent p-3 sm:p-0 rounded-lg border sm:border-0 border-slate-100 justify-between sm:justify-end">
                                    <div class="text-right">
                                        <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Amount Paid</span>
                                        <span class="text-base font-black text-emerald-600">{{ formatCurrency(payment.amount) }}</span>
                                    </div>
                                    <ChevronRight v-if="payment.sale_id" class="w-4 h-4 text-slate-300 group-hover:text-emerald-600 group-hover:translate-x-1 transition-all shrink-0 hidden sm:block" />
                                </div>
                            </component>
                            <div v-if="payments.data.length === 0" class="p-8 text-center text-sm text-slate-500">
                                No payments recorded yet.
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            
        </div>
    </AppLayout>
</template>

