<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Battle extends Model
{
    use HasFactory;

    protected $fillable = [
        'challenger_id',
        'opponent_id',
        'topic_id',
        'status', // pending, accepted, declined, completed
        'challenger_score',
        'opponent_score',
        'winner_id',
        'question_ids'
    ];

    protected $casts = [
        'question_ids' => 'array',
        'challenger_score' => 'integer',
        'opponent_score' => 'integer',
    ];

    public function challenger()
    {
        return $this->belongsTo(User::class, 'challenger_id');
    }

    public function opponent()
    {
        return $this->belongsTo(User::class, 'opponent_id');
    }

    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * Winner of the battle
     */
    public function winner()
    {
        return $this->belongsTo(User::class, 'winner_id');
    }
}
