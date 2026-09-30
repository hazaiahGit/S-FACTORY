<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequest;
use App\Models\Branch;
use App\Models\Product;
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

        $transfers = StockTransfer::with(['fromBranch', 'toBranch', 'requestedBy', 'items.product'])
            ->where('business_id', $user->business_id)
            ->where(function ($query) use ($user) {
                $query->where('from_branch_id', $user->branch_id)
                    ->orWhere('to_branch_id', $user->branch_id);
            })
            ->latest()
            ->paginate(15);

        return Inertia::render('StockTransfers/Index', [
            'transfers' => $transfers,
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $request->user();

        $branches = Branch::where('business_id', $user->business_id)
            ->where('id', '!=', $user->branch_id)
            ->get(['id', 'name']);

        $products = Product::where('business_id', $user->business_id)
            ->where('is_active', true)
            ->where('track_stock', true)
            ->get(['id', 'name', 'sku']);

        return Inertia::render('StockTransfers/Create', [
            'branches' => $branches,
            'products' => $products,
            'currentBranchId' => $user->branch_id,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'to_branch_id' => 'required|exists:branches,id',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        if ((int) $validated['to_branch_id'] === $user->branch_id) {
            return back()->with('error', 'Cannot transfer to the same branch.');
        }

        DB::transaction(function () use ($validated, $user) {
            $transfer = StockTransfer::create([
                'business_id' => $user->business_id,
                'from_branch_id' => $user->branch_id,
                'to_branch_id' => $validated['to_branch_id'],
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
        });

        return redirect()->route('stock-transfers.index')->with('success', 'Stock transfer requested successfully.');
    }

    public function show(Request $request, StockTransfer $stockTransfer)
    {
        abort_if($stockTransfer->business_id !== $request->user()->business_id, 403);
        $stockTransfer->load(['fromBranch', 'toBranch', 'requestedBy', 'approvedBy', 'dispatchedBy', 'receivedBy', 'items.product']);

        return Inertia::render('StockTransfers/Show', [
            'transfer' => $stockTransfer,
            'currentBranchId' => $request->user()->branch_id,
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

        return back()->with('success', 'Transfer dispatched.');
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

                // Increase stock in destination branch
                $this->stockService->increase(
                    $stockTransfer->to_branch_id,
                    $item->product_id,
                    $item->dispatched_quantity,
                    0, // Using 0 unit cost for transfer for simplicity, true WAC handles this better but this works for now
                    'stock_transfer_in',
                    StockTransfer::class,
                    $stockTransfer->id,
                    $stockTransfer->transfer_number
                );
            }
        });

        return back()->with('success', 'Transfer received successfully.');
    }
}
