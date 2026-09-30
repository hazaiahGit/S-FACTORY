<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link } from '@inertiajs/vue3';
import { ArrowLeft, Save, User, MapPin } from '@lucide/vue';

const props = defineProps({
    customer: Object,
});

const form = useForm({
    name: props.customer.name || '',
    customer_type: props.customer.customer_type || 'retail',
    phone: props.customer.phone || '',
    email: props.customer.email || '',
    address: props.customer.address || '',
    credit_limit: props.customer.credit_limit || 0,
    credit_allowed: props.customer.credit_allowed || false,
    notes: props.customer.notes || '',
});

const submit = () => {
    form.put(route('customers.update', props.customer.id));
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center text-lg sm:text-xl">
                <Link :href="route('customers.show', customer.id)" class="mr-3 sm:mr-4 text-slate-400 hover:text-slate-600 transition-colors">
                    <ArrowLeft class="w-5 h-5 sm:w-6 sm:h-6" />
                </Link>
                <span class="font-bold truncate">Edit {{ customer.name }}</span>
            </div>
        </template>

        <div class="max-w-4xl mx-auto space-y-6">
            <form @submit.prevent="submit" class="space-y-6">
                
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-5 sm:p-6 border-b border-slate-100 bg-slate-50 flex items-center">
                        <User class="w-5 h-5 mr-2 text-indigo-500" />
                        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Basic Details</h3>
                    </div>
                    
                    <div class="p-5 sm:p-6 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Customer Name</label>
                                <input v-model="form.name" type="text" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm font-bold" required />
                            </div>
                            
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Customer Type</label>
                                <select v-model="form.customer_type" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm">
                                    <option value="retail">Retail (Standard)</option>
                                    <option value="wholesale">Wholesale</option>
                                    <option value="vip">VIP / Corporate</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Phone Number</label>
                                <input v-model="form.phone" type="text" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" />
                            </div>
                            
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Email Address</label>
                                <input v-model="form.email" type="email" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-5 sm:p-6 border-b border-slate-100 bg-slate-50 flex items-center">
                        <MapPin class="w-5 h-5 mr-2 text-emerald-500" />
                        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Billing & Location</h3>
                    </div>
                    
                    <div class="p-5 sm:p-6 space-y-6">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Physical Address</label>
                            <textarea v-model="form.address" rows="2" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm"></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-4 bg-slate-50 border border-slate-200 rounded-lg">
                            <div class="flex items-center h-full">
                                <div class="flex items-center h-5">
                                    <input id="credit_allowed" v-model="form.credit_allowed" type="checkbox" class="focus:ring-indigo-500 h-5 w-5 text-indigo-600 border-gray-300 rounded">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="credit_allowed" class="font-bold text-slate-700">Allow Credit Sales</label>
                                    <p class="text-slate-500 text-xs">Can this customer purchase items on credit?</p>
                                </div>
                            </div>

                            <div v-if="form.credit_allowed">
                                <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Credit Limit (Max Debt)</label>
                                <FormattedNumberInput v-model="form.credit_limit" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm font-bold text-rose-600" />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Internal Notes</label>
                            <textarea v-model="form.notes" rows="2" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" placeholder="Any special instructions or notes..."></textarea>
                        </div>
                    </div>
                    
                    <div class="p-5 sm:p-6 bg-slate-50 border-t border-slate-100 flex justify-end">
                        <button type="submit" :disabled="form.processing" class="inline-flex items-center px-6 py-3 border border-transparent rounded-lg shadow-sm text-base font-bold text-slate-900 bg-amber-400 hover:bg-amber-500 focus:outline-none transition-colors">
                            <Save class="w-5 h-5 mr-2" />
                            {{ form.processing ? 'Saving...' : 'Update Customer' }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
