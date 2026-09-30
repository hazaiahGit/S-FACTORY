<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id', 'branch_id', 'name', 'code', 'phone', 'email', 'address',
        'customer_type', 'credit_limit', 'current_balance', 'total_purchases',
        'total_paid', 'opening_balance', 'credit_allowed', 'is_active', 'notes',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'total_purchases' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'opening_balance' => 'decimal:2',
        'credit_allowed' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function payments()
    {
        return $this->hasMany(SalePayment::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithDebt($query)
    {
        return $query->where('current_balance', '>', 0);
    }

    public function getOutstandingDebtAttribute(): float
    {
        return (float) $this->current_balance;
    }
}
