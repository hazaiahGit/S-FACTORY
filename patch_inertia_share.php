<?php
$file = "app/Http/Middleware/HandleInertiaRequests.php";
$content = file_get_contents($file);

$old_share = <<<'EOT'
            'auth' => [
                'user' => $user,
                'business' => $user ? $user->business : null,
                'branch' => $user ? $user->branch : null,
                'roles' => $user ? $user->getRoleNames() : [],
                'permissions' => $user ? $user->getAllPermissions()->pluck('name') : [],
            ],
EOT;

$new_share = <<<'EOT'
            'auth' => [
                'user' => $user,
                'business' => $user ? $user->business : null,
                'branch' => $user ? $user->branch : null,
                'active_branch_id' => $user ? clone $user->active_branch_id : null,
                'all_branches' => ($user && ($user->hasRole('Super Admin') || $user->hasRole('Admin'))) ? \App\Models\Branch::where('business_id', $user->business_id)->get(['id', 'name']) : [],
                'roles' => $user ? $user->getRoleNames() : [],
                'permissions' => $user ? $user->getAllPermissions()->pluck('name') : [],
            ],
EOT;

$content = str_replace($old_share, $new_share, $content);
file_put_contents($file, $content);
echo "Patched HandleInertiaRequests.\n";
?>
