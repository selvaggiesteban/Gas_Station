<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PracticalCaseSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'test_attempt_id',
        'question_id',
        'answer_text',
        'attachments',
        'auto_score',
        'teacher_score',
        'teacher_feedback',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'attachments' => 'array',
        'reviewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function testAttempt()
    {
        return $this->belongsTo(TestAttempt::class);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get the final score (teacher score takes precedence over auto score)
     */
    public function getFinalScore(): ?int
    {
        return $this->teacher_score ?? $this->auto_score;
    }

    /**
     * Check if submission has been reviewed
     */
    public function isReviewed(): bool
    {
        return $this->status === 'reviewed' || $this->status === 'challenged';
    }
}
