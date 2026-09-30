<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalePayment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sale_id', 'business_id', 'customer_id', 'user_id',
        'payment_number', 'payment_method', 'amount', 'payment_date', 'reference', 'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function sale() { return $this->belongsTo(Sale::class); }
    public function business() { return $this->belongsTo(Business::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function user() { return $this->belongsTo(User::class); }
}
