<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Lab404\Impersonate\Models\Impersonate;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasRoles, Impersonate;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'provider',
        'provider_id',
        'avatar',
        'gender',
        'avatar_equipment',
        'points',
        'level',
        'battles_won',
        'read_announcements',
        'notification_preferences',
        'holded_id',
        'nif',
        'phone',
        'address',
        'city',
        'province',
        'postal_code',
        'company_name',
        'stripe_id',
        'pm_type',
        'pm_last_four',
        'trial_ends_at',
        'last_login_at',
        'login_streak_days',
        'total_study_minutes',
        'total_tests_completed',
        'total_classes_viewed',
        'total_supuestos_submitted',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'avatar_equipment' => 'array',
            'read_announcements' => 'array',
            'notification_preferences' => 'array',
            'last_login_at' => 'datetime',
        ];
    }

    protected static function booted()
    {
        static::deleting(function ($user) {
            foreach ($user->subscriptions()->where('status', 'active')->get() as $sub) {
                $sub->update(['status' => 'cancelled']);
            }
        });
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function testAttempts()
    {
        return $this->hasMany(TestAttempt::class);
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class)->latestOfMany();
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function completedTopics()
    {
        return $this->belongsToMany(Topic::class)->withPivot('is_completed', 'completed_at')->wherePivot('is_completed', true);
    }

    public function topics()
    {
        return $this->belongsToMany(Topic::class)
            ->withPivot('is_completed', 'completed_at', 'round_number', 'phase_reading', 'phase_underlining', 'phase_review', 'phase_fast_review')
            ->withTimestamps();
    }

    /**
     * Level thresholds according to documentation
     */
    public const LEVELS = [
        'Cobre' => ['min' => 0, 'max' => 999],
        'Bronce' => ['min' => 1000, 'max' => 2999],
        'Plata' => ['min' => 3000, 'max' => 5999],
        'Oro' => ['min' => 6000, 'max' => 8999],
        'Diamante' => ['min' => 9000, 'max' => PHP_INT_MAX],
    ];

    /**
     * Get user's rank name (Spanish)
     */
    public function getRank(): string
    {
        $points = $this->points ?? 0;

        return match(true) {
            $points >= 9000 => 'Diamante',
            $points >= 6000 => 'Oro',
            $points >= 3000 => 'Plata',
            $points >= 1000 => 'Bronce',
            default => 'Cobre',
        };
    }

    /**
     * Get user's rank display name (Spanish)
     */
    public function getRankName(): string
    {
        return $this->getRank();
    }

    /**
     * Get the minimum points required for a rank
     */
    public static function getRankThreshold(string $rank): int
    {
        return self::LEVELS[$rank]['min'] ?? 0;
    }

    /**
     * Get point range for a rank
     */
    public static function getRankPointRange(string $rank): array
    {
        return [
            self::LEVELS[$rank]['min'] ?? 0,
            self::LEVELS[$rank]['max'] ?? PHP_INT_MAX,
        ];
    }

    /**
     * Get course progress percentage
     */
    public function getCourseProgress(Course $course): int
    {
        $totalTopics = $course->topics()->count();
        if ($totalTopics === 0) {
            return 0;
        }

        $completedTopics = $this->completedTopics()
            ->whereIn('topics.id', $course->topics()->pluck('topics.id'))
            ->count();

        return (int) round(($completedTopics / $totalTopics) * 100);
    }

    /**
     * Battles where user is challenger
     */
    public function battlesAsChallenger()
    {
        return $this->hasMany(Battle::class, 'challenger_id');
    }

    /**
     * Battles where user is opponent
     */
    public function battlesAsOpponent()
    {
        return $this->hasMany(Battle::class, 'opponent_id');
    }

    /**
     * Avatar items unlocked by user
     */
    public function avatarItems()
    {
        return $this->belongsToMany(AvatarItem::class, 'avatar_item_user')
            ->withPivot('is_equipped', 'unlocked_at')
            ->withTimestamps();
    }

    /**
     * Equipped avatar items
     */
    public function equippedItems()
    {
        return $this->belongsToMany(AvatarItem::class, 'avatar_item_user')
            ->wherePivot('is_equipped', true);
    }

    /**
     * Check if user has unlocked an item
     */
    public function hasUnlockedItem(AvatarItem $item): bool
    {
        return $this->avatarItems()->where('avatar_item_id', $item->id)->exists();
    }

    /**
     * Unlock an item for this user
     */
    public function unlockItem(AvatarItem $item): bool
    {
        if ($this->hasUnlockedItem($item)) {
            return true;
        }

        if (!$item->canBeUnlockedBy($this)) {
            return false;
        }

        $this->avatarItems()->attach($item->id, [
            'is_equipped' => false,
            'unlocked_at' => now(),
        ]);

        return true;
    }

    /**
     * Equip an item
     */
    public function equipItem(AvatarItem $item): bool
    {
        if (!$this->hasUnlockedItem($item)) {
            return false;
        }

        // Unequip other items of same type
        $this->avatarItems()
            ->where('type', $item->type)
            ->updateExistingPivot($this->avatarItems()->pluck('avatar_item_id'), [
                'is_equipped' => false,
            ]);

        // Equip new item
        $this->avatarItems()->updateExistingPivot($item->id, [
            'is_equipped' => true,
        ]);

        return true;
    }

    /**
     * Unequip an item
     */
    public function unequipItem(AvatarItem $item): bool
    {
        if (!$this->hasUnlockedItem($item)) {
            return false;
        }

        $this->avatarItems()->updateExistingPivot($item->id, [
            'is_equipped' => false,
        ]);

        return true;
    }

    /**
     * Get numeric representation of rank for comparison
     */
    public function getRankNumeric(): int
    {
        return match($this->getRank()) {
            'Diamante' => 5,
            'Oro' => 4,
            'Plata' => 3,
            'Bronce' => 2,
            default => 1,
        };
    }

    /**
     * Check if user has access to a specific course
     */
    public function hasAccessToCourse(Course $course): bool
    {
        if ($this->hasRole(['Admin', 'Content Manager', 'Teacher'])) {
            return true;
        }

        return $this->subscriptions()
            ->whereHas('product', function ($query) use ($course) {
                $query->where('course_id', $course->id);
            })
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->exists();
    }

    /**
     * Check and unlock all available items
     */
    public function checkAndUnlockItems(): array
    {
        $newlyUnlocked = [];
        $items = AvatarItem::whereNotIn('id', $this->avatarItems()->pluck('avatar_item_id'))
            ->get();

        foreach ($items as $item) {
            if ($item->canBeUnlockedBy($this)) {
                $this->unlockItem($item);
                $newlyUnlocked[] = $item;
            }
        }

        return $newlyUnlocked;
    }

    public function challengeParticipations()
    {
        return $this->hasMany(ChallengeParticipation::class);
    }

    public function cuerpos()
    {
        return $this->belongsToMany(Cuerpo::class, 'cuerpo_user')->withTimestamps();
    }

    public function assignedTeacher()
    {
        return $this->belongsTo(User::class, 'assigned_teacher_id');
    }

    public function assignedStudents()
    {
        return $this->hasMany(User::class, 'assigned_teacher_id');
    }
}
