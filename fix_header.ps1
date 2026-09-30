$file = "resources/js/Pages/Users/Index.vue"
$content = Get-Content $file -Raw

$content = $content -replace '(?s)<div class="flex items-center gap-3">.*?<!-- Table Card -->', @"
                <div class="flex items-center gap-3">
                    <Link
                        :href="route('roles.index')"
                        class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl shadow-sm text-sm font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500 transition-colors w-full sm:w-auto"
                    >
                        <Shield class="w-4 h-4 mr-2 text-indigo-500" />
                        <span>Manage Roles</span>
                    </Link>
                    <Link
                        :href="route('users.create')"
                        class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl shadow-sm text-sm font-semibold text-slate-900 bg-amber-400 hover:bg-amber-500 active:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition-colors w-full sm:w-auto"
                    >
                        <UserPlus class="w-4 h-4 mr-2" />
                        <span>Add User</span>
                    </Link>
                </div>
            </div>

            <!-- Table Card -->
"@

Set-Content -Path $file -Value $content
