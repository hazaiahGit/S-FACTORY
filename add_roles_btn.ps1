$file = "resources/js/Pages/Users/Index.vue"
$content = Get-Content $file -Raw

$manageRolesLink = @"
                    <Link
                        :href="route('roles.index')"
                        class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl shadow-sm text-sm font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500 transition-colors w-full sm:w-auto"
                    >
                        <Shield class="w-4 h-4 mr-2 text-indigo-500" />
                        <span>Manage Roles</span>
                    </Link>
"@

$content = $content -replace '(<Link[^>]*:href="route\(''users.create''\)"[^>]*>.*?<\/Link>)', "$manageRolesLink`n                    `$1"

# We also need to import Shield icon if not imported
if ($content -notmatch "Shield") {
    $content = $content -replace "import \{ UserPlus", "import { UserPlus, Shield"
}

Set-Content -Path $file -Value $content
