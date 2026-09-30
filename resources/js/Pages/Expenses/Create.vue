<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link } from '@inertiajs/vue3';
import { ArrowLeft, Save, ReceiptText, Banknote, ListPlus, Plus } from '@lucide/vue';

const props = defineProps({
    categories: Array,
});

const form = useForm({
    title: '',
    expense_category_id: '',
    amount: '',
    expense_date: new Date().toISOString().split('T')[0],
    payment_method: 'cash',
    reference: '',
    description: '',
});

const submit = () => {
    form.post(route('expenses.store'));
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center text-lg sm:text-xl">
                <Link :href="route('expenses.index')" class="mr-3 sm:mr-4 text-slate-400 hover:text-slate-600 transition-colors">
                    <ArrowLeft class="w-5 h-5 sm:w-6 sm:h-6" />
                </Link>
                <span class="font-bold truncate">Record Expense</span>
            </div>
        </template>

        <div class="max-w-3xl mx-auto space-y-6">
            <form @submit.prevent="submit" class="space-y-6">
                
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-5 sm:p-6 border-b border-slate-100 bg-slate-50 flex items-center">
                        <ReceiptText class="w-5 h-5 mr-2 text-rose-500" />
                        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Expense Details</h3>
                    </div>
                    
                    <div class="p-5 sm:p-6 space-y-6">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Title / Short Description</label>
                            <input v-model="form.title" type="text" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-rose-500 focus:border-rose-500 sm:text-sm font-bold" placeholder="e.g. Monthly Electricity Bill" required />
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Amount (TZS)</label>
                                <FormattedNumberInput v-model="form.amount" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-rose-500 focus:border-rose-500 sm:text-sm font-bold text-rose-600" required />
                            </div>
                            
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider">Category</label>
                                    <Link :href="route('expense-categories.index')" class="text-xs font-bold text-rose-600 hover:text-rose-700 flex items-center gap-1 transition-colors">
                                        <Plus class="w-3.5 h-3.5" /> Manage Categories
                                    </Link>
                                </div>
                                <select v-model="form.expense_category_id" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-rose-500 focus:border-rose-500 sm:text-sm font-bold text-slate-900" required>
                                    <option value="" disabled>-- Select Category --</option>
                                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                </select>
                                <p v-if="!categories || categories.length === 0" class="text-xs text-rose-500 mt-1 font-semibold">
                                    No categories found. Click "Manage Categories" above to create one.
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-slate-50 p-4 rounded-lg border border-slate-100">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Payment Method</label>
                                <select v-model="form.payment_method" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-rose-500 focus:border-rose-500 sm:text-sm">
                                    <option value="cash">Cash</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="mobile_money">Mobile Money</option>
                                    <option value="cheque">Cheque</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Date Incurred</label>
                                <input v-model="form.expense_date" type="date" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-rose-500 focus:border-rose-500 sm:text-sm font-bold" required />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Reference No. (Receipt / Transfer Code)</label>
                            <input v-model="form.reference" type="text" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-rose-500 focus:border-rose-500 sm:text-sm" />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Detailed Description (Optional)</label>
                            <textarea v-model="form.description" rows="3" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-rose-500 focus:border-rose-500 sm:text-sm"></textarea>
                        </div>
                    </div>
                    
                    <div class="p-5 sm:p-6 bg-slate-50 border-t border-slate-100 flex justify-end">
                        <button type="submit" :disabled="form.processing" class="inline-flex items-center px-6 py-3 border border-transparent rounded-lg shadow-sm text-base font-bold text-white bg-rose-600 hover:bg-rose-700 focus:outline-none transition-colors">
                            <Save class="w-5 h-5 mr-2" />
                            {{ form.processing ? 'Saving...' : 'Record Expense' }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
