<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id', 'user_id', 'user_name', 'action', 'module',
        'model_type', 'model_id', 'description',
        'old_values', 'new_values', 'ip_address', 'user_agent', 'reason',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public $timestamps = true;
    const UPDATED_AT = null; // Audit logs are append-only

    public function business() { return $this->belongsTo(Business::class); }
    public function user() { return $this->belongsTo(User::class); }

    public function subject()
    {
        return $this->morphTo('subject', 'model_type', 'model_id');
    }

    public static function record(
        string $action,
        string $module,
        string $description,
        array $oldValues = [],
        array $newValues = [],
        ?int $businessId = null,
        ?Model $model = null,
        ?string $reason = null
    ): self {
        $user = auth()->user();
        return self::create([
            'business_id' => $businessId ?? $user?->business_id,
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'action' => $action,
            'module' => $module,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->getKey(),
            'description' => $description,
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'reason' => $reason,
        ]);
    }
}
