<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequest;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Services\NumberGeneratorService;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class StockTransferController extends Controller
{
    public function __construct(
        private StockService $stockService,
        private NumberGeneratorService $numberGenerator
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $activeBranchId = $user->active_branch_id;

        $query = StockTransfer::with(['fromBranch', 'toBranch', 'requestedBy', 'items.product'])
            ->where('business_id', $user->business_id);

        if ($activeBranchId) {
            $query->where(function ($q) use ($activeBranchId) {
                $q->where('from_branch_id', $activeBranchId)
                    ->orWhere('to_branch_id', $activeBranchId);
            });
        }

        $transfers = $query->latest()->paginate(15);

        return Inertia::render('StockTransfers/Index', [
            'transfers' => $transfers,
            'isTenantAdmin' => $user->isTenantAdmin() || $user->hasRole(['Super Admin', 'Admin', 'Manager']),
            'currentBranchId' => $activeBranchId ?? $user->branch_id,
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();
        $activeBranchId = $user->active_branch_id ?? $user->branch_id;

        $branches = Branch::where('business_id', $user->business_id)
            ->get(['id', 'name']);

        $products = Product::where('business_id', $user->business_id)
            ->where('is_active', true)
            ->where('track_stock', true)
            ->get(['id', 'name', 'sku']);

        return Inertia::render('StockTransfers/Create', [
            'branches' => $branches,
            'products' => $products,
            'currentBranchId' => $activeBranchId,
            'isTenantAdmin' => $user->isTenantAdmin() || $user->hasRole(['Super Admin', 'Admin', 'Manager']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'from_branch_id' => 'nullable|exists:branches,id',
            'to_branch_id' => 'required|exists:branches,id',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'auto_complete' => 'nullable|boolean',
        ]);

        $fromBranchId = (int) ($validated['from_branch_id'] ?? $user->active_branch_id ?? $user->branch_id);
        $toBranchId = (int) $validated['to_branch_id'];

        if ($fromBranchId === $toBranchId) {
            return back()->with('error', 'Source and destination branches cannot be the same.');
        }

        DB::transaction(function () use ($validated, $user, $fromBranchId, $toBranchId) {
            $transfer = StockTransfer::create([
                'business_id' => $user->business_id,
                'from_branch_id' => $fromBranchId,
                'to_branch_id' => $toBranchId,
                'requested_by' => $user->id,
                'transfer_number' => $this->numberGenerator->generateTransferNumber($user->business_id),
                'status' => 'requested',
                'requested_date' => today(),
                'notes' => $validated['notes'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                StockTransferItem::create([
                    'transfer_id' => $transfer->id,
                    'product_id' => $item['product_id'],
                    'requested_quantity' => $item['quantity'],
                ]);
            }

            if (! empty($validated['auto_complete'])) {
                // Instantly complete the transfer: dispatch and receive stock
                $transfer->update([
                    'status' => 'received',
                    'approved_by' => $user->id,
                    'dispatched_by' => $user->id,
                    'dispatched_date' => today(),
                    'received_by' => $user->id,
                    'received_date' => today(),
                ]);

                foreach ($transfer->items as $transferItem) {
                    $qty = (float) $transferItem->requested_quantity;
                    $transferItem->update([
                        'dispatched_quantity' => $qty,
                        'received_quantity' => $qty,
                    ]);

                    // Deduct from source branch
                    $this->stockService->decrease(
                        $fromBranchId,
                        $transferItem->product_id,
                        $qty,
                        'stock_transfer_out',
                        StockTransfer::class,
                        $transfer->id,
                        $transfer->transfer_number
                    );

                    // Calculate unit cost from source stock or product
                    $sourceStock = Stock::where('branch_id', $fromBranchId)
                        ->where('product_id', $transferItem->product_id)
                        ->first();
                    $unitCost = (float) ($sourceStock?->avg_cost ?? Product::find($transferItem->product_id)?->cost_price ?? 0);

                    // Add to destination branch
                    $this->stockService->increase(
                        $toBranchId,
                        $transferItem->product_id,
                        $qty,
                        $unitCost,
                        'stock_transfer_in',
                        StockTransfer::class,
                        $transfer->id,
                        $transfer->transfer_number
                    );
                }
            } else {
                ApprovalRequest::create([
                    'business_id' => $user->business_id,
                    'requester_id' => $user->id,
                    'requestable_type' => StockTransfer::class,
                    'requestable_id' => $transfer->id,
                    'action' => 'stock_transfer',
                    'status' => 'pending',
                    'reason' => 'Transfer request '.$transfer->transfer_number,
                    'notes' => $validated['notes'] ?? null,
                ]);
            }
        });

        $msg = ! empty($validated['auto_complete'])
            ? 'Stock transfer completed and stock moved immediately.'
            : 'Stock transfer requested successfully.';

        return redirect()->route('stock-transfers.index')->with('success', $msg);
    }

    public function show(Request $request, StockTransfer $stockTransfer)
    {
        abort_if($stockTransfer->business_id !== $request->user()->business_id, 403);
        $stockTransfer->load(['fromBranch', 'toBranch', 'requestedBy', 'approvedBy', 'dispatchedBy', 'receivedBy', 'items.product']);

        $user = $request->user();
        $isTenantAdmin = $user->isTenantAdmin() || $user->hasRole(['Super Admin', 'Admin', 'Manager']);

        return Inertia::render('StockTransfers/Show', [
            'transfer' => $stockTransfer,
            'currentBranchId' => $user->active_branch_id ?? $user->branch_id,
            'isTenantAdmin' => $isTenantAdmin,
        ]);
    }

    public function approve(Request $request, StockTransfer $stockTransfer)
    {
        abort_if($stockTransfer->business_id !== $request->user()->business_id, 403);

        $stockTransfer->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
        ]);

        // Also resolve the central approval request if it exists
        $approvalRequest = ApprovalRequest::where('requestable_type', StockTransfer::class)
            ->where('requestable_id', $stockTransfer->id)
            ->where('status', 'pending')
            ->first();

        if ($approvalRequest) {
            $approvalRequest->update([
                'status' => 'approved',
                'approver_id' => $request->user()->id,
                'responded_at' => now(),
            ]);
        }

        return back()->with('success', 'Transfer approved.');
    }

    public function dispatch(Request $request, StockTransfer $stockTransfer)
    {
        abort_if($stockTransfer->business_id !== $request->user()->business_id, 403);

        if ($stockTransfer->status !== 'approved') {
            return back()->with('error', 'Transfer must be approved before it can be dispatched.');
        }

        DB::transaction(function () use ($stockTransfer, $request) {
            $stockTransfer->update([
                'status' => 'dispatched',
                'dispatched_by' => $request->user()->id,
                'dispatched_date' => today(),
            ]);

            foreach ($stockTransfer->items as $item) {
                $item->update(['dispatched_quantity' => $item->requested_quantity]);

                // Reduce stock from source branch
                $this->stockService->decrease(
                    $stockTransfer->from_branch_id,
                    $item->product_id,
                    $item->requested_quantity,
                    'stock_transfer_out',
                    StockTransfer::class,
                    $stockTransfer->id,
                    $stockTransfer->transfer_number
                );
            }
        });

        return back()->with('success', 'Transfer dispatched and stock deducted from source branch.');
    }

    public function receive(Request $request, StockTransfer $stockTransfer)
    {
        abort_if($stockTransfer->business_id !== $request->user()->business_id, 403);

        DB::transaction(function () use ($stockTransfer, $request) {
            $stockTransfer->update([
                'status' => 'received',
                'received_by' => $request->user()->id,
                'received_date' => today(),
            ]);

            foreach ($stockTransfer->items as $item) {
                $item->update(['received_quantity' => $item->dispatched_quantity]);

                $sourceStock = Stock::where('branch_id', $stockTransfer->from_branch_id)
                    ->where('product_id', $item->product_id)
                    ->first();
                $unitCost = (float) ($sourceStock?->avg_cost ?? $item->product?->cost_price ?? 0);

                // Increase stock in destination branch
                $this->stockService->increase(
                    $stockTransfer->to_branch_id,
                    $item->product_id,
                    $item->dispatched_quantity,
                    $unitCost,
                    'stock_transfer_in',
                    StockTransfer::class,
                    $stockTransfer->id,
                    $stockTransfer->transfer_number
                );
            }
        });

        return back()->with('success', 'Transfer received successfully and stock added to destination branch.');
    }

    public function complete(Request $request, StockTransfer $stockTransfer)
    {
        abort_if($stockTransfer->business_id !== $request->user()->business_id, 403);

        if ($stockTransfer->status === 'received') {
            return back()->with('info', 'Transfer is already received and completed.');
        }

        DB::transaction(function () use ($stockTransfer, $request) {
            // 1. If not yet dispatched, dispatch from source branch
            if (in_array($stockTransfer->status, ['requested', 'approved'])) {
                $stockTransfer->update([
                    'approved_by' => $stockTransfer->approved_by ?? $request->user()->id,
                    'dispatched_by' => $request->user()->id,
                    'dispatched_date' => today(),
                ]);

                foreach ($stockTransfer->items as $item) {
                    $item->update(['dispatched_quantity' => $item->requested_quantity]);

                    $this->stockService->decrease(
                        $stockTransfer->from_branch_id,
                        $item->product_id,
                        $item->requested_quantity,
                        'stock_transfer_out',
                        StockTransfer::class,
                        $stockTransfer->id,
                        $stockTransfer->transfer_number
                    );
                }
            }

            // 2. Receive at destination branch
            $stockTransfer->update([
                'status' => 'received',
                'received_by' => $request->user()->id,
                'received_date' => today(),
            ]);

            foreach ($stockTransfer->items as $item) {
                $qty = (float) ($item->dispatched_quantity > 0 ? $item->dispatched_quantity : $item->requested_quantity);
                $item->update(['received_quantity' => $qty]);

                $sourceStock = Stock::where('branch_id', $stockTransfer->from_branch_id)
                    ->where('product_id', $item->product_id)
                    ->first();
                $unitCost = (float) ($sourceStock?->avg_cost ?? $item->product?->cost_price ?? 0);

                $this->stockService->increase(
                    $stockTransfer->to_branch_id,
                    $item->product_id,
                    $qty,
                    $unitCost,
                    'stock_transfer_in',
                    StockTransfer::class,
                    $stockTransfer->id,
                    $stockTransfer->transfer_number
                );
            }

            // Resolve approval request if pending
            ApprovalRequest::where('requestable_type', StockTransfer::class)
                ->where('requestable_id', $stockTransfer->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'approved',
                    'approver_id' => $request->user()->id,
                    'responded_at' => now(),
                ]);
        });

        return back()->with('success', 'Transfer completed and stock successfully added to destination branch!');
    }
}
