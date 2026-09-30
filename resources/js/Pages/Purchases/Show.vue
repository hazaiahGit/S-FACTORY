<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, Building, Calendar, FileText, Package, Receipt, CreditCard, Truck } from '@lucide/vue';
import { computed } from 'vue';

const props = defineProps({
    purchase: Object
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
    <AppLayout :title="`PO: ${purchase.purchase_number}`">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-4">
                    <Link :href="route('purchases.index')" class="p-2 rounded-full hover:bg-slate-200 transition-colors text-slate-500">
                        <ArrowLeft class="h-5 w-5" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900">{{ purchase.purchase_number }}</h1>
                        <p class="text-sm text-slate-500 mt-0.5">Purchase Order Details</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <span :class="[
                        'px-3 py-1.5 inline-flex text-xs font-bold rounded-lg',
                        purchase.status === 'received' ? 'bg-emerald-100 text-emerald-800' : 
                        purchase.status === 'ordered' ? 'bg-blue-100 text-blue-800' : 'bg-slate-200 text-slate-800'
                    ]">
                        {{ purchase.status.toUpperCase() }}
                    </span>
                    <span :class="[
                        'px-3 py-1.5 inline-flex text-xs font-bold rounded-lg',
                        purchase.payment_status === 'paid' ? 'bg-emerald-100 text-emerald-800' : 
                        purchase.payment_status === 'partial' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800'
                    ]">
                        {{ purchase.payment_status.toUpperCase() }}
                    </span>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <!-- Left Column (Main details) -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- Items -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
                            <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                                <Package class="h-4 w-4 text-indigo-500" /> Items List
                            </h2>
                        </div>
                        <div class="divide-y divide-slate-100">
                            <div v-for="item in purchase.items" :key="item.id" class="p-4 sm:p-6 hover:bg-slate-50 transition-colors">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <h3 class="font-bold text-slate-900">{{ item.product?.name || 'Unknown Product' }}</h3>
                                        <p class="text-xs text-slate-500">{{ item.product?.sku }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-bold text-slate-900">{{ formatCurrency(item.total_cost) }}</p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-3 gap-2 mt-4 text-sm bg-white p-3 rounded-lg border border-slate-100">
                                    <div>
                                        <p class="text-xs text-slate-400 mb-0.5">Quantity</p>
                                        <p class="font-semibold text-slate-700">{{ Number(item.quantity) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-400 mb-0.5">Unit Cost</p>
                                        <p class="font-semibold text-slate-700">{{ formatCurrency(item.unit_cost) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-slate-400 mb-0.5">Received</p>
                                        <p class="font-semibold text-slate-700">{{ Number(item.received_quantity || 0) }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payments -->
                    <div v-if="purchase.payments && purchase.payments.length > 0" class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50">
                            <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                                <CreditCard class="h-4 w-4 text-emerald-500" /> Payment History
                            </h2>
                        </div>
                        <div class="divide-y divide-slate-100">
                            <div v-for="payment in purchase.payments" :key="payment.id" class="px-6 py-4 flex items-center justify-between">
                                <div>
                                    <p class="font-bold text-slate-900">{{ formatCurrency(payment.amount) }}</p>
                                    <p class="text-xs text-slate-500 mt-0.5">{{ formatDate(payment.payment_date) }} &bull; {{ payment.payment_method }}</p>
                                </div>
                                <span class="text-xs font-bold px-2 py-1 bg-slate-100 text-slate-600 rounded">
                                    {{ payment.payment_number }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column (Summary & Supplier) -->
                <div class="space-y-6">
                    
                    <!-- Supplier Info -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                        <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                            <Building class="h-4 w-4" /> Supplier Details
                        </h2>
                        <div v-if="purchase.supplier">
                            <p class="font-bold text-slate-900 text-lg">{{ purchase.supplier.name }}</p>
                            <p v-if="purchase.supplier.phone" class="text-sm text-slate-600 mt-2">{{ purchase.supplier.phone }}</p>
                            <p v-if="purchase.supplier.email" class="text-sm text-slate-600 mt-1">{{ purchase.supplier.email }}</p>
                        </div>
                        <div v-else class="text-slate-500 italic text-sm">
                            Walk-in Vendor (No details saved)
                        </div>
                    </div>

                    <!-- Order Summary -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                        <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4 flex items-center gap-2">
                            <Receipt class="h-4 w-4" /> Order Summary
                        </h2>
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Date</span>
                                <span class="font-medium text-slate-900">{{ formatDate(purchase.transaction_date) }}</span>
                            </div>
                            <div v-if="purchase.reference" class="flex justify-between">
                                <span class="text-slate-500">Reference</span>
                                <span class="font-medium text-slate-900">{{ purchase.reference }}</span>
                            </div>
                            <div class="pt-3 border-t border-slate-100 flex justify-between">
                                <span class="text-slate-500">Subtotal</span>
                                <span class="font-medium text-slate-900">{{ formatCurrency(purchase.subtotal) }}</span>
                            </div>
                            <div v-if="purchase.tax_amount > 0" class="flex justify-between">
                                <span class="text-slate-500">Tax</span>
                                <span class="font-medium text-slate-900">{{ formatCurrency(purchase.tax_amount) }}</span>
                            </div>
                            <div v-if="purchase.transport_cost > 0" class="flex justify-between">
                                <span class="text-slate-500">Transport</span>
                                <span class="font-medium text-slate-900">{{ formatCurrency(purchase.transport_cost) }}</span>
                            </div>
                            <div v-if="purchase.other_costs > 0" class="flex justify-between">
                                <span class="text-slate-500">Other Costs</span>
                                <span class="font-medium text-slate-900">{{ formatCurrency(purchase.other_costs) }}</span>
                            </div>
                            <div class="pt-4 border-t border-slate-200 flex justify-between items-center">
                                <span class="font-bold text-slate-700">Total Value</span>
                                <span class="text-xl font-black text-amber-500">{{ formatCurrency(purchase.total_amount) }}</span>
                            </div>
                            <div class="flex justify-between items-center pt-2">
                                <span class="text-slate-500">Amount Paid</span>
                                <span class="font-bold text-emerald-600">{{ formatCurrency(purchase.paid_amount) }}</span>
                            </div>
                            <div v-if="purchase.balance_amount > 0" class="flex justify-between items-center pt-2">
                                <span class="text-slate-500">Balance Due</span>
                                <span class="font-bold text-rose-500">{{ formatCurrency(purchase.balance_amount) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div v-if="purchase.notes" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                        <h2 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Notes</h2>
                        <p class="text-sm text-slate-600 whitespace-pre-wrap">{{ purchase.notes }}</p>
                    </div>

                </div>
            </div>
        </div>
    </AppLayout>
</template>
