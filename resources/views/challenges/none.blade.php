<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-12 text-center">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-gray-100 rounded-full mb-6">
                        <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">{{ __('No hay retos activos') }} </h2>
                    <p class="text-gray-600 mb-8">{{ __('Actualmente no hay ninguna misión semanal programada. ¡Vuelve pronto para ganar puntos extra!') }} </p>
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center px-6 py-3 bg-corporate-primary text-white font-bold rounded-xl hover:bg-orange-600 transition shadow-lg">
                        {{ __('Volver al Panel') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
