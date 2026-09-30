<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { Settings, Save, Percent, Building2 } from '@lucide/vue';

const props = defineProps({
    business: Object,
});

const form = useForm({
    name: props.business.name || '',
    phone: props.business.phone || '',
    email: props.business.email || '',
    address: props.business.address || '',
    tax_number: props.business.tax_number || '',
    tax_enabled: props.business.tax_enabled || false,
    tax_inclusive: props.business.tax_inclusive || false,
    tax_rate: props.business.tax_rate || 0,
    currency: props.business.currency || 'TZS',
    currency_symbol: props.business.currency_symbol || 'TSh',
});

const submit = () => {
    form.post(route('settings.business'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout>
        <template #header>System Settings</template>

        <div class="max-w-4xl mx-auto space-y-6">
            <form @submit.prevent="submit" class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex items-center space-x-3 bg-slate-50">
                    <div class="p-2 bg-amber-100 text-amber-600 rounded-lg">
                        <Building2 class="w-5 h-5" />
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-slate-800">Business Profile & VAT</h2>
                        <p class="text-sm text-slate-500">Manage your store details and taxation settings.</p>
                    </div>
                </div>

                <div class="p-6 space-y-8">
                    <!-- Basic Info -->
                    <div>
                        <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-4 border-b border-slate-100 pb-2">Business Details</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-2">
                                <label class="block text-sm font-bold text-slate-700 mb-1">Business Name</label>
                                <input v-model="form.name" type="text" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" required />
                                <div v-if="form.errors.name" class="mt-1 text-sm text-rose-500">{{ form.errors.name }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Email Address</label>
                                <input v-model="form.email" type="email" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" />
                                <div v-if="form.errors.email" class="mt-1 text-sm text-rose-500">{{ form.errors.email }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Phone Number</label>
                                <input v-model="form.phone" type="text" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" />
                                <div v-if="form.errors.phone" class="mt-1 text-sm text-rose-500">{{ form.errors.phone }}</div>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-bold text-slate-700 mb-1">Physical Address</label>
                                <textarea v-model="form.address" rows="2" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm"></textarea>
                                <div v-if="form.errors.address" class="mt-1 text-sm text-rose-500">{{ form.errors.address }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Financial & VAT -->
                    <div>
                        <h3 class="text-sm font-bold text-slate-400 uppercase tracking-wider mb-4 border-b border-slate-100 pb-2">Financial & Taxation (VAT)</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Currency Code</label>
                                <input v-model="form.currency" type="text" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" required />
                                <div v-if="form.errors.currency" class="mt-1 text-sm text-rose-500">{{ form.errors.currency }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Currency Symbol</label>
                                <input v-model="form.currency_symbol" type="text" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" required />
                                <div v-if="form.errors.currency_symbol" class="mt-1 text-sm text-rose-500">{{ form.errors.currency_symbol }}</div>
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-bold text-slate-700 mb-1">Tax / TIN Number</label>
                                <input v-model="form.tax_number" type="text" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" />
                                <div v-if="form.errors.tax_number" class="mt-1 text-sm text-rose-500">{{ form.errors.tax_number }}</div>
                            </div>

                            <div class="md:col-span-2 bg-slate-50 border border-slate-200 rounded-xl p-5">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center">
                                        <input id="tax_enabled" v-model="form.tax_enabled" type="checkbox" class="h-5 w-5 text-amber-600 focus:ring-amber-500 border-slate-300 rounded" />
                                        <label for="tax_enabled" class="ml-3 block text-sm font-bold text-slate-800">
                                            Enable VAT / Tax System
                                        </label>
                                    </div>
                                </div>
                                
                                <div v-if="form.tax_enabled" class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-slate-200 mt-4">
                                    <div>
                                        <label class="block text-sm font-bold text-slate-700 mb-1">Global VAT Rate (%)</label>
                                        <div class="relative rounded-md shadow-sm">
                                            <FormattedNumberInput v-model="form.tax_rate" max="100" class="block w-full pr-10 border-slate-300 rounded-lg focus:ring-amber-500 focus:border-amber-500 sm:text-sm" />
                                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                <Percent class="h-4 w-4 text-slate-400" />
                                            </div>
                                        </div>
                                        <div v-if="form.errors.tax_rate" class="mt-1 text-sm text-rose-500">{{ form.errors.tax_rate }}</div>
                                    </div>
                                    <div class="flex flex-col justify-center">
                                        <div class="flex items-center mt-6">
                                            <input id="tax_inclusive" v-model="form.tax_inclusive" type="checkbox" class="h-4 w-4 text-amber-600 focus:ring-amber-500 border-slate-300 rounded" />
                                            <label for="tax_inclusive" class="ml-2 block text-sm text-slate-700 font-medium">
                                                Prices entered include VAT
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Toast Success Message inline -->
                <div v-if="$page.props.flash?.success" class="px-6 py-3 bg-emerald-50 border-t border-emerald-100 flex items-center text-emerald-700">
                    <div class="font-semibold">{{ $page.props.flash.success }}</div>
                </div>

                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-end">
                    <button type="submit" :disabled="form.processing" class="inline-flex items-center justify-center py-2.5 px-6 border border-transparent shadow-sm text-sm font-bold rounded-lg text-white bg-amber-600 hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 disabled:opacity-50 transition-colors">
                        <Save class="w-4 h-4 mr-2" />
                        {{ form.processing ? 'Saving...' : 'Save Settings' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
