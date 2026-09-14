<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserQuestionMetadata extends Model
{
    protected $table = 'user_question_metadata';

    protected $fillable = [
        'user_id',
        'question_id',
        'is_doubt',
        'is_favorite',
        'notes',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
