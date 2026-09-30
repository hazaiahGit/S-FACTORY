<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id', 'name', 'code', 'phone', 'email', 'address',
        'contact_person', 'tax_number', 'payment_terms', 'credit_days',
        'credit_limit', 'opening_balance', 'current_balance', 'is_active', 'notes',
    ];

    protected $casts = [
        'credit_limit'    => 'decimal:2',
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'credit_days'     => 'integer',
        'is_active'       => 'boolean',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PurchasePayment::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(ProductBatch::class);
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getOutstandingBalanceAttribute(): float
    {
        return (float) $this->current_balance;
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithCredit($query)
    {
        return $query->where('credit_limit', '>', 0);
    }
}
