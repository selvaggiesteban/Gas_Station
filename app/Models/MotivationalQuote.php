<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MotivationalQuote extends Model
{
    use HasFactory;

    protected $fillable = [
        'text',
        'author',
        'category',
        'is_active',
        'display_count',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_count' => 'integer',
    ];

    /**
     * Category constants
     */
    const CATEGORY_STUDY = 'study';
    const CATEGORY_BATTLE = 'battle';
    const CATEGORY_GENERAL = 'general';

    /**
     * Increment display count
     */
    public function incrementDisplayCount(): void
    {
        $this->increment('display_count');
    }

    /**
     * Get random quote by category
     */
    public static function getRandom(string $category = null): ?self
    {
        $query = self::where('is_active', true);

        if ($category) {
            $query->where('category', $category);
        }

        return $query->inRandomOrder()->first();
    }

    /**
     * Scope for active quotes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope by category
     */
    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
