$file = "resources/js/Pages/Users/Index.vue"
$content = Get-Content $file -Raw

$content = $content -replace '(?s)<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">\s*<!-- Desktop Table View -->\s*<div class="grid', '<div class="grid'

# We also need to remove the closing </div> of that container which is just before <!-- Empty State -->
$content = $content -replace '(?s)</div>\s*<!-- Empty State -->', '<!-- Empty State -->'

Set-Content -Path $file -Value $content
