$file = "resources/js/Pages/Manufacturing/BOM/Create.vue"
$content = Get-Content $file -Raw

$newCardList = @"
                    <!-- Items Card List -->
                    <div class="block">
                        <div v-for="(item, index) in form.items" :key="index" class="p-4 sm:p-5 border-b border-slate-100 last:border-0 relative hover:bg-slate-50 transition-colors">
                            <button type="button" @click="removeItem(index)" class="absolute top-4 sm:top-5 right-4 sm:right-5 text-rose-400 hover:text-rose-600 bg-rose-50 p-2 rounded-lg transition-colors">
                                <Trash2 class="w-4 h-4 sm:w-5 sm:h-5" />
                            </button>
                            <div class="pr-12">
                                <div class="text-sm sm:text-base font-bold text-slate-900">{{ item.name }}</div>
                                <div class="text-sm text-slate-500 mt-1">{{ item.description }}</div>
                            </div>
                            <div class="mt-4 flex justify-between items-center bg-slate-100/50 p-3 rounded-lg text-sm font-bold border border-slate-100">
                                <span class="text-slate-500 uppercase tracking-wider text-xs">Line Cost Estimate</span>
                                <span class="text-slate-900">{{ new Intl.NumberFormat('en-TZ', { style: 'currency', currency: 'TZS', maximumFractionDigits: 0 }).format(item.total_cost) }}</span>
                            </div>
                        </div>
                        <div v-if="form.items.length === 0" class="p-8 sm:p-12 text-center text-slate-400">
                            <List class="mx-auto h-12 w-12 text-slate-300 mb-3" />
                            <p class="text-sm font-medium text-slate-500">No materials or costs added.</p>
                            <p class="text-xs text-slate-400 mt-1">Use the controls above to build your recipe.</p>
                        </div>
                    </div>
"@

$content = $content -replace '<!-- Items Card List -->[\s\S]*?<!-- Items Card List Ends -->', $newCardList # wait, I don't have <!-- Items Card List Ends -->

# Let's do it carefully with regex
$content = $content -replace '<!-- Items Card List -->[\s\S]*?<div class="p-5 sm:p-6 bg-slate-50 border-t border-slate-100 flex justify-end">', "$newCardList`n`n                    <div class=`"p-5 sm:p-6 bg-slate-50 border-t border-slate-100 flex justify-end`">"

Set-Content -Path $file -Value $content
