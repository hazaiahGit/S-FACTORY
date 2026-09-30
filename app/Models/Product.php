<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id', 'category_id', 'brand_id', 'unit_id', 'product_type_id', 'supplier_id',
        'name', 'slug', 'sku', 'barcode', 'image', 'images', 'description',
        'product_type', 'purchase_price', 'cost_price', 'selling_price',
        'wholesale_price', 'min_selling_price', 'min_stock', 'reorder_level',
        'opening_stock', 'track_stock', 'has_batches', 'has_expiry',
        'tax_applicable', 'tax_rate', 'is_active', 'is_featured', 'sort_order',
        'batch_number_prefix', 'notes',
    ];

    protected $casts = [
        'images' => 'array',
        'purchase_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'wholesale_price' => 'decimal:2',
        'min_selling_price' => 'decimal:2',
        'min_stock' => 'decimal:3',
        'reorder_level' => 'decimal:3',
        'opening_stock' => 'decimal:3',
        'tax_rate' => 'decimal:2',
        'track_stock' => 'boolean',
        'has_batches' => 'boolean',
        'has_expiry' => 'boolean',
        'tax_applicable' => 'boolean',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function productType(): BelongsTo
    {
        return $this->belongsTo(ProductType::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(ProductBatch::class);
    }

    public function stock(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function billOfMaterials(): HasMany
    {
        return $this->hasMany(BillOfMaterial::class);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    public function stockForBranch(int $branchId): ?Stock
    {
        return $this->stock()->where('branch_id', $branchId)->first();
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getTotalStockAttribute(): float
    {
        return (float) $this->stock()->sum('quantity');
    }

    public function getProfitMarginAttribute(): float
    {
        if ($this->cost_price <= 0) {
            return 0;
        }

        return round((($this->selling_price - $this->cost_price) / $this->cost_price) * 100, 2);
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeLowStock($query, ?int $branchId = null)
    {
        return $query->whereHas('stock', function ($q) use ($branchId) {
            $q->whereColumn('quantity', '<', 'products.min_stock');
            if ($branchId) {
                $q->where('branch_id', $branchId);
            }
        });
    }

    public function scopeByBarcode($query, string $barcode)
    {
        return $query->where('barcode', $barcode)->orWhere('sku', $barcode);
    }

    public function scopeTracked($query)
    {
        return $query->where('track_stock', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('product_type', $type);
    }
}
