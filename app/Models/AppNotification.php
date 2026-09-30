<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'branch_id',
        'user_id',
        'type',
        'title',
        'message',
        'action_url',
        'read_by',
    ];

    protected $casts = [
        'read_by' => 'array',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope notifications accessible by the given user.
     * Tenant admin sees all notifications across the business.
     * Branch staff only see their branch notifications or global business notices.
     */
    public function scopeForUser(Builder $query, User $user, ?int $explicitBranchId = null): Builder
    {
        $query->where('business_id', $user->business_id);

        if ($user->isTenantAdmin()) {
            if ($explicitBranchId) {
                $query->where(function ($q) use ($explicitBranchId) {
                    $q->where('branch_id', $explicitBranchId)
                        ->orWhereNull('branch_id');
                });
            }

            return $query;
        }

        // Regular staff: only active branch or business-wide announcements
        $branchId = $user->active_branch_id ?: $user->branch_id;

        return $query->where(function ($q) use ($branchId) {
            $q->where('branch_id', $branchId)
                ->orWhereNull('branch_id');
        })->where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
                ->orWhereNull('user_id');
        });
    }

    /**
     * Scope notifications that are unread for the given user.
     */
    public function scopeUnreadFor(Builder $query, User $user): Builder
    {
        return $query->where(function ($q) use ($user) {
            $q->whereNull('read_by')
                ->orWhereJsonDoesntContain('read_by', $user->id);
        });
    }

    /**
     * Determine if this notification was read by the given user.
     */
    public function isReadBy(User $user): bool
    {
        $readBy = $this->read_by ?? [];

        return in_array($user->id, $readBy);
    }

    /**
     * Mark notification as read by the given user.
     */
    public function markAsReadBy(User $user): void
    {
        $readBy = $this->read_by ?? [];

        if (! in_array($user->id, $readBy)) {
            $readBy[] = $user->id;
            $this->read_by = $readBy;
            $this->save();
        }
    }
}
