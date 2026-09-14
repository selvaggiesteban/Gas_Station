<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class UserActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'url',
        'route_name',
        'method',
        'action_type',
        'ip_address',
        'device_info',
        'time_spent',
    ];

    protected $casts = [
        'device_info' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeRecent(Builder $query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeForUser(Builder $query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeSignificant(Builder $query)
    {
        return $query->where('action_type', '!=', 'heartbeat');
    }
}
