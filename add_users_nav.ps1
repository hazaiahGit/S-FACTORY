$file = "resources/js/Layouts/AppLayout.vue"
$content = Get-Content $file -Raw

if (-not ($content -match "UserCog")) {
    $content = $content -replace "import \{", "import { UserCog,"
}

if (-not ($content -match "name: 'User Management'")) {
    $content = $content -replace "\{ name: 'Reports', route: 'reports.index', icon: ClipboardList \},", "{ name: 'Reports', route: 'reports.index', icon: ClipboardList },`n    { name: 'User Management', route: 'users.index', icon: UserCog },"
}

Set-Content -Path $file -Value $content
