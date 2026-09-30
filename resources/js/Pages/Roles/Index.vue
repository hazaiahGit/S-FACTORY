<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import {
    Shield,
    ShieldCheck,
    KeyRound,
    Users,
    Plus,
    Search,
    Pencil,
    Trash2,
    X,
    Lock,
    AlertCircle,
    LayoutGrid,
    List,
    AlertTriangle
} from '@lucide/vue';

const props = defineProps({
    roles: {
        type: Array,
        default: () => [],
    },
});

const search = ref('');
const viewMode = ref('grid'); // 'grid' | 'table'
const roleToDelete = ref(null);
const showDeleteModal = ref(false);
const isDeleting = ref(false);

const systemRoleNames = ['Super Admin', 'Cashier', 'Manager'];

const isSystemRole = (name) => {
    return systemRoleNames.includes(name);
};

// Filter roles client-side by name
const filteredRoles = computed(() => {
    if (!search.value.trim()) return props.roles;
    const query = search.value.toLowerCase().trim();
    return props.roles.filter((role) =>
        role.name?.toLowerCase().includes(query)
    );
});

// Summary metrics
const totalRolesCount = computed(() => props.roles.length);
const totalAssignedUsers = computed(() =>
    props.roles.reduce((acc, r) => acc + (Number(r.users_count) || 0), 0)
);
const systemRolesCount = computed(() =>
    props.roles.filter((r) => isSystemRole(r.name)).length
);

const clearSearch = () => {
    search.value = '';
};

const getRoleTheme = (roleName) => {
    if (!roleName) {
        return {
            badge: 'bg-slate-100 text-slate-700 border-slate-200',
            iconBg: 'bg-slate-100 text-slate-700 border-slate-200',
        };
    }
    const lower = roleName.toLowerCase();
    if (lower.includes('super') || lower.includes('admin')) {
        return {
            badge: 'bg-purple-100 text-purple-700 border-purple-200',
            iconBg: 'bg-purple-100 text-purple-700 border-purple-200',
        };
    }
    if (lower.includes('manager')) {
        return {
            badge: 'bg-blue-100 text-blue-700 border-blue-200',
            iconBg: 'bg-blue-100 text-blue-700 border-blue-200',
        };
    }
    if (lower.includes('cashier')) {
        return {
            badge: 'bg-amber-100 text-amber-700 border-amber-200',
            iconBg: 'bg-amber-100 text-amber-700 border-amber-200',
        };
    }
    if (lower.includes('supervisor')) {
        return {
            badge: 'bg-emerald-100 text-emerald-700 border-emerald-200',
            iconBg: 'bg-emerald-100 text-emerald-700 border-emerald-200',
        };
    }
    return {
        badge: 'bg-indigo-50 text-indigo-700 border-indigo-200',
        iconBg: 'bg-indigo-50 text-indigo-700 border-indigo-200',
    };
};

const canDeleteRole = (role) => {
    if (isSystemRole(role.name)) return false;
    if (role.users_count && role.users_count > 0) return false;
    return true;
};

const getDeleteDisabledReason = (role) => {
    if (isSystemRole(role.name)) {
        return 'System default roles cannot be deleted.';
    }
    if (role.users_count && role.users_count > 0) {
        return `Cannot delete role with ${role.users_count} assigned user(s).`;
    }
    return '';
};

const confirmDelete = (role) => {
    if (!canDeleteRole(role)) return;
    roleToDelete.value = role;
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    roleToDelete.value = null;
    isDeleting.value = false;
};

const deleteRole = () => {
    if (!roleToDelete.value) return;
    isDeleting.value = true;
    router.delete(route('roles.destroy', roleToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            closeDeleteModal();
        },
        onFinish: () => {
            isDeleting.value = false;
        },
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Roles & Permissions" />

        <template #header>
            <div class="flex items-center text-lg sm:text-xl font-bold">
                <Shield class="w-6 h-6 mr-3 text-amber-500" />
                <span>Roles & Permissions</span>
            </div>
        </template>

        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Header Banner / Summary Stats -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Roles</p>
                        <h4 class="text-2xl font-black text-slate-900 mt-1">{{ totalRolesCount }}</h4>
                    </div>
                    <div class="h-12 w-12 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center">
                        <Shield class="w-6 h-6" />
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Assigned Users</p>
                        <h4 class="text-2xl font-black text-slate-900 mt-1">{{ totalAssignedUsers }}</h4>
                    </div>
                    <div class="h-12 w-12 rounded-xl bg-blue-100 text-blue-700 flex items-center justify-center">
                        <Users class="w-6 h-6" />
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs flex items-center justify-between">
                    <div>
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">System Protected</p>
                        <h4 class="text-2xl font-black text-slate-900 mt-1">{{ systemRolesCount }}</h4>
                    </div>
                    <div class="h-12 w-12 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center">
                        <Lock class="w-6 h-6" />
                    </div>
                </div>
            </div>

            <!-- Toolbar: Search, View Mode, Action Buttons -->
            <div class="flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-4 bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
                <!-- Search Input -->
                <div class="relative w-full max-w-md">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <Search class="h-4 w-4" />
                    </div>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search roles by name..."
                        class="block w-full pl-10 pr-9 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:bg-white focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-colors"
                    />
                    <button
                        v-if="search"
                        type="button"
                        @click="clearSearch"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <!-- Right Side Actions & View Mode Toggle -->
                <div class="flex items-center gap-3 self-end sm:self-center">
                    <!-- View Mode Toggle -->
                    <div class="inline-flex rounded-xl bg-slate-100 p-1 border border-slate-200">
                        <button
                            type="button"
                            @click="viewMode = 'grid'"
                            :class="[
                                'inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold transition-all',
                                viewMode === 'grid'
                                    ? 'bg-white text-slate-900 shadow-xs'
                                    : 'text-slate-500 hover:text-slate-900'
                            ]"
                            title="Grid View"
                        >
                            <LayoutGrid class="w-4 h-4 mr-1.5" />
                            <span>Cards</span>
                        </button>
                        <button
                            type="button"
                            @click="viewMode = 'table'"
                            :class="[
                                'inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold transition-all',
                                viewMode === 'table'
                                    ? 'bg-white text-slate-900 shadow-xs'
                                    : 'text-slate-500 hover:text-slate-900'
                            ]"
                            title="Table View"
                        >
                            <List class="w-4 h-4 mr-1.5" />
                            <span>Table</span>
                        </button>
                    </div>

                    <!-- Manage Users Link -->
                    <Link
                        :href="route('users.index')"
                        class="inline-flex items-center px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none transition-colors"
                    >
                        <Users class="w-4 h-4 mr-2 text-slate-500" />
                        <span class="hidden md:inline">Users List</span>
                    </Link>

                    <!-- Add Role Button -->
                    <Link
                        :href="route('roles.create')"
                        class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl shadow-xs text-sm font-semibold text-slate-900 bg-amber-400 hover:bg-amber-500 active:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition-colors"
                    >
                        <Plus class="w-4 h-4 mr-1.5" />
                        <span>Add Role</span>
                    </Link>
                </div>
            </div>

            <!-- Card Grid View -->
            <div
                v-if="viewMode === 'grid' && filteredRoles.length > 0"
                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5"
            >
                <div
                    v-for="role in filteredRoles"
                    :key="role.id"
                    class="bg-white rounded-2xl shadow-xs border border-slate-200 overflow-hidden hover:shadow-md hover:border-slate-300 transition-all flex flex-col group"
                >
                    <!-- Card Header -->
                    <div class="p-5 border-b border-slate-100 bg-slate-50/50">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center space-x-3 min-w-0">
                                <div
                                    :class="[
                                        'h-12 w-12 rounded-xl flex items-center justify-center border shrink-0 shadow-xs font-black text-base',
                                        getRoleTheme(role.name).iconBg
                                    ]"
                                >
                                    <ShieldCheck v-if="isSystemRole(role.name)" class="w-6 h-6" />
                                    <Shield v-else class="w-6 h-6" />
                                </div>
                                <div class="min-w-0 flex-1">
                                    <h3 class="text-base font-bold text-slate-900 truncate group-hover:text-amber-600 transition-colors">
                                        {{ role.name }}
                                    </h3>
                                    <div class="mt-1">
                                        <span
                                            v-if="isSystemRole(role.name)"
                                            class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-purple-100 text-purple-700 border border-purple-200"
                                        >
                                            <Lock class="w-2.5 h-2.5 mr-1" />
                                            System Default
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-700 border border-emerald-200"
                                        >
                                            Custom Role
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card Body: Stats -->
                    <div class="p-5 space-y-3 flex-1">
                        <!-- Attached Users Count -->
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100">
                            <div class="flex items-center text-xs font-semibold text-slate-600">
                                <Users class="w-4 h-4 mr-2 text-blue-500 shrink-0" />
                                <span>Users Assigned</span>
                            </div>
                            <span class="text-sm font-black text-slate-900 px-2 py-0.5 rounded-lg bg-white border border-slate-200 shadow-2xs">
                                {{ role.users_count || 0 }}
                            </span>
                        </div>

                        <!-- Attached Permissions Count -->
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-50 border border-slate-100">
                            <div class="flex items-center text-xs font-semibold text-slate-600">
                                <KeyRound class="w-4 h-4 mr-2 text-amber-500 shrink-0" />
                                <span>Permissions Granted</span>
                            </div>
                            <span class="text-sm font-black text-slate-900 px-2 py-0.5 rounded-lg bg-white border border-slate-200 shadow-2xs">
                                {{ role.permissions_count || 0 }}
                            </span>
                        </div>
                    </div>

                    <!-- Card Footer: Actions -->
                    <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center gap-2">
                        <!-- Edit Button -->
                        <Link
                            :href="route('roles.edit', role.id)"
                            class="inline-flex flex-1 justify-center items-center px-3 py-2 text-xs font-bold rounded-xl text-amber-800 bg-amber-200/70 hover:bg-amber-300 transition-colors shadow-2xs"
                        >
                            <Pencil class="w-3.5 h-3.5 mr-1.5" />
                            <span>Edit Role</span>
                        </Link>

                        <!-- Delete Button / Tooltip -->
                        <button
                            v-if="canDeleteRole(role)"
                            type="button"
                            @click="confirmDelete(role)"
                            class="inline-flex items-center justify-center p-2 rounded-xl text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition-colors shadow-2xs"
                            title="Delete Role"
                        >
                            <Trash2 class="w-4 h-4" />
                        </button>
                        <span
                            v-else
                            class="inline-flex items-center justify-center p-2 rounded-xl text-slate-400 bg-slate-100 border border-slate-200 cursor-not-allowed"
                            :title="getDeleteDisabledReason(role)"
                        >
                            <Lock class="w-4 h-4" />
                        </span>
                    </div>
                </div>
            </div>

            <!-- Table View -->
            <div
                v-else-if="viewMode === 'table' && filteredRoles.length > 0"
                class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden"
            >
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50/75 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                                <th class="py-3.5 px-5">Role Name</th>
                                <th class="py-3.5 px-5">Type</th>
                                <th class="py-3.5 px-5 text-center">Assigned Users</th>
                                <th class="py-3.5 px-5 text-center">Permissions Attached</th>
                                <th class="py-3.5 px-5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-sm">
                            <tr
                                v-for="role in filteredRoles"
                                :key="role.id"
                                class="hover:bg-slate-50/70 transition-colors"
                            >
                                <td class="py-4 px-5">
                                    <div class="flex items-center space-x-3">
                                        <div
                                            :class="[
                                                'h-9 w-9 rounded-lg flex items-center justify-center border font-bold text-sm shrink-0',
                                                getRoleTheme(role.name).iconBg
                                            ]"
                                        >
                                            <Shield class="w-4 h-4" />
                                        </div>
                                        <div>
                                            <span class="font-bold text-slate-900 block">{{ role.name }}</span>
                                            <span class="text-xs text-slate-400">ID: #{{ role.id }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-5">
                                    <span
                                        v-if="isSystemRole(role.name)"
                                        class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-purple-100 text-purple-700 border border-purple-200"
                                    >
                                        <Lock class="w-2.5 h-2.5 mr-1" />
                                        System Default
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-700 border border-emerald-200"
                                    >
                                        Custom Role
                                    </span>
                                </td>
                                <td class="py-4 px-5 text-center">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                        <Users class="w-3.5 h-3.5 mr-1" />
                                        {{ role.users_count || 0 }}
                                    </span>
                                </td>
                                <td class="py-4 px-5 text-center">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                        <KeyRound class="w-3.5 h-3.5 mr-1" />
                                        {{ role.permissions_count || 0 }}
                                    </span>
                                </td>
                                <td class="py-4 px-5 text-right space-x-2">
                                    <Link
                                        :href="route('roles.edit', role.id)"
                                        class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-bold text-amber-800 bg-amber-100 hover:bg-amber-200 border border-amber-200 transition-colors shadow-2xs"
                                    >
                                        <Pencil class="w-3 h-3 mr-1" />
                                        Edit
                                    </Link>
                                    <button
                                        v-if="canDeleteRole(role)"
                                        type="button"
                                        @click="confirmDelete(role)"
                                        class="inline-flex items-center px-2.5 py-1.5 rounded-lg text-xs font-bold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition-colors shadow-2xs"
                                        title="Delete Role"
                                    >
                                        <Trash2 class="w-3.5 h-3.5" />
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-if="filteredRoles.length === 0"
                class="flex flex-col items-center justify-center p-12 text-center bg-white rounded-2xl shadow-xs border border-slate-200"
            >
                <div class="h-16 w-16 bg-amber-50 text-amber-600 rounded-full flex items-center justify-center mb-4 border border-amber-200">
                    <Shield class="h-8 w-8" />
                </div>
                <h3 class="text-lg font-bold text-slate-900 mb-1">No roles found</h3>
                <p class="text-slate-500 text-sm max-w-sm mb-6">
                    {{ search ? `No roles matched "${search}". Try searching with different keywords.` : 'There are currently no roles defined in the system.' }}
                </p>
                <div class="flex items-center gap-3">
                    <button
                        v-if="search"
                        type="button"
                        @click="clearSearch"
                        class="inline-flex items-center px-4 py-2.5 rounded-xl border border-slate-300 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors"
                    >
                        Clear Search
                    </button>
                    <Link
                        :href="route('roles.create')"
                        class="inline-flex items-center px-5 py-2.5 rounded-xl shadow-xs text-sm font-semibold text-slate-900 bg-amber-400 hover:bg-amber-500 transition-colors"
                    >
                        <Plus class="w-4 h-4 mr-2" />
                        Create Role
                    </Link>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <div
            v-if="showDeleteModal"
            class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
        >
            <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-xl border border-slate-200 space-y-4 animate-in fade-in zoom-in-95 duration-150">
                <div class="flex items-center space-x-3 text-rose-600">
                    <div class="h-10 w-10 rounded-xl bg-rose-100 flex items-center justify-center shrink-0">
                        <AlertTriangle class="w-5 h-5 text-rose-600" />
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Delete Role</h3>
                        <p class="text-xs text-slate-500">This action cannot be undone.</p>
                    </div>
                </div>

                <p class="text-sm text-slate-600 leading-relaxed">
                    Are you sure you want to permanently delete the role
                    <span class="font-bold text-slate-900">"{{ roleToDelete?.name }}"</span>?
                </p>

                <div class="flex items-center justify-end space-x-3 pt-3">
                    <button
                        type="button"
                        @click="closeDeleteModal"
                        :disabled="isDeleting"
                        class="px-4 py-2 rounded-xl border border-slate-300 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition-colors"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="deleteRole"
                        :disabled="isDeleting"
                        class="inline-flex items-center px-4 py-2 rounded-xl shadow-xs text-sm font-semibold text-white bg-rose-600 hover:bg-rose-700 active:bg-rose-800 disabled:opacity-50 transition-colors"
                    >
                        <Trash2 class="w-4 h-4 mr-1.5" />
                        <span>{{ isDeleting ? 'Deleting...' : 'Confirm Delete' }}</span>
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
