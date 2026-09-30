<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { useForm, router, Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import {
    Building2,
    Plus,
    Ban,
    CheckCircle2,
    Users,
    PackageOpen,
    Search,
    Calendar,
    Edit3,
    ArrowUpRight,
    RefreshCw,
    SlidersHorizontal,
    GitBranch,
    ShieldCheck,
    AlertCircle,
    X,
} from '@lucide/vue';
import Modal from '@/Components/Modal.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    tenants: Array,
    packages: Array,
    stats: Object,
});

// Modals State
const isCreateModalOpen = ref(false);
const isEditModalOpen = ref(false);
const selectedTenant = ref(null);

// Search and Filter State
const searchQuery = ref('');
const statusFilter = ref('all');
const packageFilter = ref('all');

// Form for New Tenant & Admin
const createForm = useForm({
    name: '',
    email: '',
    phone: '',
    address: '',
    subscription_package_id: '',
    admin_name: '',
    admin_email: '',
    admin_password: '',
});

// Form for Editing Subscription
const subscriptionForm = useForm({
    subscription_package_id: '',
    subscription_status: 'active',
    subscription_ends_at: '',
});

const openCreateModal = () => {
    createForm.reset();
    createForm.clearErrors();
    isCreateModalOpen.value = true;
};

const closeCreateModal = () => {
    isCreateModalOpen.value = false;
    createForm.reset();
    createForm.clearErrors();
};

const submitCreate = () => {
    createForm.post(route('system.tenants.store'), {
        preserveScroll: true,
        onSuccess: () => closeCreateModal(),
    });
};

const openEditModal = (tenant) => {
    selectedTenant.value = tenant;
    subscriptionForm.subscription_package_id = tenant.subscription_package_id || '';
    subscriptionForm.subscription_status = tenant.subscription_status || 'active';
    subscriptionForm.subscription_ends_at = tenant.subscription_ends_at ? tenant.subscription_ends_at.substring(0, 10) : '';
    subscriptionForm.clearErrors();
    isEditModalOpen.value = true;
};

const closeEditModal = () => {
    isEditModalOpen.value = false;
    selectedTenant.value = null;
    subscriptionForm.reset();
    subscriptionForm.clearErrors();
};

const submitUpdateSubscription = () => {
    if (!selectedTenant.value) return;
    subscriptionForm.put(route('system.tenants.subscription', selectedTenant.value.id), {
        preserveScroll: true,
        onSuccess: () => closeEditModal(),
    });
};

// Activate Tenant
const activateTenant = (tenant) => {
    if (confirm(`Are you sure you want to ACTIVATE "${tenant.name}"?`)) {
        router.post(route('system.tenants.activate', tenant.id), {}, {
            preserveScroll: true,
        });
    }
};

// Suspend Tenant
const suspendTenant = (tenant) => {
    if (confirm(`Are you sure you want to SUSPEND "${tenant.name}"? Users from this business will be locked out.`)) {
        router.post(route('system.tenants.suspend', tenant.id), {}, {
            preserveScroll: true,
        });
    }
};

// Filtered Tenants
const filteredTenants = computed(() => {
    if (!props.tenants) return [];

    return props.tenants.filter((tenant) => {
        // Search query
        const query = searchQuery.value.toLowerCase().trim();
        const matchesQuery =
            !query ||
            tenant.name?.toLowerCase().includes(query) ||
            tenant.email?.toLowerCase().includes(query) ||
            tenant.code?.toLowerCase().includes(query) ||
            tenant.slug?.toLowerCase().includes(query);

        // Status filter
        const matchesStatus =
            statusFilter.value === 'all' ||
            (statusFilter.value === 'active' && tenant.subscription_status === 'active' && tenant.is_active) ||
            (statusFilter.value === 'suspended' && (tenant.subscription_status === 'suspended' || !tenant.is_active));

        // Package filter
        const matchesPackage =
            packageFilter.value === 'all' ||
            (packageFilter.value === 'none' && !tenant.subscription_package_id) ||
            String(tenant.subscription_package_id) === String(packageFilter.value);

        return matchesQuery && matchesStatus && matchesPackage;
    });
});

const formatDate = (dateStr) => {
    if (!dateStr) return 'No expiration set';
    try {
        const d = new Date(dateStr);
        return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
    } catch {
        return dateStr;
    }
};

const getDaysRemaining = (dateStr) => {
    if (!dateStr) return null;
    const end = new Date(dateStr);
    const now = new Date();
    const diffTime = end - now;
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24));
};
</script>

<template>
    <Head title="System Tenants & Businesses" />

    <AppLayout>
        <template #header>
            <div class="flex items-center space-x-2 text-slate-800">
                <Building2 class="w-5 h-5 text-amber-500 shrink-0" />
                <span class="font-semibold text-lg sm:text-xl truncate">Tenants & Businesses</span>
            </div>
        </template>

        <div class="py-6 sm:py-8 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto space-y-6">

            <!-- Hero & Actions Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 sm:p-6 rounded-2xl shadow-xs border border-slate-200/80">
                <div>
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                        <span>Tenant Management</span>
                        <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200">
                            {{ filteredTenants.length }} {{ filteredTenants.length === 1 ? 'business' : 'businesses' }}
                        </span>
                    </h1>
                    <p class="text-sm text-slate-500 mt-1">
                        Control client workspaces, provision new tenant accounts, and manage subscriptions.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5 sm:gap-3">
                    <Link
                        :href="route('system.packages.index')"
                        class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-semibold rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 transition-all hover:shadow-xs active:scale-95"
                    >
                        <PackageOpen class="w-4 h-4 mr-2 text-slate-500" />
                        <span>Manage Packages</span>
                    </Link>

                    <button
                        @click="openCreateModal"
                        class="inline-flex items-center justify-center px-4 py-2.5 text-sm font-bold rounded-xl text-white bg-amber-500 hover:bg-amber-600 shadow-sm shadow-amber-500/20 transition-all hover:shadow-md active:scale-95"
                    >
                        <Plus class="w-4 h-4 mr-1.5 stroke-[2.5]" />
                        <span>New Tenant</span>
                    </button>
                </div>
            </div>

            <!-- KPI Stats Cards -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3.5 sm:gap-5">
                <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs relative overflow-hidden flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Tenants</span>
                        <div class="p-2 rounded-xl bg-slate-100 text-slate-700">
                            <Building2 class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="text-2xl sm:text-3xl font-black text-slate-900">
                        {{ stats?.total_tenants ?? tenants.length }}
                    </div>
                    <div class="text-xs text-slate-500 mt-1">Registered businesses</div>
                </div>

                <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs relative overflow-hidden flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Active</span>
                        <div class="p-2 rounded-xl bg-emerald-50 text-emerald-600">
                            <CheckCircle2 class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="text-2xl sm:text-3xl font-black text-emerald-700">
                        {{ stats?.active_tenants ?? tenants.filter(t => t.subscription_status === 'active').length }}
                    </div>
                    <div class="text-xs text-emerald-600/80 mt-1">Running normally</div>
                </div>

                <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs relative overflow-hidden flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold text-rose-500 uppercase tracking-wider">Suspended</span>
                        <div class="p-2 rounded-xl bg-rose-50 text-rose-600">
                            <Ban class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="text-2xl sm:text-3xl font-black text-rose-600">
                        {{ stats?.suspended_tenants ?? tenants.filter(t => t.subscription_status === 'suspended').length }}
                    </div>
                    <div class="text-xs text-rose-500/80 mt-1">Access restricted</div>
                </div>

                <div class="bg-white p-4 sm:p-5 rounded-2xl border border-slate-200/80 shadow-xs relative overflow-hidden flex flex-col justify-between">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold text-indigo-500 uppercase tracking-wider">Tenant Users</span>
                        <div class="p-2 rounded-xl bg-indigo-50 text-indigo-600">
                            <Users class="w-4 h-4" />
                        </div>
                    </div>
                    <div class="text-2xl sm:text-3xl font-black text-indigo-900">
                        {{ stats?.total_users ?? tenants.reduce((acc, t) => acc + (t.users_count || 0), 0) }}
                    </div>
                    <div class="text-xs text-indigo-500 mt-1">Total active accounts</div>
                </div>
            </div>

            <!-- Filters & Search Toolbar -->
            <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col md:flex-row gap-3 items-stretch md:items-center justify-between">
                <!-- Search input -->
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <Search class="w-4 h-4" />
                    </div>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search tenant by name, code, email..."
                        class="w-full pl-10 pr-4 py-2 text-sm bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-all placeholder:text-slate-400"
                    />
                    <button
                        v-if="searchQuery"
                        @click="searchQuery = ''"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <!-- Status Filter Pills -->
                <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-xl self-start sm:self-auto overflow-x-auto max-w-full">
                    <button
                        type="button"
                        @click="statusFilter = 'all'"
                        :class="[
                            'px-3 py-1.5 text-xs font-bold rounded-lg transition-all',
                            statusFilter === 'all'
                                ? 'bg-white text-slate-900 shadow-xs'
                                : 'text-slate-600 hover:text-slate-900'
                        ]"
                    >
                        All ({{ tenants.length }})
                    </button>
                    <button
                        type="button"
                        @click="statusFilter = 'active'"
                        :class="[
                            'px-3 py-1.5 text-xs font-bold rounded-lg transition-all flex items-center gap-1.5',
                            statusFilter === 'active'
                                ? 'bg-white text-emerald-700 shadow-xs'
                                : 'text-slate-600 hover:text-emerald-700'
                        ]"
                    >
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        Active
                    </button>
                    <button
                        type="button"
                        @click="statusFilter = 'suspended'"
                        :class="[
                            'px-3 py-1.5 text-xs font-bold rounded-lg transition-all flex items-center gap-1.5',
                            statusFilter === 'suspended'
                                ? 'bg-white text-rose-700 shadow-xs'
                                : 'text-slate-600 hover:text-rose-700'
                        ]"
                    >
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                        Suspended
                    </button>
                </div>

                <!-- Package Dropdown Filter -->
                <div class="min-w-[160px]">
                    <select
                        v-model="packageFilter"
                        class="w-full py-2 px-3 text-xs font-semibold text-slate-700 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500"
                    >
                        <option value="all">All Packages</option>
                        <option value="none">No Package</option>
                        <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">
                            {{ pkg.name }}
                        </option>
                    </select>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="filteredTenants.length === 0" class="bg-white rounded-2xl border border-slate-200/80 p-12 text-center">
                <div class="w-14 h-14 bg-amber-50 text-amber-500 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <Building2 class="w-7 h-7" />
                </div>
                <h3 class="text-base font-bold text-slate-900">No tenants found</h3>
                <p class="text-sm text-slate-500 mt-1 max-w-sm mx-auto">
                    {{ searchQuery || statusFilter !== 'all' || packageFilter !== 'all' ? 'Try adjusting your search query or filters to find what you are looking for.' : 'Get started by onboarding your first business tenant and tenant administrator.' }}
                </p>
                <button
                    v-if="!searchQuery && statusFilter === 'all' && packageFilter === 'all'"
                    @click="openCreateModal"
                    class="mt-4 inline-flex items-center px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-sm font-bold rounded-xl shadow-xs"
                >
                    <Plus class="w-4 h-4 mr-1.5" />
                    Onboard First Tenant
                </button>
            </div>

            <!-- When Tenants Exist -->
            <template v-else>
                <!-- Desktop View: Modern High-Density Table (hidden on mobile) -->
                <div class="hidden md:block bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                                <th class="py-3.5 px-6">Tenant Organization</th>
                                <th class="py-3.5 px-4">Subscription Package</th>
                                <th class="py-3.5 px-4 text-center">Status</th>
                                <th class="py-3.5 px-4 text-center">Usage</th>
                                <th class="py-3.5 px-4">Renews / Expires</th>
                                <th class="py-3.5 px-6 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            <tr
                                v-for="tenant in filteredTenants"
                                :key="tenant.id"
                                class="hover:bg-slate-50/60 transition-colors group"
                            >
                                <!-- Tenant Name & Details -->
                                <td class="py-4 px-6">
                                    <div class="flex items-center space-x-3.5">
                                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-amber-500/10 to-amber-600/20 text-amber-700 flex items-center justify-center font-black text-sm uppercase shrink-0 border border-amber-500/20">
                                            {{ tenant.name.substring(0, 2) }}
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-2">
                                                <span class="font-bold text-slate-900 group-hover:text-amber-600 transition-colors truncate">
                                                    {{ tenant.name }}
                                                </span>
                                                <span v-if="tenant.code" class="text-[10px] font-mono font-bold px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 border border-slate-200">
                                                    {{ tenant.code }}
                                                </span>
                                            </div>
                                            <div class="text-xs text-slate-400 flex items-center gap-2 mt-0.5 truncate">
                                                <span v-if="tenant.email">{{ tenant.email }}</span>
                                                <span v-else class="italic">No billing email</span>
                                                <span v-if="tenant.phone">• {{ tenant.phone }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Package Details -->
                                <td class="py-4 px-4">
                                    <div v-if="tenant.subscription_package" class="flex items-center gap-2">
                                        <div class="p-1.5 rounded-lg bg-indigo-50 text-indigo-600">
                                            <PackageOpen class="w-4 h-4" />
                                        </div>
                                        <div>
                                            <div class="font-bold text-slate-800 text-xs sm:text-sm">
                                                {{ tenant.subscription_package.name }}
                                            </div>
                                            <div class="text-[11px] text-slate-400">
                                                TZS {{ Number(tenant.subscription_package.price).toLocaleString() }} / {{ tenant.subscription_package.duration_days }}d
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="inline-flex items-center text-xs font-semibold text-slate-400 bg-slate-100 px-2.5 py-1 rounded-lg">
                                        No Package Assigned
                                    </div>
                                </td>

                                <!-- Status Badge -->
                                <td class="py-4 px-4 text-center">
                                    <span
                                        v-if="tenant.subscription_status === 'active' && tenant.is_active"
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/60"
                                    >
                                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                        Active
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200/60"
                                    >
                                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                        Suspended
                                    </span>
                                </td>

                                <!-- Usage: Users & Branches -->
                                <td class="py-4 px-4 text-center">
                                    <div class="inline-flex items-center gap-2 bg-slate-50 border border-slate-200/70 px-2.5 py-1 rounded-lg text-xs font-semibold text-slate-600">
                                        <span class="flex items-center gap-1" title="Users">
                                            <Users class="w-3.5 h-3.5 text-slate-400" />
                                            {{ tenant.users_count || 0 }}
                                        </span>
                                        <span class="text-slate-300">|</span>
                                        <span class="flex items-center gap-1" title="Branches">
                                            <GitBranch class="w-3.5 h-3.5 text-slate-400" />
                                            {{ tenant.branches_count || 1 }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Expiration info -->
                                <td class="py-4 px-4">
                                    <div class="text-xs">
                                        <div class="font-semibold text-slate-700 flex items-center gap-1">
                                            <Calendar class="w-3.5 h-3.5 text-slate-400" />
                                            {{ formatDate(tenant.subscription_ends_at) }}
                                        </div>
                                        <div v-if="tenant.subscription_ends_at" class="text-[11px] mt-0.5 font-medium">
                                            <span v-if="getDaysRemaining(tenant.subscription_ends_at) > 0" class="text-slate-400">
                                                {{ getDaysRemaining(tenant.subscription_ends_at) }} days remaining
                                            </span>
                                            <span v-else class="text-rose-600 font-bold">
                                                Expired {{ Math.abs(getDaysRemaining(tenant.subscription_ends_at)) }} days ago
                                            </span>
                                        </div>
                                    </div>
                                </td>

                                <!-- Actions -->
                                <td class="py-4 px-6 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Edit Subscription Button -->
                                        <button
                                            @click="openEditModal(tenant)"
                                            title="Edit subscription & package"
                                            class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-200 transition-colors"
                                        >
                                            <Edit3 class="w-3.5 h-3.5 mr-1 text-slate-500" />
                                            Plan
                                        </button>

                                        <!-- Suspend Button (When Active) -->
                                        <button
                                            v-if="tenant.subscription_status === 'active' && tenant.is_active"
                                            @click="suspendTenant(tenant)"
                                            title="Suspend this tenant"
                                            class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition-colors"
                                        >
                                            <Ban class="w-3.5 h-3.5 mr-1" />
                                            Suspend
                                        </button>

                                        <!-- ACTIVATE Button (When Suspended / Inactive) -->
                                        <button
                                            v-else
                                            @click="activateTenant(tenant)"
                                            title="Reactivate this tenant"
                                            class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-xs shadow-emerald-600/20 transition-all active:scale-95"
                                        >
                                            <CheckCircle2 class="w-3.5 h-3.5 mr-1 stroke-[2.5]" />
                                            Activate
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

                <!-- Mobile View: Touch-Friendly Card Deck (hidden on desktop) -->
                <div class="block md:hidden space-y-4">
                <div
                    v-for="tenant in filteredTenants"
                    :key="tenant.id"
                    class="bg-white rounded-2xl border border-slate-200/90 shadow-xs p-4 space-y-3.5 relative overflow-hidden"
                >
                    <!-- Card Top: Avatar, Name, Code, and Status Badge -->
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center space-x-3 min-w-0">
                            <div class="w-10 h-10 rounded-xl bg-amber-50 border border-amber-200 text-amber-700 font-black text-sm flex items-center justify-center shrink-0 uppercase">
                                {{ tenant.name.substring(0, 2) }}
                            </div>
                            <div class="min-w-0">
                                <div class="font-bold text-slate-900 text-base leading-tight truncate">
                                    {{ tenant.name }}
                                </div>
                                <div class="flex items-center gap-1.5 mt-0.5">
                                    <span v-if="tenant.code" class="text-[10px] font-mono font-bold px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 border border-slate-200">
                                        {{ tenant.code }}
                                    </span>
                                    <span class="text-xs text-slate-400 truncate">{{ tenant.email || 'No email' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Status Badge -->
                        <span
                            v-if="tenant.subscription_status === 'active' && tenant.is_active"
                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0"
                        >
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            Active
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200 shrink-0"
                        >
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                            Suspended
                        </span>
                    </div>

                    <!-- Card Body Grid: Package, Usage, Expiration -->
                    <div class="grid grid-cols-2 gap-2 bg-slate-50/80 p-3 rounded-xl border border-slate-100 text-xs">
                        <div>
                            <span class="text-[10px] font-bold uppercase text-slate-400 block mb-0.5">Subscription</span>
                            <span class="font-bold text-slate-800">
                                {{ tenant.subscription_package ? tenant.subscription_package.name : 'No Package' }}
                            </span>
                        </div>
                        <div>
                            <span class="text-[10px] font-bold uppercase text-slate-400 block mb-0.5">Usage</span>
                            <span class="font-semibold text-slate-700">
                                {{ tenant.users_count || 0 }} users • {{ tenant.branches_count || 1 }} branches
                            </span>
                        </div>
                        <div class="col-span-2 pt-1 border-t border-slate-200/50 flex items-center justify-between">
                            <span class="text-[10px] font-bold uppercase text-slate-400">Renews</span>
                            <span class="font-semibold text-slate-700 flex items-center gap-1">
                                <Calendar class="w-3 h-3 text-slate-400" />
                                {{ formatDate(tenant.subscription_ends_at) }}
                            </span>
                        </div>
                    </div>

                    <!-- Card Bottom Action Buttons (Touch Friendly) -->
                    <div class="grid grid-cols-2 gap-2 pt-1">
                        <!-- Edit Subscription Button -->
                        <button
                            @click="openEditModal(tenant)"
                            class="inline-flex items-center justify-center py-2.5 px-3 rounded-xl text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-200 transition-colors"
                        >
                            <Edit3 class="w-3.5 h-3.5 mr-1 text-slate-500" />
                            Manage Plan
                        </button>

                        <!-- Suspend Button (If Active) -->
                        <button
                            v-if="tenant.subscription_status === 'active' && tenant.is_active"
                            @click="suspendTenant(tenant)"
                            class="inline-flex items-center justify-center py-2.5 px-3 rounded-xl text-xs font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition-colors"
                        >
                            <Ban class="w-3.5 h-3.5 mr-1" />
                            Suspend
                        </button>

                        <!-- ACTIVATE Button (If Suspended) -->
                        <button
                            v-else
                            @click="activateTenant(tenant)"
                            class="inline-flex items-center justify-center py-2.5 px-3 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-700 shadow-sm transition-colors active:scale-95"
                        >
                            <CheckCircle2 class="w-3.5 h-3.5 mr-1 stroke-[2.5]" />
                            Activate
                        </button>
                    </div>
                </div>
            </div>
        </template>

        </div>

        <!-- Modal: Create New Tenant & Admin Account -->
        <Modal :show="isCreateModalOpen" @close="closeCreateModal" maxWidth="2xl">
            <div class="p-5 sm:p-7">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-5">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl bg-amber-500/10 text-amber-600 flex items-center justify-center">
                            <Building2 class="w-5 h-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-black text-slate-900">Provision New Tenant</h2>
                            <p class="text-xs text-slate-500">Create the organization workspace and initial administrator</p>
                        </div>
                    </div>
                    <button @click="closeCreateModal" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="space-y-5">
                    <!-- Section 1: Business Details -->
                    <div class="bg-slate-50/70 p-4 rounded-xl border border-slate-200/80 space-y-4">
                        <div class="flex items-center gap-2 text-xs font-bold text-slate-700 uppercase tracking-wider">
                            <Building2 class="w-4 h-4 text-amber-500" />
                            <span>Business Information</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="name" value="Business Name *" />
                                <TextInput
                                    id="name"
                                    v-model="createForm.name"
                                    type="text"
                                    class="mt-1 block w-full rounded-xl"
                                    placeholder="e.g. Acme Hardware Supplies"
                                    required
                                />
                                <InputError :message="createForm.errors.name" class="mt-1" />
                            </div>

                            <div>
                                <InputLabel for="pkg" value="Subscription Package" />
                                <select
                                    id="pkg"
                                    v-model="createForm.subscription_package_id"
                                    class="mt-1 block w-full border-slate-300 focus:border-amber-500 focus:ring-amber-500 rounded-xl shadow-xs text-sm"
                                >
                                    <option value="">None (Free / Manual)</option>
                                    <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">
                                        {{ pkg.name }} — TZS {{ Number(pkg.price).toLocaleString() }} ({{ pkg.duration_days }}d)
                                    </option>
                                </select>
                                <InputError :message="createForm.errors.subscription_package_id" class="mt-1" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="email" value="Business Email" />
                                <TextInput
                                    id="email"
                                    v-model="createForm.email"
                                    type="email"
                                    class="mt-1 block w-full rounded-xl"
                                    placeholder="contact@business.com"
                                />
                                <InputError :message="createForm.errors.email" class="mt-1" />
                            </div>

                            <div>
                                <InputLabel for="phone" value="Phone Number" />
                                <TextInput
                                    id="phone"
                                    v-model="createForm.phone"
                                    type="text"
                                    class="mt-1 block w-full rounded-xl"
                                    placeholder="+255 700 000 000"
                                />
                                <InputError :message="createForm.errors.phone" class="mt-1" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="address" value="Physical Address" />
                            <TextInput
                                id="address"
                                v-model="createForm.address"
                                type="text"
                                class="mt-1 block w-full rounded-xl"
                                placeholder="Plot 12, Industrial Area, Dar es Salaam"
                            />
                            <InputError :message="createForm.errors.address" class="mt-1" />
                        </div>
                    </div>

                    <!-- Section 2: Tenant Admin Account -->
                    <div class="bg-indigo-50/50 p-4 rounded-xl border border-indigo-100 space-y-4">
                        <div class="flex items-center gap-2 text-xs font-bold text-indigo-800 uppercase tracking-wider">
                            <ShieldCheck class="w-4 h-4 text-indigo-600" />
                            <span>Primary Tenant Administrator Account</span>
                        </div>

                        <div>
                            <InputLabel for="admin_name" value="Admin Full Name *" />
                            <TextInput
                                id="admin_name"
                                v-model="createForm.admin_name"
                                type="text"
                                class="mt-1 block w-full rounded-xl"
                                placeholder="Jane Doe"
                                required
                            />
                            <InputError :message="createForm.errors.admin_name" class="mt-1" />
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="admin_email" value="Admin Login Email *" />
                                <TextInput
                                    id="admin_email"
                                    v-model="createForm.admin_email"
                                    type="email"
                                    class="mt-1 block w-full rounded-xl"
                                    placeholder="admin@tenantbusiness.com"
                                    required
                                />
                                <InputError :message="createForm.errors.admin_email" class="mt-1" />
                            </div>

                            <div>
                                <InputLabel for="admin_password" value="Initial Password *" />
                                <TextInput
                                    id="admin_password"
                                    v-model="createForm.admin_password"
                                    type="password"
                                    class="mt-1 block w-full rounded-xl"
                                    placeholder="Minimum 8 characters"
                                    required
                                />
                                <InputError :message="createForm.errors.admin_password" class="mt-1" />
                            </div>
                        </div>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex items-center justify-end space-x-3 pt-3 border-t border-slate-100">
                        <button
                            type="button"
                            @click="closeCreateModal"
                            class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="createForm.processing"
                            class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 font-bold text-sm text-white shadow-xs transition-all active:scale-95 disabled:opacity-50"
                        >
                            <span v-if="createForm.processing">Provisioning...</span>
                            <span v-else>Create Tenant & Admin</span>
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Modal: Edit Subscription & Status -->
        <Modal :show="isEditModalOpen" @close="closeEditModal" maxWidth="lg">
            <div class="p-5 sm:p-7">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-5">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                            <Edit3 class="w-5 h-5" />
                        </div>
                        <div>
                            <h2 class="text-lg font-black text-slate-900">Manage Subscription</h2>
                            <p class="text-xs text-slate-500">{{ selectedTenant?.name }}</p>
                        </div>
                    </div>
                    <button @click="closeEditModal" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitUpdateSubscription" class="space-y-4">
                    <div>
                        <InputLabel for="edit_pkg" value="Subscription Package" />
                        <select
                            id="edit_pkg"
                            v-model="subscriptionForm.subscription_package_id"
                            class="mt-1 block w-full border-slate-300 focus:border-amber-500 focus:ring-amber-500 rounded-xl shadow-xs text-sm"
                        >
                            <option value="">No Package Assigned</option>
                            <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">
                                {{ pkg.name }} (TZS {{ Number(pkg.price).toLocaleString() }} / {{ pkg.duration_days }}d)
                            </option>
                        </select>
                        <InputError :message="subscriptionForm.errors.subscription_package_id" class="mt-1" />
                    </div>

                    <div>
                        <InputLabel for="edit_status" value="Subscription Status *" />
                        <select
                            id="edit_status"
                            v-model="subscriptionForm.subscription_status"
                            class="mt-1 block w-full border-slate-300 focus:border-amber-500 focus:ring-amber-500 rounded-xl shadow-xs text-sm"
                            required
                        >
                            <option value="active">Active (Access Allowed)</option>
                            <option value="suspended">Suspended (Access Restricted)</option>
                            <option value="expired">Expired (Grace Period)</option>
                        </select>
                        <InputError :message="subscriptionForm.errors.subscription_status" class="mt-1" />
                    </div>

                    <div>
                        <InputLabel for="edit_ends" value="Subscription Expiration Date" />
                        <TextInput
                            id="edit_ends"
                            v-model="subscriptionForm.subscription_ends_at"
                            type="date"
                            class="mt-1 block w-full rounded-xl"
                        />
                        <p class="text-[11px] text-slate-400 mt-1">Leave empty for open-ended access.</p>
                        <InputError :message="subscriptionForm.errors.subscription_ends_at" class="mt-1" />
                    </div>

                    <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
                        <button
                            type="button"
                            @click="closeEditModal"
                            class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="subscriptionForm.processing"
                            class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 font-bold text-sm text-white shadow-xs transition-all active:scale-95 disabled:opacity-50"
                        >
                            <span v-if="subscriptionForm.processing">Saving...</span>
                            <span v-else>Update Subscription</span>
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

    </AppLayout>
</template>