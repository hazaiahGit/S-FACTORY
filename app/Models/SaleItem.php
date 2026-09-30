<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id', 'product_id', 'batch_id', 'quantity', 'collected_quantity',
        'returned_quantity', 'unit_price', 'unit_cost', 'price_type',
        'discount_percent', 'discount_amount', 'tax_percent', 'tax_amount',
        'total_price', 'line_cogs', 'line_profit', 'fulfillment_status', 'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'collected_quantity' => 'decimal:3',
        'returned_quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_percent' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_price' => 'decimal:2',
        'line_cogs' => 'decimal:2',
        'line_profit' => 'decimal:2',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function batch()
    {
        return $this->belongsTo(ProductBatch::class, 'batch_id');
    }
}
