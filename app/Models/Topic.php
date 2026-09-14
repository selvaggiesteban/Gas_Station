<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Topic extends Model
{
    protected $fillable = ['block_id', 'title', 'slug', 'content', 'video_url', 'video_type', 'class_type', 'pdf_url', 'pdf_path', 'order', 'is_free_preview', 'available_at', 'is_active', 'estimated_minutes'];

    protected $casts = [
        'available_at' => 'datetime',
        'is_free_preview' => 'boolean',
        'video_type' => 'string',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($topic) {
            if (empty($topic->slug)) {
                $topic->slug = Str::slug($topic->title) . '-' . time() . '-' . rand(100, 999);
            }
        });

        static::updating(function ($topic) {
            if (empty($topic->slug) && $topic->isDirty('title')) {
                $topic->slug = Str::slug($topic->title) . '-' . time() . '-' . rand(100, 999);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function block()
    {
        return $this->belongsTo(Block::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class)->withPivot('is_completed', 'completed_at');
    }

    /**
     * Get users who have completed this topic
     */
    public function completedByUsers()
    {
        return $this->belongsToMany(User::class)
            ->withPivot('is_completed', 'completed_at')
            ->wherePivot('is_completed', true);
    }

    public function pdfVersions()
    {
        return $this->hasMany(TopicPdfVersion::class)->orderByDesc('version_number');
    }

    public function resources()
    {
        return $this->hasMany(TopicResource::class)->orderBy('display_order');
    }

    /**
     * Check if topic is available (drip content)
     */
    public function isAvailable(): bool
    {
        if ($this->is_free_preview) {
            return true;
        }

        if (is_null($this->available_at)) {
            return true;
        }

        return $this->available_at->isPast();
    }

    /**
     * Get time until available
     */
    public function getTimeUntilAvailable(): ?string
    {
        if ($this->isAvailable()) {
            return null;
        }

        return $this->available_at->diffForHumans();
    }

    /**
     * Scope to get only available topics
     */
    public function scopeAvailable($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('available_at')
              ->orWhere('available_at', '<=', now())
              ->orWhere('is_free_preview', true);
        });
    }

    /**
     * Scope to get locked topics
     */
    public function scopeLocked($query)
    {
        return $query->whereNotNull('available_at')
                    ->where('available_at', '>', now())
                    ->where('is_free_preview', false);
    }
}
