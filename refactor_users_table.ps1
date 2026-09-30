$file = "resources/js/Pages/Users/Index.vue"
$content = Get-Content $file -Raw

$cardsHTML = @"
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-4 sm:gap-6">
                    <div
                        v-for="user in users.data"
                        :key="user.id"
                        class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden hover:shadow-md transition-all flex flex-col group relative"
                    >
                        <!-- Status Badge -->
                        <div class="absolute top-4 right-4 z-10">
                            <span
                                :class="[
                                    'inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider shadow-sm',
                                    user.is_active
                                        ? 'bg-emerald-100 text-emerald-700 border border-emerald-200'
                                        : 'bg-rose-100 text-rose-700 border border-rose-200'
                                ]"
                            >
                                <span
                                    :class="[
                                        'w-1.5 h-1.5 rounded-full mr-1.5',
                                        user.is_active ? 'bg-emerald-500' : 'bg-rose-500'
                                    ]"
                                ></span>
                                {{ user.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>

                        <!-- Card Header: Initials, Name & Role -->
                        <div class="p-5 border-b border-slate-100 bg-slate-50/50 relative">
                            <div class="flex items-start">
                                <div class="h-12 w-12 rounded-xl bg-amber-100 border border-amber-200 text-amber-800 font-bold text-lg flex items-center justify-center shrink-0 shadow-sm mr-4 relative overflow-hidden">
                                    <div class="absolute inset-0 bg-gradient-to-br from-white/40 to-transparent"></div>
                                    <span class="relative z-10">{{ getInitials(user.name) }}</span>
                                </div>
                                <div class="flex-1 min-w-0 pr-20">
                                    <h3 class="text-base font-bold text-slate-900 truncate group-hover:text-amber-600 transition-colors">
                                        {{ user.name }}
                                    </h3>
                                    <div class="mt-1.5 flex flex-wrap gap-1.5">
                                        <template v-if="user.roles && user.roles.length > 0">
                                            <span
                                                v-for="role in user.roles"
                                                :key="role.id"
                                                :class="[
                                                    'inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider shadow-sm',
                                                    getRoleBadgeClass(role.name)
                                                ]"
                                            >
                                                {{ role.name }}
                                            </span>
                                        </template>
                                        <span v-else class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-slate-200 text-slate-600 shadow-sm">
                                            No Role
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card Body: Email, Phone, Branch -->
                        <div class="p-5 space-y-4 flex-1">
                            <!-- Contact Info -->
                            <div class="space-y-2.5">
                                <div class="flex items-center text-sm text-slate-600 group/item">
                                    <div class="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center mr-3 shrink-0 group-hover/item:bg-blue-100 transition-colors">
                                        <Mail class="w-3.5 h-3.5 text-slate-500 group-hover/item:text-blue-600" />
                                    </div>
                                    <span class="truncate font-medium">{{ user.email }}</span>
                                </div>
                                <div class="flex items-center text-sm text-slate-600 group/item">
                                    <div class="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center mr-3 shrink-0 group-hover/item:bg-emerald-100 transition-colors">
                                        <Phone class="w-3.5 h-3.5 text-slate-500 group-hover/item:text-emerald-600" />
                                    </div>
                                    <span class="truncate font-medium">{{ user.phone || 'No phone set' }}</span>
                                </div>
                            </div>

                            <!-- Branch Info -->
                            <div class="pt-4 border-t border-slate-100">
                                <div class="flex items-center text-sm">
                                    <Building2 class="w-4 h-4 mr-2 text-indigo-400 shrink-0" />
                                    <span class="font-bold text-slate-700 truncate">
                                        {{ user.branch?.name || user.primary_branch?.name || 'All Branches' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Card Footer: Actions -->
                        <div class="p-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-2">
                            <Link
                                :href="route('users.edit', user.id)"
                                class="inline-flex flex-1 justify-center items-center px-3 py-2 text-sm font-bold rounded-xl text-amber-700 bg-amber-100 hover:bg-amber-200 transition-colors shadow-sm"
                            >
                                <Pencil class="w-4 h-4 mr-2" />
                                Edit User
                            </Link>
                        </div>
                    </div>
                </div>
"@

$content = $content -replace '(?s)<div class="overflow-x-auto">.*?</table>\s*</div>', $cardsHTML

# Remove the wrapper div that was around the table (if it exists and is empty now)
$content = $content -replace '<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">\s*(<div class="grid)', '$1'
$content = $content -replace '</div>\s*</div>\s*(<!-- Empty State -->)', '</div>$1'


Set-Content -Path $file -Value $content
