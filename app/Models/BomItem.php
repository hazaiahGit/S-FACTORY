<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BomItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'bom_id', 'product_id', 'item_type', 'description',
        'quantity', 'unit_id', 'unit_cost', 'total_cost', 'is_optional',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'is_optional' => 'boolean',
    ];

    public function bom() { return $this->belongsTo(BillOfMaterial::class, 'bom_id'); }
    public function product() { return $this->belongsTo(Product::class); }
    public function unit() { return $this->belongsTo(Unit::class); }
}
