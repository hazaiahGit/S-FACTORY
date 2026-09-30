<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, router, Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
    PackageOpen,
    Plus,
    Edit2,
    Trash2,
    Building2,
    Users,
    GitBranch,
    Calendar,
    CheckCircle2,
    X,
    Sparkles
} from '@lucide/vue';
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
        form.is_active = Boolean(pkg.is_active);
    } else {
        editingPackage.value = null;
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
    if (editingPackage.value) {
        form.put(route('system.packages.update', editingPackage.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('system.packages.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
        });
    }
};

const deletePackage = (id) => {
    if (confirm('Are you sure you want to delete this subscription plan?')) {
        router.delete(route('system.packages.destroy', id), {
            preserveScroll: true
        });
    }
};
</script>

<template>
    <Head title="System Subscription Packages" />

    <AppLayout>
        <template #header>
            <div class="flex items-center space-x-2 text-slate-800">
                <PackageOpen class="w-5 h-5 text-amber-500 shrink-0" />
                <span class="font-semibold text-lg sm:text-xl truncate">Subscription Packages</span>
            </div>
        </template>

        <div class="py-6 sm:py-8 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">

            <!-- Hero & Action Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl shadow-xs border border-slate-200/80">
                <div>
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                        <span>Subscription Plans</span>
                        <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200">
                            {{ packages.length }} {{ packages.length === 1 ? 'package' : 'packages' }}
                        </span>
                    </h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Define pricing, limits, and duration for tenant accounts.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5 sm:gap-3">
                    <Link
                        :href="route('system.tenants.index')"
                        class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-semibold rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 transition-all active:scale-95"
                    >
                        <Building2 class="w-4 h-4 mr-2 text-slate-500" />
                        <span>Back to Tenants</span>
                    </Link>

                    <button
                        @click="openModal()"
                        class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-bold rounded-xl text-white bg-amber-500 hover:bg-amber-600 shadow-sm shadow-amber-500/20 transition-all hover:shadow-md active:scale-95"
                    >
                        <Plus class="w-4 h-4 mr-1.5 stroke-[2.5]" />
                        <span>New Package</span>
                    </button>
                </div>
            </div>

            <!-- Packages Grid (Responsive 1-col on mobile, 2-col tablet, 3-col desktop) -->
            <div v-if="packages.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 sm:gap-6">
                <div
                    v-for="pkg in packages"
                    :key="pkg.id"
                    class="bg-white rounded-2xl border border-slate-200/90 shadow-xs hover:shadow-md transition-all flex flex-col justify-between overflow-hidden group"
                >
                    <!-- Header Bar -->
                    <div class="p-5 sm:p-6 pb-4">
                        <div class="flex items-center justify-between mb-3">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-amber-50 text-amber-700 border border-amber-200/60">
                                <Sparkles class="w-3 h-3 mr-1 text-amber-500" />
                                Plan
                            </span>
                            <span
                                v-if="pkg.is_active"
                                class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200"
                            >
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Available
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1 text-[11px] font-bold text-slate-500 bg-slate-100 px-2 py-0.5 rounded-md border border-slate-200"
                            >
                                Inactive
                            </span>
                        </div>

                        <h3 class="text-xl font-black text-slate-900 leading-snug">
                            {{ pkg.name }}
                        </h3>

                        <p class="text-xs text-slate-500 mt-1 min-h-[32px] line-clamp-2">
                            {{ pkg.description || 'Standard subscription tier for commercial operations.' }}
                        </p>

                        <!-- Price Tag -->
                        <div class="mt-4 pt-4 border-t border-slate-100 flex items-baseline">
                            <span class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
                                TZS {{ Number(pkg.price).toLocaleString() }}
                            </span>
                            <span class="text-xs font-semibold text-slate-400 ml-2">
                                / {{ pkg.duration_days }} days
                            </span>
                        </div>
                    </div>

                    <!-- Limits & Details -->
                    <div class="px-5 sm:px-6 py-4 bg-slate-50/70 border-y border-slate-100 space-y-2.5 text-xs text-slate-600">
                        <div class="flex items-center justify-between">
                            <span class="flex items-center text-slate-500">
                                <Users class="w-3.5 h-3.5 mr-1.5 text-slate-400" />
                                User Accounts
                            </span>
                            <span class="font-bold text-slate-900">
                                {{ pkg.max_users ? `${pkg.max_users} users` : 'Unlimited' }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="flex items-center text-slate-500">
                                <GitBranch class="w-3.5 h-3.5 mr-1.5 text-slate-400" />
                                Branch Locations
                            </span>
                            <span class="font-bold text-slate-900">
                                {{ pkg.max_branches ? `${pkg.max_branches} branches` : 'Unlimited' }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="flex items-center text-slate-500">
                                <Calendar class="w-3.5 h-3.5 mr-1.5 text-slate-400" />
                                Billing Cycle
                            </span>
                            <span class="font-bold text-slate-900">
                                {{ pkg.duration_days }} days
                            </span>
                        </div>
                    </div>

                    <!-- Actions Bottom Bar -->
                    <div class="p-4 sm:p-5 flex items-center justify-end space-x-2 bg-white">
                        <button
                            @click="openModal(pkg)"
                            class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-200 transition-colors"
                        >
                            <Edit2 class="w-3.5 h-3.5 mr-1.5 text-slate-500" />
                            Edit Plan
                        </button>
                        <button
                            @click="deletePackage(pkg.id)"
                            class="inline-flex items-center px-3 py-1.5 rounded-xl text-xs font-bold text-rose-600 hover:bg-rose-50 border border-rose-200 transition-colors"
                        >
                            <Trash2 class="w-3.5 h-3.5 mr-1.5" />
                            Delete
                        </button>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="bg-white rounded-2xl border border-slate-200/80 p-12 text-center">
                <div class="w-14 h-14 bg-amber-50 text-amber-500 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <PackageOpen class="w-7 h-7" />
                </div>
                <h3 class="text-base font-bold text-slate-900">No subscription packages configured</h3>
                <p class="text-sm text-slate-500 mt-1 max-w-sm mx-auto">
                    Create subscription packages to assign pricing, user quotas, and branch limits to tenants.
                </p>
                <button
                    @click="openModal()"
                    class="mt-4 inline-flex items-center px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-bold rounded-xl shadow-xs"
                >
                    <Plus class="w-4 h-4 mr-1.5" />
                    Create First Package
                </button>
            </div>

        </div>

        <!-- Modal: Create / Edit Package -->
        <Modal :show="isModalOpen" @close="closeModal" maxWidth="md">
            <div class="p-5 sm:p-7">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-5">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-600 flex items-center justify-center">
                            <PackageOpen class="w-5 h-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-black text-slate-900">
                                {{ editingPackage ? 'Edit Package' : 'Create Package' }}
                            </h2>
                            <p class="text-xs text-slate-500">Configure pricing and user/branch limits</p>
                        </div>
                    </div>
                    <button @click="closeModal" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <InputLabel for="name" value="Package Name *" />
                        <TextInput
                            id="name"
                            v-model="form.name"
                            type="text"
                            class="mt-1 block w-full rounded-xl"
                            placeholder="e.g. Professional Plan"
                            required
                        />
                        <InputError :message="form.errors.name" class="mt-1" />
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="price" value="Price (TZS) *" />
                            <TextInput
                                id="price"
                                v-model="form.price"
                                type="number"
                                step="0.01"
                                class="mt-1 block w-full rounded-xl"
                                required
                            />
                            <InputError :message="form.errors.price" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel for="duration" value="Duration (Days) *" />
                            <TextInput
                                id="duration"
                                v-model="form.duration_days"
                                type="number"
                                class="mt-1 block w-full rounded-xl"
                                required
                            />
                            <InputError :message="form.errors.duration_days" class="mt-1" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <InputLabel for="max_users" value="Max Users" />
                            <TextInput
                                id="max_users"
                                v-model="form.max_users"
                                type="number"
                                placeholder="Empty = Unlimited"
                                class="mt-1 block w-full rounded-xl"
                            />
                            <InputError :message="form.errors.max_users" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel for="max_branches" value="Max Branches" />
                            <TextInput
                                id="max_branches"
                                v-model="form.max_branches"
                                type="number"
                                placeholder="Empty = Unlimited"
                                class="mt-1 block w-full rounded-xl"
                            />
                            <InputError :message="form.errors.max_branches" class="mt-1" />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="desc" value="Short Description" />
                        <TextInput
                            id="desc"
                            v-model="form.description"
                            type="text"
                            class="mt-1 block w-full rounded-xl"
                            placeholder="Brief summary of included benefits..."
                        />
                        <InputError :message="form.errors.description" class="mt-1" />
                    </div>

                    <div class="flex items-center pt-1">
                        <label class="flex items-center text-xs font-semibold text-slate-700 cursor-pointer">
                            <input
                                type="checkbox"
                                v-model="form.is_active"
                                class="rounded border-slate-300 text-amber-500 focus:ring-amber-500 mr-2"
                            />
                            Available for selection when onboarding tenants
                        </label>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
                        <button
                            type="button"
                            @click="closeModal"
                            class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 font-bold text-sm text-white shadow-xs transition-all active:scale-95 disabled:opacity-50"
                        >
                            <span v-if="form.processing">Saving...</span>
                            <span v-else>{{ editingPackage ? 'Update Plan' : 'Create Plan' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

    </AppLayout>
</template>