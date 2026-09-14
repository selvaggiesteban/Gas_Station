<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Error 404 - Página no encontrada | AcademicUS') }} </title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        corporate: {
                            primary: '#EB7716',
                            secondary: '#1a1a2e',
                            dark: '#0f0f1a',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .bg-corporate-primary { background-color: #EB7716; }
        .bg-corporate-secondary { background-color: #1a1a2e; }
        .text-corporate-primary { color: #EB7716; }
        .text-corporate-secondary { color: #1a1a2e; }
        .border-corporate-primary { border-color: #EB7716; }
        .hover\:bg-corporate-primary:hover { background-color: #EB7716; }
        .hover\:text-corporate-primary:hover { color: #EB7716; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full mx-4">
        <div class="text-center">
            <!-- 404 Illustration -->
            <div class="mb-8">
                <div class="w-48 h-48 mx-auto bg-orange-100 rounded-full flex items-center justify-center">
                    <svg class="w-24 h-24 text-corporate-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="text-7xl font-black text-corporate-secondary mt-4">404</div>
            </div>

            <h1 class="text-3xl font-bold text-corporate-secondary mb-4">{{ __('Página no encontrada') }} </h1>
            <p class="text-gray-600 mb-4">{{ __('Lo sentimos, la página que estás buscando no existe o ha sido movida.') }} </p>

            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <p class="text-sm text-yellow-800">
                    <strong>{{ __('¿Qué puedes hacer?') }} </strong><br>{{ __('Verifica la URL o vuelve al inicio para continuar tu preparación.') }} </p>
            </div>

            <div class="space-y-3">
                <a href="{{ url('/') }}" class="inline-flex items-center justify-center w-full px-6 py-3 bg-corporate-primary text-white font-semibold rounded-lg hover:opacity-90 transition">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>{{ __('Volver al inicio') }} </a>
                <button onclick="history.back()" class="inline-flex items-center justify-center w-full px-6 py-3 border-2 border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>{{ __('Volver atrás') }} </button>
            </div>

            <div class="mt-8 pt-8 border-t border-gray-200">
                <p class="text-sm text-gray-500">{{ __('¿Necesitas ayuda?') }} <a href="mailto:soporte@academicus.com" class="text-corporate-primary hover:underline">{{ __('Contacta con soporte') }} </a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
