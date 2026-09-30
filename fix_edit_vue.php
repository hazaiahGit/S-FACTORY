<?php
$file = "resources/js/Pages/Manufacturing/BOM/Edit.vue";
$content = file_get_contents($file);

$template = <<<'EOT'
<template>
    <AppLayout>
        <template #header>
            <div class="flex items-center text-lg sm:text-xl">
                <Link :href="route('bom.index')" class="mr-3 sm:mr-4 text-slate-400 hover:text-slate-600 transition-colors">
                    <ArrowLeft class="w-5 h-5 sm:w-6 sm:h-6" />
                </Link>
                <span class="font-bold truncate">Save Changes (BOM)</span>
            </div>
        </template>

        <div class="max-w-5xl mx-auto space-y-6">
            <form @submit.prevent="submit" class="space-y-6">
                
                <!-- Main Details -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-5 border-b border-slate-100 bg-slate-50">
                        <h3 class="text-sm font-bold text-slate-800 flex items-center">
                            <BookOpen class="w-4 h-4 mr-2 text-indigo-500" />
                            Recipe Details
                        </h3>
                    </div>
                    <div class="p-5 sm:p-6 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Recipe Name</label>
                                <input v-model="form.name" type="text" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" placeholder="e.g. Tofali Recipe" required />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Output Product</label>
                                <select v-model="form.product_id" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" required>
                                    <option value="" disabled>-- Select Product --</option>
                                    <option v-for="prod in products" :key="prod.id" :value="prod.id">{{ prod.name }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Expected Output Qty</label>
                                <input v-model="form.expected_output" type="number" step="0.01" min="0.01" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" required />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Output Unit</label>
                                <select v-model="form.output_unit_id" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm">
                                    <option value="">None</option>
                                    <option v-for="u in units" :key="u.id" :value="u.id">{{ u.name }}</option>
                                </select>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Description / Notes</label>
                            <textarea v-model="form.description" rows="2" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm"></textarea>
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox" v-model="form.is_active" id="is_active" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-slate-300 rounded">
                            <label for="is_active" class="ml-2 block text-sm text-slate-900">Active Recipe</label>
                        </div>
                    </div>
                </div>

                <!-- Materials / Ingredients -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                        <h3 class="text-sm font-bold text-slate-800 flex items-center">
                            <List class="w-4 h-4 mr-2 text-indigo-500" />
                            Materials (Ingredients)
                        </h3>
                        <button type="button" @click="addMaterial" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-bold rounded-lg text-indigo-700 bg-indigo-100 hover:bg-indigo-200 transition-colors">
                            <Plus class="w-3.5 h-3.5 mr-1" />
                            Add Item
                        </button>
                    </div>
                    <div class="p-0 overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Material</th>
                                    <th scope="col" class="px-6 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider w-32">Qty</th>
                                    <th scope="col" class="px-6 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider w-32">Unit Cost</th>
                                    <th scope="col" class="px-6 py-3 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider w-32">Total</th>
                                    <th scope="col" class="px-6 py-3 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider w-16"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-200">
                                <tr v-for="(item, index) in form.items" :key="index">
                                    <td class="px-6 py-3">
                                        <select v-model="item.product_id" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" required>
                                            <option value="" disabled>Select...</option>
                                            <option v-for="prod in materials" :key="prod.id" :value="prod.id">{{ prod.name }}</option>
                                        </select>
                                    </td>
                                    <td class="px-6 py-3">
                                        <input v-model="item.quantity" type="number" step="0.01" min="0.01" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" required />
                                    </td>
                                    <td class="px-6 py-3">
                                        <input v-model="item.unit_cost" @input="item.total_cost = item.unit_cost * item.quantity" type="number" step="0.01" min="0" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" />
                                    </td>
                                    <td class="px-6 py-3 text-right font-bold text-slate-700">
                                        {{ (item.quantity * item.unit_cost).toFixed(2) }}
                                    </td>
                                    <td class="px-6 py-3 text-right">
                                        <button type="button" @click="removeMaterial(index)" class="text-rose-400 hover:text-rose-600 transition-colors p-1" title="Remove">
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="flex justify-end pt-2 border-t border-slate-200">
                    <button type="submit" :disabled="form.processing" class="inline-flex justify-center items-center py-2 px-6 border border-transparent shadow-sm text-sm font-bold rounded-lg text-slate-900 bg-amber-400 hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 disabled:opacity-50 transition-colors">
                        <Save class="w-4 h-4 mr-2" />
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
EOT;

$content = preg_replace('/<template>.*<\/template>/s', $template, $content);
file_put_contents($file, $content);
echo "Replaced Edit.vue template.\n";
?>
