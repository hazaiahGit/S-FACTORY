<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id', 'branch_id', 'product_id', 'supplier_id',
        'batch_number', 'lot_number', 'purchase_date', 'expiry_date',
        'quantity_received', 'quantity_remaining', 'unit_cost', 'total_cost',
        'reference', 'notes',
    ];

    protected $casts = [
        'quantity_received' => 'decimal:3',
        'quantity_remaining' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'purchase_date' => 'date',
        'expiry_date' => 'date',
    ];

    public function business() { return $this->belongsTo(Business::class); }
    public function branch() { return $this->belongsTo(Branch::class); }
    public function product() { return $this->belongsTo(Product::class); }
    public function supplier() { return $this->belongsTo(Supplier::class); }

    public function scopeActive($query)
    {
        return $query->where('quantity_remaining', '>', 0);
    }
}
