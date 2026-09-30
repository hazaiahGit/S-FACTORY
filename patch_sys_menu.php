<?php
$file = "resources/js/Layouts/AppLayout.vue";
$content = file_get_contents($file);

$sys_menu = <<<'EOT'
            <!-- System Management (System Admins Only) -->
            <div v-if="$page.props.auth.user && $page.props.auth.user.is_system_admin" class="px-3 mt-4 mb-2">
                <div :class="['text-xs font-bold text-slate-500 uppercase tracking-wider pl-3', !sidebarOpen ? 'lg:hidden' : '']">
                    System Admin
                </div>
                <Link
                    :href="route('system.tenants.index')"
                    @click="window?.innerWidth < 1024 ? sidebarOpen = false : null"
                    :class="[
                        route().current('system.tenants.*') || route().current('system.packages.*')
                            ? 'bg-rose-500/10 text-rose-500'
                            : 'hover:bg-slate-800 hover:text-white',
                        'group flex items-center px-3 py-2.5 text-sm font-medium rounded-lg transition-colors mt-2 text-slate-400'
                    ]"
                    :title="!sidebarOpen ? 'System Tenants' : ''"
                >
                    <ServerCrash :class="[
                            route().current('system.tenants.*') || route().current('system.packages.*') ? 'text-rose-500' : 'text-slate-400 group-hover:text-white',
                            'flex-shrink-0 h-5 w-5'
                        ]" />
                    <span :class="['ml-3', !sidebarOpen ? 'lg:hidden' : '']">Tenants & Packages</span>
                </Link>
            </div>

            <!-- Bottom settings -->
EOT;

$content = str_replace(
    "import { Factory, Home, Menu, Bell, Settings, Search, Package, Users, Truck, ShoppingCart, Activity, FileText, ClipboardList, Target, AlertTriangle, UserCog, LogOut, Building2 } from '@lucide/vue';",
    "import { Factory, Home, Menu, Bell, Settings, Search, Package, Users, Truck, ShoppingCart, Activity, FileText, ClipboardList, Target, AlertTriangle, UserCog, LogOut, Building2, ServerCrash } from '@lucide/vue';",
    $content
);

$content = str_replace("<!-- Bottom settings -->", $sys_menu, $content);
file_put_contents($file, $content);
echo "Patched AppLayout menu.\n";
?>
