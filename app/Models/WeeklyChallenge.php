<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeeklyChallenge extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'goal_type',
        'goal_amount',
        'reward_points',
        'reward_badge_id',
        'start_date',
        'end_date',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'goal_amount' => 'integer',
        'reward_points' => 'integer',
    ];

    /**
     * Goal type constants
     */
    const GOAL_POINTS = 'points';
    const GOAL_POMODOROS = 'pomodoros';
    const GOAL_TESTS_COMPLETED = 'tests_completed';
    const GOAL_BATTLES_WON = 'battles_won';
    const GOAL_TOPICS_COMPLETED = 'topics_completed';

    /**
     * Relationship to creator
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relationship to reward badge
     */
    public function rewardBadge()
    {
        return $this->belongsTo(AvatarItem::class, 'reward_badge_id');
    }

    /**
     * Relationship to participations
     */
    public function participations()
    {
        return $this->hasMany(ChallengeParticipation::class, 'challenge_id');
    }

    /**
     * Check if challenge is currently active
     */
    public function isCurrentlyActive(): bool
    {
        $today = now()->toDateString();
        return $this->is_active &&
               $today >= $this->start_date->toDateString() &&
               $today <= $this->end_date->toDateString();
    }

    /**
     * Check if challenge is expired
     */
    public function isExpired(): bool
    {
        return now()->toDateString() > $this->end_date->toDateString();
    }

    /**
     * Get progress for a specific user
     */
    public function getUserProgress(int $userId): ?ChallengeParticipation
    {
        return $this->participations()
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * Get or create participation for user
     */
    public function getOrCreateParticipation(int $userId): ChallengeParticipation
    {
        return $this->participations()
            ->firstOrCreate(
                ['user_id' => $userId],
                ['current_progress' => 0, 'is_completed' => false]
            );
    }

    /**
     * Update progress for a user on this challenge
     */
    public function updateProgressForUser(User $user, int $amount = 1): void
    {
        $participation = $this->getOrCreateParticipation($user->id);
        if (!$participation->is_completed) {
            $participation->updateProgress($amount);
        }
    }

    /**
     * Get goal type label
     */
    public function getGoalTypeLabelAttribute(): string
    {
        return match($this->goal_type) {
            self::GOAL_POINTS => 'Puntos',
            self::GOAL_POMODOROS => 'Pomodoros',
            self::GOAL_TESTS_COMPLETED => 'Tests completados',
            self::GOAL_BATTLES_WON => 'Batallas ganadas',
            self::GOAL_TOPICS_COMPLETED => 'Temas completados',
            default => $this->goal_type,
        };
    }

    /**
     * Scope for active challenges
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for current challenges
     */
    public function scopeCurrent($query)
    {
        $today = now()->toDateString();
        return $query->where('is_active', true)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today);
    }

    /**
     * Scope for upcoming challenges
     */
    public function scopeUpcoming($query)
    {
        return $query->where('is_active', true)
            ->whereDate('start_date', '>', now()->toDateString());
    }

    /**
     * Scope for past challenges
     */
    public function scopePast($query)
    {
        return $query->whereDate('end_date', '<', now()->toDateString());
    }
}
