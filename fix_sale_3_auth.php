<?php
auth()->loginUsingId(1);
$sale = \App\Models\Sale::find(3);
if ($sale) {
    $sale->update(['status' => 'on_hold']);
    $service = app(\App\Services\SaleService::class);
    $service->updateSaleStatus($sale, 'paid');
    echo "Sale 3 fixed via state machine.\n";
}
?>
