<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id', 'branch_id', 'bom_id', 'product_id', 'user_id', 'approved_by',
        'production_number', 'status', 'planned_quantity', 'actual_quantity', 'waste_quantity',
        'planned_date', 'start_date', 'completion_date',
        'total_material_cost', 'total_labour_cost', 'total_overhead_cost',
        'total_production_cost', 'unit_cost', 'notes',
    ];

    protected $casts = [
        'planned_quantity' => 'decimal:3',
        'actual_quantity' => 'decimal:3',
        'waste_quantity' => 'decimal:3',
        'total_material_cost' => 'decimal:2',
        'total_labour_cost' => 'decimal:2',
        'total_overhead_cost' => 'decimal:2',
        'total_production_cost' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'planned_date' => 'date',
        'start_date' => 'date',
        'completion_date' => 'date',
    ];

    public function business() { return $this->belongsTo(Business::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function bom() { return $this->belongsTo(BillOfMaterial::class, 'bom_id'); }
    public function product() { return $this->belongsTo(Product::class); }
    public function user() { return $this->belongsTo(User::class); }
    public function approvedBy() { return $this->belongsTo(User::class, 'approved_by'); }
    public function materials() { return $this->hasMany(ProductionMaterial::class); }

    public function calculateUnitCost(): float
    {
        $qty = (float)($this->actual_quantity ?? $this->planned_quantity);
        if ($qty <= 0) return 0;
        return round((float)$this->total_production_cost / $qty, 2);
    }

    public function scopeCompleted($query) { return $query->where('status', 'completed'); }
    public function scopeInProgress($query) { return $query->where('status', 'in_progress'); }
}
