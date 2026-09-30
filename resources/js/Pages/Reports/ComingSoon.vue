<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, HardHat } from '@lucide/vue';

const props = defineProps({
    title: String
});
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center text-lg sm:text-xl">
                <Link :href="route('reports.index')" class="mr-3 sm:mr-4 text-slate-400 hover:text-slate-600 transition-colors">
                    <ArrowLeft class="w-5 h-5 sm:w-6 sm:h-6" />
                </Link>
                <span class="font-bold truncate">{{ title }}</span>
            </div>
        </template>

        <div class="max-w-4xl mx-auto py-12">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-12 text-center">
                <div class="w-20 h-20 bg-amber-50 rounded-full flex items-center justify-center mx-auto mb-6">
                    <HardHat class="w-10 h-10 text-amber-500" />
                </div>
                <h2 class="text-2xl font-black text-slate-900 mb-2">Report Under Construction</h2>
                <p class="text-slate-500 max-w-md mx-auto mb-8">
                    The detailed analytics for "{{ title }}" are currently being developed. Check back soon for advanced filtering, charts, and PDF exports.
                </p>
                <Link :href="route('reports.index')" class="inline-flex items-center justify-center px-6 py-3 border border-transparent rounded-lg shadow-sm text-sm font-bold text-slate-900 bg-amber-400 hover:bg-amber-500 focus:outline-none transition-colors">
                    Back to Reports Dashboard
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
