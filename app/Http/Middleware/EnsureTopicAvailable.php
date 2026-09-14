<?php

namespace App\Http\Middleware;

use App\Models\Topic;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTopicAvailable
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $topic = $request->route('topic');

        if ($topic instanceof Topic && !$topic->isAvailable()) {
            $timeUntil = $topic->getTimeUntilAvailable();

            return redirect()->back()->with('error', "Este contenido estará disponible {$timeUntil}");
        }

        return $next($request);
    }
}
