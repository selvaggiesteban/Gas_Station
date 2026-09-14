<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserCourseAccessController extends Controller
{
    /**
     * Vista de cursos suscritos por un usuario con fechas y estado de bloqueo.
     * N2: bloqueo automático por estado de suscripción.
     */
    public function index(User $user)
    {
        $subscriptions = $user->subscriptions()
            ->with(['product.course.blocks', 'plan'])
            ->latest('created_at')
            ->get()
            ->map(function ($sub) use ($user) {
                $product = $sub->product;
                $course = $product?->course;
                $blockedAuto = in_array($sub->status, ['expired', 'cancelled', 'payment_failed', 'suspended']);
                return [
                    'subscription' => $sub,
                    'product' => $product,
                    'course' => $course,
                    'started_at' => $sub->starts_at,
                    'ends_at' => $sub->ends_at,
                    'status' => $sub->status,
                    'is_blocked' => (bool) ($sub->is_blocked ?? false),
                    'blocked_auto' => $blockedAuto,
                ];
            });

        return view('admin.users.courses', compact('user', 'subscriptions'));
    }

    /**
     * Bloquear / desbloquear manualmente el acceso de un usuario a un curso.
     */
    public function toggleBlock(Request $request, User $user, Subscription $subscription)
    {
        if ($subscription->user_id !== $user->id) {
            abort(403);
        }

        $subscription->is_blocked = !$subscription->is_blocked;
        $subscription->save();

        return back()->with('success', $subscription->is_blocked ? 'Acceso al curso bloqueado manualmente.' : 'Acceso al curso restaurado.');
    }
}
