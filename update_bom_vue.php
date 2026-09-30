<?php
$file = "resources/js/Pages/Manufacturing/BOM/Index.vue";
$content = file_get_contents($file);

$old_template = <<<'EOT'
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="block">
                    <div v-for="bom in boms.data" :key="bom.id" class="p-4 sm:p-5 border-b border-slate-100 last:border-0 hover:bg-slate-50 transition-colors">
                        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4">
                            <div>
                                <h3 class="text-base sm:text-lg font-bold text-indigo-600">{{ bom.name }}</h3>
                                <div class="text-sm font-bold text-slate-900 mt-1">Output Product: {{ bom.product?.name }}</div>
                                <div class="text-xs text-slate-500 mt-0.5" v-if="bom.description">{{ bom.description }}</div>
                            </div>
                            
                            <div class="flex flex-wrap gap-4 sm:gap-6 bg-slate-50 sm:bg-transparent p-3 sm:p-0 rounded-lg border sm:border-0 border-slate-100 w-full sm:w-auto">
                                <div>
                                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Expected Output</span>
                                    <span class="text-sm font-black text-slate-900">{{ formatNumber(bom.expected_output) }}</span>
                                </div>
                                <div>
                                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Items</span>
                                    <span class="text-sm font-bold text-slate-700">{{ bom.items_count || 0 }} materials</span>
                                </div>
                                <div>
                                    <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Status</span>
                                    <span :class="[
                                        'px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider rounded-full',
                                        bom.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'
                                    ]">
                                        {{ bom.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div v-if="boms.data.length === 0" class="p-8 sm:p-12 text-center text-slate-400">
                        <BookOpen class="mx-auto h-12 w-12 text-slate-300 mb-3" />
                        <p class="font-medium text-slate-600">No recipes (BOMs) found.</p>
                        <p class="text-sm mt-1">Create a recipe first before starting production.</p>
                    </div>
                </div>
                
                <div v-if="boms.links && boms.data.length > 0" class="px-6 py-4 border-t border-slate-100 bg-slate-50">
EOT;

$new_template = <<<'EOT'
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
EOT;

$content = str_replace($old_template, $new_template, $content);

$old_script = <<<'EOT'
const formatNumber = (value) => {
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(value);
};
</script>
EOT;

$new_script = <<<'EOT'
const formatNumber = (value) => {
    return new Intl.NumberFormat('en-US', { maximumFractionDigits: 2 }).format(value);
};

const deleteBom = (bom) => {
    if (confirm(`Are you sure you want to delete the recipe "${bom.name}"?\nThis action cannot be undone.`)) {
        router.delete(route('bom.destroy', bom.id));
    }
};
</script>
EOT;

$content = str_replace($old_script, $new_script, $content);

file_put_contents($file, $content);
echo "Updated Vue file.\n";
?>
