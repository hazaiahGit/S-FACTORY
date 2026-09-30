<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sale extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id', 'branch_id', 'customer_id', 'user_id', 'approved_by',
        'sale_number', 'sale_type', 'status', 'payment_status', 'fulfillment_status',
        'transaction_date', 'due_date',
        'subtotal', 'discount_percent', 'discount_amount', 'tax_amount',
        'total_amount', 'paid_amount', 'balance_amount', 'cogs', 'gross_profit',
        'notes', 'delete_reason', 'valid_until',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'cogs' => 'decimal:2',
        'gross_profit' => 'decimal:2',
        'transaction_date' => 'date',
        'due_date' => 'date',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function payments()
    {
        return $this->hasMany(SalePayment::class);
    }

    public function returns()
    {
        return $this->hasMany(SaleReturn::class);
    }

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeCredit($query)
    {
        return $query->where('payment_status', 'credit');
    }

    public function scopeByBranch($query, int $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeForPeriod($query, $from, $to)
    {
        return $query->whereBetween('transaction_date', [$from, $to]);
    }

    public function scopeSales($query)
    {
        return $query->where('sale_type', 'sale');
    }

    public function scopeQuotations($query)
    {
        return $query->where('sale_type', 'quotation');
    }
}
