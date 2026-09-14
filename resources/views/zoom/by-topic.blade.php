{{-- resources/views/zoom/by-topic.blade.php --}}
<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8">Clases: {{ $topic->title }} </h1>

        @if($meetings->count() > 0)
            <div class="grid gap-6">
                @foreach($meetings as $meeting)
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="text-sm text-gray-500 mb-1">{{ $meeting->start_time->format('d/m/Y H:i') }} </p>
                                <h2 class="text-xl font-semibold">{{ __('Clase en vivo') }} </h2>
                                <p class="text-gray-600 mt-1">Duración: {{ $meeting->duration_minutes }} minutos</p>
                            </div>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">{{ __('Programada') }} </span>
                        </div>

                        <div class="mt-4 flex gap-3">
                            <a href="{{ route('zoom.ver-clase', $meeting) }}" class="text-corporate-primary hover:text-orange-600 font-medium">{{ __('Ver detalles →') }} </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-16 bg-gray-50 rounded-lg">
                <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h2 class="text-xl font-semibold text-gray-700 mb-2">{{ __('No hay clases programadas') }} </h2>
                <p class="text-gray-500">{{ __('No hay videoclases programadas para este tema.') }} </p>
            </div>
        @endif

        <div class="mt-8 text-center">
            <a href="{{ route('cursos.ver', $topic->block->course) }}" class="inline-flex items-center text-gray-600 hover:text-gray-900">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>{{ __('Volver al curso') }} </a>
        </div>
    </div>
</x-app-layout>
