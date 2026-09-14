<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('Error 500 - Error del servidor | AcademicUS') }} </title>
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
            <!-- 500 Illustration -->
            <div class="mb-8">
                <div class="w-48 h-48 mx-auto bg-orange-100 rounded-full flex items-center justify-center">
                    <svg class="w-24 h-24 text-corporate-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </div>
                <div class="text-7xl font-black text-corporate-secondary mt-4">500</div>
            </div>

            <h1 class="text-3xl font-bold text-corporate-secondary mb-4">{{ __('Algo salió mal') }} </h1>
            <p class="text-gray-600 mb-4">{{ __('Lo sentimos, ha ocurrido un error en nuestro servidor. Estamos trabajando para solucionarlo.') }} </p>

            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <p class="text-sm text-yellow-800">
                    <strong>{{ __('¿Qué puedes hacer?') }} </strong><br>{{ __('Intenta recargar la página en unos momentos, o vuelve al inicio y continúa tu estudio.') }} </p>
            </div>

            <div class="space-y-3">
                <button onclick="window.location.reload()" class="inline-flex items-center justify-center w-full px-6 py-3 bg-corporate-primary text-white font-semibold rounded-lg hover:opacity-90 transition">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>{{ __('Recargar página') }} </button>
                <a href="{{ url('/') }}" class="inline-flex items-center justify-center w-full px-6 py-3 border-2 border-gray-300 text-gray-700 font-semibold rounded-lg hover:bg-gray-50 transition">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>{{ __('Volver al inicio') }} </a>
            </div>

            <div class="mt-8 pt-8 border-t border-gray-200">
                <p class="text-sm text-gray-500">{{ __('Si el problema persiste,') }} <a href="mailto:soporte@academicus.com" class="text-corporate-primary hover:underline">{{ __('contacta con soporte') }} </a>{{ __('y te ayudaremos.') }} </p>
            </div>
        </div>
    </div>
</body>
</html>
