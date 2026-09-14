<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'type',
        'value',
        'max_uses',
        'used_count',
        'min_order_amount',
        'expires_at',
        'is_active',
        'applies_to',
        'plan_id',
        'created_by',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'max_uses' => 'integer',
        'used_count' => 'integer',
    ];

    /**
     * Type constants
     */
    const TYPE_PERCENTAGE = 'percentage';
    const TYPE_FIXED_AMOUNT = 'fixed_amount';

    /**
     * Applies to constants
     */
    const APPLIES_ALL = 'all';
    const APPLIES_SPECIFIC_PLAN = 'specific_plan';

    /**
     * Relationship to plan
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Relationship to creator
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if coupon is valid
     */
    public function isValid(?Plan $plan = null, float $orderAmount = 0): bool
    {
        // Check if active
        if (!$this->is_active) {
            return false;
        }

        // Check expiration
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        // Check usage limit
        if ($this->max_uses !== null && $this->used_count >= $this->max_uses) {
            return false;
        }

        // Check minimum order amount
        if ($orderAmount < $this->min_order_amount) {
            return false;
        }

        // Check plan restriction
        if ($this->applies_to === self::APPLIES_SPECIFIC_PLAN) {
            if (!$plan || $plan->id !== $this->plan_id) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calculate discount amount
     */
    public function calculateDiscount(float $amount): float
    {
        if ($this->type === self::TYPE_PERCENTAGE) {
            return round($amount * ($this->value / 100), 2);
        }

        // Fixed amount - don't exceed the order amount
        return min($this->value, $amount);
    }

    /**
     * Apply coupon (increment usage)
     */
    public function apply(): void
    {
        $this->increment('used_count');
    }

    /**
     * Get formatted discount value
     */
    public function getFormattedValueAttribute(): string
    {
        if ($this->type === self::TYPE_PERCENTAGE) {
            return $this->value . '%';
        }

        return number_format($this->value, 2, ',', '.') . ' €';
    }

    /**
     * Scope for active coupons
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope for valid coupons
     */
    public function scopeValid($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->where(function ($q) {
                $q->whereNull('max_uses')
                  ->orWhereRaw('used_count < max_uses');
            });
    }

    /**
     * Find coupon by code
     */
    public static function findByCode(string $code): ?self
    {
        return self::where('code', strtoupper($code))->first();
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($coupon) {
            // Auto uppercase code
            $coupon->code = strtoupper($coupon->code);
        });
    }
}