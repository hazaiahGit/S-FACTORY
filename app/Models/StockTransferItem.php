<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransferItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'transfer_id',
        'product_id',
        'requested_quantity',
        'dispatched_quantity',
        'received_quantity',
        'unit_cost',
        'notes',
    ];

    protected $casts = [
        'requested_quantity' => 'decimal:3',
        'dispatched_quantity' => 'decimal:3',
        'received_quantity' => 'decimal:3',
        'unit_cost' => 'decimal:2',
    ];

    public function transfer()
    {
        return $this->belongsTo(StockTransfer::class, 'transfer_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
