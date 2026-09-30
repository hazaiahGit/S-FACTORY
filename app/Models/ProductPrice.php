<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'price_type', 'name', 'price',
        'min_quantity', 'valid_from', 'valid_to', 'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'min_quantity' => 'decimal:3',
        'valid_from' => 'date',
        'valid_to' => 'date',
        'is_active' => 'boolean',
    ];

    public function product() { return $this->belongsTo(Product::class); }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
