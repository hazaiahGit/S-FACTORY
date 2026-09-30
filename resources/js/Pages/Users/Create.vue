<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    ArrowLeft,
    UserPlus,
    User,
    Mail,
    Phone,
    Lock,
    Building2,
    Shield,
    KeyRound,
    Save,
    CheckSquare,
    Square
} from '@lucide/vue';

const props = defineProps({
    branches: {
        type: Array,
        default: () => [],
    },
    roles: {
        type: Array,
        default: () => [],
    },
    permissions: {
        type: [Object, Array],
        default: () => ({}),
    },
});

const form = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    branch_id: props.branches?.[0]?.id || '',
    role: props.roles?.[0]?.name || '',
    permissions: [],
    is_active: true,
});

// Normalize permissions into an object { [groupName]: [permissionObj, ...] }
const groupedPermissions = computed(() => {
    if (!props.permissions) return {};

    // If already a key-value object (from Laravel Collection groupBy)
    if (!Array.isArray(props.permissions)) {
        return props.permissions;
    }

    // If an array of permissions or array of groups
    const result = {};
    props.permissions.forEach((item) => {
        if (item.group_name) {
            if (!result[item.group_name]) result[item.group_name] = [];
            result[item.group_name].push(item);
        } else if (item.group && item.permissions) {
            result[item.group] = item.permissions;
        } else {
            if (!result['general']) result['general'] = [];
            result['general'].push(item);
        }
    });
    return result;
});

const formatName = (str) => {
    if (!str) return '';
    return str
        .replace(/[-_]/g, ' ')
        .replace(/\b\w/g, (c) => c.toUpperCase());
};

const isGroupAllSelected = (perms) => {
    if (!perms || perms.length === 0) return false;
    return perms.every((p) => form.permissions.includes(p.name));
};

const toggleGroupPermissions = (perms) => {
    if (!perms || perms.length === 0) return;
    if (isGroupAllSelected(perms)) {
        const namesToRemove = perms.map((p) => p.name);
        form.permissions = form.permissions.filter((name) => !namesToRemove.includes(name));
    } else {
        const namesToAdd = perms
            .map((p) => p.name)
            .filter((name) => !form.permissions.includes(name));
        form.permissions = [...form.permissions, ...namesToAdd];
    }
};

const selectAllPermissions = () => {
    const all = [];
    Object.values(groupedPermissions.value).forEach((groupList) => {
        groupList.forEach((p) => {
            if (p.name && !all.includes(p.name)) {
                all.push(p.name);
            }
        });
    });
    form.permissions = all;
};

const deselectAllPermissions = () => {
    form.permissions = [];
};

const submit = () => {
    form.post(route('users.store'));
};
</script>

<template>
    <AppLayout>
        <Head title="Create User" />

        <template #header>
            <div class="flex items-center text-lg sm:text-xl font-bold">
                <Link
                    :href="route('users.index')"
                    class="mr-3 text-slate-400 hover:text-slate-700 transition-colors p-1 rounded-lg hover:bg-slate-100"
                >
                    <ArrowLeft class="w-5 h-5 sm:w-6 sm:h-6" />
                </Link>
                <span>Create New User</span>
            </div>
        </template>

        <div class="max-w-5xl mx-auto space-y-6">
            <form @submit.prevent="submit" class="space-y-6">
                <!-- User Basic Details Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-5 sm:p-6 border-b border-slate-100 bg-slate-50/75 flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="h-9 w-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center mr-3">
                                <User class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
                                    Account Information
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5">Basic profile details and primary assignments</p>
                            </div>
                        </div>

                        <!-- Status Toggle -->
                        <div class="flex items-center space-x-2">
                            <label for="is_active" class="text-xs font-semibold text-slate-700 cursor-pointer">
                                Active Status
                            </label>
                            <input
                                id="is_active"
                                v-model="form.is_active"
                                type="checkbox"
                                class="h-4 w-4 rounded border-slate-300 text-amber-500 focus:ring-amber-500/20"
                            />
                        </div>
                    </div>

                    <div class="p-5 sm:p-6 space-y-5">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <!-- Full Name -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                    Full Name <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <User class="w-4 h-4" />
                                    </div>
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        placeholder="e.g. John Doe"
                                        class="block w-full pl-10 pr-3.5 py-2.5 bg-white border border-slate-300 rounded-xl shadow-sm text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-colors"
                                        required
                                    />
                                </div>
                                <p v-if="form.errors.name" class="mt-1.5 text-xs text-rose-600 font-medium">
                                    {{ form.errors.name }}
                                </p>
                            </div>

                            <!-- Email Address -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                    Email Address <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <Mail class="w-4 h-4" />
                                    </div>
                                    <input
                                        v-model="form.email"
                                        type="email"
                                        placeholder="e.g. john@sfactory.com"
                                        class="block w-full pl-10 pr-3.5 py-2.5 bg-white border border-slate-300 rounded-xl shadow-sm text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-colors"
                                        required
                                    />
                                </div>
                                <p v-if="form.errors.email" class="mt-1.5 text-xs text-rose-600 font-medium">
                                    {{ form.errors.email }}
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                            <!-- Phone -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                    Phone Number
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <Phone class="w-4 h-4" />
                                    </div>
                                    <input
                                        v-model="form.phone"
                                        type="tel"
                                        placeholder="e.g. +255 700 000 000"
                                        class="block w-full pl-10 pr-3.5 py-2.5 bg-white border border-slate-300 rounded-xl shadow-sm text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-colors"
                                    />
                                </div>
                                <p v-if="form.errors.phone" class="mt-1.5 text-xs text-rose-600 font-medium">
                                    {{ form.errors.phone }}
                                </p>
                            </div>

                            <!-- Branch Select -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                    Assigned Branch <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <Building2 class="w-4 h-4" />
                                    </div>
                                    <select
                                        v-model="form.branch_id"
                                        class="block w-full pl-10 pr-8 py-2.5 bg-white border border-slate-300 rounded-xl shadow-sm text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-colors"
                                        required
                                    >
                                        <option value="" disabled>Select Branch</option>
                                        <option v-for="branch in branches" :key="branch.id" :value="branch.id">
                                            {{ branch.name }}
                                        </option>
                                    </select>
                                </div>
                                <p v-if="form.errors.branch_id" class="mt-1.5 text-xs text-rose-600 font-medium">
                                    {{ form.errors.branch_id }}
                                </p>
                            </div>

                            <!-- Role Select -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                    Role <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <Shield class="w-4 h-4" />
                                    </div>
                                    <select
                                        v-model="form.role"
                                        class="block w-full pl-10 pr-8 py-2.5 bg-white border border-slate-300 rounded-xl shadow-sm text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-colors"
                                        required
                                    >
                                        <option value="" disabled>Select Role</option>
                                        <option v-for="role in roles" :key="role.id" :value="role.name">
                                            {{ role.name }}
                                        </option>
                                    </select>
                                </div>
                                <p v-if="form.errors.role" class="mt-1.5 text-xs text-rose-600 font-medium">
                                    {{ form.errors.role }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Password & Credentials Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-5 sm:p-6 border-b border-slate-100 bg-slate-50/75 flex items-center">
                        <div class="h-9 w-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center mr-3">
                            <Lock class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
                                Security & Password
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">Define login authentication credentials</p>
                        </div>
                    </div>

                    <div class="p-5 sm:p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <!-- Password -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                    Password <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <Lock class="w-4 h-4" />
                                    </div>
                                    <input
                                        v-model="form.password"
                                        type="password"
                                        placeholder="••••••••"
                                        class="block w-full pl-10 pr-3.5 py-2.5 bg-white border border-slate-300 rounded-xl shadow-sm text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-colors"
                                        required
                                    />
                                </div>
                                <p v-if="form.errors.password" class="mt-1.5 text-xs text-rose-600 font-medium">
                                    {{ form.errors.password }}
                                </p>
                            </div>

                            <!-- Password Confirmation -->
                            <div>
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                    Confirm Password <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <KeyRound class="w-4 h-4" />
                                    </div>
                                    <input
                                        v-model="form.password_confirmation"
                                        type="password"
                                        placeholder="••••••••"
                                        class="block w-full pl-10 pr-3.5 py-2.5 bg-white border border-slate-300 rounded-xl shadow-sm text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-colors"
                                        required
                                    />
                                </div>
                                <p v-if="form.errors.password_confirmation" class="mt-1.5 text-xs text-rose-600 font-medium">
                                    {{ form.errors.password_confirmation }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Custom Direct Permissions Card -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-5 sm:p-6 border-b border-slate-100 bg-slate-50/75 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div class="flex items-center">
                            <div class="h-9 w-9 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center mr-3">
                                <KeyRound class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
                                    Direct Permissions
                                </h3>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    Grant specific individual permissions in addition to role capabilities
                                </p>
                            </div>
                        </div>

                        <!-- Global Permission Actions -->
                        <div class="flex items-center gap-2 self-start sm:self-center">
                            <button
                                type="button"
                                @click="selectAllPermissions"
                                class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors"
                            >
                                <CheckSquare class="w-3.5 h-3.5 mr-1 text-slate-500" />
                                Select All
                            </button>
                            <button
                                type="button"
                                @click="deselectAllPermissions"
                                class="inline-flex items-center px-3 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors"
                            >
                                <Square class="w-3.5 h-3.5 mr-1 text-slate-500" />
                                Clear All
                            </button>
                        </div>
                    </div>

                    <div class="p-5 sm:p-6 space-y-6">
                        <p v-if="form.errors.permissions" class="text-xs text-rose-600 font-medium mb-3">
                            {{ form.errors.permissions }}
                        </p>

                        <!-- Grouped Permissions -->
                        <div
                            v-for="(groupPerms, groupName) in groupedPermissions"
                            :key="groupName"
                            class="border border-slate-200 rounded-xl p-4 bg-slate-50/50"
                        >
                            <!-- Group Header -->
                            <div class="flex items-center justify-between pb-3 border-b border-slate-200/80 mb-3">
                                <div class="flex items-center space-x-2">
                                    <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                                    <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider">
                                        {{ formatName(groupName) }}
                                    </h4>
                                    <span class="text-[11px] font-medium text-slate-400">
                                        ({{ groupPerms.filter(p => form.permissions.includes(p.name)).length }}/{{ groupPerms.length }})
                                    </span>
                                </div>
                                <button
                                    type="button"
                                    @click="toggleGroupPermissions(groupPerms)"
                                    class="text-xs font-semibold text-amber-600 hover:text-amber-700 transition-colors"
                                >
                                    {{ isGroupAllSelected(groupPerms) ? 'Deselect Group' : 'Select Group' }}
                                </button>
                            </div>

                            <!-- Permission Checkboxes Grid -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                                <label
                                    v-for="perm in groupPerms"
                                    :key="perm.id || perm.name"
                                    :class="[
                                        'flex items-start p-2.5 rounded-lg border text-xs cursor-pointer transition-all',
                                        form.permissions.includes(perm.name)
                                            ? 'bg-amber-50/80 border-amber-300 text-slate-900 shadow-xs'
                                            : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'
                                    ]"
                                >
                                    <input
                                        type="checkbox"
                                        :value="perm.name"
                                        v-model="form.permissions"
                                        class="mt-0.5 h-4 w-4 rounded border-slate-300 text-amber-500 focus:ring-amber-500/20"
                                    />
                                    <span class="ml-2.5 font-medium leading-relaxed select-none">
                                        {{ formatName(perm.name) }}
                                    </span>
                                </label>
                            </div>
                        </div>

                        <div
                            v-if="Object.keys(groupedPermissions).length === 0"
                            class="text-center py-8 text-slate-400 text-xs"
                        >
                            No permissions available to configure.
                        </div>
                    </div>
                </div>

                <!-- Form Action Buttons -->
                <div class="flex items-center justify-end space-x-3 pt-2">
                    <Link
                        :href="route('users.index')"
                        class="px-5 py-2.5 rounded-xl border border-slate-300 bg-white text-sm font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none transition-colors"
                    >
                        Cancel
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center px-6 py-2.5 rounded-xl shadow-sm text-sm font-semibold text-slate-900 bg-amber-400 hover:bg-amber-500 active:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    >
                        <Save class="w-4 h-4 mr-2" />
                        <span>{{ form.processing ? 'Creating User...' : 'Create User' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>


