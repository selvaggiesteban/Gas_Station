<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class ZoomMeeting extends Model
{
    use HasFactory;

    protected $fillable = [
        'topic_id',
        'meeting_id',
        'join_url',
        'start_url',
        'start_time',
        'duration_minutes',
        'timezone',
        'password',
        'settings',
        'status',
        'recording_url',
        'created_by',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'duration_minutes' => 'integer',
        'settings' => 'array',
    ];

    /**
     * Status constants
     */
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_LIVE = 'live';
    const STATUS_FINISHED = 'finished';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Relationship to topic
     */
    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * Relationship to creator
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if meeting is upcoming (within next 15 minutes)
     */
    public function isUpcoming(): bool
    {
        return $this->status === self::STATUS_SCHEDULED
            && $this->start_time->greaterThan(now()->subMinutes(15))
            && $this->start_time->lessThan(now()->addMinutes(15));
    }

    /**
     * Check if meeting is live
     */
    public function isLive(): bool
    {
        return $this->status === self::STATUS_LIVE;
    }

    /**
     * Check if meeting is in the future
     */
    public function isFuture(): bool
    {
        return $this->status === self::STATUS_SCHEDULED
            && $this->start_time->isFuture();
    }

    /**
     * Check if meeting has passed
     */
    public function hasPassed(): bool
    {
        return $this->start_time->addMinutes($this->duration_minutes)->isPast();
    }

    /**
     * Get formatted start time
     */
    public function getFormattedStartTimeAttribute(): string
    {
        return $this->start_time->format('d/m/Y H:i');
    }

    /**
     * Get end time
     */
    public function getEndTimeAttribute(): Carbon
    {
        return $this->start_time->copy()->addMinutes($this->duration_minutes);
    }

    /**
     * Scope for upcoming meetings this week
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('start_time', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ]);
    }

    /**
     * Scope for upcoming meetings
     */
    public function scopeUpcoming($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED)
            ->where('start_time', '>=', now()->subMinutes(15));
    }

    /**
     * Scope for meetings needing reminder (within next 15 minutes)
     */
    public function scopeNeedingReminder($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED)
            ->whereBetween('start_time', [now(), now()->addMinutes(15)]);
    }

    /**
     * Get meeting title based on topic
     */
    public function getTitleAttribute(): string
    {
        return $this->topic->name ?? 'Clase en directo';
    }
}