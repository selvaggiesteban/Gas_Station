<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademyAnnouncement extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'type', // info, warning, urgent
        'published_by',
        'published_at',
        'expires_at',
        'is_active',
        'target_roles', // json array of roles to show to
        'target_cuerpos', // json array of cuerpo ids; null = all
        'attachment',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'target_roles' => 'array',
        'target_cuerpos' => 'array',
    ];

    public function publisher()
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->where(function ($q) {
                $q->whereNull('published_at')
                      ->orWhere('published_at', '<=', now());
            });
    }

    public function scopeForRole($query, string $role)
    {
        return $query->whereJsonContains('target_roles', $role)
            ->orWhereNull('target_roles')
            ->orWhere('target_roles', '[]');
    }

    public function scopeForCuerpo($query, $cuerpoId)
    {
        return $query->where(function ($q) use ($cuerpoId) {
            $q->whereNull('target_cuerpos')
              ->orWhere('target_cuerpos', '[]')
              ->orWhereJsonContains('target_cuerpos', $cuerpoId);
        });
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function markAsRead(int $userId): void
    {
        $this->reads()->syncWithoutDetaching([$userId => ['read_at' => now()]]);
    }

    public function reads()
    {
        return $this->belongsToMany(User::class, 'announcement_reads')
            ->withPivot('read_at')
            ->withTimestamps();
    }
}
