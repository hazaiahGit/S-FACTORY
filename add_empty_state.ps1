$file = "resources/js/Pages/Users/Index.vue"
$content = Get-Content $file -Raw

$emptyState = @"
                <!-- Empty State -->
                <div v-if="users.data.length === 0" class="flex flex-col items-center justify-center p-12 text-center bg-white rounded-2xl shadow-sm border border-slate-200">
                    <div class="h-16 w-16 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                        <Users class="h-8 w-8 text-slate-400" />
                    </div>
                    <h3 class="text-lg font-bold text-slate-900 mb-1">No users found</h3>
                    <p class="text-slate-500 text-sm max-w-sm mb-6">
                        {{ filters.search ? 'We couldn\'t find any users matching your search criteria.' : 'Get started by creating your first user.' }}
                    </p>
                    <Link
                        v-if="!filters.search"
                        :href="route('users.create')"
                        class="inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-bold rounded-lg text-slate-900 bg-amber-400 hover:bg-amber-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 shadow-sm transition-colors"
                    >
                        <UserPlus class="w-4 h-4 mr-2" />
                        Add User
                    </Link>
                    <Link
                        v-else
                        :href="route('users.index')"
                        class="text-sm font-medium text-amber-600 hover:text-amber-700 hover:underline"
                    >
                        Clear search filters
                    </Link>
                </div>
"@

$content = $content -replace '</div><!-- Pagination Footer -->', "$emptyState`n`n                <!-- Pagination Footer -->"

Set-Content -Path $file -Value $content
