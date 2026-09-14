<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Test extends Model
{
    protected $fillable = ['course_id', 'title', 'description', 'type', 'time_limit_minutes', 'duration_minutes', 'attempt_limit', 'passing_score', 'is_active', 'is_free'];

    protected $casts = [
        'time_limit_minutes' => 'integer',
        'duration_minutes' => 'integer',
        'attempt_limit' => 'integer',
        'passing_score' => 'integer',
    ];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function attempts()
    {
        return $this->hasMany(TestAttempt::class);
    }

    /**
     * Check if user has reached attempt limit
     */
    public function canUserAttempt(?int $userId = null): array
    {
        if ($userId === null) {
            $userId = auth()->id();
        }

        // If no limit set, user can attempt unlimited times
        if ($this->attempt_limit === null) {
            return [
                'can_attempt' => true,
                'remaining' => null,
                'message' => 'Intentos ilimitados disponibles',
            ];
        }

        $attemptCount = $this->attempts()
            ->where('user_id', $userId)
            ->whereNotNull('completed_at')
            ->count();

        $remaining = $this->attempt_limit - $attemptCount;

        if ($remaining <= 0) {
            return [
                'can_attempt' => false,
                'remaining' => 0,
                'message' => "Has agotado tus {$this->attempt_limit} intentos permitidos.",
            ];
        }

        return [
            'can_attempt' => true,
            'remaining' => $remaining,
            'message' => "Te quedan {$remaining} de {$this->attempt_limit} intentos.",
        ];
    }

    /**
     * Check if user can attempt this test (boolean)
     */
    public function userCanAttempt(?int $userId = null): bool
    {
        return $this->canUserAttempt($userId)['can_attempt'];
    }

    /**
     * Get attempt status message
     */
    public function getAttemptStatusMessage(?int $userId = null): string
    {
        return $this->canUserAttempt($userId)['message'];
    }

    /**
     * Get remaining attempts
     */
    public function getRemainingAttempts(?int $userId = null): ?int
    {
        return $this->canUserAttempt($userId)['remaining'];
    }

    /**
     * Check if test is in exam mode (limited attempts)
     */
    public function isExamMode(): bool
    {
        return $this->attempt_limit !== null;
    }

    /**
     * Check if test is practice mode (unlimited attempts)
     */
    public function isPracticeMode(): bool
    {
        return $this->attempt_limit === null;
    }
}
