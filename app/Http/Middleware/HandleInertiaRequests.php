<?php

namespace App\Http\Middleware;

use App\Models\AppNotification;
use App\Models\Branch;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'business' => $user ? $user->business : null,
                'branch' => $user ? $user->branch : null,
                'is_admin' => $user ? ($user->isTenantAdmin() || $user->hasRole(['Super Admin', 'Admin'])) : false,
                'active_branch_id' => $user ? $user->active_branch_id : null,
                'all_branches' => ($user && ($user->isTenantAdmin() || $user->hasRole(['Super Admin', 'Admin']))) ? Branch::where('business_id', $user->business_id)->get(['id', 'name']) : [],
                'roles' => $user ? $user->getRoleNames() : [],
                'permissions' => $user ? $user->getAllPermissions()->pluck('name') : [],
                'notifications_unread_count' => fn () => $user ? AppNotification::forUser($user)->unreadFor($user)->count() : 0,
                'recent_notifications' => fn () => $user ? AppNotification::with('branch:id,name')
                    ->forUser($user)
                    ->latest()
                    ->limit(6)
                    ->get()
                    ->map(fn ($n) => [
                        'id' => $n->id,
                        'type' => $n->type,
                        'title' => $n->title,
                        'message' => $n->message,
                        'action_url' => $n->action_url,
                        'branch_name' => $n->branch?->name,
                        'read' => $n->isReadBy($user),
                        'time' => $n->created_at->diffForHumans(),
                    ]) : [],
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
        ];
    }
}
