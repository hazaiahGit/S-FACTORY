<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ApprovalController extends Controller
{
    public function index(Request $request)
    {
        $businessId = $request->user()->business_id;
        $status = $request->input('status', 'pending');

        $query = ApprovalRequest::with(['requester', 'approver', 'requestable'])
            ->where('business_id', $businessId)
            ->latest();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $approvals = $query->paginate(20)->withQueryString();

        $counts = [
            'pending' => ApprovalRequest::where('business_id', $businessId)->where('status', 'pending')->count(),
            'approved' => ApprovalRequest::where('business_id', $businessId)->where('status', 'approved')->count(),
            'rejected' => ApprovalRequest::where('business_id', $businessId)->where('status', 'rejected')->count(),
        ];

        return Inertia::render('Approvals/Index', [
            'approvals' => $approvals,
            'counts' => $counts,
            'filters' => ['status' => $status]
        ]);
    }

    public function approve(Request $request, ApprovalRequest $approvalRequest)
    {
        abort_if($approvalRequest->business_id !== $request->user()->business_id, 403);
        abort_if($approvalRequest->status !== 'pending', 400, 'Request is not pending.');

        $approvalRequest->update([
            'status' => 'approved',
            'approver_id' => $request->user()->id,
            'responded_at' => now(),
            'notes' => $request->input('notes'),
        ]);

        $model = $approvalRequest->requestable;
        if ($model && method_exists($model, 'markAsApproved')) {
            $model->markAsApproved();
        } elseif ($model && in_array('status', $model->getFillable())) {
            $model->update(['status' => 'approved']);
        }

        return back()->with('success', 'Request approved successfully.');
    }

    public function reject(Request $request, ApprovalRequest $approvalRequest)
    {
        abort_if($approvalRequest->business_id !== $request->user()->business_id, 403);
        abort_if($approvalRequest->status !== 'pending', 400, 'Request is not pending.');

        $request->validate([
            'rejection_reason' => 'required|string|max:500'
        ]);

        $approvalRequest->update([
            'status' => 'rejected',
            'approver_id' => $request->user()->id,
            'responded_at' => now(),
            'rejection_reason' => $request->rejection_reason,
        ]);

        $model = $approvalRequest->requestable;
        if ($model && method_exists($model, 'markAsRejected')) {
            $model->markAsRejected($request->rejection_reason);
        } elseif ($model && in_array('status', $model->getFillable())) {
            $model->update(['status' => 'rejected']);
        }

        return back()->with('success', 'Request rejected.');
    }
}
