<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudySession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'schedule_id',
        'topic_id',
        'type',
        'duration_minutes',
        'started_at',
        'ended_at',
        'completed',
        'notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'completed' => 'boolean',
    ];

    const TYPE_POMODORO = 'pomodoro';
    const TYPE_SHORT_BREAK = 'short_break';
    const TYPE_LONG_BREAK = 'long_break';

    const DURATIONS = [
        self::TYPE_POMODORO => 25,
        self::TYPE_SHORT_BREAK => 5,
        self::TYPE_LONG_BREAK => 15,
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }

    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * Check if session is currently active
     */
    public function isActive(): bool
    {
        return $this->ended_at === null;
    }

    /**
     * Get elapsed time in seconds
     */
    public function getElapsedSeconds(): int
    {
        if ($this->ended_at) {
            return $this->started_at->diffInSeconds($this->ended_at);
        }

        return $this->started_at->diffInSeconds(now());
    }

    /**
     * Get remaining seconds
     */
    public function getRemainingSeconds(): int
    {
        $totalSeconds = $this->duration_minutes * 60;
        $elapsed = $this->getElapsedSeconds();

        return max(0, $totalSeconds - $elapsed);
    }

    /**
     * Check if session is completed (time elapsed)
     */
    public function isTimeCompleted(): bool
    {
        return $this->getRemainingSeconds() <= 0;
    }

    /**
     * Complete the session
     */
    public function markAsCompleted(?string $notes = null): void
    {
        $this->update([
            'ended_at' => now(),
            'completed' => true,
            'notes' => $notes ?? $this->notes,
        ]);
    }

    /**
     * Scope for today's sessions
     */
    public function scopeToday($query)
    {
        return $query->whereDate('started_at', today());
    }

    /**
     * Scope for this week's sessions
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('started_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    /**
     * Scope for completed sessions
     */
    public function scopeCompleted($query)
    {
        return $query->where('completed', true);
    }

    /**
     * Get total study time for user (in minutes)
     */
    public static function getTotalStudyMinutesForUser(int $userId, ?string $period = null): int
    {
        $query = self::where('user_id', $userId)
            ->where('type', self::TYPE_POMODORO)
            ->where('completed', true);

        if ($period === 'today') {
            $query->today();
        } elseif ($period === 'week') {
            $query->thisWeek();
        } elseif ($period === 'month') {
            $query->whereMonth('started_at', now()->month)
                  ->whereYear('started_at', now()->year);
        }

        return $query->sum('duration_minutes') ?? 0;
    }

    /**
     * Get completed pomodoros count for user
     */
    public static function getCompletedPomodorosCount(int $userId, ?string $period = null): int
    {
        $query = self::where('user_id', $userId)
            ->where('type', self::TYPE_POMODORO)
            ->where('completed', true);

        if ($period === 'today') {
            $query->today();
        } elseif ($period === 'week') {
            $query->thisWeek();
        } elseif ($period === 'month') {
            $query->whereMonth('started_at', now()->month)
                  ->whereYear('started_at', now()->year);
        }

        return $query->count();
    }
}
