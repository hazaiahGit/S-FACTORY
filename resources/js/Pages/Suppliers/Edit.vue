<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, Link } from '@inertiajs/vue3';
import { ArrowLeft, Save, Building, Briefcase } from '@lucide/vue';

const props = defineProps({
    supplier: Object,
});

const form = useForm({
    name: props.supplier.name || '',
    contact_person: props.supplier.contact_person || '',
    phone: props.supplier.phone || '',
    email: props.supplier.email || '',
    address: props.supplier.address || '',
    tax_number: props.supplier.tax_number || '',
    payment_terms: props.supplier.payment_terms || 'immediate',
    credit_days: props.supplier.credit_days || 0,
    credit_limit: props.supplier.credit_limit || 0,
    notes: props.supplier.notes || '',
});

const submit = () => {
    form.put(route('suppliers.update', props.supplier.id));
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center text-lg sm:text-xl">
                <Link :href="route('suppliers.show', supplier.id)" class="mr-3 sm:mr-4 text-slate-400 hover:text-slate-600 transition-colors">
                    <ArrowLeft class="w-5 h-5 sm:w-6 sm:h-6" />
                </Link>
                <span class="font-bold truncate">Edit {{ supplier.name }}</span>
            </div>
        </template>

        <div class="max-w-4xl mx-auto space-y-6">
            <form @submit.prevent="submit" class="space-y-6">
                
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-5 sm:p-6 border-b border-slate-100 bg-slate-50 flex items-center">
                        <Building class="w-5 h-5 mr-2 text-indigo-500" />
                        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Company Details</h3>
                    </div>
                    
                    <div class="p-5 sm:p-6 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Company / Supplier Name</label>
                                <input v-model="form.name" type="text" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm font-bold" required />
                            </div>
                            
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Tax Number (TIN/VAT)</label>
                                <input v-model="form.tax_number" type="text" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Contact Person</label>
                                <input v-model="form.contact_person" type="text" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Phone</label>
                                <input v-model="form.phone" type="text" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Email</label>
                                <input v-model="form.email" type="email" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" />
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-5 sm:p-6 border-b border-slate-100 bg-slate-50 flex items-center">
                        <Briefcase class="w-5 h-5 mr-2 text-emerald-500" />
                        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Terms & Address</h3>
                    </div>
                    
                    <div class="p-5 sm:p-6 space-y-6">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Physical Address</label>
                            <textarea v-model="form.address" rows="2" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm"></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 p-5 bg-slate-50 border border-slate-200 rounded-lg">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Payment Terms</label>
                                <select v-model="form.payment_terms" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm">
                                    <option value="immediate">Immediate (Cash)</option>
                                    <option value="credit">On Credit</option>
                                </select>
                            </div>

                            <div v-if="form.payment_terms === 'credit'">
                                <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Credit Days</label>
                                <FormattedNumberInput v-model="form.credit_days" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm font-bold" />
                            </div>
                            
                            <div v-if="form.payment_terms === 'credit'">
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
                            {{ form.processing ? 'Saving...' : 'Update Supplier' }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
