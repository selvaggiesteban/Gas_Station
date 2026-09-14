<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_id',
        'product_id',
        'slug',
        'starts_at',
        'ends_at',
        'status',
        'is_blocked',
        'payment_id',
        'payment_token',
        'payment_provider',
        'stripe_subscription_id',
        'renewal_count',
        'last_renewed_at',
        'payment_failure_count',
        'last_payment_error',
        'last_payment_id',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'last_renewed_at' => 'datetime',
        'renewal_count' => 'integer',
        'payment_failure_count' => 'integer',
        'is_blocked' => 'boolean',
    ];

    /**
     * Status constants
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_EXPIRED = 'expired';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_PAYMENT_FAILED = 'payment_failed';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_PENDING = 'pending';

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Check if subscription is currently active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->ends_at
            && $this->ends_at->isFuture();
    }

    /**
     * Check if subscription has expired
     */
    public function isExpired(): bool
    {
        return $this->ends_at === null || $this->ends_at->isPast();
    }

    /**
     * Check if subscription can be renewed
     */
    public function canRenew(): bool
    {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_PAYMENT_FAILED])
            && $this->payment_token !== null
            && $this->payment_failure_count < 3;
    }

    /**
     * Cancel the subscription
     */
    public function cancel(): bool
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'payment_token' => null, // Clear token for security
        ]);

        Log::info("Subscription #{$this->id} cancelled by user #{$this->user_id}");

        return true;
    }

    /**
     * Renew the subscription manually
     */
    public function renew(): bool
    {
        if (!$this->canRenew()) {
            return false;
        }

        try {
            $this->update([
                'starts_at' => now(),
                'ends_at' => now()->addDays($this->plan->duration_days),
                'status' => self::STATUS_ACTIVE,
                'renewal_count' => ($this->renewal_count ?? 0) + 1,
                'last_renewed_at' => now(),
                'payment_failure_count' => 0, // Reset failure count
                'last_payment_error' => null,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Manual renewal failed for subscription #{$this->id}: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Get remaining days
     */
    public function getRemainingDays(): ?int
    {
        if (!$this->ends_at) {
            return null;
        }

        return now()->diffInDays($this->ends_at, false);
    }

    /**
     * Check if subscription is about to expire (within 7 days)
     */
    public function isAboutToExpire(): bool
    {
        if (!$this->ends_at) {
            return false;
        }

        return $this->ends_at->diffInDays(now()) <= 7
            && $this->ends_at->isFuture();
    }

    /**
     * Scope for active subscriptions
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('ends_at', '>', now());
    }

    /**
     * Scope for expired subscriptions
     */
    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('ends_at', '<', now())
              ->orWhereNull('ends_at');
        });
    }

    /**
     * Scope for renewable subscriptions
     */
    public function scopeRenewable($query)
    {
        return $query->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_PAYMENT_FAILED])
            ->whereNotNull('payment_token')
            ->where(function ($q) {
                $q->whereNull('payment_failure_count')
                  ->orWhere('payment_failure_count', '<', 3);
            });
    }
}
