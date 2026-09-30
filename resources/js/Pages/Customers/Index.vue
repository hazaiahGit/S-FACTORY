<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { Users, Plus, Search, Phone, Mail, MapPin, Trash2, Edit, ChevronRight, Building2 } from '@lucide/vue';

const props = defineProps({
    customers: Object,
    filters: Object,
});

const page = usePage();
const userRoles = page.props.auth.roles ?? [];
const isSuperAdmin = userRoles.includes('Super Admin') || userRoles.includes('Admin');
const isManager = userRoles.includes('Manager');
const canManage = isSuperAdmin || isManager;

const search = ref(props.filters?.search || '');

const debounce = (fn, delay) => {
    let timeoutId;
    return (...args) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn(...args), delay);
    };
};

const performSearch = debounce(() => {
    router.get(
        route('customers.index'),
        { search: search.value },
        { preserveState: true, preserveScroll: true, replace: true }
    );
}, 300);

watch(search, performSearch);

const deleteCustomer = (id) => {
    if (confirm('Are you sure you want to delete this customer? This action cannot be undone.')) {
        router.delete(route('customers.destroy', id), {
            preserveScroll: true
        });
    }
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-TZ', { style: 'currency', currency: 'TZS', maximumFractionDigits: 0 }).format(value || 0);
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center text-lg sm:text-xl font-bold">
                <Users class="w-6 h-6 mr-3 text-amber-500" />
                Customers Directory
            </div>
        </template>

        <div class="max-w-7xl mx-auto space-y-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="relative w-full max-w-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <Search class="h-5 w-5 text-slate-400" />
                    </div>
                    <input v-model="search" type="text" class="block w-full pl-10 pr-3 py-2 border-slate-200 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" placeholder="Search by name, phone, or email..." />
                </div>
                
                <Link :href="route('customers.create')" class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-bold text-slate-900 bg-amber-400 hover:bg-amber-500 focus:outline-none transition-colors w-full sm:w-auto">
                    <Plus class="w-4 h-4 mr-2" />
                    New Customer
                </Link>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="divide-y divide-slate-100">
                    <div v-for="customer in customers.data" :key="customer.id" class="p-5 hover:bg-slate-50 transition-colors">
                        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                            <!-- Left: Details -->
                            <div class="space-y-1.5">
                                <div class="flex items-center gap-2.5 flex-wrap">
                                    <Link :href="route('customers.show', customer.id)" class="text-base sm:text-lg font-bold text-slate-900 hover:text-amber-600 transition-colors">
                                        {{ customer.name }}
                                    </Link>
                                    <span v-if="customer.branch" class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                        <Building2 class="w-3 h-3 mr-1 text-amber-600" />
                                        {{ customer.branch.name }}
                                    </span>
                                    <span v-if="customer.customer_type" class="px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded bg-slate-100 text-slate-600 border border-slate-200">
                                        {{ customer.customer_type }}
                                    </span>
                                </div>
                                <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500">
                                    <span v-if="customer.phone" class="flex items-center"><Phone class="w-3.5 h-3.5 mr-1 text-slate-400" /> {{ customer.phone }}</span>
                                    <span v-if="customer.email" class="flex items-center"><Mail class="w-3.5 h-3.5 mr-1 text-slate-400" /> {{ customer.email }}</span>
                                    <span v-if="customer.address" class="flex items-center"><MapPin class="w-3.5 h-3.5 mr-1 text-slate-400" /> {{ customer.address }}</span>
                                </div>
                            </div>
                            
                            <!-- Middle: Metrics -->
                            <div class="flex flex-wrap items-center gap-4 sm:gap-6 bg-slate-50 p-3 rounded-lg border border-slate-100 text-xs sm:text-sm">
                                <div>
                                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Sales</span>
                                    <span class="font-black text-slate-900">{{ formatCurrency(customer.sales_sum_total_amount) }}</span>
                                </div>
                                <div>
                                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Paid</span>
                                    <span class="font-bold text-emerald-600">{{ formatCurrency(customer.payments_sum_amount) }}</span>
                                </div>
                                <div>
                                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider">Balance Due</span>
                                    <span :class="['font-black', (customer.sales_sum_total_amount - customer.payments_sum_amount) > 0 ? 'text-rose-600' : 'text-slate-600']">
                                        {{ formatCurrency(customer.sales_sum_total_amount - customer.payments_sum_amount) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Right: CRUD Action Buttons -->
                            <div class="flex items-center gap-2 pt-2 lg:pt-0 border-t lg:border-t-0 border-slate-100">
                                <Link
                                    :href="route('customers.show', customer.id)"
                                    class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 transition-colors"
                                >
                                    View
                                </Link>

                                <Link
                                    :href="route('customers.edit', customer.id)"
                                    class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition-colors"
                                >
                                    <Edit class="w-3.5 h-3.5 mr-1 text-slate-500" />
                                    Edit
                                </Link>

                                <button
                                    @click="deleteCustomer(customer.id)"
                                    class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold text-rose-600 bg-white border border-rose-200 hover:bg-rose-50 transition-colors"
                                    title="Delete Customer"
                                >
                                    <Trash2 class="w-3.5 h-3.5 mr-1" />
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div v-if="customers.data.length === 0" class="p-8 sm:p-12 text-center text-slate-400">
                        <Users class="mx-auto h-12 w-12 text-slate-300 mb-3" />
                        <p class="font-medium text-slate-600">No customers found.</p>
                        <p class="text-sm mt-1">Add your first customer to start tracking sales and balances.</p>
                    </div>
                </div>
                
                <div v-if="customers.links && customers.data.length > 0" class="px-6 py-4 border-t border-slate-100 bg-slate-50">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <span class="text-sm text-slate-500">Showing {{ customers.from }} to {{ customers.to }} of {{ customers.total }}</span>
                        <div class="flex space-x-1">
                            <template v-for="(link, i) in customers.links" :key="i">
                                <Link 
                                    v-if="link.url" 
                                    :href="link.url" 
                                    v-html="link.label"
                                    :class="[
                                        'px-3 py-1 text-sm border rounded-md transition-colors',
                                        link.active ? 'bg-amber-500 text-white border-amber-500 font-bold' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'
                                    ]"
                                />
                                <span v-else v-html="link.label" class="px-3 py-1 text-sm border border-slate-100 rounded-md text-slate-400 bg-slate-50 cursor-not-allowed"></span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
