<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import {
    ArrowLeft,
    Shield,
    KeyRound,
    Save,
    CheckSquare,
    Square,
    Search,
    X,
    AlertCircle,
    Lock,
    Check,
    AlertTriangle,
    Info
} from '@lucide/vue';

const props = defineProps({
    role: {
        type: Object,
        required: true,
    },
    permissions: {
        type: [Array, Object],
        default: () => [],
    },
});

const isSuperAdmin = computed(() => props.role?.name === 'Super Admin');
const isDefaultRole = computed(() => ['Super Admin', 'Cashier', 'Manager'].includes(props.role?.name));

// Pre-select existing permissions as array of strings
const initialPermissions = (props.role?.permissions || []).map((p) => {
    return typeof p === 'string' ? p : p.name;
});

const form = useForm({
    name: props.role?.name || '',
    permissions: initialPermissions,
});

const permissionSearch = ref('');

const formatName = (str) => {
    if (!str) return '';
    return str
        .replace(/[-_]/g, ' ')
        .replace(/\b\w/g, (c) => c.toUpperCase());
};

// Categorize permissions into clean module groups
const getPermissionCategory = (permName) => {
    if (!permName) return 'General Permissions';
    const lower = permName.toLowerCase().trim();

    if (lower.includes('sale') || lower.includes('pos')) return 'POS & Sales';
    if (lower.includes('product') || lower.includes('categor') || lower.includes('brand') || lower.includes('unit')) return 'Products & Catalog';
    if (lower.includes('inventory') || lower.includes('stock')) return 'Inventory & Stock';
    if (lower.includes('purchase')) return 'Purchases';
    if (lower.includes('recipe') || lower.includes('bom') || lower.includes('production') || lower.includes('manufactur')) return 'Manufacturing';
    if (lower.includes('customer')) return 'Customers';
    if (lower.includes('supplier')) return 'Suppliers';
    if (lower.includes('expense')) return 'Expenses';
    if (lower.includes('user') || lower.includes('role')) return 'Users & Access Control';
    if (lower.includes('setting') || lower.includes('branch')) return 'Settings & Branches';
    if (lower.includes('report') || lower.includes('audit')) return 'Reports & Audits';
    if (lower.includes('target') || lower.includes('goal')) return 'Targets & Performance';
    if (lower.includes('approval')) return 'Approvals';

    if (permName.includes('.')) {
        return formatName(permName.split('.')[0]);
    }
    if (permName.includes(':')) {
        return formatName(permName.split('.')[0]);
    }

    const words = permName.split(' ');
    if (words.length > 1) {
        return formatName(words.slice(1).join(' '));
    }

    return 'General Permissions';
};

// Group permissions into an object { [groupName]: [permissionObj, ...] }
const groupedPermissions = computed(() => {
    if (!props.permissions) return {};

    // If already a key-value object
    if (!Array.isArray(props.permissions)) {
        return props.permissions;
    }

    const groups = {};

    props.permissions.forEach((item) => {
        if (!item) return;

        let groupName = 'General Permissions';

        if (item.group_name) {
            groupName = item.group_name;
        } else if (item.category) {
            groupName = item.category;
        } else if (item.group && item.permissions) {
            groups[item.group] = item.permissions;
            return;
        } else {
            const rawName = typeof item === 'string' ? item : item.name;
            groupName = getPermissionCategory(rawName);
        }

        if (!groups[groupName]) {
            groups[groupName] = [];
        }
        groups[groupName].push(item);
    });

    return groups;
});

// Filter groups based on search input
const filteredGroupedPermissions = computed(() => {
    const q = permissionSearch.value.trim().toLowerCase();
    if (!q) return groupedPermissions.value;

    const filtered = {};
    Object.entries(groupedPermissions.value).forEach(([groupName, perms]) => {
        const matching = perms.filter((p) => {
            const name = typeof p === 'string' ? p : p.name;
            return name.toLowerCase().includes(q) || groupName.toLowerCase().includes(q);
        });
        if (matching.length > 0) {
            filtered[groupName] = matching;
        }
    });
    return filtered;
});

// Total count of all permissions passed in props
const totalPermissionsCount = computed(() => {
    let count = 0;
    Object.values(groupedPermissions.value).forEach((perms) => {
        count += perms.length;
    });
    return count;
});

const isGroupAllSelected = (perms) => {
    if (!perms || perms.length === 0) return false;
    return perms.every((p) => {
        const name = typeof p === 'string' ? p : p.name;
        return form.permissions.includes(name);
    });
};

const toggleGroupPermissions = (perms) => {
    if (isSuperAdmin.value) return;
    if (!perms || perms.length === 0) return;
    const namesInGroup = perms
        .map((p) => (typeof p === 'string' ? p : p.name))
        .filter(Boolean);

    if (isGroupAllSelected(perms)) {
        form.permissions = form.permissions.filter((name) => !namesInGroup.includes(name));
    } else {
        const namesToAdd = namesInGroup.filter((name) => !form.permissions.includes(name));
        form.permissions = [...form.permissions, ...namesToAdd];
    }
};

const selectAllPermissions = () => {
    if (isSuperAdmin.value) return;
    const all = [];
    Object.values(groupedPermissions.value).forEach((groupList) => {
        groupList.forEach((p) => {
            const name = typeof p === 'string' ? p : p.name;
            if (name && !all.includes(name)) {
                all.push(name);
            }
        });
    });
    form.permissions = all;
};

const clearAllPermissions = () => {
    if (isSuperAdmin.value) return;
    form.permissions = [];
};

const submit = () => {
    if (isSuperAdmin.value) return;
    form.put(route('roles.update', props.role.id));
};
</script>

<template>
    <AppLayout>
        <Head :title="`Edit Role - ${role.name}`" />

        <template #header>
            <div class="flex items-center text-lg sm:text-xl font-bold">
                <Link
                    :href="route('roles.index')"
                    class="mr-3 text-slate-400 hover:text-slate-700 transition-colors p-1 rounded-lg hover:bg-slate-100"
                >
                    <ArrowLeft class="w-5 h-5 sm:w-6 sm:h-6" />
                </Link>
                <span>Edit Role: {{ role.name }}</span>
            </div>
        </template>

        <div class="max-w-5xl mx-auto space-y-6">
            <!-- Super Admin Notice Banner -->
            <div
                v-if="isSuperAdmin"
                class="bg-purple-50 border-l-4 border-purple-500 p-4 rounded-xl shadow-xs flex items-start"
            >
                <div class="p-1 bg-purple-100 text-purple-700 rounded-lg mr-3 shrink-0">
                    <Lock class="w-5 h-5" />
                </div>
                <div>
                    <h4 class="text-sm font-bold text-purple-900">Protected System Role</h4>
                    <p class="text-xs text-purple-700 mt-0.5">
                        The Super Admin role is protected by the application architecture. Its permissions and identity cannot be altered to prevent accidental lockouts.
                    </p>
                </div>
            </div>

            <!-- Core Default Role Notice Banner -->
            <div
                v-else-if="isDefaultRole"
                class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded-xl shadow-xs flex items-start"
            >
                <div class="p-1 bg-blue-100 text-blue-700 rounded-lg mr-3 shrink-0">
                    <Info class="w-5 h-5" />
                </div>
                <div>
                    <h4 class="text-sm font-bold text-blue-900">Core Default Role</h4>
                    <p class="text-xs text-blue-700 mt-0.5">
                        "{{ role.name }}" is a built-in role. You can update its name and adjust attached permissions, but the role itself cannot be deleted from the system.
                    </p>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Role Basic Information Card -->
                <div class="bg-white rounded-2xl shadow-xs border border-slate-200 overflow-hidden">
                    <div class="p-5 sm:p-6 border-b border-slate-100 bg-slate-50/75 flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="h-9 w-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center mr-3">
                                <Shield class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
                                    Role Information
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Manage role name and identifier
                                </p>
                            </div>
                        </div>

                        <span
                            v-if="isDefaultRole"
                            class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider bg-purple-100 text-purple-700 border border-purple-200"
                        >
                            <Lock class="w-2.5 h-2.5 mr-1" />
                            System Default
                        </span>
                    </div>

                    <div class="p-5 sm:p-6">
                        <div class="max-w-xl">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                Role Name <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <Shield class="w-4 h-4" />
                                </div>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Role Name"
                                    :disabled="isSuperAdmin"
                                    class="block w-full pl-10 pr-3.5 py-2.5 bg-white border border-slate-300 rounded-xl shadow-2xs text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 disabled:bg-slate-100 disabled:cursor-not-allowed transition-colors"
                                    required
                                />
                            </div>
                            <p v-if="form.errors.name" class="mt-1.5 text-xs text-rose-600 font-medium">
                                {{ form.errors.name }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Role Permissions Assignment Card -->
                <div class="bg-white rounded-2xl shadow-xs border border-slate-200 overflow-hidden">
                    <div class="p-5 sm:p-6 border-b border-slate-100 bg-slate-50/75 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                        <div class="flex items-center">
                            <div class="h-9 w-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center mr-3 shrink-0">
                                <KeyRound class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
                                    Role Permissions
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Customize which system permissions are granted to this role
                                </p>
                            </div>
                        </div>

                        <!-- Global Permission Actions -->
                        <div v-if="!isSuperAdmin" class="flex flex-wrap items-center gap-2.5">
                            <span class="text-xs font-bold text-slate-600 bg-slate-100 px-3 py-1.5 rounded-lg border border-slate-200">
                                {{ form.permissions.length }} of {{ totalPermissionsCount }} Selected
                            </span>
                            <button
                                type="button"
                                @click="selectAllPermissions"
                                class="inline-flex items-center px-3 py-1.5 text-xs font-bold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 hover:border-slate-400 transition-colors shadow-2xs"
                            >
                                <CheckSquare class="w-3.5 h-3.5 mr-1.5 text-amber-600" />
                                Select All
                            </button>
                            <button
                                type="button"
                                @click="clearAllPermissions"
                                class="inline-flex items-center px-3 py-1.5 text-xs font-bold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 hover:border-slate-400 transition-colors shadow-2xs"
                            >
                                <Square class="w-3.5 h-3.5 mr-1.5 text-slate-400" />
                                Clear All
                            </button>
                        </div>
                    </div>

                    <!-- Permission Search Filter Bar -->
                    <div class="p-4 sm:px-6 bg-slate-50/50 border-b border-slate-100 flex items-center justify-between gap-4">
                        <div class="relative w-full max-w-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                                <Search class="h-3.5 w-3.5" />
                            </div>
                            <input
                                v-model="permissionSearch"
                                type="text"
                                placeholder="Filter permissions..."
                                class="block w-full pl-9 pr-8 py-1.5 bg-white border border-slate-200 rounded-lg text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500 transition-colors"
                            />
                            <button
                                v-if="permissionSearch"
                                type="button"
                                @click="permissionSearch = ''"
                                class="absolute inset-y-0 right-0 pr-2.5 flex items-center text-slate-400 hover:text-slate-600"
                            >
                                <X class="h-3 w-3" />
                            </button>
                        </div>

                        <span class="text-xs text-slate-400 hidden sm:inline">
                            Organized by module
                        </span>
                    </div>

                    <div class="p-5 sm:p-6 space-y-6">
                        <p v-if="form.errors.permissions" class="text-xs text-rose-600 font-medium">
                            {{ form.errors.permissions }}
                        </p>

                        <!-- Grouped Permissions -->
                        <div
                            v-for="(groupPerms, groupName) in filteredGroupedPermissions"
                            :key="groupName"
                            class="border border-slate-200 rounded-2xl p-4 sm:p-5 bg-slate-50/40 space-y-3.5"
                        >
                            <!-- Group Header -->
                            <div class="flex items-center justify-between pb-3 border-b border-slate-200/80">
                                <div class="flex items-center space-x-2.5">
                                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500 shadow-2xs"></span>
                                    <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                                        {{ groupName }}
                                    </h4>
                                    <span class="text-[11px] font-semibold text-slate-400">
                                        ({{ groupPerms.filter(p => form.permissions.includes(typeof p === 'string' ? p : p.name)).length }}/{{ groupPerms.length }})
                                    </span>
                                </div>
                                <button
                                    v-if="!isSuperAdmin"
                                    type="button"
                                    @click="toggleGroupPermissions(groupPerms)"
                                    class="text-xs font-bold text-amber-600 hover:text-amber-700 transition-colors py-0.5 px-2 rounded-md hover:bg-amber-50"
                                >
                                    {{ isGroupAllSelected(groupPerms) ? 'Deselect Group' : 'Select Group' }}
                                </button>
                            </div>

                            <!-- Permission Checkboxes Grid -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                <label
                                    v-for="perm in groupPerms"
                                    :key="perm.id || perm.name"
                                    :class="[
                                        'relative flex items-center p-3 rounded-xl border text-xs transition-all select-none',
                                        isSuperAdmin ? 'cursor-default' : 'cursor-pointer',
                                        form.permissions.includes(perm.name)
                                            ? 'bg-amber-50/90 border-amber-300 text-slate-900 shadow-2xs ring-1 ring-amber-400/30'
                                            : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50 hover:border-slate-300'
                                    ]"
                                >
                                    <input
                                        type="checkbox"
                                        :value="perm.name"
                                        v-model="form.permissions"
                                        :disabled="isSuperAdmin"
                                        class="h-4 w-4 rounded border-slate-300 text-amber-500 focus:ring-amber-500/20 disabled:cursor-not-allowed transition-colors"
                                    />
                                    <div class="ml-3 min-w-0 flex-1">
                                        <span class="font-semibold block truncate">
                                            {{ formatName(perm.name) }}
                                        </span>
                                        <span class="text-[10px] text-slate-400 font-mono block truncate mt-0.5">
                                            {{ perm.name }}
                                        </span>
                                    </div>
                                    <span v-if="form.permissions.includes(perm.name)" class="text-amber-600 ml-1">
                                        <Check class="w-3.5 h-3.5" />
                                    </span>
                                </label>
                            </div>
                        </div>

                        <!-- Empty State when filtering or no permissions -->
                        <div
                            v-if="Object.keys(filteredGroupedPermissions).length === 0"
                            class="text-center py-10 text-slate-400 text-xs"
                        >
                            <KeyRound class="w-8 h-8 text-slate-300 mx-auto mb-2" />
                            <p class="font-medium text-slate-600">No permissions found</p>
                            <p v-if="permissionSearch" class="text-slate-400 mt-1">
                                No permissions matched "{{ permissionSearch }}".
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Form Action Buttons -->
                <div class="flex items-center justify-end space-x-3 pt-2">
                    <Link
                        :href="route('roles.index')"
                        class="px-5 py-2.5 rounded-xl border border-slate-300 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none transition-colors"
                    >
                        {{ isSuperAdmin ? 'Back to Roles' : 'Cancel' }}
                    </Link>
                    <button
                        v-if="!isSuperAdmin"
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center px-6 py-2.5 rounded-xl shadow-xs text-sm font-semibold text-slate-900 bg-amber-400 hover:bg-amber-500 active:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    >
                        <Save class="w-4 h-4 mr-2" />
                        <span>{{ form.processing ? 'Saving Changes...' : 'Update Role' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
