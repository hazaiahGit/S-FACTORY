<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, router, Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Building2, Plus, Edit2, Trash2, MapPin, Phone, Mail, CheckCircle, XCircle } from '@lucide/vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    branches: Array,
});

const isModalOpen = ref(false);
const editingBranch = ref(null);

const totalBranches = computed(() => props.branches.length);
const activeBranches = computed(() => props.branches.filter(b => b.is_active).length);

const branchTypeOptions = ['Retail', 'Warehouse', 'Factory', 'Office', 'Distribution Center', 'Other'];

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
        form.is_active = true;
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
    if (confirm('Are you sure you want to delete this branch? This cannot be undone.')) {
        router.delete(route('branches.destroy', id), { preserveScroll: true });
    }
};

const getBranchTypeColor = (type) => {
    const colors = {
        'Retail': 'bg-blue-100 text-blue-700',
        'Warehouse': 'bg-orange-100 text-orange-700',
        'Factory': 'bg-purple-100 text-purple-700',
        'Office': 'bg-emerald-100 text-emerald-700',
        'Distribution Center': 'bg-yellow-100 text-yellow-700',
    };
    return colors[type] || 'bg-slate-100 text-slate-600';
};
</script>

<template>
    <Head title="Branches & Locations" />

    <AppLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold text-slate-900 flex items-center gap-2">
                        <Building2 class="w-5 h-5 text-indigo-500" />
                        Branches &amp; Locations
                    </h1>
                    <p class="text-sm text-slate-500 mt-0.5">Manage your business branches and outlets</p>
                </div>
                <button @click="openModal()" class="inline-flex items-center gap-2 px-4 py-2 bg-amber-400 hover:bg-amber-500 text-slate-900 font-bold text-sm rounded-lg shadow-sm transition-colors">
                    <Plus class="w-4 h-4" /> Add Branch
                </button>
            </div>
        </template>

        <div class="space-y-6">
            <!-- Stats Row -->
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 flex items-center gap-4">
                    <div class="w-10 h-10 rounded-lg bg-indigo-50 flex items-center justify-center shrink-0">
                        <Building2 class="w-5 h-5 text-indigo-500" />
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-900">{{ totalBranches }}</div>
                        <div class="text-xs text-slate-500 font-medium">Total Branches</div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 flex items-center gap-4">
                    <div class="w-10 h-10 rounded-lg bg-emerald-50 flex items-center justify-center shrink-0">
                        <CheckCircle class="w-5 h-5 text-emerald-500" />
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-900">{{ activeBranches }}</div>
                        <div class="text-xs text-slate-500 font-medium">Active</div>
                    </div>
                </div>
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 flex items-center gap-4 col-span-2 sm:col-span-1">
                    <div class="w-10 h-10 rounded-lg bg-rose-50 flex items-center justify-center shrink-0">
                        <XCircle class="w-5 h-5 text-rose-400" />
                    </div>
                    <div>
                        <div class="text-2xl font-black text-slate-900">{{ totalBranches - activeBranches }}</div>
                        <div class="text-xs text-slate-500 font-medium">Inactive</div>
                    </div>
                </div>
            </div>

            <!-- Branch Cards -->
            <div v-if="branches.length > 0" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5">
                <div
                    v-for="branch in branches"
                    :key="branch.id"
                    :class="['bg-white rounded-xl border shadow-sm overflow-hidden transition-all duration-200 hover:shadow-md hover:-translate-y-0.5 flex flex-col', branch.is_active ? 'border-slate-200' : 'border-slate-200 opacity-60']"
                >
                    <!-- Top accent line -->
                    <div :class="['h-1.5 w-full', branch.is_main ? 'bg-gradient-to-r from-indigo-500 to-purple-500' : 'bg-gradient-to-r from-slate-200 to-slate-300']"></div>

                    <div class="p-5 flex-1">
                        <!-- Header -->
                        <div class="flex items-start justify-between mb-3">
                            <div class="flex-1 min-w-0 pr-2">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="text-base font-bold text-slate-900">{{ branch.name }}</h3>
                                    <span v-if="branch.is_main" class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-100 text-indigo-700 uppercase tracking-wider whitespace-nowrap">Main HQ</span>
                                </div>
                                <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                                    <span v-if="branch.type" :class="['text-xs font-semibold px-2 py-0.5 rounded-full', getBranchTypeColor(branch.type)]">{{ branch.type }}</span>
                                    <span v-if="branch.code" class="text-xs text-slate-400 font-mono bg-slate-50 px-1.5 py-0.5 rounded">{{ branch.code }}</span>
                                </div>
                            </div>
                            <span :class="['shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase', branch.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500']">
                                {{ branch.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        <!-- Contact Info -->
                        <div class="space-y-1.5">
                            <div v-if="branch.address" class="flex items-start gap-2 text-sm text-slate-600">
                                <MapPin class="w-3.5 h-3.5 text-slate-400 mt-0.5 shrink-0" />
                                <span class="line-clamp-2">{{ branch.address }}</span>
                            </div>
                            <div v-if="branch.phone" class="flex items-center gap-2 text-sm text-slate-600">
                                <Phone class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                                <span>{{ branch.phone }}</span>
                            </div>
                            <div v-if="branch.email" class="flex items-center gap-2 text-sm text-slate-600">
                                <Mail class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                                <span class="truncate">{{ branch.email }}</span>
                            </div>
                            <p v-if="!branch.address && !branch.phone && !branch.email" class="text-xs text-slate-400 italic pt-1">No contact details added</p>
                        </div>
                    </div>

                    <!-- Footer Actions -->
                    <div class="px-5 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-1.5">
                        <button
                            @click="openModal(branch)"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition-colors"
                        >
                            <Edit2 class="w-3.5 h-3.5" /> Edit
                        </button>
                        <button
                            v-if="!branch.is_main"
                            @click="deleteBranch(branch.id)"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-lg transition-colors"
                        >
                            <Trash2 class="w-3.5 h-3.5" /> Delete
                        </button>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="flex flex-col items-center justify-center py-20 bg-white rounded-xl border border-dashed border-slate-300">
                <div class="w-16 h-16 rounded-2xl bg-indigo-50 flex items-center justify-center mb-4">
                    <Building2 class="w-8 h-8 text-indigo-400" />
                </div>
                <h3 class="text-lg font-bold text-slate-900 mb-1">No branches yet</h3>
                <p class="text-sm text-slate-500 mb-6">Create your first branch to start managing locations.</p>
                <button @click="openModal()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white font-bold text-sm rounded-lg hover:bg-indigo-700 transition-colors">
                    <Plus class="w-4 h-4" /> Create First Branch
                </button>
            </div>
        </div>

        <!-- Branch Modal -->
        <Modal :show="isModalOpen" @close="closeModal" maxWidth="lg">
            <div class="p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-10 h-10 rounded-xl bg-indigo-100 flex items-center justify-center">
                        <Building2 class="w-5 h-5 text-indigo-600" />
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">{{ editingBranch ? 'Edit Branch' : 'New Branch' }}</h2>
                        <p class="text-xs text-slate-500">{{ editingBranch ? 'Update branch details below' : 'Fill in the details to create a new branch' }}</p>
                    </div>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <InputLabel for="name" value="Branch Name *" />
                        <TextInput id="name" v-model="form.name" type="text" class="mt-1 block w-full" placeholder="e.g. Main HQ & Hardware Store" required autofocus />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="code" value="Branch Code" />
                            <TextInput id="code" v-model="form.code" type="text" class="mt-1 block w-full" placeholder="e.g. HQ-01" />
                            <InputError :message="form.errors.code" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="type" value="Branch Type" />
                            <select id="type" v-model="form.type" class="mt-1 block w-full border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                <option v-for="opt in branchTypeOptions" :key="opt" :value="opt">{{ opt }}</option>
                            </select>
                            <InputError :message="form.errors.type" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="phone" value="Phone Number" />
                        <TextInput id="phone" v-model="form.phone" type="tel" class="mt-1 block w-full" placeholder="+255 7XX XXX XXX" />
                        <InputError :message="form.errors.phone" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="address" value="Physical Address" />
                        <TextInput id="address" v-model="form.address" type="text" class="mt-1 block w-full" placeholder="Street, City, Region" />
                        <InputError :message="form.errors.address" class="mt-2" />
                    </div>

                    <!-- Toggle switches -->
                    <div class="flex flex-col sm:flex-row gap-3 p-4 bg-slate-50 rounded-xl border border-slate-200">
                        <label class="flex items-center gap-3 cursor-pointer flex-1">
                            <div class="relative shrink-0">
                                <input type="checkbox" v-model="form.is_active" class="sr-only peer" />
                                <div class="w-10 h-6 bg-slate-300 peer-checked:bg-indigo-500 rounded-full transition-colors"></div>
                                <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-4"></div>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-slate-700">Active Branch</div>
                                <div class="text-xs text-slate-500">Branch is open and operational</div>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 cursor-pointer flex-1">
                            <div class="relative shrink-0">
                                <input type="checkbox" v-model="form.is_main" class="sr-only peer" />
                                <div class="w-10 h-6 bg-slate-300 peer-checked:bg-indigo-500 rounded-full transition-colors"></div>
                                <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform peer-checked:translate-x-4"></div>
                            </div>
                            <div>
                                <div class="text-sm font-semibold text-slate-700">Main Headquarters</div>
                                <div class="text-xs text-slate-500">Mark as the primary HQ</div>
                            </div>
                        </label>
                    </div>

                    <div class="flex justify-end gap-3 pt-2 border-t border-slate-100">
                        <button type="button" @click="closeModal" class="px-4 py-2 border border-slate-300 rounded-lg text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors">Cancel</button>
                        <button type="submit" :disabled="form.processing" class="px-6 py-2 bg-indigo-600 rounded-lg font-bold text-sm text-white hover:bg-indigo-700 disabled:opacity-50 transition-colors">
                            {{ form.processing ? 'Saving...' : (editingBranch ? 'Save Changes' : 'Create Branch') }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>