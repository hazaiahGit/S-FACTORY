<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id', 'product_id', 'batch_id', 'quantity', 'received_quantity',
        'unit_cost', 'landed_unit_cost', 'discount_percent', 'discount_amount',
        'tax_percent', 'tax_amount', 'total_cost', 'batch_number', 'expiry_date', 'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'received_quantity' => 'decimal:3',
        'unit_cost' => 'decimal:2',
        'landed_unit_cost' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_percent' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    public function purchase() { return $this->belongsTo(Purchase::class); }
    public function product() { return $this->belongsTo(Product::class); }
    public function batch() { return $this->belongsTo(ProductBatch::class, 'batch_id'); }
}
