<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, Building, Phone, Mail, MapPin, Receipt, Wallet, TrendingUp, Edit, Briefcase } from '@lucide/vue';

const props = defineProps({
    supplier: Object,
    purchases: Object,
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
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-center text-lg sm:text-xl">
                    <Link :href="route('suppliers.index')" class="mr-3 sm:mr-4 text-slate-400 hover:text-slate-600 transition-colors">
                        <ArrowLeft class="w-5 h-5 sm:w-6 sm:h-6" />
                    </Link>
                    <span class="font-bold truncate">{{ supplier.name }}</span>
                    <span :class="[
                        'px-2.5 py-1 text-[10px] sm:text-xs font-bold uppercase tracking-wider rounded-full whitespace-nowrap ml-4',
                        supplier.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'
                    ]">
                        {{ supplier.is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                
                <Link v-if="$can('edit purchases')" :href="route('suppliers.edit', supplier.id)" class="inline-flex items-center justify-center px-4 py-2 border border-slate-300 rounded-lg shadow-sm text-sm font-bold text-slate-700 bg-white hover:bg-slate-50 transition-colors w-full sm:w-auto">
                    <Edit class="w-4 h-4 mr-2" />
                    Edit Details
                </Link>
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
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Total Purchases</p>
                        <p class="text-2xl font-black text-slate-900">{{ formatCurrency(summary.total_purchases) }}</p>
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
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">We Owe Them</p>
                        <p class="text-2xl font-black text-rose-600">{{ formatCurrency(summary.balance) }}</p>
                        <p v-if="supplier.payment_terms === 'credit'" class="text-xs font-medium text-slate-500 mt-1">Limit: {{ formatCurrency(supplier.credit_limit) }}</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Supplier Info Profile -->
                <div class="lg:col-span-1 bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden h-fit">
                    <div class="p-5 border-b border-slate-100 bg-slate-50">
                        <h3 class="text-sm font-bold text-slate-800 flex items-center">
                            <Building class="w-4 h-4 mr-2 text-indigo-500" />
                            Supplier Profile
                        </h3>
                    </div>
                    <div class="p-5 space-y-4">
                        <div v-if="supplier.contact_person" class="flex items-center text-sm font-bold text-slate-800">
                            {{ supplier.contact_person }}
                        </div>
                        <div v-if="supplier.phone" class="flex items-center text-sm">
                            <Phone class="w-4 h-4 text-slate-400 mr-3" />
                            <span class="font-medium text-slate-700">{{ supplier.phone }}</span>
                        </div>
                        <div v-if="supplier.email" class="flex items-center text-sm">
                            <Mail class="w-4 h-4 text-slate-400 mr-3" />
                            <span class="font-medium text-slate-700">{{ supplier.email }}</span>
                        </div>
                        <div v-if="supplier.address" class="flex items-start text-sm">
                            <MapPin class="w-4 h-4 text-slate-400 mr-3 mt-0.5" />
                            <span class="font-medium text-slate-700">{{ supplier.address }}</span>
                        </div>
                        
                        <div class="pt-4 mt-4 border-t border-slate-100">
                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Payment Terms</span>
                            <span class="text-sm font-bold text-slate-700 capitalize">{{ supplier.payment_terms }}</span>
                            <span v-if="supplier.payment_terms === 'credit'" class="text-xs text-slate-500 ml-1">({{ supplier.credit_days }} days)</span>
                        </div>
                        
                        <div v-if="supplier.notes" class="pt-4 mt-4 border-t border-slate-100">
                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Internal Notes</span>
                            <p class="text-sm text-slate-600">{{ supplier.notes }}</p>
                        </div>
                    </div>
                </div>

                <!-- Ledger / Recent Activity -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Recent Purchases -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                            <h3 class="text-sm font-bold text-slate-800 flex items-center">
                                <Receipt class="w-4 h-4 mr-2 text-indigo-500" />
                                Recent Purchases
                            </h3>
                        </div>
                        <div class="block">
                            <div v-for="purchase in purchases.data" :key="purchase.id" class="p-4 sm:p-5 border-b border-slate-100 last:border-0 hover:bg-slate-50 transition-colors">
                                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4">
                                    <div>
                                        <div class="text-sm font-bold text-indigo-600">{{ purchase.purchase_number }}</div>
                                        <div class="text-xs text-slate-500 mt-1">{{ formatDate(purchase.transaction_date) }}</div>
                                    </div>
                                    <div class="flex gap-4 sm:gap-6 bg-slate-50 sm:bg-transparent p-3 sm:p-0 rounded-lg border sm:border-0 border-slate-100">
                                        <div class="text-right">
                                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Total</span>
                                            <span class="text-sm font-black text-slate-900">{{ formatCurrency(purchase.total_amount) }}</span>
                                        </div>
                                        <div class="text-right">
                                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Balance</span>
                                            <span :class="['text-sm font-bold', purchase.balance_amount > 0 ? 'text-rose-600' : 'text-emerald-600']">
                                                {{ formatCurrency(purchase.balance_amount) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-if="purchases.data.length === 0" class="p-6 text-center text-sm text-slate-500">
                                No purchases recorded for this supplier yet.
                            </div>
                        </div>
                    </div>

                    <!-- Recent Payments -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                            <h3 class="text-sm font-bold text-slate-800 flex items-center">
                                <Wallet class="w-4 h-4 mr-2 text-emerald-500" />
                                Recent Payments Sent
                            </h3>
                        </div>
                        <div class="block">
                            <div v-for="payment in payments.data" :key="payment.id" class="p-4 sm:p-5 border-b border-slate-100 last:border-0 hover:bg-slate-50 transition-colors">
                                <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4">
                                    <div>
                                        <div class="text-sm font-bold text-slate-900">{{ payment.payment_number }}</div>
                                        <div class="text-xs text-slate-500 mt-1">{{ formatDate(payment.payment_date) }} • {{ payment.payment_method }}</div>
                                    </div>
                                    <div class="text-right bg-slate-50 sm:bg-transparent p-3 sm:p-0 rounded-lg border sm:border-0 border-slate-100">
                                        <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Amount</span>
                                        <span class="text-lg font-black text-emerald-600">{{ formatCurrency(payment.amount) }}</span>
                                    </div>
                                </div>
                            </div>
                            <div v-if="payments.data.length === 0" class="p-6 text-center text-sm text-slate-500">
                                No payments recorded yet.
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            
        </div>
    </AppLayout>
</template>

