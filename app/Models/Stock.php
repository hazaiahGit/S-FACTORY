<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;

    protected $table = 'stock';

    protected $fillable = [
        'business_id', 'branch_id', 'product_id',
        'quantity', 'reserved_quantity', 'damaged_quantity',
        'avg_cost', 'stock_value',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'reserved_quantity' => 'decimal:3',
        'damaged_quantity' => 'decimal:3',
        'avg_cost' => 'decimal:2',
        'stock_value' => 'decimal:2',
    ];

    public function business() { return $this->belongsTo(Business::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function product() { return $this->belongsTo(Product::class); }

    public function getAvailableQuantityAttribute(): float
    {
        return max(0, (float)$this->quantity - (float)$this->reserved_quantity);
    }

    public function scopeForBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }
}
