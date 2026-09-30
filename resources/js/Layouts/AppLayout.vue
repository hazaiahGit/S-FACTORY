<script setup>
import { ref, computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import { UserCog, LayoutDashboard,
    Package,
    ShoppingCart,
    Users,
    Settings,
    Menu,
    Bell,
    LogOut,
    Factory,
    ListTree,
    ArrowRightLeft,
    Wallet,
    Receipt,
    ClipboardList,
    ChevronDown,
    Target,
    AlertTriangle,
    Building2,
    ServerCrash
} from '@lucide/vue';
import { Menu as HeadlessMenu, MenuButton, MenuItems, MenuItem, Popover, PopoverButton, PopoverPanel } from '@headlessui/vue';

const page = usePage();
const user = page.props.auth.user;
const business = page.props.auth.business;
const branch = page.props.auth.branch;

const sidebarOpen = ref(true);
const toggleSidebar = () => {
    sidebarOpen.value = !sidebarOpen.value;
};

// Dummy notifications for UI
const notifications = ref([
    { id: 1, type: 'alert', title: 'Low Stock Alert', message: 'Roofing Nails 3" is running below minimum stock.', time: '10 mins ago', read: false },
    { id: 2, type: 'success', title: 'Goal Achieved', message: 'You have reached 100% of your Monthly Sales Target!', time: '1 hour ago', read: false },
    { id: 3, type: 'info', title: 'System Update', message: 'New reports module is now available.', time: '2 hours ago', read: true }
]);

const unreadCount = computed(() => notifications.value.filter(n => !n.read).length);
const markAllRead = () => {
    notifications.value.forEach(n => n.read = true);
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

const allNavItems = [
    { name: 'Dashboard', route: 'dashboard', icon: LayoutDashboard, permission: null },
    { name: 'POS / Sales', route: 'sales.index', icon: ShoppingCart, permission: 'view sales' },
    { name: 'Purchases', route: 'purchases.index', icon: Receipt, permission: 'view purchases' },
    { name: 'Products', route: 'products.index', icon: Package, permission: 'view products' },
    { name: 'Inventory', route: 'inventory.index', icon: Package, permission: 'view inventory' },
    { name: 'Recipes / BOM', route: 'bom.index', icon: ListTree, permission: 'view recipes' },
    { name: 'Transfers', route: 'stock-transfers.index', icon: ArrowRightLeft, permission: 'view inventory' },
    { name: 'Customers', route: 'customers.index', icon: Users, permission: 'view sales' },
    { name: 'Suppliers', route: 'suppliers.index', icon: Users, permission: 'view purchases' },
    { name: 'Expenses', route: 'expenses.index', icon: Wallet, permission: 'view profit' },
    { name: 'Targets & Goals', route: 'targets.index', icon: Target, permission: 'view report' },
    { name: 'Reports', route: 'reports.index', icon: ClipboardList, permission: 'view report' },
        { name: 'User Management', route: 'users.index', icon: UserCog, permission: 'manage users' },
    { name: 'Branches', route: 'branches.index', icon: Building2, permission: 'manage users' }, // Reusing manage users permission for now, or just allow super admin
];

const navigation = computed(() => allNavItems.filter(item => hasPermission(item.permission)));
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
                <div class="text-sm font-semibold text-white truncate">{{ business?.name || 'My Business' }}</div>
                <div class="text-xs text-slate-400 truncate mt-1 flex items-center">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 mr-2"></span>
                    {{ branch?.name || 'Main Branch' }}
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1 sidebar-scroll">
                <Link
                    v-for="item in navigation"
                    :key="item.name"
                    :href="route(item.route)"
                    @click="window?.innerWidth < 1024 ? sidebarOpen = false : null"
                    :class="[
                        route().current(item.route) || route().current(item.route.split('.')[0] + '.*')
                            ? 'bg-amber-500/10 text-amber-500'
                            : 'hover:bg-slate-800 hover:text-white',
                        'group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors'
                    ]"
                    :title="!sidebarOpen ? item.name : ''"
                >
                    <component
                        :is="item.icon"
                        :class="[
                            route().current(item.route) ? 'text-amber-500' : 'text-slate-400 group-hover:text-white',
                            'flex-shrink-0 h-5 w-5'
                        ]"
                        aria-hidden="true"
                    />
                    <span :class="['ml-3', !sidebarOpen ? 'lg:hidden' : '']">{{ item.name }}</span>
                </Link>
            </nav>

                        <!-- System Management (System Admins Only) -->
            <div v-if="$page.props.auth.user && $page.props.auth.user.is_system_admin" class="px-3 mt-4 mb-2">
                <div :class="['text-xs font-bold text-slate-500 uppercase tracking-wider pl-3', !sidebarOpen ? 'lg:hidden' : '']">
                    System Admin
                </div>
                <Link
                    :href="route('system.tenants.index')"
                    @click="window?.innerWidth < 1024 ? sidebarOpen = false : null"
                    :class="[
                        route().current('system.tenants.*') || route().current('system.packages.*')
                            ? 'bg-rose-500/10 text-rose-500'
                            : 'hover:bg-slate-800 hover:text-white',
                        'group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors mt-2 text-slate-400'
                    ]"
                    :title="!sidebarOpen ? 'System Tenants' : ''"
                >
                    <ServerCrash :class="[
                            route().current('system.tenants.*') || route().current('system.packages.*') ? 'text-rose-500' : 'text-slate-400 group-hover:text-white',
                            'flex-shrink-0 h-5 w-5'
                        ]" />
                    <span :class="['ml-3', !sidebarOpen ? 'lg:hidden' : '']">Tenants & Packages</span>
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

                <div class="flex items-center space-x-4">
                                        <!-- Branch Filter (Admins Only) -->
                    <div v-if="$page.props.auth.all_branches && $page.props.auth.all_branches.length > 0" class="hidden sm:flex items-center bg-slate-50 border border-slate-200 rounded-lg px-2 py-1 relative">
                        <Building2 class="w-4 h-4 text-slate-400 mr-2" />
                        <select 
                            @change="e => { router.post(route('active-branch.update'), { branch_id: e.target.value }, { preserveScroll: true }) }"
                            :value="$page.props.auth.active_branch_id"
                            class="text-sm bg-transparent border-none focus:ring-0 text-slate-700 font-medium py-1 pr-8 pl-0 cursor-pointer w-40 truncate"
                        >
                            <option v-for="branch in $page.props.auth.all_branches" :key="branch.id" :value="branch.id">
                                {{ branch.name }}
                            </option>
                        </select>
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
                                    <h3 class="text-sm font-bold text-slate-800">Notifications</h3>
                                    <button @click="markAllRead" v-if="unreadCount > 0" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">Mark all read</button>
                                </div>
                                <div class="max-h-96 overflow-y-auto">
                                    <div v-for="notification in notifications" :key="notification.id" :class="['p-4 border-b border-slate-50 last:border-0 hover:bg-slate-50 transition-colors cursor-pointer', !notification.read ? 'bg-indigo-50/30' : '']">
                                        <div class="flex items-start">
                                            <div class="flex-shrink-0 mt-0.5">
                                                <div v-if="notification.type === 'alert'" class="h-8 w-8 rounded-full bg-rose-100 flex items-center justify-center">
                                                    <AlertTriangle class="h-4 w-4 text-rose-600" />
                                                </div>
                                                <div v-else-if="notification.type === 'success'" class="h-8 w-8 rounded-full bg-emerald-100 flex items-center justify-center">
                                                    <Target class="h-4 w-4 text-emerald-600" />
                                                </div>
                                                <div v-else class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center">
                                                    <Bell class="h-4 w-4 text-blue-600" />
                                                </div>
                                            </div>
                                            <div class="ml-3 flex-1">
                                                <p :class="['text-sm font-bold', !notification.read ? 'text-slate-900' : 'text-slate-700']">
                                                    {{ notification.title }}
                                                </p>
                                                <p class="text-xs text-slate-500 mt-0.5">
                                                    {{ notification.message }}
                                                </p>
                                                <p class="text-[10px] text-slate-400 mt-1 font-medium">
                                                    {{ notification.time }}
                                                </p>
                                            </div>
                                            <div v-if="!notification.read" class="flex-shrink-0 ml-2">
                                                <div class="h-2 w-2 bg-indigo-600 rounded-full"></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-if="notifications.length === 0" class="p-6 text-center">
                                        <Bell class="h-8 w-8 text-slate-300 mx-auto mb-2" />
                                        <p class="text-sm text-slate-500">No new notifications</p>
                                    </div>
                                </div>
                                <div class="px-4 py-2 border-t border-slate-100 bg-slate-50 text-center">
                                    <Link href="#" class="text-xs font-bold text-slate-600 hover:text-slate-900 transition-colors">View all notifications</Link>
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




