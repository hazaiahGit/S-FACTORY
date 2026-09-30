$file1 = "resources/js/Pages/Users/Create.vue"
$content1 = Get-Content $file1 -Raw
$content1 = $content1 -replace '<!-- Direct Permissions -->', "<!-- Direct Permissions -->`n                    <div v-if=`"Object.keys(permissions || {}).length > 0 && permissions['System Permissions']?.length > 0`">"
$content1 = $content1 -replace '<!-- Submit Button -->', "</div>`n`n                    <!-- Submit Button -->"
Set-Content -Path $file1 -Value $content1

$file2 = "resources/js/Pages/Users/Edit.vue"
$content2 = Get-Content $file2 -Raw
$content2 = $content2 -replace '<!-- Direct Permissions -->', "<!-- Direct Permissions -->`n                    <div v-if=`"Object.keys(permissions || {}).length > 0 && permissions['System Permissions']?.length > 0`">"
$content2 = $content2 -replace '<!-- Form Actions -->', "</div>`n`n                    <!-- Form Actions -->"
Set-Content -Path $file2 -Value $content2
