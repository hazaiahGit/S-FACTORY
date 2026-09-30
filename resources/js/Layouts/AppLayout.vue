<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import {
    UserCog, LayoutDashboard, Package, ShoppingCart, Users, Settings,
    Menu, Bell, LogOut, Factory, ListTree, ArrowRightLeft, Wallet,
    Receipt, ClipboardList, ChevronDown, ChevronRight, Target, AlertTriangle,
    Building2, PackageOpen, ServerCrash, FolderTree, Scale, Boxes, Tags
} from '@lucide/vue';
import { Menu as HeadlessMenu, MenuButton, MenuItems, MenuItem, Popover, PopoverButton, PopoverPanel } from '@headlessui/vue';

const page = usePage();
const user = page.props.auth.user;
const business = page.props.auth.business;
const branch = page.props.auth.branch;

// On mobile (< 1024px), sidebar should be closed by default. On desktop (>= 1024px), open by default.
const isClient = typeof window !== 'undefined';
const sidebarOpen = ref(isClient ? window.innerWidth >= 1024 : false);

const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value;
};

// Automatically keep sidebar closed on mobile after navigating
let removeNavListener = null;
onMounted(() => {
    if (typeof window !== 'undefined' && window.innerWidth < 1024) {
        sidebarOpen.value = false;
    }

    removeNavListener = router.on('navigate', () => {
        if (typeof window !== 'undefined' && window.innerWidth < 1024) {
            sidebarOpen.value = false;
        }
    });
});

onUnmounted(() => {
    if (removeNavListener) {
        removeNavListener();
    }
});

const notifications = computed(() => page.props.auth.recent_notifications ?? []);
const unreadCount = computed(() => page.props.auth.notifications_unread_count ?? 0);

const markAllRead = () => {
    router.post(route('notifications.read-all'), {}, { preserveScroll: true });
};

const handleNotificationClick = (notification) => {
    if (!notification.read) {
        router.post(route('notifications.read', notification.id));
    } else if (notification.action_url) {
        router.visit(notification.action_url);
    }
};

const logout = () => {
    router.post(route('logout'));
};

const userPermissions = computed(() => page.props.auth.permissions ?? []);
const userRoles = computed(() => page.props.auth.roles ?? []);
const isSuperAdmin = computed(() => userRoles.value.includes('Super Admin'));

const hasPermission = (permission) => {
    if (isSuperAdmin.value) return true;
    if (!permission) return true;
    return userPermissions.value.includes(permission);
};

// Navigation groups definition
const navGroups = [
    {
        type: 'single',
        name: 'Dashboard',
        route: 'dashboard',
        icon: LayoutDashboard,
        permission: null,
    },
    {
        type: 'single',
        name: 'POS / Sales',
        route: 'sales.index',
        icon: ShoppingCart,
        permission: 'view sales',
    },
    {
        type: 'single',
        name: 'Purchases',
        route: 'purchases.index',
        icon: Receipt,
        permission: 'view purchases',
    },
    {
        type: 'group',
        name: 'Inventory',
        icon: Package,
        permission: 'view inventory',
        children: [
            { name: 'Stock', route: 'inventory.index', icon: Package, permission: 'view inventory' },
            { name: 'Transfers', route: 'stock-transfers.index', icon: ArrowRightLeft, permission: 'view inventory' },
            { name: 'Recipes / BOM', route: 'bom.index', icon: ListTree, permission: 'view recipes' },
        ],
    },
    {
        type: 'group',
        name: 'Products',
        icon: Boxes,
        permission: 'view products',
        children: [
            { name: 'Products', route: 'products.index', icon: Package, permission: 'view products' },
            { name: 'Categories', route: 'categories.index', icon: FolderTree, permission: 'view products' },
            { name: 'Units of Measure', route: 'units.index', icon: Scale, permission: 'view products' },
            { name: 'Product Types', route: 'product-types.index', icon: Boxes, permission: 'view products' },
        ],
    },
    {
        type: 'group',
        name: 'Customers & Suppliers',
        icon: Users,
        permission: 'view sales',
        children: [
            { name: 'Customers', route: 'customers.index', icon: Users, permission: 'view sales' },
            { name: 'Suppliers', route: 'suppliers.index', icon: Users, permission: 'view purchases' },
        ],
    },
    {
        type: 'group',
        name: 'Expenses',
        icon: Wallet,
        permission: 'view profit',
        children: [
            { name: 'Expenses', route: 'expenses.index', icon: Wallet, permission: 'view profit' },
            { name: 'Expense Categories', route: 'expense-categories.index', icon: Tags, permission: 'view profit' },
        ],
    },
    {
        type: 'group',
        name: 'Reports & Goals',
        icon: ClipboardList,
        permission: 'view report',
        children: [
            { name: 'Reports', route: 'reports.index', icon: ClipboardList, permission: 'view report' },
            { name: 'Targets & Goals', route: 'targets.index', icon: Target, permission: 'view report' },
        ],
    },
    {
        type: 'group',
        name: 'Administration',
        icon: UserCog,
        permission: 'manage users',
        children: [
            { name: 'User Management', route: 'users.index', icon: UserCog, permission: 'manage users' },
            { name: 'Branches', route: 'branches.index', icon: Building2, permission: 'manage users' },
        ],
    },
];

// Track which groups are open
const openGroups = ref({});

const isGroupActive = (group) => {
    return group.children?.some(child => route().current(child.route) || route().current(child.route.split('.')[0] + '.*'));
};

const toggleGroup = (groupName) => {
    openGroups.value[groupName] = !openGroups.value[groupName];
};

const isGroupOpen = (group) => {
    // Auto-open if a child route is active
    if (isGroupActive(group)) return true;
    return openGroups.value[group.name] ?? false;
};

const visibleNavGroups = computed(() => {
    if (user?.is_system_admin && !user?.business_id) return [];
    return navGroups
        .map(group => {
            if (group.type === 'single') {
                return hasPermission(group.permission) ? group : null;
            }
            // Group: filter children
            const visibleChildren = group.children.filter(c => hasPermission(c.permission));
            if (visibleChildren.length === 0) return null;
            if (visibleChildren.length === 1) {
                // Flatten single-child groups into a single nav item
                return { type: 'single', ...visibleChildren[0] };
            }
            return { ...group, children: visibleChildren };
        })
        .filter(Boolean);
});
</script>

<template>
    <div class="h-screen bg-slate-50 flex overflow-hidden">
        <!-- Mobile Sidebar Backdrop -->
        <div v-if="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 z-40 bg-slate-900/80 backdrop-blur-sm lg:hidden transition-opacity"></div>

        <!-- Sidebar -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-50 bg-slate-900 text-slate-300 transition-transform duration-300 ease-in-out flex flex-col h-full lg:static lg:translate-x-0',
                sidebarOpen ? 'w-64 translate-x-0' : 'w-64 -translate-x-full lg:w-20'
            ]"
        >
            <!-- Logo area -->
            <div class="h-16 flex items-center justify-center bg-slate-950 border-b border-slate-800 px-4">
                <Factory class="h-8 w-8 text-amber-500 shrink-0" />
                <span :class="['ml-3 font-bold text-lg text-white truncate uppercase tracking-wider', !sidebarOpen ? 'lg:hidden' : '']">
                    S-FACTORY
                </span>
            </div>

            <!-- Business & Branch Info -->
            <div :class="['p-4 border-b border-slate-800 bg-slate-800/50', !sidebarOpen ? 'lg:hidden' : '']">
                <div class="text-sm font-semibold text-white truncate">{{ business?.name || (user?.is_system_admin ? 'S-Factory Platform' : 'My Business') }}</div>
                <div class="text-xs text-slate-400 truncate mt-1 flex items-center">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 mr-2"></span>
                    {{ branch?.name || (user?.is_system_admin ? 'Platform Admin' : 'Main Branch') }}
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-0.5 sidebar-scroll">
                <template v-for="item in visibleNavGroups" :key="item.name">
                    <!-- Single nav item -->
                    <Link
                        v-if="item.type === 'single'"
                        :href="route(item.route)"
                        @click="typeof window !== 'undefined' && window.innerWidth < 1024 ? sidebarOpen = false : null"
                        :class="[
                            route().current(item.route) || route().current(item.route.split('.')[0] + '.*')
                                ? 'bg-amber-500/10 text-amber-400'
                                : 'text-slate-400 hover:bg-slate-800 hover:text-white',
                            'group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors'
                        ]"
                        :title="!sidebarOpen ? item.name : ''"
                    >
                        <component
                            :is="item.icon"
                            :class="[
                                route().current(item.route) || route().current(item.route.split('.')[0] + '.*') ? 'text-amber-400' : 'text-slate-400 group-hover:text-white',
                                'flex-shrink-0 h-5 w-5'
                            ]"
                            aria-hidden="true"
                        />
                        <span :class="['ml-3 truncate', !sidebarOpen ? 'lg:hidden' : '']">{{ item.name }}</span>
                    </Link>

                    <!-- Group with dropdown -->
                    <div v-else-if="item.type === 'group'">
                        <!-- Group header button -->
                        <button
                            @click="toggleGroup(item.name)"
                            :title="!sidebarOpen ? item.name : ''"
                            :class="[
                                isGroupActive(item) ? 'text-amber-400' : 'text-slate-400 hover:bg-slate-800 hover:text-white',
                                'w-full group flex items-center justify-between px-3 py-2.5 text-sm font-medium rounded-lg transition-colors'
                            ]"
                        >
                            <div class="flex items-center">
                                <component
                                    :is="item.icon"
                                    :class="[
                                        isGroupActive(item) ? 'text-amber-400' : 'text-slate-400 group-hover:text-white',
                                        'flex-shrink-0 h-5 w-5'
                                    ]"
                                    aria-hidden="true"
                                />
                                <span :class="['ml-3 truncate', !sidebarOpen ? 'lg:hidden' : '']">{{ item.name }}</span>
                            </div>
                            <ChevronDown
                                v-if="sidebarOpen"
                                :class="[
                                    'h-4 w-4 flex-shrink-0 transition-transform duration-200',
                                    isGroupOpen(item) ? 'rotate-180 text-amber-400' : 'text-slate-500'
                                ]"
                            />
                        </button>

                        <!-- Children (collapsible) -->
                        <div
                            v-show="isGroupOpen(item)"
                            :class="['mt-0.5 space-y-0.5 overflow-hidden transition-all', sidebarOpen ? 'pl-4' : '']"
                        >
                            <Link
                                v-for="child in item.children"
                                :key="child.name"
                                :href="route(child.route)"
                                @click="typeof window !== 'undefined' && window.innerWidth < 1024 ? sidebarOpen = false : null"
                                :class="[
                                    route().current(child.route) || route().current(child.route.split('.')[0] + '.*')
                                        ? 'bg-amber-500/10 text-amber-400 border-l-2 border-amber-400'
                                        : 'text-slate-400 hover:bg-slate-800/70 hover:text-white border-l-2 border-transparent',
                                    'group flex items-center px-3 py-2 text-sm rounded-lg transition-colors'
                                ]"
                                :title="!sidebarOpen ? child.name : ''"
                            >
                                <component
                                    :is="child.icon"
                                    :class="[
                                        route().current(child.route) || route().current(child.route.split('.')[0] + '.*') ? 'text-amber-400' : 'text-slate-500 group-hover:text-slate-300',
                                        'flex-shrink-0 h-4 w-4'
                                    ]"
                                    aria-hidden="true"
                                />
                                <span :class="['ml-2.5 truncate', !sidebarOpen ? 'lg:hidden' : '']">{{ child.name }}</span>
                            </Link>
                        </div>
                    </div>
                </template>
            </nav>


                        <!-- System Management (System Admins Only) -->
            <div v-if="$page.props.auth.user && $page.props.auth.user.is_system_admin" class="px-3 mt-4 mb-2 space-y-1">
                <div :class="['text-xs font-bold text-slate-500 uppercase tracking-wider pl-3 mb-2', !sidebarOpen ? 'lg:hidden' : '']">
                    System Admin
                </div>
                <Link
                    :href="route('system.tenants.index')"
                    @click="window?.innerWidth < 1024 ? sidebarOpen = false : null"
                    :class="[
                        route().current('system.tenants.*')
                            ? 'bg-rose-500/10 text-rose-500'
                            : 'hover:bg-slate-800 hover:text-white',
                        'group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors text-slate-400'
                    ]"
                    :title="!sidebarOpen ? 'Tenants & Admins' : ''"
                >
                    <Building2 :class="[
                            route().current('system.tenants.*') ? 'text-rose-500' : 'text-slate-400 group-hover:text-white',
                            'flex-shrink-0 h-5 w-5'
                        ]" />
                    <span :class="['ml-3', !sidebarOpen ? 'lg:hidden' : '']">Tenants & Admins</span>
                </Link>
                <Link
                    :href="route('system.packages.index')"
                    @click="window?.innerWidth < 1024 ? sidebarOpen = false : null"
                    :class="[
                        route().current('system.packages.*')
                            ? 'bg-rose-500/10 text-rose-500'
                            : 'hover:bg-slate-800 hover:text-white',
                        'group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors text-slate-400'
                    ]"
                    :title="!sidebarOpen ? 'Subscription Packages' : ''"
                >
                    <PackageOpen :class="[
                            route().current('system.packages.*') ? 'text-rose-500' : 'text-slate-400 group-hover:text-white',
                            'flex-shrink-0 h-5 w-5'
                        ]" />
                    <span :class="['ml-3', !sidebarOpen ? 'lg:hidden' : '']">Subscription Packages</span>
                </Link>
            </div>

            <!-- Bottom settings -->
            <div class="p-3 border-t border-slate-800">
                <Link
                    :href="route('settings.index')"
                    @click="window?.innerWidth < 1024 ? sidebarOpen = false : null"
                    class="group flex items-center px-3 py-2 text-sm font-medium rounded-md hover:bg-slate-800 hover:text-white transition-colors text-slate-400"
                    :title="!sidebarOpen ? 'Settings' : ''"
                >
                    <Settings class="h-5 w-5 shrink-0 group-hover:text-white" />
                    <span :class="['ml-3', !sidebarOpen ? 'lg:hidden' : '']">Settings</span>
                </Link>
            </div>
        </aside>

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden lg:pl-0">
            <!-- Header -->
            <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-4 sm:px-6 shadow-sm z-10 sticky top-0">
                <div class="flex items-center">
                    <button
                        @click="toggleSidebar"
                        class="text-slate-500 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-amber-500 rounded-md p-1"
                    >
                        <Menu class="h-6 w-6" />
                    </button>

                    <h1 class="ml-4 text-xl font-semibold text-slate-800 hidden sm:block">
                        <slot name="header"></slot>
                    </h1>
                </div>

                <div class="flex items-center space-x-2 sm:space-x-4">
                    <!-- Branch Filter (Admins Only - Mobile Friendly!) -->
                    <div
                        v-if="$page.props.auth.all_branches && $page.props.auth.all_branches.length > 0"
                        class="flex items-center bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-lg px-2 py-1 relative shadow-sm transition-colors"
                        title="Filter data by branch"
                    >
                        <Building2 class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-amber-500 mr-1 sm:mr-1.5 shrink-0" />
                        <select 
                            @change="e => { router.post(route('active-branch.update'), { branch_id: e.target.value || null }, { preserveScroll: true }) }"
                            :value="$page.props.auth.active_branch_id || ''"
                            class="text-xs sm:text-sm bg-transparent border-none focus:ring-0 text-slate-800 font-bold py-0.5 sm:py-1 pr-6 sm:pr-8 pl-0 cursor-pointer w-24 sm:w-44 truncate"
                        >
                            <option value="">🏢 All Branches</option>
                            <option v-for="branch in $page.props.auth.all_branches" :key="branch.id" :value="branch.id">
                                📍 {{ branch.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Fixed Branch Badge (Non-Admins Only - Cannot Change Branch) -->
                    <div
                        v-else-if="$page.props.auth.branch"
                        class="flex items-center bg-slate-100 border border-slate-200 rounded-lg px-2 sm:px-2.5 py-1 text-xs font-bold text-slate-700 shadow-sm"
                        title="Your assigned branch"
                    >
                        <Building2 class="w-3.5 h-3.5 text-slate-400 mr-1.5 shrink-0" />
                        <span class="truncate max-w-[100px] sm:max-w-[160px]">📍 {{ $page.props.auth.branch.name }}</span>
                    </div>

                    <!-- Notifications -->
                    <Popover class="relative">
                        <PopoverButton class="relative p-1 text-slate-400 hover:text-slate-600 transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 rounded-full">
                            <Bell class="h-6 w-6" />
                            <span v-if="unreadCount > 0" class="absolute top-1 right-1 flex h-3 w-3 items-center justify-center rounded-full bg-red-500 ring-2 ring-white text-[8px] font-bold text-white">
                                {{ unreadCount }}
                            </span>
                        </PopoverButton>

                        <transition
                            enter-active-class="transition ease-out duration-200"
                            enter-from-class="opacity-0 translate-y-1"
                            enter-to-class="opacity-100 translate-y-0"
                            leave-active-class="transition ease-in duration-150"
                            leave-from-class="opacity-100 translate-y-0"
                            leave-to-class="opacity-0 translate-y-1"
                        >
                            <PopoverPanel class="absolute right-0 z-50 mt-2 w-80 md:w-96 origin-top-right rounded-xl bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none overflow-hidden">
                                <div class="px-4 py-3 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-sm font-bold text-slate-800">Notifications</h3>
                                        <span v-if="unreadCount > 0" class="px-2 py-0.5 text-[10px] font-black rounded-full bg-amber-500 text-white">{{ unreadCount }}</span>
                                    </div>
                                    <button @click="markAllRead" v-if="unreadCount > 0" class="text-xs text-amber-600 hover:text-amber-800 font-bold transition-colors">Mark all read</button>
                                </div>
                                <div class="max-h-96 overflow-y-auto">
                                    <div 
                                        v-for="notification in notifications" 
                                        :key="notification.id" 
                                        @click="handleNotificationClick(notification)"
                                        :class="['p-4 border-b border-slate-50 last:border-0 hover:bg-slate-50 transition-colors cursor-pointer', !notification.read ? 'bg-amber-50/30' : '']"
                                    >
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0 mt-0.5">
                                                <div v-if="notification.type === 'alert'" class="h-8 w-8 rounded-full bg-rose-100 flex items-center justify-center">
                                                    <AlertTriangle class="h-4 w-4 text-rose-600" />
                                                </div>
                                                <div v-else-if="notification.type === 'warning'" class="h-8 w-8 rounded-full bg-amber-100 flex items-center justify-center">
                                                    <AlertTriangle class="h-4 w-4 text-amber-600" />
                                                </div>
                                                <div v-else-if="notification.type === 'success'" class="h-8 w-8 rounded-full bg-emerald-100 flex items-center justify-center">
                                                    <Target class="h-4 w-4 text-emerald-600" />
                                                </div>
                                                <div v-else class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                                                    <Bell class="h-4 w-4 text-blue-600" />
                                                </div>
                                            </div>
                                            <div class="ml-3 flex-1 min-w-0">
                                                <div class="flex items-center gap-1.5 flex-wrap">
                                                    <span v-if="notification.branch_name" class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                                        📍 {{ notification.branch_name }}
                                                    </span>
                                                    <p :class="['text-xs font-bold truncate', !notification.read ? 'text-slate-900' : 'text-slate-700']">
                                                        {{ notification.title }}
                                                    </p>
                                                </div>
                                                <p class="text-xs text-slate-500 mt-0.5 line-clamp-2">
                                                    {{ notification.message }}
                                                </p>
                                                <p class="text-[10px] text-slate-400 mt-1 font-medium">
                                                    {{ notification.time }}
                                                </p>
                                            </div>
                                            <div v-if="!notification.read" class="flex-shrink-0 ml-2 mt-1">
                                                <div class="h-2 w-2 bg-amber-500 rounded-full"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-if="notifications.length === 0" class="p-8 text-center">
                                        <Bell class="h-8 w-8 text-slate-300 mx-auto mb-2" />
                                        <p class="text-sm font-bold text-slate-700">No notifications</p>
                                        <p class="text-xs text-slate-400 mt-0.5">You're all caught up!</p>
                                    </div>
                                </div>
                                <div class="px-4 py-2.5 border-t border-slate-100 bg-slate-50 text-center">
                                    <Link :href="route('notifications.index')" class="text-xs font-bold text-slate-600 hover:text-amber-600 transition-colors">
                                        View all notifications &rarr;
                                    </Link>
                                </div>
                            </PopoverPanel>
                        </transition>
                    </Popover>

                    <!-- Profile dropdown -->
                    <HeadlessMenu as="div" class="relative">
                        <MenuButton class="flex items-center max-w-xs text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 p-1 hover:bg-slate-100 transition-colors">
                            <div class="h-8 w-8 rounded-full bg-amber-100 flex items-center justify-center text-amber-700 font-bold border border-amber-200 shadow-sm">
                                {{ user?.name?.charAt(0) || 'U' }}
                            </div>
                            <span class="ml-2 hidden md:block text-slate-700 font-medium">{{ user?.name }}</span>
                            <ChevronDown class="ml-1 h-4 w-4 text-slate-400 hidden md:block" />
                        </MenuButton>
                        <transition
                            enter-active-class="transition ease-out duration-100"
                            enter-from-class="transform opacity-0 scale-95"
                            enter-to-class="transform opacity-100 scale-100"
                            leave-active-class="transition ease-in duration-75"
                            leave-from-class="transform opacity-100 scale-100"
                            leave-to-class="transform opacity-0 scale-95"
                        >
                            <MenuItems class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none">
                                <div class="px-4 py-2 border-b border-slate-100">
                                    <p class="text-sm font-medium text-slate-900 truncate">{{ user?.name }}</p>
                                    <p class="text-xs text-slate-500 truncate">{{ user?.email }}</p>
                                </div>
                                <MenuItem v-slot="{ active }">
                                    <Link :href="route('profile.edit')" :class="[active ? 'bg-slate-50' : '', 'block px-4 py-2 text-sm text-slate-700']">Your Profile</Link>
                                </MenuItem>
                                <MenuItem v-slot="{ active }">
                                    <button @click="logout" :class="[active ? 'bg-slate-50' : '', 'block w-full text-left px-4 py-2 text-sm text-red-600']">
                                        Sign out
                                    </button>
                                </MenuItem>
                            </MenuItems>
                        </transition>
                    </HeadlessMenu>
                </div>
            </header>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto bg-slate-50 p-4 sm:p-6 lg:p-8">
                <!-- Flash Messages -->
                <div v-if="$page.props.flash?.success" class="mb-4 bg-emerald-50 border-l-4 border-emerald-400 p-4 rounded-md shadow-sm">
                    <div class="flex">
                        <div class="ml-3">
                            <p class="text-sm text-emerald-700">{{ $page.props.flash.success }}</p>
                        </div>
                    </div>
                </div>
                <div v-if="$page.props.flash?.error" class="mb-4 bg-red-50 border-l-4 border-red-400 p-4 rounded-md shadow-sm">
                    <div class="flex">
                        <div class="ml-3">
                            <p class="text-sm text-red-700">{{ $page.props.flash.error }}</p>
                        </div>
                    </div>
                </div>

                <slot></slot>
            </main>
        </div>
    </div>
</template>




