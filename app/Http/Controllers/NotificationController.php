<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\Branch;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    /**
     * Display a listing of notifications.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $branchFilter = $request->filled('branch_id') ? (int) $request->branch_id : null;
        $typeFilter = $request->input('type');
        $statusFilter = $request->input('status', 'all'); // 'all', 'unread', 'read'

        $query = AppNotification::with(['branch', 'user'])
            ->forUser($user, $branchFilter)
            ->when($typeFilter, fn ($q) => $q->where('type', $typeFilter))
            ->when($statusFilter === 'unread', fn ($q) => $q->unreadFor($user))
            ->when($statusFilter === 'read', fn ($q) => $q->whereJsonContains('read_by', $user->id))
            ->latest();

        $notifications = $query->paginate(20)->through(function ($notification) use ($user) {
            return [
                'id' => $notification->id,
                'type' => $notification->type,
                'title' => $notification->title,
                'message' => $notification->message,
                'action_url' => $notification->action_url,
                'branch_id' => $notification->branch_id,
                'branch_name' => $notification->branch?->name,
                'is_read' => $notification->isReadBy($user),
                'created_at' => $notification->created_at->toISOString(),
                'time_ago' => $notification->created_at->diffForHumans(),
            ];
        })->withQueryString();

        $branches = $user->isTenantAdmin()
            ? Branch::where('business_id', $user->business_id)->get(['id', 'name'])
            : [];

        $unreadCount = AppNotification::forUser($user)->unreadFor($user)->count();

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
            'branches' => $branches,
            'unreadCount' => $unreadCount,
            'filters' => [
                'branch_id' => $branchFilter,
                'type' => $typeFilter,
                'status' => $statusFilter,
            ],
        ]);
    }

    /**
     * Mark a single notification as read.
     */
    public function markRead(Request $request, $id)
    {
        $user = $request->user();

        $notification = AppNotification::forUser($user)->findOrFail($id);
        $notification->markAsReadBy($user);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        if ($notification->action_url) {
            return redirect($notification->action_url);
        }

        return back();
    }

    /**
     * Mark all accessible notifications as read for current user.
     */
    public function markAllRead(Request $request)
    {
        $user = $request->user();

        $notifications = AppNotification::forUser($user)
            ->unreadFor($user)
            ->get();

        foreach ($notifications as $notification) {
            $notification->markAsReadBy($user);
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back();
    }
}
