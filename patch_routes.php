<?php
$file = "routes/web.php";
$content = file_get_contents($file);

$inject = <<<'EOT'
    Route::get('inventory/movements', [StockController::class, 'movements'])->name('inventory.movements');
    Route::delete('inventory/movements/{movement}', [StockController::class, 'destroyMovement'])->name('inventory.movements.destroy');
EOT;

$content = str_replace("Route::get('inventory/movements', [StockController::class, 'movements'])->name('inventory.movements');", $inject, $content);
file_put_contents($file, $content);
echo "Patched web.php.\n";
?>
