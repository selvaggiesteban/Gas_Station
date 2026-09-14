<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Jobs\ProcessUserActivityJob;
use Jenssegers\Agent\Agent;
use Illuminate\Support\Facades\Auth;
use App\Services\ActivityLoggerService;

class LogUserActivity
{
    public function __construct(protected ActivityLoggerService $loggerService)
    {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    /**
     * Handle tasks after the response has been sent to the browser.
     */
    public function terminate(Request $request, Response $response): void
    {
        if (!Auth::check() || !Auth::user()->hasRole('Student')) {
            return;
        }

        $url = $request->fullUrl();
        $routeName = $request->route() ? $request->route()->getName() : null;

        // Excluir rutas de administración, assets y polling
        if ($this->shouldExclude($url, $routeName)) {
            return;
        }

        $agent = new Agent();
        $agent->setUserAgent($request->userAgent());

        $deviceInfo = [
            'browser' => $agent->browser(),
            'platform' => $agent->platform(),
            'device' => $agent->device(),
            'is_mobile' => $agent->isMobile(),
        ];

        $activityData = [
            'user_id' => Auth::id(),
            'session_id' => session()->getId(),
            'url' => $url,
            'route_name' => $routeName,
            'method' => $request->method(),
            'action_type' => $this->loggerService->getActionType($routeName, $url),
            'ip_address' => $request->ip(),
            'device_info' => $deviceInfo,
            'time_spent' => 0,
            'created_at' => now(),
        ];

        ProcessUserActivityJob::dispatch($activityData);
    }

    protected function shouldExclude(string $url, ?string $routeName): bool
    {
        $excludedRoutes = [
            'admin.*',
            'notificaciones.*',
            'tienda.*',
            'debugbar.*',
            'telescope.*',
        ];

        if ($routeName && \Illuminate\Support\Str::is($excludedRoutes, $routeName)) {
            return true;
        }

        $excludedPaths = [
            'admin/*',
            'api/*',
            'assets/*',
            'storage/*',
            '*/heartbeat',
        ];

        if (\Illuminate\Support\Str::is($excludedPaths, parse_url($url, PHP_URL_PATH))) {
            return true;
        }

        return false;
    }
}
