$file = "resources/js/Pages/Users/Index.vue"
$content = Get-Content $file -Raw

$content = $content -replace '<!-- Pagination Footer -->', '</div><!-- Pagination Footer -->'

Set-Content -Path $file -Value $content
