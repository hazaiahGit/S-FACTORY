<?php
require __DIR__."/vendor/autoload.php";
$app = require_once __DIR__."/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$products = App\Models\Product::whereNotNull("image")->get();
foreach ($products as $p) {
    if (!str_starts_with($p->image, "uploads/")) {
        $p->image = "uploads/" . $p->image;
        $p->save();
        echo "Updated image path for product: {$p->name}\n";
    }
}

