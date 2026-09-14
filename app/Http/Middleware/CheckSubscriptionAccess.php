<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckSubscriptionAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $policy = 'read_only')
    {
        $user = Auth::user();

        // Allow admins, content managers, and teachers full access
        if ($user->hasRole(['Admin', 'Content Manager', 'Teacher'])) {
            return $next($request);
        }

        // Check if user has ANY active subscription first (fast check)
        $hasActiveSubscription = $user->subscriptions()->where('status', 'active')->exists();

        if (!$hasActiveSubscription) {
            return $this->handleNoAccess($request);
        }

        // If a specific course or topic is requested, perform granular authorization
        $route = $request->route();
        $courseId = null;

        // Try to extract course context from the route
        if ($route->hasParameter('topic')) {
            $topic = $route->parameter('topic');
            if (!is_object($topic)) {
                $topic = \App\Models\Topic::with('block.course')->find($topic);
            }
            $courseId = $topic?->block?->course_id;
        } elseif ($route->hasParameter('course')) {
            $course = $route->parameter('course');
            $courseId = is_object($course) ? $course->id : $course;
        } elseif ($route->hasParameter('test')) {
            $test = $route->parameter('test');
            if (!is_object($test)) {
                $test = \App\Models\Test::find($test);
            }
            $courseId = $test?->course_id;
        }

        // Check if the content is free (Simulacros, etc.)
        if ($this->isRequestingFreeContent($request)) {
            return $next($request);
        }

        // If we identified a specific course, ensure the user has an active subscription for IT
        if ($courseId) {
            $hasCourseAccess = $user->subscriptions()
                ->where('status', 'active')
                ->whereHas('product', function ($query) use ($courseId) {
                    $query->where('course_id', $courseId);
                })->exists();

            if (!$hasCourseAccess) {
                // Return a specific error indicating they don't have access to THIS specific content
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tienes una suscripción activa para este curso específico.',
                        'redirect' => route('cursos.catalogo'),
                    ], 403);
                }

                return redirect()->route('cursos.catalogo')
                    ->with('error', 'No tienes acceso a este curso. Necesitas adquirir la suscripción correspondiente.');
            }
        }

        // Passed all checks
        return $next($request);

        // Get policy from config or parameter
        $effectivePolicy = config('subscription.post_subscription_policy', $policy);

        return match($effectivePolicy) {
            'no_access' => $this->handleNoAccess($request),
            'read_only' => $this->handleReadOnly($request, $next),
            default => $this->handleNoAccess($request),
        };
    }

    /**
     * Determine if the current request is for free content.
     */
    private function isRequestingFreeContent(Request $request): bool
    {
        $route = $request->route();
        
        // If viewing a test, check if it's marked as free or a simulacro
        if ($route->getName() === 'examenes.presentar') {
            $test = $route->parameter('test');
            // Support both ID and Model binding
            if (!is_object($test)) {
                $test = \App\Models\Test::find($test);
            }
            if ($test) {
                $isSimulacro = strtolower($test->type) === 'simulacro' || str_contains(strtolower($test->title), 'simulacro');
                return $test->is_free || $isSimulacro;
            }
        }

        return false;
    }

    /**
     * Handle no access policy - redirect to subscription page
     */
    private function handleNoAccess(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Tu suscripción ha expirado. Por favor, renueva para continuar.',
                'redirect' => route('subscription.index'),
            ], 403);
        }

        return redirect()->route('subscription.index')
            ->with('error', 'Tu suscripción ha expirado. Por favor, renueva para continuar accediendo al contenido.');
    }

    /**
     * Handle read-only policy - allow viewing but restrict features
     */
    private function handleReadOnly(Request $request, Closure $next)
    {
        $restrictedRoutes = [
            'tests.show',
            'tests.submit',
            'battles.create',
            'battles.store',
            'topics.complete',
        ];

        $currentRoute = $request->route()->getName();

        // Check if current route is restricted
        if (in_array($currentRoute, $restrictedRoutes)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta función requiere una suscripción activa.',
                ], 403);
            }

            return redirect()->back()
                ->with('error', 'Esta función requiere una suscripción activa. Por favor, renueva tu suscripción.');
        }

        // Add a view share to indicate read-only mode
        view()->share('subscriptionReadOnly', true);

        return $next($request);
    }
}