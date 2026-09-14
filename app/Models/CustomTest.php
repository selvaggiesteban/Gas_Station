<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomTest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'blocks',
        'topics',
        'question_types',
        'difficulty',
        'question_count',
        'time_limit_minutes',
        'status',
        'generated_questions',
        'score',
        'completed_at',
    ];

    protected $casts = [
        'blocks' => 'array',
        'topics' => 'array',
        'question_types' => 'array',
        'generated_questions' => 'array',
        'question_count' => 'integer',
        'time_limit_minutes' => 'integer',
        'score' => 'integer',
        'completed_at' => 'datetime',
    ];

    /**
     * Status constants
     */
    const STATUS_DRAFT = 'draft';
    const STATUS_GENERATED = 'generated';
    const STATUS_COMPLETED = 'completed';

    /**
     * Difficulty constants
     */
    const DIFFICULTY_EASY = 'easy';
    const DIFFICULTY_MEDIUM = 'medium';
    const DIFFICULTY_HARD = 'hard';
    const DIFFICULTY_MIXED = 'mixed';

    /**
     * Relationship to user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get questions for this custom test
     */
    public function questions()
    {
        if (empty($this->generated_questions)) {
            return collect();
        }

        return Question::whereIn('id', $this->generated_questions)->get();
    }

    /**
     * Check if test is draft
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if test is generated
     */
    public function isGenerated(): bool
    {
        return $this->status === self::STATUS_GENERATED;
    }

    /**
     * Check if test is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Mark as generated
     */
    public function markAsGenerated(array $questionIds): void
    {
        $this->update([
            'status' => self::STATUS_GENERATED,
            'generated_questions' => $questionIds,
        ]);
    }

    /**
     * Mark as completed
     */
    public function markAsCompleted(int $score): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'score' => $score,
            'completed_at' => now(),
        ]);
    }

    /**
     * Get difficulty label
     */
    public function getDifficultyLabelAttribute(): string
    {
        return match($this->difficulty) {
            self::DIFFICULTY_EASY => 'Fácil',
            self::DIFFICULTY_MEDIUM => 'Medio',
            self::DIFFICULTY_HARD => 'Difícil',
            self::DIFFICULTY_MIXED => 'Mezclado',
            default => $this->difficulty,
        };
    }

    /**
     * Scope for user's custom tests
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for completed tests
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for recent tests
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}