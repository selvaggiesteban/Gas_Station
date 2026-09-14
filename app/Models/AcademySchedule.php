<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AcademySchedule extends Model
{
    use HasFactory;

    protected $table = 'schedules';

    protected $fillable = [
        'topic_id',
        'start_time',
        'end_time',
        'phase',
        'is_from_academy',
        'title',
        'user_id'
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_from_academy' => 'boolean',
    ];

    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }
}
