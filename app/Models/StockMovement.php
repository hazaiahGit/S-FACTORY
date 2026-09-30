<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id', 'branch_id', 'product_id', 'user_id', 'batch_id',
        'movement_type', 'reference_type', 'reference_id', 'reference_number',
        'quantity_before', 'quantity_change', 'quantity_after',
        'unit_cost', 'total_cost', 'notes', 'transaction_date',
    ];

    protected $casts = [
        'quantity_before' => 'decimal:3',
        'quantity_change' => 'decimal:3',
        'quantity_after' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    public function business() { return $this->belongsTo(Business::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function product() { return $this->belongsTo(Product::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function batch() { return $this->belongsTo(ProductBatch::class, 'batch_id'); }

    public function reference()
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }

    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId)->orderBy('created_at', 'desc');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('movement_type', $type);
    }
}
