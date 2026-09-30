<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\StockTransfer;
use App\Models\ApprovalRequest;

$transfers = StockTransfer::where('status', 'requested')->get();
$count = 0;

foreach($transfers as $transfer) {
    $exists = ApprovalRequest::where('requestable_type', StockTransfer::class)
        ->where('requestable_id', $transfer->id)
        ->exists();
        
    if (!$exists) {
        ApprovalRequest::create([
            'business_id' => $transfer->business_id,
            'requester_id' => $transfer->requested_by,
            'requestable_type' => StockTransfer::class,
            'requestable_id' => $transfer->id,
            'action' => 'stock_transfer',
            'status' => 'pending',
            'reason' => 'Transfer request ' . $transfer->transfer_number,
            'notes' => $transfer->notes,
        ]);
        $count++;
    }
}
echo "Created $count missing approval requests.\n";
