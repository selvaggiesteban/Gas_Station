<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class RedirectOldPaths
{
    /**
     * Handle an incoming request and redirect old PDF paths to new SEO slugs.
     */
    public function handle(Request $request, Closure $next)
    {
        $path = $request->path();

        // Mapa de redirecciones críticas (Deshabilitado por solicitud del usuario)
        $redirects = [
            // No se requieren redirecciones ya que el proyecto no ha sido publicado.
        ];

        foreach ($redirects as $old => $new) {
            if ($path === $old || str_starts_with($path, $old . '/')) {
                $newPath = str_replace($old, $new, $path);
                return Redirect::to($newPath, 301);
            }
        }

        return $next($request);
    }
}
