<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'start_time',
        'end_time',
        'is_official', // true if set by academy, false if personal
        'is_completed',
        'task_type',
        'color',
        'position',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_official' => 'boolean',
        'is_completed' => 'boolean',
        'task_type' => 'string',
        'color' => 'string',
        'position' => 'integer',
    ];

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function ($schedule) {
            if ($schedule->position === null) {
                $maxPosition = static::where('user_id', $schedule->user_id)
                    ->where('is_official', $schedule->is_official)
                    ->max('position');
                $schedule->position = ($maxPosition ?? 0) + 1;
            }
        });
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('position')->orderBy('start_time');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function studySessions()
    {
        return $this->hasMany(StudySession::class);
    }

    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }
    
    
}
