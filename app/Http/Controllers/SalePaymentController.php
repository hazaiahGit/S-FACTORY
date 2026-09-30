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
        $user = $request->user();
        abort_unless($sale->business_id === $user->business_id, 403);

        if (! $user->isTenantAdmin() && ! $user->hasRole(['Super Admin', 'Admin']) && $sale->branch_id && $sale->branch_id !== $user->branch_id) {
            abort(403, 'Unauthorized to record payment for sale belonging to another branch.');
        }

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
