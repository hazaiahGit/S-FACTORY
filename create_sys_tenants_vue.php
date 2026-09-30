<?php
$content = <<<'EOT'
<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, router, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Building2, Plus, ServerCrash, Ban, CheckCircle } from '@lucide/vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    tenants: Array,
    packages: Array,
});

const isModalOpen = ref(false);

const form = useForm({
    name: '',
    email: '',
    phone: '',
    address: '',
    subscription_package_id: '',
    admin_name: '',
    admin_email: '',
    admin_password: '',
});

const openModal = () => {
    form.reset();
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.clearErrors();
};

const submit = () => {
    form.post(route('system.tenants.store'), {
        onSuccess: () => closeModal(),
    });
};

const deactivateTenant = (id) => {
    if (confirm('Are you sure you want to suspend this tenant?')) {
        router.delete(route('system.tenants.destroy', id), {
            preserveScroll: true
        });
    }
};
</script>

<template>
    <Head title="System Tenants" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-slate-800 leading-tight flex items-center">
                    <ServerCrash class="w-5 h-5 mr-2 text-indigo-500" />
                    Tenants & Businesses
                </h2>
                <div class="space-x-3">
                    <Link :href="route('system.packages.index')" class="text-sm font-bold text-indigo-600 hover:text-indigo-800 mr-4">Manage Packages</Link>
                    <button @click="openModal()" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-bold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                        <Plus class="w-4 h-4 mr-1" /> New Tenant
                    </button>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Tenant Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Package</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Users</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200">
                            <tr v-for="tenant in tenants" :key="tenant.id">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-bold text-slate-900">{{ tenant.name }}</div>
                                    <div class="text-xs text-slate-500">{{ tenant.email || 'No email' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                    {{ tenant.subscription_package ? tenant.subscription_package.name : 'No Package' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span :class="['px-2 inline-flex text-xs leading-5 font-semibold rounded-full', tenant.subscription_status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800']">
                                        {{ tenant.subscription_status }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                    {{ tenant.users_count }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button v-if="tenant.subscription_status === 'active'" @click="deactivateTenant(tenant.id)" class="text-rose-600 hover:text-rose-900 ml-3">Suspend</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <Modal :show="isModalOpen" @close="closeModal" maxWidth="xl">
            <div class="p-6">
                <h2 class="text-lg font-bold text-slate-900 mb-6">Create New Tenant & Admin</h2>
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Tenant Info -->
                    <div>
                        <h3 class="text-sm font-bold text-indigo-600 uppercase tracking-wider border-b pb-2 mb-4">Business Details</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="name" value="Business Name *" />
                                <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
                                <InputError :message="form.errors.name" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="pkg" value="Subscription Package" />
                                <select id="pkg" v-model="form.subscription_package_id" class="mt-1 block w-full border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="">None</option>
                                    <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">{{ pkg.name }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Admin Info -->
                    <div>
                        <h3 class="text-sm font-bold text-emerald-600 uppercase tracking-wider border-b pb-2 mb-4">Tenant Admin Account</h3>
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <InputLabel for="admin_name" value="Admin Name *" />
                                <TextInput id="admin_name" v-model="form.admin_name" type="text" class="mt-1 block w-full" required />
                                <InputError :message="form.errors.admin_name" class="mt-2" />
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <InputLabel for="admin_email" value="Admin Login Email *" />
                                    <TextInput id="admin_email" v-model="form.admin_email" type="email" class="mt-1 block w-full" required />
                                    <InputError :message="form.errors.admin_email" class="mt-2" />
                                </div>
                                <div>
                                    <InputLabel for="admin_password" value="Admin Password *" />
                                    <TextInput id="admin_password" v-model="form.admin_password" type="password" class="mt-1 block w-full" required />
                                    <InputError :message="form.errors.admin_password" class="mt-2" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3 border-t pt-4">
                        <button type="button" @click="closeModal" class="px-4 py-2 border rounded-md text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</button>
                        <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-indigo-600 rounded-md font-bold text-sm text-white hover:bg-indigo-700 disabled:opacity-50">Create Tenant & Admin</button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
EOT;

file_put_contents('resources/js/Pages/System/Tenants/Index.vue', $content);
echo "Created Tenants Vue.\n";
?>
