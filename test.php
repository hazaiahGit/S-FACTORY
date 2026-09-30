<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sale = \App\Models\Sale::with('items')->find(3);
echo json_encode($sale->toArray(), JSON_PRETTY_PRINT);
