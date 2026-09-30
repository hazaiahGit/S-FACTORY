<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { BookOpen, Plus, Search } from '@lucide/vue';

const props = defineProps({
    boms: Object,
    filters: Object,
});

const page = usePage();
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
        route('bom.index'),
        { search: search.value },
        { preserveState: true, preserveScroll: true, replace: true }
    );
}, 300);

watch(search, performSearch);

const formatNumber = (value) => {
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(value);
};

const deleteBom = (bom) => {
    if (confirm(`Are you sure you want to delete the recipe "${bom.name}"?\nThis action cannot be undone.`)) {
        router.delete(route('bom.destroy', bom.id));
    }
};
</script>

<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center text-lg sm:text-xl font-bold">
                <BookOpen class="w-6 h-6 mr-3 text-indigo-500" />
                Recipes / Bill of Materials
            </div>
        </template>

        <div class="max-w-7xl mx-auto space-y-6">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="relative w-full max-w-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <Search class="h-5 w-5 text-slate-400" />
                    </div>
                    <input v-model="search" type="text" class="block w-full pl-10 pr-3 py-2 border-slate-200 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" placeholder="Search recipes..." />
                </div>
                
                <Link :href="route('bom.create')" class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-lg shadow-sm text-sm font-bold text-slate-900 bg-amber-400 hover:bg-amber-500 focus:outline-none transition-colors w-full sm:w-auto">
                    <Plus class="w-4 h-4 mr-2" />
                    New Recipe (BOM)
                </Link>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div v-for="bom in boms.data" :key="bom.id" class="bg-white rounded-2xl shadow-sm border border-slate-200 hover:border-indigo-300 hover:shadow-md transition-all p-5 flex flex-col relative">
                    
                    <div class="absolute top-4 right-4 flex space-x-2">
                        <Link :href="route('bom.edit', bom.id)" class="p-2 bg-slate-50 hover:bg-indigo-50 text-slate-400 hover:text-indigo-600 rounded-lg transition-colors" title="Edit">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path></svg>
                        </Link>
                        <button @click="deleteBom(bom)" class="p-2 bg-slate-50 hover:bg-rose-50 text-slate-400 hover:text-rose-600 rounded-lg transition-colors" title="Delete">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
                        </button>
                    </div>

                    <div class="pr-16">
                        <h3 class="text-lg font-black text-indigo-600 leading-tight mb-2">{{ bom.name }}</h3>
                        <span :class="[
                            'inline-block px-2.5 py-1 text-[10px] font-black uppercase tracking-wide rounded-lg mb-4',
                            bom.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'
                        ]">
                            {{ bom.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    
                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-100 mb-4 flex-1">
                        <div class="mb-3">
                            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Output Product</span>
                            <span class="text-sm font-bold text-slate-900">{{ bom.product?.name || '---' }}</span>
                        </div>
                        <div class="flex justify-between items-center pt-3 border-t border-slate-200 border-dashed">
                            <div>
                                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Expected Output</span>
                                <span class="text-sm font-black text-indigo-700">{{ formatNumber(bom.expected_output) }}</span>
                            </div>
                            <div class="text-right">
                                <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-0.5">Materials</span>
                                <span class="text-sm font-bold text-slate-700">{{ bom.items_count || 0 }} items</span>
                            </div>
                        </div>
                    </div>
                    
                    <div v-if="bom.description" class="text-xs text-slate-500 line-clamp-2 mt-auto border-t border-slate-100 pt-3">
                        {{ bom.description }}
                    </div>
                </div>
            </div>

            <div v-if="boms.data.length === 0" class="bg-white rounded-xl shadow-sm border border-slate-200 p-12 text-center">
                <BookOpen class="mx-auto h-12 w-12 text-slate-300 mb-3" />
                <p class="font-medium text-slate-600 text-lg">No recipes (BOMs) found.</p>
                <p class="text-slate-500 mt-1">Create a recipe first before starting production.</p>
                <Link :href="route('bom.create')" class="inline-flex items-center justify-center px-4 py-2 mt-4 border border-transparent rounded-lg shadow-sm text-sm font-bold text-slate-900 bg-amber-400 hover:bg-amber-500 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    New Recipe
                </Link>
            </div>
            
            <div v-if="boms.links && boms.data.length > 0" class="bg-white rounded-xl shadow-sm border border-slate-200 px-6 py-4">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <span class="text-sm text-slate-500">Showing {{ boms.from }} to {{ boms.to }} of {{ boms.total }}</span>
                    <div class="flex space-x-1">
                        <template v-for="(link, i) in boms.links" :key="i">
                            <Link 
                                v-if="link.url" 
                                :href="link.url" 
                                v-html="link.label"
                                :class="[
                                    'px-3 py-1 text-sm border rounded-md transition-colors',
                                    link.active ? 'bg-amber-500 text-white border-amber-500 font-bold' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50'
                                ]"
                            />
                            <span v-else v-html="link.label" class="px-3 py-1 text-sm border border-slate-100 rounded-md text-slate-400 bg-slate-50 cursor-not-allowed"></span>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
