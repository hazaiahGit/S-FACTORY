<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'business_id',
        'is_system_admin',
        'branch_id',
        'phone',
        'avatar',
        'employee_id',
        'job_title',
        'is_active',
        'last_login_at',
        'last_login_ip',
        'financial_visibility',
        'dashboard_widgets',
        'preferred_language',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'is_system_admin' => 'boolean',
            'financial_visibility' => 'array',
            'dashboard_widgets' => 'array',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function primaryBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'user_branches')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    // ─── Scopes ───────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id',
            'is_system_admin', $businessId);
    }

    // ─── Financial Visibility (driven by Spatie permissions) ─────────────

    public function canSeeProfit(): bool
    {
        if ($this->hasRole('Super Admin')) {
            return true;
        }

        return $this->hasPermissionTo('view profit');
    }

    public function canSeeStockValue(): bool
    {
        if ($this->hasRole('Super Admin')) {
            return true;
        }

        return $this->hasPermissionTo('view stock value');
    }

    public function canSeeReport(): bool
    {
        if ($this->hasRole('Super Admin')) {
            return true;
        }

        return $this->hasPermissionTo('view report');
    }

    public function canSeeRevenue(): bool
    {
        // Revenue (total sales) is visible to anyone who can view sales
        if ($this->hasRole('Super Admin')) {
            return true;
        }

        return $this->hasPermissionTo('view sales');
    }

    public function canSeeCost(): bool
    {
        return $this->canSeeStockValue();
    }

    public function canSeeExpenses(): bool
    {
        if ($this->hasRole('Super Admin')) {
            return true;
        }

        return $this->hasPermissionTo('view profit');
    }

    public function canSeeCustomerDebt(): bool
    {
        return $this->canSeeRevenue();
    }

    public function canSeeSupplierDebt(): bool
    {
        return $this->canSeeRevenue();
    }

    // ─── Helpers ──────────────────────────────────────────────────────

    public function isTenantAdmin(): bool
    {
        if (! $this->business_id) {
            return false;
        }

        return $this->hasRole('Super Admin')
            || $this->hasRole('Admin')
            || $this->can('manage settings')
            || $this->can('manage users');
    }

    public function getActiveBranchIdAttribute()
    {
        if ($this->isTenantAdmin()) {
            if (session()->has('active_branch_id')) {
                $val = session('active_branch_id');

                return ($val === 'all' || $val === null || $val === '') ? null : (int) $val;
            }

            return $this->branch_id;
        }

        return $this->branch_id;
    }

    public function recordLogin(string $ip): void
    {
        $this->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ]);
    }

    public function getAvatarUrlAttribute(): ?string
    {
        if ($this->avatar) {
            return asset('storage/'.$this->avatar);
        }

        return null;
    }
}
