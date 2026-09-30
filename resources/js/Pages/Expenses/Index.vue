<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { ReceiptText, Plus, Search, MapPin, Calendar, CreditCard, Trash2 } from '@lucide/vue';

const props = defineProps({
    expenses: Object,
    filters: Object,
});

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
        route('expenses.index'),
        { search: search.value },
        { preserveState: true, preserveScroll: true, replace: true }
    );
}, 300);

watch(search, performSearch);

const formatCurrency = (value) => {
    return new Intl.NumberFormat('en-TZ', { style: 'currency', currency: 'TZS', maximumFractionDigits: 0 }).format(value || 0);
};

const formatDate = (dateString) => {
    if (!dateString) return '-';
    return new Date(dateString).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
};

const deleteExpense = (id) => {
    if (confirm("Are you sure you want to delete this expense record?")) {
        router.delete(route('expenses.destroy', id));
    }
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center text-lg sm:text-xl font-bold">
                <ReceiptText class="w-6 h-6 mr-3 text-rose-500" />
                Expenses
            </div>
        </template>

        <div class="max-w-7xl mx-auto space-y-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="relative w-full max-w-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <Search class="h-5 w-5 text-slate-400" />
                    </div>
                    <input v-model="search" type="text" class="block w-full pl-10 pr-3 py-2 border-slate-200 rounded-lg shadow-sm focus:ring-rose-500 focus:border-rose-500 sm:text-sm" placeholder="Search expenses..." />
                </div>
                
                <Link :href="route('expenses.create')" class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-bold text-white bg-rose-600 hover:bg-rose-700 focus:outline-none transition-colors w-full sm:w-auto">
                    <Plus class="w-4 h-4 mr-2" />
                    Record Expense
                </Link>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="block">
                    <div v-for="expense in expenses.data" :key="expense.id" class="p-4 sm:p-5 border-b border-slate-100 last:border-0 hover:bg-slate-50 transition-colors">
                        <div class="flex flex-col sm:flex-row justify-between sm:items-center gap-4 mb-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center flex-shrink-0" :style="expense.category?.color ? `background-color: ${expense.category.color}15; color: ${expense.category.color}` : ''">
                                    <ReceiptText class="w-5 h-5" />
                                </div>
                                <div>
                                    <h4 class="text-base font-bold text-slate-900">{{ expense.title }}</h4>
                                    <div class="text-xs text-slate-500 font-medium">{{ expense.expense_number }}</div>
                                </div>
                            </div>
                            
                            <div class="text-left sm:text-right">
                                <span class="text-lg font-black text-rose-600">{{ formatCurrency(expense.amount) }}</span>
                                <div class="flex items-center justify-start sm:justify-end gap-2 text-[10px] uppercase font-bold text-slate-400 tracking-wider mt-1">
                                    <span class="bg-slate-100 px-2 py-0.5 rounded text-slate-500" :style="expense.category?.color ? `background-color: ${expense.category.color}15; color: ${expense.category.color}` : ''">
                                        {{ expense.category?.name || 'Uncategorized' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-4 text-xs font-medium text-slate-500 bg-slate-50 p-3 rounded-lg border border-slate-100 relative pr-10">
                            <button v-if="$can('manage settings')" @click="deleteExpense(expense.id)" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-rose-600 transition-colors p-1.5 rounded-md hover:bg-white">
                                <Trash2 class="w-4 h-4" />
                            </button>
                            
                            <div class="flex items-center">
                                <Calendar class="w-3.5 h-3.5 mr-1.5 text-slate-400" />
                                {{ formatDate(expense.expense_date) }}
                            </div>
                            <div class="w-px h-3 bg-slate-300 hidden sm:block"></div>
                            <div class="flex items-center">
                                <CreditCard class="w-3.5 h-3.5 mr-1.5 text-slate-400" />
                                <span class="capitalize">{{ expense.payment_method }}</span>
                            </div>
                            <div class="w-px h-3 bg-slate-300 hidden sm:block" v-if="expense.reference"></div>
                            <div class="flex items-center" v-if="expense.reference">
                                Ref: {{ expense.reference }}
                            </div>
                        </div>
                        
                        <p v-if="expense.description" class="text-sm text-slate-600 mt-3 pl-14">
                            {{ expense.description }}
                        </p>
                    </div>
                    
                    <div v-if="expenses.data.length === 0" class="p-8 sm:p-12 text-center text-slate-400">
                        <ReceiptText class="mx-auto h-12 w-12 text-slate-300 mb-3" />
                        <p class="font-medium text-slate-600">No expenses recorded yet.</p>
                        <p class="text-sm mt-1">Track your operating costs and overheads here.</p>
                    </div>
                </div>
                
                <div v-if="expenses.links && expenses.data.length > 0" class="px-6 py-4 border-t border-slate-100 bg-slate-50">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <span class="text-sm text-slate-500">Showing {{ expenses.from }} to {{ expenses.to }} of {{ expenses.total }}</span>
                        <div class="flex space-x-1">
                            <template v-for="(link, i) in expenses.links" :key="i">
                                <Link 
                                    v-if="link.url" 
                                    :href="link.url" 
                                    v-html="link.label"
                                    :class="[
                                        'px-3 py-1 text-sm border rounded-md transition-colors',
                                        link.active ? 'bg-rose-500 text-white border-rose-500 font-bold' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'
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

