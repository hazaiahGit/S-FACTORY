<?php
$sale = \App\Models\Sale::find(3);
if ($sale) {
    // Revert it temporarily so the state machine detects a change
    $sale->update(['status' => 'on_hold']);
    
    // Now push it through the new proper state machine
    $service = app(\App\Services\SaleService::class);
    $service->updateSaleStatus($sale, 'paid');
    
    echo "Sale 3 fixed via state machine.\n";
}
?>
