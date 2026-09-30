<?php
$file = "resources/js/Pages/Targets/Index.vue";
$content = file_get_contents($file);

// Import Edit2 and Trash2
$content = str_replace(
    "import { Target, Plus, ChevronRight, Activity, ShoppingCart, TrendingUp, Users, Package, AlertCircle, Clock } from '@lucide/vue';",
    "import { Target, Plus, ChevronRight, Activity, ShoppingCart, TrendingUp, Users, Package, AlertCircle, Clock, Edit2, Trash2 } from '@lucide/vue';",
    $content
);

// Add deleteTarget function
$delete_method = <<<'EOT'
const deleteTarget = (id) => {
    if (confirm('Are you sure you want to delete this target?')) {
        router.delete(route('targets.destroy', id), {
            preserveScroll: true
        });
    }
};

const formatNumber = (value, unit) => {
EOT;

$content = str_replace(
    "const formatNumber = (value, unit) => {",
    $delete_method,
    $content
);

// Replace card footer
$old_footer = <<<'EOT'
                    <!-- Footer Actions -->
                    <div class="px-6 py-3 bg-slate-50 border-t border-slate-100 flex justify-between items-center">
                        <span class="text-xs font-medium text-slate-500">
                            Required pace: {{ formatNumber(target.progress.daily_required, target.measurement_unit) }} / day
                        </span>
                        <Link :href="route('targets.show', target.id)" class="text-amber-600 hover:text-amber-700 p-1">
                            <ChevronRight class="w-5 h-5" />
                        </Link>
                    </div>
EOT;

$new_footer = <<<'EOT'
                    <!-- Footer Actions -->
                    <div class="px-6 py-3 bg-slate-50 border-t border-slate-100 flex justify-between items-center">
                        <span class="text-xs font-medium text-slate-500 truncate mr-2" title="Required Pace">
                            Req: {{ formatNumber(target.progress.daily_required, target.measurement_unit) }}/day
                        </span>
                        <div class="flex items-center space-x-1 shrink-0">
                            <Link :href="route('targets.edit', target.id)" class="text-slate-400 hover:text-indigo-600 p-1.5 rounded hover:bg-indigo-50 transition-colors" title="Edit">
                                <Edit2 class="w-4 h-4" />
                            </Link>
                            <button @click="deleteTarget(target.id)" class="text-slate-400 hover:text-rose-600 p-1.5 rounded hover:bg-rose-50 transition-colors" title="Delete">
                                <Trash2 class="w-4 h-4" />
                            </button>
                            <Link :href="route('targets.show', target.id)" class="text-amber-500 hover:text-amber-700 p-1.5 rounded hover:bg-amber-50 transition-colors ml-1" title="View Details">
                                <ChevronRight class="w-4 h-4" />
                            </Link>
                        </div>
                    </div>
EOT;

$content = str_replace($old_footer, $new_footer, $content);
file_put_contents($file, $content);
echo "Patched Targets/Index.vue.\n";
?>
