<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id', 'requester_id', 'approver_id',
        'requestable_type', 'requestable_id',
        'action', 'status', 'reason', 'notes', 'rejection_reason',
        'data', 'responded_at',
    ];

    protected $casts = [
        'data' => 'array',
        'responded_at' => 'datetime',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }

    public function requestable()
    {
        return $this->morphTo('requestable', 'requestable_type', 'requestable_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
