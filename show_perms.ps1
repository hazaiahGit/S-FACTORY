$file1 = "resources/js/Pages/Users/Create.vue"
$content1 = Get-Content $file1 -Raw
$content1 = $content1 -replace '<!-- Direct Permissions -->\s*<div v-if="Object.keys\(permissions \|\| \{\}\)\.length > 0 && permissions\[\''System Permissions\''\]\?\.length > 0">', '<!-- Direct Permissions -->'
$content1 = $content1 -replace '</div>\s*<!-- Submit Button -->', '<!-- Submit Button -->'
Set-Content -Path $file1 -Value $content1

$file2 = "resources/js/Pages/Users/Edit.vue"
$content2 = Get-Content $file2 -Raw
$content2 = $content2 -replace '<!-- Direct Permissions -->\s*<div v-if="Object.keys\(permissions \|\| \{\}\)\.length > 0 && permissions\[\''System Permissions\''\]\?\.length > 0">', '<!-- Direct Permissions -->'
$content2 = $content2 -replace '</div>\s*<!-- Form Actions -->', '<!-- Form Actions -->'
Set-Content -Path $file2 -Value $content2
