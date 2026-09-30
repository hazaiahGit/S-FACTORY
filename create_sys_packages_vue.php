<?php
$content = <<<'EOT'
<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, router, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { PackageOpen, Plus, Edit2, Trash2 } from '@lucide/vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    packages: Array,
});

const isModalOpen = ref(false);
const editingPackage = ref(null);

const form = useForm({
    name: '',
    description: '',
    price: 0,
    duration_days: 30,
    max_users: null,
    max_branches: null,
    is_active: true,
});

const openModal = (pkg = null) => {
    if (pkg) {
        editingPackage.value = pkg;
        form.name = pkg.name;
        form.description = pkg.description || '';
        form.price = pkg.price;
        form.duration_days = pkg.duration_days;
        form.max_users = pkg.max_users;
        form.max_branches = pkg.max_branches;
        form.is_active = pkg.is_active;
    } else {
        editingPackage.value = null;
        form.reset();
    }
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
    form.clearErrors();
};

const submit = () => {
    if (editingPackage.value) {
        form.put(route('system.packages.update', editingPackage.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('system.packages.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deletePackage = (id) => {
    if (confirm('Are you sure you want to delete this package?')) {
        router.delete(route('system.packages.destroy', id), {
            preserveScroll: true
        });
    }
};
</script>

<template>
    <Head title="Subscription Packages" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-slate-800 leading-tight flex items-center">
                    <PackageOpen class="w-5 h-5 mr-2 text-indigo-500" />
                    Subscription Packages
                </h2>
                <button @click="openModal()" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-bold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                    <Plus class="w-4 h-4 mr-1" /> New Package
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div v-for="pkg in packages" :key="pkg.id" class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden relative">
                        <div class="p-6">
                            <h3 class="text-xl font-black text-slate-800 mb-1">{{ pkg.name }}</h3>
                            <p class="text-2xl font-bold text-emerald-600 mb-4">${{ pkg.price }} <span class="text-sm font-medium text-slate-500">/ {{ pkg.duration_days }} days</span></p>
                            
                            <ul class="space-y-2 text-sm text-slate-600 mb-6">
                                <li><strong>Users:</strong> {{ pkg.max_users || 'Unlimited' }}</li>
                                <li><strong>Branches:</strong> {{ pkg.max_branches || 'Unlimited' }}</li>
                                <li v-if="pkg.description" class="pt-2 border-t border-slate-100 text-slate-500 text-xs">{{ pkg.description }}</li>
                            </ul>
                        </div>
                        <div class="px-6 py-3 bg-slate-50 border-t border-slate-100 flex justify-end space-x-2">
                            <button @click="openModal(pkg)" class="p-1.5 text-slate-400 hover:text-indigo-600 rounded">
                                <Edit2 class="w-4 h-4" />
                            </button>
                            <button @click="deletePackage(pkg.id)" class="p-1.5 text-slate-400 hover:text-rose-600 rounded">
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Modal :show="isModalOpen" @close="closeModal" maxWidth="md">
            <div class="p-6">
                <h2 class="text-lg font-bold text-slate-900 mb-6">{{ editingPackage ? 'Edit Package' : 'Create Package' }}</h2>
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <InputLabel for="name" value="Package Name" />
                        <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="price" value="Price" />
                            <TextInput id="price" v-model="form.price" type="number" step="0.01" class="mt-1 block w-full" required />
                        </div>
                        <div>
                            <InputLabel for="duration" value="Duration (Days)" />
                            <TextInput id="duration" v-model="form.duration_days" type="number" class="mt-1 block w-full" required />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="max_users" value="Max Users (Empty = Unltd)" />
                            <TextInput id="max_users" v-model="form.max_users" type="number" class="mt-1 block w-full" />
                        </div>
                        <div>
                            <InputLabel for="max_branches" value="Max Branches (Empty = Unltd)" />
                            <TextInput id="max_branches" v-model="form.max_branches" type="number" class="mt-1 block w-full" />
                        </div>
                    </div>
                    <div>
                        <InputLabel for="desc" value="Description" />
                        <TextInput id="desc" v-model="form.description" type="text" class="mt-1 block w-full" />
                    </div>
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" @click="closeModal" class="px-4 py-2 border rounded-md text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</button>
                        <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-indigo-600 rounded-md font-bold text-sm text-white hover:bg-indigo-700 disabled:opacity-50">Save</button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
EOT;

file_put_contents('resources/js/Pages/System/Packages/Index.vue', $content);
echo "Created Packages Vue.\n";
?>
