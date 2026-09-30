<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'price', 'duration_days',
        'max_users', 'max_branches', 'features', 'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration_days' => 'integer',
        'max_users' => 'integer',
        'max_branches' => 'integer',
        'features' => 'array',
        'is_active' => 'boolean',
    ];

    public function businesses()
    {
        return $this->hasMany(Business::class);
    }
}