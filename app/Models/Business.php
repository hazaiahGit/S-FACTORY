<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Business extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'code', 'logo', 'address', 'phone', 'email', 'website',
        'tax_number', 'business_type', 'currency', 'currency_symbol', 'locale',
        'timezone', 'costing_method', 'tax_enabled', 'tax_inclusive', 'tax_rate',
        'invoice_prefix', 'po_prefix', 'receipt_prefix', 'batch_prefix',
        'negative_stock_allowed', 'require_delete_reason',
        'approval_required_for_adjustments', 'settings', 'is_active',
        'subscription_package_id', 'subscription_ends_at', 'subscription_status',
    ];

    protected $casts = [
        'tax_enabled' => 'boolean',
        'tax_inclusive' => 'boolean',
        'negative_stock_allowed' => 'boolean',
        'require_delete_reason' => 'boolean',
        'approval_required_for_adjustments' => 'boolean',
        'is_active' => 'boolean',
        'is_main' => 'boolean',
        'tax_rate' => 'decimal:2',
        'settings' => 'array',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function productTypes(): HasMany
    {
        return $this->hasMany(ProductType::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function subscriptionPackage()
    {
        return $this->belongsTo(SubscriptionPackage::class);
    }

    public function mainBranch(): ?Branch
    {
        return $this->branches()->where('is_main', true)->first();
    }

    public function getSetting(string $key, mixed $default = null): mixed
    {
        return data_get($this->settings, $key, $default);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
