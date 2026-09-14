<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TestAttempt extends Model
{
    protected $fillable = ['user_id', 'test_id', 'score', 'passed', 'answers', 'started_at', 'completed_at'];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'passed' => 'boolean',
        'answers' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function test()
    {
        return $this->belongsTo(Test::class);
    }
}
