<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Error 403 - Acceso prohibido | AcademicUS') }} </title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: 'corporate-primary',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <div class="text-center">
            <!-- 403 Illustration -->
            <div class="mb-8">
                <div class="w-48 h-48 mx-auto bg-red-100 rounded-full flex items-center justify-center">
                    <svg class="w-24 h-24 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <div class="text-7xl font-black text-gray-800 mt-4">403</div>
            </div>

            <h1 class="text-3xl font-bold text-gray-900 mb-4">{{ __('Acceso prohibido') }} </h1>
            <p class="text-gray-600 mb-4">{{ __('No tienes permiso para acceder a esta página.') }} </p>

            @if(!auth()->check())<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                    <p class="text-sm text-yellow-800">{{ __('Es posible que necesites iniciar sesión o tener una suscripción activa para acceder a este contenido.') }} </p>
                </div>
            @elseif(!auth()->user()->subscription || !auth()->user()->subscription->isActive())<div class="bg-primary/10 border border-primary/20 rounded-lg p-4 mb-6">
                    <p class="text-sm text-primary">{{ __('Necesitas una suscripción activa para acceder a este contenido.') }} </p>
                </div>
            @endif

            <div class="space-y-3">
                @if(!auth()->check())<a href="{{ route('login') }}" class="inline-flex items-center justify-center w-full px-6 py-3 bg-primary text-white font-semibold rounded-lg hover:bg-primary/90 transition">
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                        </svg>{{ __('Iniciar sesión') }} </a>
                @elseif(!auth()->user()->subscription || !auth()->user()->subscription->isActive())<a href="{{ route('subscription.index') }}" class="inline-flex items-center justify-center w-full px-6 py-3 bg-primary text-white font-semibold rounded-lg hover:bg-primary/90 transition">
                        <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>{{ __('Ver suscripciones') }} </a>
                @endif
                <a href="{{ url('/') }}" class="inline-flex items-center justify-center w-full px-6 py-3 border-2 border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>{{ __('Volver al inicio') }} </a>
            </div>

            <div class="mt-8 pt-8 border-t border-gray-200">
                <p class="text-sm text-gray-500">{{ __('¿Crees que esto es un error?') }} <a href="mailto:soporte@academicus.com" class="text-primary hover:underline">{{ __('Contacta con soporte') }} </a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
