<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { watch } from 'vue';

const props = defineProps({
    target: Object,
    branches: Array,
    users: Array,
    products: Array,
    categories: Array,
});

const form = useForm({
    name: props.target.name,
    target_type: props.target.target_type,
    target_value: props.target.target_value,
    measurement_unit: props.target.measurement_unit,
    period_type: props.target.period_type,
    start_date: props.target.start_date,
    end_date: props.target.end_date,
    branch_id: props.target.branch_id || '',
    user_id: props.target.user_id || '',
    product_id: props.target.product_id || '',
    category_id: props.target.category_id || '',
    description: props.target.description || '',
});

// Auto-set dates based on period type
watch(() => form.period_type, (newPeriod) => {
    const today = new Date();
    const start = new Date(today.getFullYear(), today.getMonth(), 1);
    
    if (newPeriod === 'monthly') {
        const end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
        form.start_date = start.toISOString().split('T')[0];
        form.end_date = end.toISOString().split('T')[0];
    } else if (newPeriod === 'yearly') {
        const startY = new Date(today.getFullYear(), 0, 1);
        const endY = new Date(today.getFullYear(), 11, 31);
        form.start_date = startY.toISOString().split('T')[0];
        form.end_date = endY.toISOString().split('T')[0];
    }
});

// Unit auto-select
watch(() => form.target_type, (type) => {
    if (['sales', 'profit', 'purchase', 'collection', 'expense'].includes(type)) {
        form.measurement_unit = 'amount';
    } else if (['production', 'product'].includes(type)) {
        form.measurement_unit = 'quantity';
    } else if (type === 'customer') {
        form.measurement_unit = 'count';
    }
    
    // Clear scopes if irrelevant
    if (type !== 'product' && type !== 'production') form.product_id = '';
    if (type !== 'category') form.category_id = '';
});

const submit = () => {
    form.put(route('targets.update', props.target.id));
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center">
                <Link :href="route('targets.index')" class="mr-4 text-slate-400 hover:text-slate-600">
                    <ArrowLeft class="w-6 h-6" />
                </Link>
                Edit Target
            </div>
        </template>

        <div class="max-w-4xl mx-auto">
            <form @submit.prevent="submit" class="bg-white shadow-sm rounded-xl border border-slate-200 overflow-hidden">
                <div class="p-6 md:p-8 space-y-8">
                    
                    <!-- Basic Info -->
                    <div>
                        <h3 class="text-lg font-bold text-slate-800 mb-4 border-b border-slate-100 pb-2">Target Definition</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-slate-700">Target Name</label>
                                <input v-model="form.name" type="text" required placeholder="e.g. Q3 Regional Sales Goal" class="mt-1 block w-full border-slate-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm">
                                <p v-if="form.errors.name" class="mt-1 text-sm text-rose-500">{{ form.errors.name }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700">Target Type</label>
                                <select v-model="form.target_type" required class="mt-1 block w-full border-slate-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm">
                                    <option value="sales">Sales / Revenue</option>
                                    <option value="production">Manufacturing / Production</option>
                                    <option value="profit">Gross Profit</option>
                                    <option value="purchase">Purchasing Volume</option>
                                    <option value="collection">Debt Collection</option>
                                    <option value="customer">New Customers</option>
                                    <option value="product">Specific Product Sales</option>
                                    <option value="category">Category Sales</option>
                                    <option value="expense">Expense Limit</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700">Measurement Unit</label>
                                <select v-model="form.measurement_unit" required class="mt-1 block w-full border-slate-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm bg-slate-50">
                                    <option value="amount">Amount (TZS)</option>
                                    <option value="quantity">Quantity (kg, pcs, etc.)</option>
                                    <option value="count">Count (People/Transactions)</option>
                                </select>
                            </div>

                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-slate-700">Target Value</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div v-if="form.measurement_unit === 'amount'" class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-slate-500 sm:text-sm">TSh</span>
                                    </div>
                                    <FormattedNumberInput v-model="form.target_value" required :class="['block w-full border-slate-300 rounded-md focus:ring-amber-500 focus:border-amber-500 sm:text-sm', form.measurement_unit === 'amount' ? 'pl-12' : '']" placeholder="0.00" />
                                </div>
                                <p v-if="form.errors.target_value" class="mt-1 text-sm text-rose-500">{{ form.errors.target_value }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Period -->
                    <div>
                        <h3 class="text-lg font-bold text-slate-800 mb-4 border-b border-slate-100 pb-2">Timeline</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Period Type</label>
                                <select v-model="form.period_type" class="mt-1 block w-full border-slate-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm">
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                    <option value="quarterly">Quarterly</option>
                                    <option value="yearly">Yearly</option>
                                    <option value="custom">Custom Range</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Start Date</label>
                                <input v-model="form.start_date" type="date" required class="mt-1 block w-full border-slate-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">End Date</label>
                                <input v-model="form.end_date" type="date" required class="mt-1 block w-full border-slate-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm">
                            </div>
                            <p v-if="form.errors.end_date" class="md:col-span-3 text-sm text-rose-500">{{ form.errors.end_date }}</p>
                        </div>
                    </div>

                    <!-- Scope / Assignment -->
                    <div>
                        <h3 class="text-lg font-bold text-slate-800 mb-4 border-b border-slate-100 pb-2">Scope & Assignment (Optional)</h3>
                        <p class="text-sm text-slate-500 mb-4">Leave fields blank to apply the target to the entire business.</p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Specific Branch</label>
                                <select v-model="form.branch_id" class="mt-1 block w-full border-slate-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm">
                                    <option value="">All Branches</option>
                                    <option v-for="b in branches" :key="b.id" :value="b.id">{{ b.name }}</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Specific Employee / Salesperson</label>
                                <select v-model="form.user_id" class="mt-1 block w-full border-slate-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm">
                                    <option value="">Anyone</option>
                                    <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                                </select>
                            </div>

                            <div v-if="['product', 'production', 'sales', 'profit'].includes(form.target_type)">
                                <label class="block text-sm font-medium text-slate-700">Specific Product</label>
                                <select v-model="form.product_id" class="mt-1 block w-full border-slate-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm">
                                    <option value="">Any Product</option>
                                    <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                                </select>
                            </div>

                            <div v-if="form.target_type === 'category'">
                                <label class="block text-sm font-medium text-slate-700">Specific Category</label>
                                <select v-model="form.category_id" class="mt-1 block w-full border-slate-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm">
                                    <option value="">Select Category...</option>
                                    <option v-for="c in categories" :key="c.id" :value="c.id">{{ c.name }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Description / Notes</label>
                        <textarea v-model="form.description" rows="3" class="mt-1 block w-full border-slate-300 rounded-md shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm"></textarea>
                    </div>

                </div>
                
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-end">
                    <Link :href="route('targets.index')" class="mr-4 text-sm font-medium text-slate-600 hover:text-slate-800">Cancel</Link>
                    <button type="submit" :disabled="form.processing" class="inline-flex justify-center py-2 px-6 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 disabled:opacity-50">
                        {{ form.processing ? 'Saving...' : 'Save Target' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
