<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">{{ __('Gestión de Contenido') }} </h1>
            <p class="text-gray-600 mt-2">Bienvenido, {{ auth()->user()->name }}.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Retos Semanales -->
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-yellow-500">
                <h3 class="text-lg font-semibold mb-2">{{ __('Retos Semanales') }} </h3>
                <p class="text-gray-600 mb-4">{{ __('Configura las metas de puntos y estudio.') }} </p>
                <a href="{{ route('weekly-challenges.index') }}" class="text-corporate-primary hover:underline font-bold">{{ __('Gestionar Retos →') }} </a>
            </div>

            <!-- Anuncios -->
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
                <h3 class="text-lg font-semibold mb-2">{{ __('Anuncios') }} </h3>
                <p class="text-gray-600 mb-4">{{ __('Publica novedades en la academia.') }} </p>
                <a href="{{ route('content-manager.announcements.index') }}" class="text-corporate-primary hover:underline font-bold">{{ __('Gestionar Anuncios →') }} </a>
            </div>

            <!-- Cronograma -->
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
                <h3 class="text-lg font-semibold mb-2">{{ __('Cronograma Oficial') }} </h3>
                <p class="text-gray-600 mb-4">{{ __('Establece el plan de estudio mensual.') }} </p>
                <a href="{{ route('content-manager.schedules.index') }}" class="text-corporate-primary hover:underline font-bold">{{ __('Gestionar Cronogramas →') }} </a>
            </div>
        </div>
    </div>
</x-app-layout>
