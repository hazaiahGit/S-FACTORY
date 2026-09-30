$file = "resources/js/Pages/Manufacturing/BOM/Create.vue"
$content = Get-Content $file -Raw

# Replace the variables
$content = $content -replace "const selectedMaterial = ref\(''\);[\s\S]*?const customItemCost = ref\(0\);", "const customItemName = ref('');`nconst customItemDesc = ref('');`nconst customItemCost = ref(0);"

# Replace the methods
$content = $content -replace "const addMaterial = \(\) => \{[\s\S]*?const addCustomItem = \(\) => \{[\s\S]*?    \};", @"
const addCustomMaterial = () => {
    if (!customItemName.value) {
        alert("Please provide a name for the material.");
        return;
    }
    
    form.items.push({
        item_type: 'material',
        product_id: null,
        name: customItemName.value,
        description: customItemDesc.value,
        quantity: 1,
        unit_cost: Number(customItemCost.value || 0),
        total_cost: Number(customItemCost.value || 0),
    });
    
    customItemName.value = '';
    customItemDesc.value = '';
    customItemCost.value = 0;
};
"@

# Replace the HTML adders section
$content = $content -replace '<!-- Adders -->[\s\S]*?<!-- Items Card List -->', @"
                    <!-- Add Custom Material -->
                    <div class="p-5 border-b border-slate-100 bg-slate-50/50">
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Add Material / Cost</label>
                        <div class="flex flex-col sm:flex-row gap-2">
                            <input v-model="customItemName" type="text" placeholder="Material Name (e.g. Flour)" class="block w-full sm:w-1/3 border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm" />
                            <input v-model="customItemDesc" type="text" placeholder="Description / Qty (e.g. 10 kg)" class="block w-full sm:w-1/3 border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm" />
                            <input v-model.number="customItemCost" type="number" min="0" placeholder="Total Cost" class="block w-full sm:w-1/4 border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 text-sm" />
                            <button type="button" @click="addCustomMaterial" class="inline-flex justify-center items-center px-4 py-2 border border-transparent shadow-sm text-sm font-bold rounded-lg text-slate-900 bg-emerald-100 hover:bg-emerald-200 focus:outline-none transition-colors whitespace-nowrap">
                                <Plus class="w-4 h-4 mr-1" /> Add
                            </button>
                        </div>
                    </div>

                    <!-- Items Card List -->
"@

# Also update the Item Card UI to only show Name, Description, and Total Cost, instead of quantity inputs etc.
# Wait, let's see how the Item Card UI looks first.
Set-Content -Path $file -Value $content
