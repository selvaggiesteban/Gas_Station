<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TutoringQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'course_id',
        'topic_id',
        'title',
        'content',
        'status', // pending, answered, resolved, escalated
        'answered_by',
        'answered_at',
        'answer',
        'is_public',
    ];

    protected $casts = [
        'answered_at' => 'datetime',
        'is_public' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    public function answerer()
    {
        return $this->belongsTo(User::class, 'answered_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAnswered($query)
    {
        return $query->where('status', 'answered');
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function markAsAnswered(int $teacherId, string $answer): void
    {
        $this->update([
            'status' => 'answered',
            'answered_by' => $teacherId,
            'answered_at' => now(),
            'answer' => $answer,
        ]);
    }

    public function markAsResolved(): void
    {
        $this->update(['status' => 'resolved']);
    }

    public function escalate(): void
    {
        $this->update(['status' => 'escalated']);
    }
}
