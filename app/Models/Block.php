<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Block extends Model
{
    protected $fillable = ['course_id', 'title', 'order'];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function topics()
    {
        return $this->hasMany(Topic::class)->orderBy('order');
    }

    /**
     * Devuelve la posición (1-based) de este bloque dentro del curso.
     */
    public function getPositionInCourse(): int
    {
        $ordered = $this->course->blocks()->orderBy('order')->orderBy('id')->pluck('id')->toArray();
        $pos = array_search($this->id, $ordered, true);
        return $pos === false ? 1 : ($pos + 1);
    }

    /**
     * Comprueba si el bloque está desbloqueado para un usuario
     * según el mes natural desde subscription.starts_at.
     * - El bloque 1 se desbloquea desde el día 1 (mes 0).
     * - El bloque N se desbloquea el día 1 del mes N-1.
     * Si no hay suscripción activa, no está desbloqueado.
     */
    public function isUnlockedFor(?User $user): bool
    {
        if (!$user) return false;

        // Admins, Content Manager y Teacher ven todo
        if ($user->hasAnyRole(['Admin', 'Content Manager', 'Teacher'])) {
            return true;
        }

        $subscription = $user->subscriptions()
            ->where('status', 'active')
            ->where('ends_at', '>', now())
            ->whereHas('product', fn($q) => $q->where('course_id', $this->course_id))
            ->orderByDesc('starts_at')
            ->first();

        if (!$subscription) {
            return false;
        }

        $startsAt = $subscription->starts_at instanceof Carbon
            ? $subscription->starts_at
            : Carbon::parse($subscription->starts_at);

        $position = $this->getPositionInCourse();
        $unlockMonth = $startsAt->copy()->addMonths($position - 1);
        $now = now();

        return $now->greaterThanOrEqualTo($unlockMonth->startOfMonth());
    }
}
