{{-- resources/views/zoom/this-week.blade.php --}}
<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8">{{ __('Clases de esta semana') }} </h1>

        @if($meetings->count() > 0)
            <div class="grid gap-6">
                @foreach($meetings as $meeting)
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex justify-between items-start">
                            <div>
                                <h2 class="text-xl font-semibold mb-2">{{ $meeting->topic?->title ?? 'Clase en vivo' }} </h2>
                                <p class="text-gray-600 mb-2">
                                    <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    {{ $meeting->start_time->format('d/m/Y H:i') }} ({{ $meeting->duration_minutes }} min)
                                </p>
                                <p class="text-gray-600">
                                    Profesor: {{ $meeting->creator?->name ?? 'Por asignar' }}
                                </p>
                            </div>
                            <div>
                                @if($meeting->isLive())<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">{{ __('EN VIVO') }} </span>
                                @elseif($meeting->isUpcoming())<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">{{ __('Próxima') }} </span>
                                @endif
                            </div>
                        </div>

                        <div class="mt-4 flex gap-3">
                            <a href="{{ route('zoom.ver-clase', $meeting) }}" class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">{{ __('Ver detalles') }} </a>

                            @if($meeting->isLive() || $meeting->isUpcoming())<a href="{{ route('zoom.entrar', $meeting) }}" class="inline-flex items-center px-4 py-2 bg-corporate-primary text-white rounded-lg hover:bg-orange-600 transition">{{ __('Unirse a la clase →') }} </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-16 bg-gray-50 rounded-lg">
                <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
                <h2 class="text-xl font-semibold text-gray-700 mb-2">{{ __('No hay clases esta semana') }} </h2>
                <p class="text-gray-500">{{ __('No tienes videoclases programadas para esta semana.') }} </p>
            </div>
        @endif
    </div>
</x-app-layout>
