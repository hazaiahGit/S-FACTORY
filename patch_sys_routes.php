<?php
$web = file_get_contents('routes/web.php');

$sys_routes = <<<'EOT'
    // ─── System Administrator Area ─────────────────────────────────────
    Route::middleware(['system.admin'])->prefix('system')->name('system.')->group(function () {
        Route::resource('tenants', \App\Http\Controllers\System\TenantController::class)->except(['create', 'show', 'edit']);
        Route::put('tenants/{tenant}/subscription', [\App\Http\Controllers\System\TenantController::class, 'updateSubscription'])->name('tenants.subscription');
        Route::resource('packages', \App\Http\Controllers\System\PackageController::class)->except(['create', 'show', 'edit']);
    });
EOT;

if (strpos($web, 'System Administrator Area') === false) {
    $web = str_replace("Route::resource('targets'", $sys_routes . "\n\n    Route::resource('targets'", $web);
    file_put_contents('routes/web.php', $web);
}
echo "Added System routes.\n";
?>
