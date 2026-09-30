$file = "resources/js/Pages/Manufacturing/BOM/Create.vue"
$content = Get-Content $file -Raw

$content = $content -replace '<div class="grid grid-cols-1 md:grid-cols-3 gap-6">\s*<div>\s*<label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wider">Recipe Name</label>\s*<input v-model="form.name" type="text" class="block w-full border-slate-300 rounded-lg shadow-sm focus:ring-amber-500 focus:border-amber-500 sm:text-sm" placeholder="e.g. Concrete Mix v1" required />\s*</div>', '<div class="grid grid-cols-1 md:grid-cols-2 gap-6">'

Set-Content -Path $file -Value $content
