<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import {
    Users,
    UserPlus,
    Search,
    Pencil,
    Shield,
    Building2,
    CheckCircle2,
    XCircle,
    Mail,
    Phone,
    X
} from '@lucide/vue';

const props = defineProps({
    users: {
        type: Object,
        required: true,
    },
    filters: {
        type: Object,
        default: () => ({ search: '' }),
    },
});

const search = ref(props.filters?.search || '');

const debounce = (fn, delay) => {
    let timeoutId;
    return (...args) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn(...args), delay);
    };
};

const performSearch = debounce(() => {
    router.get(
        route('users.index'),
        { search: search.value },
        { preserveState: true, preserveScroll: true, replace: true }
    );
}, 300);

watch(search, performSearch);

const clearSearch = () => {
    search.value = '';
    performSearch();
};

const getInitials = (name) => {
    if (!name) return 'U';
    const parts = name.trim().split(/\s+/);
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
};

const getRoleBadgeClass = (roleName) => {
    if (!roleName) return 'bg-slate-100 text-slate-700 border-slate-200';
    const lower = roleName.toLowerCase();
    if (lower.includes('super') || lower.includes('admin')) {
        return 'bg-purple-50 text-purple-700 border-purple-200';
    }
    if (lower.includes('manager')) {
        return 'bg-blue-50 text-blue-700 border-blue-200';
    }
    if (lower.includes('cashier')) {
        return 'bg-amber-50 text-amber-700 border-amber-200';
    }
    if (lower.includes('supervisor')) {
        return 'bg-emerald-50 text-emerald-700 border-emerald-200';
    }
    return 'bg-slate-100 text-slate-700 border-slate-200';
};
</script>

<template>
    <AppLayout>
        <Head title="Users Management" />

        <template #header>
            <div class="flex items-center text-lg sm:text-xl font-bold">
                <Users class="w-6 h-6 mr-3 text-amber-500" />
                <span>Users Management</span>
            </div>
        </template>

        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Header Actions & Search Bar -->
            <div class="flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-4">
                <div class="relative w-full max-w-md">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <Search class="h-4 w-4" />
                    </div>
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search by name, email, or phone..."
                        class="block w-full pl-10 pr-9 py-2.5 bg-white border border-slate-200 rounded-xl shadow-sm text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 transition-colors"
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

                <div class="flex items-center gap-3">
                    <Link
                        :href="route('roles.index')"
                        class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl shadow-sm text-sm font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500 transition-colors w-full sm:w-auto"
                    >
                        <Shield class="w-4 h-4 mr-2 text-indigo-500" />
                        <span>Manage Roles</span>
                    </Link>
                    <Link
                        :href="route('users.create')"
                        class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl shadow-sm text-sm font-semibold text-slate-900 bg-amber-400 hover:bg-amber-500 active:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition-colors w-full sm:w-auto"
                    >
                        <UserPlus class="w-4 h-4 mr-2" />
                        <span>Add User</span>
                    </Link>
                </div>
            </div>

            <!-- Cards Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-4 sm:gap-6">
                    <div
                        v-for="user in users.data"
                        :key="user.id"
                        class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden hover:shadow-md transition-all flex flex-col group relative"
                    >
                        <!-- Status Badge -->
                        <div class="absolute top-4 right-4 z-10">
                            <span
                                :class="[
                                    'inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider shadow-sm',
                                    user.is_active
                                        ? 'bg-emerald-100 text-emerald-700 border border-emerald-200'
                                        : 'bg-rose-100 text-rose-700 border border-rose-200'
                                ]"
                            >
                                <span
                                    :class="[
                                        'w-1.5 h-1.5 rounded-full mr-1.5',
                                        user.is_active ? 'bg-emerald-500' : 'bg-rose-500'
                                    ]"
                                ></span>
                                {{ user.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        <!-- Card Header: Initials, Name & Role -->
                        <div class="p-5 border-b border-slate-100 bg-slate-50/50 relative">
                            <div class="flex items-start">
                                <div class="h-12 w-12 rounded-xl bg-amber-100 border border-amber-200 text-amber-800 font-bold text-lg flex items-center justify-center shrink-0 shadow-sm mr-4 relative overflow-hidden">
                                    <div class="absolute inset-0 bg-gradient-to-br from-white/40 to-transparent"></div>
                                    <span class="relative z-10">{{ getInitials(user.name) }}</span>
                                </div>
                                <div class="flex-1 min-w-0 pr-20">
                                    <h3 class="text-base font-bold text-slate-900 truncate group-hover:text-amber-600 transition-colors">
                                        {{ user.name }}
                                    </h3>
                                    <div class="mt-1.5 flex flex-wrap gap-1.5">
                                        <template v-if="user.roles && user.roles.length > 0">
                                            <span
                                                v-for="role in user.roles"
                                                :key="role.id"
                                                :class="[
                                                    'inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider shadow-sm',
                                                    getRoleBadgeClass(role.name)
                                                ]"
                                            >
                                                {{ role.name }}
                                            </span>
                                        </template>
                                        <span v-else class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-slate-200 text-slate-600 shadow-sm">
                                            No Role
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card Body: Email, Phone, Branch -->
                        <div class="p-5 space-y-4 flex-1">
                            <!-- Contact Info -->
                            <div class="space-y-2.5">
                                <div class="flex items-center text-sm text-slate-600 group/item">
                                    <div class="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center mr-3 shrink-0 group-hover/item:bg-blue-100 transition-colors">
                                        <Mail class="w-3.5 h-3.5 text-slate-500 group-hover/item:text-blue-600" />
                                    </div>
                                    <span class="truncate font-medium">{{ user.email }}</span>
                                </div>
                                <div class="flex items-center text-sm text-slate-600 group/item">
                                    <div class="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center mr-3 shrink-0 group-hover/item:bg-emerald-100 transition-colors">
                                        <Phone class="w-3.5 h-3.5 text-slate-500 group-hover/item:text-emerald-600" />
                                    </div>
                                    <span class="truncate font-medium">{{ user.phone || 'No phone set' }}</span>
                                </div>
                            </div>

                            <!-- Branch Info -->
                            <div class="pt-4 border-t border-slate-100">
                                <div class="flex items-center text-sm">
                                    <Building2 class="w-4 h-4 mr-2 text-indigo-400 shrink-0" />
                                    <span class="font-bold text-slate-700 truncate">
                                        {{ user.branch?.name || user.primary_branch?.name || 'All Branches' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Card Footer: Actions -->
                        <div class="p-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-2">
                            <Link v-if="$can('manage users')" :href="route('users.edit', user.id)"
                                class="inline-flex flex-1 justify-center items-center px-3 py-2 text-sm font-bold rounded-xl text-amber-700 bg-amber-100 hover:bg-amber-200 transition-colors shadow-sm"
                            >
                                <Pencil class="w-4 h-4 mr-2" />
                                Edit User
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="users.data.length === 0" class="flex flex-col items-center justify-center p-12 text-center bg-white rounded-2xl shadow-sm border border-slate-200">
                    <div class="h-16 w-16 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                        <Users class="h-8 w-8 text-slate-400" />
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-1">No users found</h3>
                    <p class="text-slate-500 text-sm max-w-sm mb-6">
                        {{ filters.search ? 'We couldn\'t find any users matching your search criteria.' : 'Get started by creating your first user.' }}
                    </p>
                    <Link
                        v-if="!filters.search"
                        :href="route('users.create')"
                        class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-bold rounded-lg text-slate-900 bg-amber-400 hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 shadow-sm transition-colors"
                    >
                        <UserPlus class="w-4 h-4 mr-2" />
                        Add User
                    </Link>
                    <Link
                        v-else
                        :href="route('users.index')"
                        class="text-sm font-medium text-amber-600 hover:text-amber-700 hover:underline"
                    >
                        Clear search filters
                    </Link>
                </div>

                <!-- Pagination Footer -->
                <div
                    v-if="users.links && users.links.length > 3"
                    class="px-6 py-4 border-t border-slate-100 bg-slate-50/75 flex flex-col sm:flex-row items-center justify-between gap-4"
                >
                    <div class="text-xs text-slate-500 font-medium">
                        Showing
                        <span class="font-bold text-slate-700">{{ users.from || 0 }}</span>
                        to
                        <span class="font-bold text-slate-700">{{ users.to || 0 }}</span>
                        of
                        <span class="font-bold text-slate-700">{{ users.total || 0 }}</span>
                        results
                    </div>

                    <div class="flex items-center space-x-1">
                        <template v-for="(link, index) in users.links" :key="index">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                v-html="link.label"
                                :class="[
                                    'px-3 py-1.5 text-xs font-medium rounded-lg border transition-colors',
                                    link.active
                                        ? 'bg-amber-500 text-white border-amber-500 font-bold shadow-sm'
                                        : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-100'
                                ]"
                            />
                            <span
                                v-else
                                v-html="link.label"
                                class="px-3 py-1.5 text-xs font-medium rounded-lg border border-slate-200 text-slate-400 bg-slate-100/70 cursor-not-allowed"
                            />
                        </template>
                                        </div>
                </div>
        </div>
    </AppLayout>
</template>












