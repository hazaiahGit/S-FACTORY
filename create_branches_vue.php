<?php
$content = <<<'EOT'
<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, router, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Building2, Plus, Edit2, Trash2, MapPin, Phone, Mail } from '@lucide/vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    branches: Array,
});

const isModalOpen = ref(false);
const editingBranch = ref(null);

const form = useForm({
    name: '',
    code: '',
    type: 'Retail',
    address: '',
    phone: '',
    email: '',
    is_main: false,
    is_active: true,
});

const openModal = (branch = null) => {
    if (branch) {
        editingBranch.value = branch;
        form.name = branch.name;
        form.code = branch.code || '';
        form.type = branch.type || 'Retail';
        form.address = branch.address || '';
        form.phone = branch.phone || '';
        form.email = branch.email || '';
        form.is_main = branch.is_main;
        form.is_active = branch.is_active;
    } else {
        editingBranch.value = null;
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
    if (editingBranch.value) {
        form.put(route('branches.update', editingBranch.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('branches.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

const deleteBranch = (id) => {
    if (confirm('Are you sure you want to delete this branch?')) {
        router.delete(route('branches.destroy', id), {
            preserveScroll: true
        });
    }
};
</script>

<template>
    <Head title="Branches" />

    <AppLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-slate-800 leading-tight flex items-center">
                    <Building2 class="w-5 h-5 mr-2 text-indigo-500" />
                    Branches & Locations
                </h2>
                <button @click="openModal()" class="inline-flex items-center px-4 py-2 bg-amber-400 border border-transparent rounded-md font-bold text-xs text-slate-900 uppercase tracking-widest hover:bg-amber-500 active:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm">
                    <Plus class="w-4 h-4 mr-1" /> New Branch
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <div v-for="branch in branches" :key="branch.id" class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden hover:shadow-md transition-shadow relative">
                        <div v-if="branch.is_main" class="absolute top-0 right-0 bg-indigo-500 text-white text-[10px] font-bold px-3 py-1 rounded-bl-lg uppercase tracking-wider">
                            Main HQ
                        </div>
                        <div class="p-6">
                            <h3 class="text-lg font-bold text-slate-800 mb-1 pr-16">{{ branch.name }}</h3>
                            <p class="text-sm text-slate-500 mb-4">{{ branch.type || 'Branch' }} {{ branch.code ? `• ${branch.code}` : '' }}</p>
                            
                            <div class="space-y-2 text-sm text-slate-600">
                                <div v-if="branch.address" class="flex items-start">
                                    <MapPin class="w-4 h-4 mr-2 text-slate-400 mt-0.5 shrink-0" />
                                    <span>{{ branch.address }}</span>
                                </div>
                                <div v-if="branch.phone" class="flex items-center">
                                    <Phone class="w-4 h-4 mr-2 text-slate-400 shrink-0" />
                                    <span>{{ branch.phone }}</span>
                                </div>
                                <div v-if="branch.email" class="flex items-center">
                                    <Mail class="w-4 h-4 mr-2 text-slate-400 shrink-0" />
                                    <span>{{ branch.email }}</span>
                                </div>
                            </div>
                        </div>
                        <div class="px-6 py-3 bg-slate-50 border-t border-slate-100 flex justify-end items-center space-x-2">
                            <button @click="openModal(branch)" class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded transition-colors" title="Edit Branch">
                                <Edit2 class="w-4 h-4" />
                            </button>
                            <button v-if="!branch.is_main" @click="deleteBranch(branch.id)" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded transition-colors" title="Delete Branch">
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>

                <div v-if="branches.length === 0" class="text-center py-12 bg-white rounded-xl border border-dashed border-slate-300">
                    <Building2 class="w-12 h-12 text-slate-300 mx-auto mb-3" />
                    <h3 class="text-lg font-medium text-slate-900">No branches found</h3>
                    <p class="mt-1 text-sm text-slate-500">Get started by creating a new branch.</p>
                </div>
            </div>
        </div>

        <Modal :show="isModalOpen" @close="closeModal" maxWidth="md">
            <div class="p-6">
                <h2 class="text-lg font-bold text-slate-900 mb-6">
                    {{ editingBranch ? 'Edit Branch' : 'Create New Branch' }}
                </h2>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <InputLabel for="name" value="Branch Name *" />
                        <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="code" value="Branch Code" />
                            <TextInput id="code" v-model="form.code" type="text" class="mt-1 block w-full" />
                            <InputError :message="form.errors.code" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="type" value="Type" />
                            <TextInput id="type" v-model="form.type" type="text" class="mt-1 block w-full" />
                            <InputError :message="form.errors.type" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="phone" value="Phone Number" />
                        <TextInput id="phone" v-model="form.phone" type="text" class="mt-1 block w-full" />
                        <InputError :message="form.errors.phone" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="address" value="Physical Address" />
                        <TextInput id="address" v-model="form.address" type="text" class="mt-1 block w-full" />
                        <InputError :message="form.errors.address" class="mt-2" />
                    </div>

                    <div class="flex items-center space-x-4 pt-2">
                        <label class="flex items-center">
                            <input type="checkbox" v-model="form.is_active" class="rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                            <span class="ml-2 text-sm text-slate-600">Active Branch</span>
                        </label>
                        <label class="flex items-center">
                            <input type="checkbox" v-model="form.is_main" class="rounded border-slate-300 text-indigo-600 shadow-sm focus:ring-indigo-500" />
                            <span class="ml-2 text-sm text-slate-600">Is Main HQ</span>
                        </label>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" @click="closeModal" class="px-4 py-2 border border-slate-300 rounded-md text-sm font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Cancel
                        </button>
                        <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-bold text-sm text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50">
                            {{ form.processing ? 'Saving...' : 'Save Branch' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
EOT;

file_put_contents('resources/js/Pages/Branches/Index.vue', $content);
echo "Created Branches/Index.vue.\n";
?>
