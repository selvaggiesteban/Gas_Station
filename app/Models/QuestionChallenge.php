<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionChallenge extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'question_id',
        'test_attempt_id',
        'reason',
        'user_explanation',
        'status',
        'reviewed_by',
        'reviewer_notes',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function testAttempt()
    {
        return $this->belongsTo(TestAttempt::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Scope for pending challenges (review queue)
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for reviewed challenges
     */
    public function scopeReviewed($query)
    {
        return $query->whereIn('status', ['approved', 'rejected']);
    }

    /**
     * Approve the challenge
     */
    public function approve(int $reviewerId, ?string $notes = null): void
    {
        $this->update([
            'status' => 'approved',
            'reviewed_by' => $reviewerId,
            'reviewer_notes' => $notes,
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Reject the challenge
     */
    public function reject(int $reviewerId, ?string $notes = null): void
    {
        $this->update([
            'status' => 'rejected',
            'reviewed_by' => $reviewerId,
            'reviewer_notes' => $notes,
            'reviewed_at' => now(),
        ]);
    }
}
