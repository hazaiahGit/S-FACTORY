<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BillOfMaterial extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'bill_of_materials';

    protected $fillable = [
        'business_id', 'product_id', 'name', 'version', 'expected_output',
        'output_unit_id', 'description', 'is_active', 'is_default',
    ];

    protected $casts = [
        'expected_output' => 'decimal:3',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function outputUnit()
    {
        return $this->belongsTo(Unit::class, 'output_unit_id');
    }

    public function items()
    {
        return $this->hasMany(BomItem::class, 'bom_id');
    }

    public function productionOrders()
    {
        return $this->hasMany(ProductionOrder::class, 'bom_id');
    }

    public function calculateTotalCost(): float
    {
        return (float) $this->items()->sum('total_cost');
    }

    public function calculateUnitCost(): float
    {
        if ($this->expected_output <= 0) {
            return 0;
        }

        return $this->calculateTotalCost() / (float) $this->expected_output;
    }
}
