<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\Request;

class SalePaymentController extends Controller
{
    public function __construct(
        protected SaleService $saleService
    ) {}

    public function store(Request $request, Sale $sale)
    {
        $businessId = $request->user()->business_id;
        abort_unless($sale->business_id === $businessId, 403);

        $maxAmount = max(0.01, (float) $sale->balance_amount);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.$maxAmount],
            'payment_method' => ['required', 'string', 'in:cash,mobile_money,bank_transfer,card,cheque,other'],
            'payment_date' => ['nullable', 'date'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $this->saleService->recordPayment($sale, $validated);

        return redirect()->back()->with('success', 'Payment recorded successfully.');
    }
}
