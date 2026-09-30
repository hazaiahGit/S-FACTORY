<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: { type: Boolean },
    status: { type: String },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Sign In — S-FACTORY" />

    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-amber-900 flex">

        <!-- Left Panel — Branding (hidden on mobile) -->
        <div class="hidden lg:flex lg:w-1/2 xl:w-3/5 flex-col justify-between p-12 relative overflow-hidden">
            <!-- Decorative blobs -->
            <div class="absolute top-0 right-0 w-96 h-96 bg-amber-500/10 rounded-full -translate-y-1/2 translate-x-1/2 blur-3xl pointer-events-none"></div>
            <div class="absolute bottom-0 left-0 w-72 h-72 bg-indigo-500/10 rounded-full translate-y-1/2 -translate-x-1/2 blur-3xl pointer-events-none"></div>

            <!-- Logo -->
            <div class="flex items-center space-x-3 relative z-10">
                <div class="h-10 w-10 bg-amber-500 rounded-xl flex items-center justify-center shadow-lg">
                    <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <span class="text-white font-black text-xl tracking-widest uppercase">S-FACTORY</span>
            </div>

            <!-- Hero Text -->
            <div class="relative z-10">
                <h1 class="text-4xl xl:text-5xl font-black text-white leading-tight">
                    Run Your Hardware<br />
                    <span class="text-amber-400">Business Smarter.</span>
                </h1>
                <p class="mt-6 text-slate-300 text-lg max-w-md leading-relaxed">
                    Full-featured management system for hardware stores and small factories. Sales, inventory, manufacturing, and more — all in one place.
                </p>

                <!-- Feature pills -->
                <div class="mt-10 flex flex-wrap gap-3">
                    <span v-for="f in ['POS & Sales', 'Inventory', 'Manufacturing', 'Reports', 'Purchases', 'Expenses']" :key="f"
                        class="px-4 py-2 bg-white/10 backdrop-blur-sm text-white text-sm font-medium rounded-full border border-white/10">
                        {{ f }}
                    </span>
                </div>
            </div>

            <!-- Bottom tagline -->
            <p class="text-slate-500 text-sm relative z-10">© {{ new Date().getFullYear() }} S-Factory Business Suite</p>
        </div>

        <!-- Right Panel — Form -->
        <div class="flex-1 flex items-center justify-center p-6 sm:p-12">
            <div class="w-full max-w-md">

                <!-- Mobile Logo -->
                <div class="lg:hidden flex items-center justify-center space-x-3 mb-10">
                    <div class="h-12 w-12 bg-amber-500 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <span class="text-white font-black text-2xl tracking-widest uppercase">S-FACTORY</span>
                </div>

                <!-- Card -->
                <div class="bg-white/10 backdrop-blur-xl rounded-3xl p-8 sm:p-10 shadow-2xl border border-white/10">

                    <div class="mb-8">
                        <h2 class="text-2xl sm:text-3xl font-black text-white">Welcome back</h2>
                        <p class="mt-2 text-slate-400 text-sm">Sign in to access your business dashboard</p>
                    </div>

                    <!-- Status message (e.g. password reset) -->
                    <div v-if="status" class="mb-6 px-4 py-3 bg-emerald-500/20 border border-emerald-500/30 rounded-xl text-emerald-300 text-sm font-medium">
                        {{ status }}
                    </div>

                    <form @submit.prevent="submit" class="space-y-5">
                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-bold text-slate-300 mb-2">Email Address</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                    </svg>
                                </div>
                                <input
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    autocomplete="email"
                                    autofocus
                                    required
                                    placeholder="you@example.com"
                                    :class="['w-full pl-12 pr-4 py-3.5 bg-white/10 border rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 transition-all text-sm', form.errors.email ? 'border-rose-500 focus:ring-rose-500/40' : 'border-white/10 focus:ring-amber-500/40 focus:border-amber-500/50']"
                                />
                            </div>
                            <p v-if="form.errors.email" class="mt-1.5 text-xs text-rose-400">{{ form.errors.email }}</p>
                        </div>

                        <!-- Password -->
                        <div>
                            <label for="password" class="block text-sm font-bold text-slate-300 mb-2">Password</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <input
                                    id="password"
                                    v-model="form.password"
                                    :type="showPassword ? 'text' : 'password'"
                                    autocomplete="current-password"
                                    required
                                    placeholder="••••••••"
                                    :class="['w-full pl-12 pr-12 py-3.5 bg-white/10 border rounded-xl text-white placeholder-slate-500 focus:outline-none focus:ring-2 transition-all text-sm', form.errors.password ? 'border-rose-500 focus:ring-rose-500/40' : 'border-white/10 focus:ring-amber-500/40 focus:border-amber-500/50']"
                                />
                                <!-- Toggle show/hide -->
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute inset-y-0 right-0 pr-4 flex items-center text-slate-400 hover:text-white transition-colors"
                                    tabindex="-1"
                                >
                                    <!-- Eye icon -->
                                    <svg v-if="!showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    <!-- Eye-off icon -->
                                    <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                    </svg>
                                </button>
                            </div>
                            <p v-if="form.errors.password" class="mt-1.5 text-xs text-rose-400">{{ form.errors.password }}</p>
                        </div>

                        <!-- Remember me + Forgot password -->
                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2.5 cursor-pointer group">
                                <div class="relative">
                                    <input type="checkbox" v-model="form.remember" class="sr-only" />
                                    <div :class="['w-5 h-5 rounded border-2 transition-all flex items-center justify-center', form.remember ? 'bg-amber-500 border-amber-500' : 'bg-white/10 border-white/20 group-hover:border-white/40']">
                                        <svg v-if="form.remember" class="h-3 w-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                </div>
                                <span class="text-sm text-slate-300 select-none">Remember me</span>
                            </label>

                            <Link
                                v-if="canResetPassword"
                                :href="route('password.request')"
                                class="text-sm text-amber-400 hover:text-amber-300 font-medium transition-colors"
                            >
                                Forgot password?
                            </Link>
                        </div>

                        <!-- Submit -->
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full py-3.5 px-6 bg-amber-500 hover:bg-amber-400 active:bg-amber-600 text-white font-black text-sm rounded-xl shadow-lg shadow-amber-500/25 transition-all duration-200 disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2 mt-2"
                        >
                            <svg v-if="form.processing" class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ form.processing ? 'Signing in...' : 'Sign In' }}
                        </button>
                    </form>
                </div>

                <p class="mt-6 text-center text-xs text-slate-600">
                    Protected by S-Factory Security
                </p>
            </div>
        </div>
    </div>
</template>
