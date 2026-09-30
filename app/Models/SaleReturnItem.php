<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SaleReturnItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'return_id', 'sale_item_id', 'product_id', 'quantity',
        'unit_price', 'unit_cost', 'total_price', 'condition', 'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function saleReturn() { return $this->belongsTo(SaleReturn::class, 'return_id'); }
    public function saleItem() { return $this->belongsTo(SaleItem::class); }
    public function product() { return $this->belongsTo(Product::class); }
}
