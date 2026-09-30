<?php
$file = "resources/js/Layouts/AppLayout.vue";
$content = file_get_contents($file);

// Add Branch icon to imports
$content = str_replace(
    "import { Factory, Home, Menu, Bell, Settings, Search, Package, Users, Truck, ShoppingCart, Activity, FileText, ClipboardList, Target, AlertTriangle, UserCog, LogOut } from '@lucide/vue';",
    "import { Factory, Home, Menu, Bell, Settings, Search, Package, Users, Truck, ShoppingCart, Activity, FileText, ClipboardList, Target, AlertTriangle, UserCog, LogOut, Building2 } from '@lucide/vue';",
    $content
);

// Add Branches to nav items (only for super admin or admin)
$nav_add = <<<'EOT'
    { name: 'User Management', route: 'users.index', icon: UserCog, permission: 'manage users' },
    { name: 'Branches', route: 'branches.index', icon: Building2, permission: 'manage users' }, // Reusing manage users permission for now, or just allow super admin
EOT;

$content = str_replace(
    "{ name: 'User Management', route: 'users.index', icon: UserCog, permission: 'manage users' },",
    $nav_add,
    $content
);

file_put_contents($file, $content);
echo "Patched AppLayout.vue NavItems.\n";
?>
