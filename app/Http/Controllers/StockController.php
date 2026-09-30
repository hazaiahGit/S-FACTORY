<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StockController extends Controller
{
    /**
     * Display a listing of the stock for the current branch.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $stocks = Stock::with(['product', 'branch'])
            ->where('business_id', $user->business_id)
            ->when($user->active_branch_id, function ($q, $branchId) {
                $q->where('branch_id', $branchId);
            })
            ->when($request->search, function ($query, $search) {
                $query->whereHas('product', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            })
            ->orderBy('id', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Stock/Index', [
            'stocks' => $stocks,
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Display stock movements (Audit trail).
     */
    public function movements(Request $request): Response
    {
        $user = $request->user();

        $movements = StockMovement::with(['product', 'user', 'branch'])
            ->where('business_id', $user->business_id)
            ->when($user->active_branch_id, function ($q, $branchId) {
                $q->where('branch_id', $branchId);
            })
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('product', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%");
                    })
                        ->orWhere('movement_type', 'like', "%{$search}%")
                        ->orWhere('reference_number', 'like', "%{$search}%")
                        ->orWhere('transaction_date', 'like', "%{$search}%")
                        ->orWhere('quantity_change', 'like', "%{$search}%")
                        ->orWhere('quantity_after', 'like', "%{$search}%")
                        ->orWhere('unit_cost', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Stock/Movements', [
            'movements' => $movements,
            'filters' => $request->only(['search']),
        ]);
    }

    public function destroyMovement(Request $request, StockMovement $movement)
    {
        $user = $request->user();

        // Ensure user has admin rights
        if (! $user->hasRole('Super Admin')) {
            return back()->with('error', 'Only Super Admins can delete audit trails.');
        }

        if ($movement->business_id !== $user->business_id) {
            abort(403);
        }

        DB::transaction(function () use ($movement) {
            // Revert stock quantity
            $stock = Stock::where('product_id', $movement->product_id)
                ->where('branch_id', $movement->branch_id)
                ->first();

            if ($stock) {
                $stock->quantity -= (float) $movement->quantity_change;
                $stock->stock_value = $stock->quantity * $stock->avg_cost;
                $stock->save();
            }

            // Delete the trail record
            $movement->delete();
        });

        return back()->with('success', 'Audit trail deleted and stock reverted successfully.');
    }
}
