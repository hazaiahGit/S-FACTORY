<?php
$file = "resources/js/Pages/Stock/Movements.vue";
$content = file_get_contents($file);

$td_inject = <<<'EOT'
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-slate-600">
                                    {{ formatCurrency(mv.unit_cost) }}
                                </td>
                                <td class="px-4 py-4 whitespace-nowrap text-right text-sm">
                                    <button v-if="$page.props.auth.roles?.includes('Super Admin')" @click="deleteMovement(mv.id)" class="text-rose-400 hover:text-rose-600 transition-colors p-1" title="Delete & Revert Stock">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </td>
                            </tr>
EOT;

$content = preg_replace('/<td class="px-6 py-4 whitespace-nowrap text-right text-sm text-slate-600">\s*\{\{ formatCurrency\(mv\.unit_cost\) \}\}\s*<\/td>\s*<\/tr>/s', $td_inject, $content);
file_put_contents($file, $content);
echo "Patched desktop table action column.\n";
?>
