<?php
$file = "resources/js/Layouts/AppLayout.vue";
$content = file_get_contents($file);

$branch_filter = <<<'EOT'
                    <!-- Branch Filter (Admins Only) -->
                    <div v-if="$page.props.auth.all_branches && $page.props.auth.all_branches.length > 0" class="hidden sm:flex items-center bg-slate-50 border border-slate-200 rounded-lg px-2 py-1 relative">
                        <Building2 class="w-4 h-4 text-slate-400 mr-2" />
                        <select 
                            @change="e => { router.post(route('active-branch.update'), { branch_id: e.target.value }, { preserveScroll: true }) }"
                            :value="$page.props.auth.active_branch_id"
                            class="text-sm bg-transparent border-none focus:ring-0 text-slate-700 font-medium py-1 pr-8 pl-0 cursor-pointer w-40 truncate"
                        >
                            <option v-for="branch in $page.props.auth.all_branches" :key="branch.id" :value="branch.id">
                                {{ branch.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Notifications -->
EOT;

$content = str_replace("<!-- Notifications -->", $branch_filter, $content);

file_put_contents($file, $content);
echo "Patched AppLayout.vue branch filter.\n";
?>
