<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AvatarItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'gender',
        'image_path',
        'unlock_level',
        'unlock_rank',
        'unlock_battles_won',
        'unlock_pomodoros',
        'is_default',
        'position',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'unlock_level' => 'integer',
        'unlock_battles_won' => 'integer',
        'unlock_pomodoros' => 'integer',
    ];

    const TYPE_UNIFORM = 'uniform';
    const TYPE_BADGE = 'badge';
    const TYPE_FRAME = 'frame';
    const TYPE_ACCESSORY = 'accessory';

    const RANK_COBRE = 'Cobre';
    const RANK_BRONCE = 'Bronce';
    const RANK_PLATA = 'Plata';
    const RANK_ORO = 'Oro';
    const RANK_DIAMANTE = 'Diamante';

    public function users()
    {
        return $this->belongsToMany(User::class, 'avatar_item_user')
            ->withPivot('is_equipped', 'unlocked_at')
            ->withTimestamps();
    }

    /**
     * Get equipped users
     */
    public function equippedBy()
    {
        return $this->belongsToMany(User::class, 'avatar_item_user')
            ->wherePivot('is_equipped', true);
    }

    /**
     * Get numeric representation of rank for comparison
     */
    public function getUnlockRankNumeric(): int
    {
        return match($this->unlock_rank) {
            self::RANK_DIAMANTE => 5,
            self::RANK_ORO => 4,
            self::RANK_PLATA => 3,
            self::RANK_BRONCE => 2,
            self::RANK_COBRE => 1,
            default => 0,
        };
    }

    /**
     * Check if item can be unlocked by user
     */
    public function canBeUnlockedBy(User $user): bool
    {
        // Check rank requirement
        if ($this->unlock_rank && $user->getRankNumeric() < $this->getUnlockRankNumeric()) {
            return false;
        }

        // Check level requirement (if used as numeric rank)
        if ($this->unlock_level > 0 && $user->getRankNumeric() < $this->unlock_level) {
            return false;
        }

        // Check battles won requirement
        if ($this->unlock_battles_won !== null && ($user->battles_won ?? 0) < $this->unlock_battles_won) {
            return false;
        }

        // Check pomodoros requirement
        if ($this->unlock_pomodoros !== null) {
            $completedPomodoros = \App\Models\StudySession::where('user_id', $user->id)
                ->where('type', \App\Models\StudySession::TYPE_POMODORO)
                ->where('completed', true)
                ->count();

            if ($completedPomodoros < $this->unlock_pomodoros) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get unlock description
     */
    public function getUnlockDescription(): string
    {
        $requirements = [];

        if ($this->unlock_level > 1) {
            $requirements[] = "Nivel {$this->unlock_level}";
        }

        if ($this->unlock_rank) {
            $requirements[] = "Rango {$this->unlock_rank}";
        }

        if ($this->unlock_battles_won) {
            $requirements[] = "{$this->unlock_battles_won} victorias en batallas";
        }

        if ($this->unlock_pomodoros) {
            $requirements[] = "{$this->unlock_pomodoros} pomodoros completados";
        }

        if (empty($requirements)) {
            return 'Disponible desde el inicio';
        }

        return 'Desbloquea: ' . implode(', ', $requirements);
    }

    /**
     * Scope by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope default items
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope by unlock level
     */
    public function scopeUnlockableAtLevel($query, int $level)
    {
        return $query->where('unlock_level', '<=', $level)
            ->whereNull('unlock_rank')
            ->whereNull('unlock_battles_won')
            ->whereNull('unlock_pomodoros');
    }

    /**
     * Order by position
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('position')->orderBy('unlock_level');
    }
}
