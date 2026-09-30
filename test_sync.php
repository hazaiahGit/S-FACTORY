<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$u = App\Models\User::find(2);
$u->syncPermissions([]);
echo json_encode($u->permissions->pluck('name'));
