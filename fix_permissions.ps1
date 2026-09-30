$file = "app/Http/Controllers/UserController.php"
$content = Get-Content $file -Raw

$content = $content -replace "Permission::all\(\['id', 'name', 'group_name'\]\)", "Permission::all(['id', 'name'])"
$content = $content -replace "`$permissions->groupBy\('group_name'\)", "['System Permissions' => `$permissions]"

Set-Content -Path $file -Value $content
