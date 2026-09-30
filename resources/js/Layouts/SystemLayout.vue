<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { LayoutDashboard, Building2, PackageOpen, ShieldCheck, Menu, X } from '@lucide/vue';

const page = usePage();
const user = page.props.auth?.user;
const mobileOpen = ref(false);
</script>

<template>
    <div class="min-h-screen bg-slate-950 flex">
        <!-- Sidebar -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 border-r border-slate-800 flex flex-col transition-transform duration-300 lg:static lg:inset-auto lg:translate-x-0',
                mobileOpen ? 'translate-x-0' : '-translate-x-full'
            ]"
        >
            <!-- Logo -->
            <div class="h-16 flex items-center gap-3 px-6 border-b border-slate-800 bg-slate-950 shrink-0">
                <div class="w-8 h-8 rounded-lg bg-rose-500 flex items-center justify-center shrink-0">
                    <ShieldCheck class="w-5 h-5 text-white" />
                </div>
                <div>
                    <div class="text-sm font-black text-white tracking-wider uppercase">S-Factory</div>
                    <div class="text-[10px] text-rose-400 font-bold uppercase tracking-widest">System Admin</div>
                </div>
            </div>

            <!-- Nav Links -->
            <nav class="flex-1 overflow-y-auto py-6 px-3 space-y-1">
                <p class="text-[10px] font-bold text-slate-600 uppercase tracking-widest px-3 mb-3">Management</p>
                <Link
                    :href="route('system.tenants.index')"
                    :class="[
                        route().current('system.tenants.*')
                            ? 'bg-rose-500/10 text-rose-400 border-rose-500'
                            : 'text-slate-400 hover:bg-slate-800 hover:text-white border-transparent',
                        'flex items-center gap-3 px-3 py-2.5 text-sm font-semibold rounded-lg border-l-2 transition-colors'
                    ]"
                >
                    <Building2 class="w-5 h-5 shrink-0" />
                    Tenants
                </Link>
                <Link
                    :href="route('system.packages.index')"
                    :class="[
                        route().current('system.packages.*')
                            ? 'bg-rose-500/10 text-rose-400 border-rose-500'
                            : 'text-slate-400 hover:bg-slate-800 hover:text-white border-transparent',
                        'flex items-center gap-3 px-3 py-2.5 text-sm font-semibold rounded-lg border-l-2 transition-colors'
                    ]"
                >
                    <PackageOpen class="w-5 h-5 shrink-0" />
                    Subscription Packages
                </Link>
            </nav>

            <!-- User / Back to app -->
            <div class="p-4 border-t border-slate-800 shrink-0">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-9 h-9 rounded-full bg-rose-500/20 flex items-center justify-center shrink-0">
                        <span class="text-rose-400 font-black text-sm">{{ user?.name?.charAt(0) || 'S' }}</span>
                    </div>
                    <div class="min-w-0">
                        <div class="text-sm font-bold text-white truncate">{{ user?.name }}</div>
                        <div class="text-xs text-slate-500">System Administrator</div>
                    </div>
                </div>
                <Link
                    :href="route('dashboard')"
                    class="flex items-center gap-2 text-xs font-semibold text-slate-400 hover:text-white bg-slate-800 hover:bg-slate-700 px-3 py-2 rounded-lg transition-colors"
                >
                    <LayoutDashboard class="w-4 h-4" />
                    Back to Main App
                </Link>
            </div>
        </aside>

        <!-- Mobile Backdrop -->
        <div
            v-if="mobileOpen"
            @click="mobileOpen = false"
            class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm lg:hidden"
        ></div>

        <!-- Main Content Area -->
        <div class="flex-1 flex flex-col min-h-screen min-w-0">
            <!-- Top Bar -->
            <header class="h-16 bg-slate-900 border-b border-slate-800 flex items-center justify-between px-4 sm:px-6 sticky top-0 z-30 shrink-0">
                <div class="flex items-center gap-4">
                    <button @click="mobileOpen = !mobileOpen" class="lg:hidden text-slate-400 hover:text-white p-1 rounded transition-colors">
                        <Menu v-if="!mobileOpen" class="w-6 h-6" />
                        <X v-else class="w-6 h-6" />
                    </button>
                    <div class="flex items-center gap-2">
                        <ShieldCheck class="w-4 h-4 text-rose-500" />
                        <span class="text-slate-300 font-bold text-sm hidden sm:inline">System Administration Panel</span>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <div v-if="$page.props.flash?.success" class="text-xs text-emerald-400 bg-emerald-400/10 border border-emerald-400/20 px-3 py-1.5 rounded-full font-semibold">
                        ✓ {{ $page.props.flash.success }}
                    </div>
                    <div v-if="$page.props.flash?.error" class="text-xs text-rose-400 bg-rose-400/10 border border-rose-400/20 px-3 py-1.5 rounded-full font-semibold">
                        {{ $page.props.flash.error }}
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                <slot />
            </main>
        </div>
    </div>
</template>
