<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChallengeParticipation extends Model
{
    use HasFactory;

    protected $table = 'challenge_participations';

    protected $fillable = [
        'challenge_id',
        'user_id',
        'current_progress',
        'is_completed',
        'completed_at',
    ];

    protected $casts = [
        'current_progress' => 'integer',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    /**
     * Relationship to challenge
     */
    public function challenge()
    {
        return $this->belongsTo(WeeklyChallenge::class, 'challenge_id');
    }

    /**
     * Relationship to user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Update progress and check completion
     */
    public function updateProgress(int $amount): void
    {
        $this->current_progress += $amount;

        if (!$this->is_completed && $this->current_progress >= $this->challenge->goal_amount) {
            $this->is_completed = true;
            $this->completed_at = now();

            // Award rewards
            $this->awardRewards();
        }

        $this->save();
    }

    /**
     * Award points and badge to user
     */
    private function awardRewards(): void
    {
        $user = $this->user;
        $challenge = $this->challenge;

        // Award points
        if ($challenge->reward_points > 0) {
            $user->points += $challenge->reward_points;
            $user->saveQuietly();
        }

        // Award badge if applicable
        if ($challenge->reward_badge_id) {
            $user->avatarItems()->syncWithoutDetaching([
                $challenge->reward_badge_id => [
                    'unlocked_at' => now(),
                    'equipped' => false,
                ]
            ]);
        }
    }

    /**
     * Get progress percentage
     */
    public function getProgressPercentageAttribute(): int
    {
        $goal = $this->challenge->goal_amount;
        if ($goal <= 0) {
            return 0;
        }
        return min(100, round(($this->current_progress / $goal) * 100));
    }

    /**
     * Scope for completed participations
     */
    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }

    /**
     * Scope for in-progress participations
     */
    public function scopeInProgress($query)
    {
        return $query->where('is_completed', false);
    }

    /**
     * Scope for user's participations
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
