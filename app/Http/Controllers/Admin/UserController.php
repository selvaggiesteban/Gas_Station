<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\UserActivity;
use App\Services\ActivityLoggerService;
use Illuminate\Http\Request;
use App\Http\Requests\Admin\AssignSubscriptionRequest;
use App\Http\Requests\Admin\UpdateUserRoleRequest;

class UserController extends Controller
{
    private ActivityLoggerService $loggerService;

    public function __construct(ActivityLoggerService $loggerService)
    {
        $this->loggerService = $loggerService;
    }

    public function index(Request $request)
    {
        $query = User::with(['roles', 'cuerpos', 'subscriptions' => function($q) {
            $q->where('status', 'active');
        }]);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filtro por rol
        if ($request->filled('role')) {
            $query->whereHas('roles', fn($q) => $q->where('name', $request->role));
        }

        // Filtro por cuerpo
        if ($request->filled('cuerpo_id')) {
            $query->whereHas('cuerpos', fn($q) => $q->where('cuerpos.id', $request->cuerpo_id));
        }

        // Filtro por suscripción activa
        if ($request->filled('subscription')) {
            if ($request->subscription === 'active') {
                $query->whereHas('subscriptions', fn($q) =>
                    $q->where('status', 'active')->where('ends_at', '>', now())
                );
            } elseif ($request->subscription === 'inactive') {
                $query->whereDoesntHave('subscriptions', fn($q) =>
                    $q->where('status', 'active')->where('ends_at', '>', now())
                );
            }
        }

        $users = $query->paginate(20)->withQueryString();

        $cuerpos = \App\Models\Cuerpo::where('is_active', true)->orderBy('name')->get();
        $roles = \Spatie\Permission\Models\Role::all();

        return view('admin.users.index', compact('users', 'cuerpos', 'roles'));
    }

    public function show(User $user)
    {
        $user->load(['subscriptions.plan', 'roles']);
        $plans = Plan::all();
        
        $stats = [
            'points' => $user->points,
            'level' => $user->level,
            'completed_topics' => $user->completedTopics()->count(),
            'tests_taken' => $user->testAttempts()->count(),
        ];

        return view('admin.users.show', compact('user', 'plans', 'stats'));
    }

    public function activity(User $user)
    {
        $activities = UserActivity::where('user_id', $user->id)
            ->latest()
            ->paginate(50);

        $activities->getCollection()->transform(function ($activity) {
            $activity->description = $this->loggerService->getActionDescription(
                $activity->route_name,
                $activity->url
            );
            return $activity;
        });

        return view('admin.users.activity', compact('user', 'activities'));
    }

    public function assignSubscription(AssignSubscriptionRequest $request, User $user)
    {
        $plan = Plan::find($request->plan_id);
        $endsAt = now()->addMonths((int) $request->duration_months);

        Subscription::updateOrCreate(
            [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
            ],
            [
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => $endsAt,
                'payment_id' => 'MANUAL_' . strtoupper($request->payment_method) . '_' . time(),
            ]
        );

        if (!$user->hasRole('Student')) {
            $user->assignRole('Student');
        }

        return redirect()->route('admin.usuarios.show', $user)
            ->with('success', "Suscripción asignada manualmente al plan {$plan->name} hasta " . $endsAt->format('d/m/Y'));
    }

    public function update(UpdateUserRoleRequest $request, User $user)
    {
        if ($user->id === auth()->id() && $request->role !== 'Admin') {
            return redirect()->back()->with('error', 'No puedes quitarte el rol de Administrador a ti mismo.');
        }

        $user->syncRoles([$request->role]);

        return redirect()->route('admin.usuarios.show', $user)
            ->with('success', "Rol actualizado correctamente a {$request->role}.");
    }

    /**
     * Assign / change tutor and cuerpos for a user.
     */
    public function assignProfile(Request $request, User $user)
    {
        $data = $request->validate([
            'assigned_teacher_id' => 'nullable|exists:users,id',
            'cuerpos' => 'nullable|array',
            'cuerpos.*' => 'exists:cuerpos,id',
        ]);

        if (array_key_exists('assigned_teacher_id', $data)) {
            $user->assigned_teacher_id = $data['assigned_teacher_id'] ?: null;
            $user->save();
        }

        if (array_key_exists('cuerpos', $data)) {
            $user->cuerpos()->sync($data['cuerpos'] ?? []);
        }

        return redirect()->route('admin.usuarios.show', $user)
            ->with('success', 'Perfil actualizado: tutor y cuerpos guardados.');
    }
}
