<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_id',
        'name',
        'slug',
        'stripe_product_id',
        'stripe_price_id',
        'description',
        'type',
        'price',
        'duration_days',
        'duration_months',
        'is_recurring',
        'is_active',
        'features',
        'display_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'duration_days' => 'integer',
        'duration_months' => 'integer',
        'is_recurring' => 'boolean',
        'is_active' => 'boolean',
        'features' => 'array',
        'display_order' => 'integer',
    ];

    /**
     * Type constants
     */
    const TYPE_MONTHLY = 'monthly';
    const TYPE_QUARTERLY = 'quarterly';
    const TYPE_YEARLY = 'yearly';
    const TYPE_PACK = 'pack';

    /**
     * Relationship to course
     */
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 2, ',', '.') . ' €';
    }

    /**
     * Get monthly price (for comparison)
     */
    public function getMonthlyPriceAttribute(): float
    {
        return $this->duration_days > 0
            ? round($this->price / ($this->duration_days / 30), 2)
            : $this->price;
    }

    /**
     * Check if this is the best value
     */
    public function isBestValue(): bool
    {
        if (!$this->course) {
            return false;
        }

        $lowestMonthly = $this->course->products
            ->where('is_active', true)
            ->min('monthly_price');

        return $this->monthly_price == $lowestMonthly;
    }

    /**
     * Get savings compared to monthly
     */
    public function getSavingsPercent(): ?int
    {
        $monthlyProduct = $this->course->products
            ->where('type', self::TYPE_MONTHLY)
            ->where('is_active', true)
            ->first();

        if (!$monthlyProduct || $this->type === self::TYPE_MONTHLY) {
            return null;
        }

        $expectedPrice = $monthlyProduct->price * ($this->duration_days / 30);
        $savings = $expectedPrice - $this->price;

        return $expectedPrice > 0 ? round(($savings / $expectedPrice) * 100) : 0;
    }

    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            self::TYPE_MONTHLY => 'Mensual',
            self::TYPE_QUARTERLY => 'Trimestral',
            self::TYPE_YEARLY => 'Anual',
            self::TYPE_PACK => 'Pack Especial',
            default => $this->type,
        };
    }

    /**
     * Get duration label
     */
    public function getDurationLabelAttribute(): string
    {
        return match(true) {
            $this->duration_days === 30 => '1 mes',
            $this->duration_days === 90 => '3 meses',
            $this->duration_days === 180 => '6 meses',
            $this->duration_days === 365 => '1 año',
            default => $this->duration_days . ' días',
        };
    }

    /**
     * Scope for active products
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for products by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for ordered products
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('price');
    }

    /**
     * Boot method
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = \Str::slug($product->name . '-' . $product->course_id);
            }
        });
    }
}