<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'name',
        'code',
        'description',
        'is_sold',
        'is_purchased',
        'is_manufactured',
        'track_stock',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'is_sold' => 'boolean',
        'is_purchased' => 'boolean',
        'is_manufactured' => 'boolean',
        'track_stock' => 'boolean',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
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

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForSale($query)
    {
        return $query->where('is_sold', true);
    }

    public function scopeForPurchase($query)
    {
        return $query->where('is_purchased', true);
    }

    public function scopeManufacturable($query)
    {
        return $query->where('is_manufactured', true);
    }
}
