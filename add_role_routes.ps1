$file = "routes/web.php"
$content = Get-Content $file -Raw

if ($content -notmatch "RoleController") {
    $content = $content -replace "use App\\Http\\Controllers\\UserController;", "use App\Http\Controllers\UserController;`nuse App\Http\Controllers\RoleController;"
    $content = $content -replace "Route::resource\('users', UserController::class\);", "Route::resource('users', UserController::class);`n    Route::resource('roles', RoleController::class);"
    Set-Content -Path $file -Value $content
}
