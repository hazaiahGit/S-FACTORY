<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductionMaterial extends Model
{
    use HasFactory;

    protected $fillable = [
        'production_order_id', 'product_id', 'item_type', 'description',
        'planned_quantity', 'actual_quantity', 'unit_cost', 'total_cost', 'notes',
    ];

    protected $casts = [
        'planned_quantity' => 'decimal:3',
        'actual_quantity' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    public function productionOrder() { return $this->belongsTo(ProductionOrder::class); }
    public function product() { return $this->belongsTo(Product::class); }
}
