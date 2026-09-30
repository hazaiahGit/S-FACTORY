<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $business = \App\Models\Business::first();
    $sales = \App\Models\Sale::with(['customer'])->take(1)->get();
    
    $summary = [
        'total_revenue' => 1000,
        'total_collected' => 500,
        'total_outstanding' => 500,
        'total_transactions' => 1
    ];
    
    $filters = [
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-30',
        'status' => ''
    ];
    
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.sales-pdf', [
        'business' => $business,
        'sales' => $sales,
        'summary' => $summary,
        'filters' => $filters
    ]);
    
    $content = $pdf->output();
    echo "PDF length: " . strlen($content) . " bytes\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
