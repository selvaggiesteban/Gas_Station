{{-- resources/views/tests/pending.blade.php --}}
<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-3xl font-bold mb-8 text-center">{{ __('Test Pendiente de Revisión') }} </h1>

            @if(session('info'))
                <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-6">
                    {{ session('info') }}
                </div>
            @endif

            <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                <div class="text-center mb-6">
                    <svg class="w-20 h-20 mx-auto text-yellow-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <h2 class="text-2xl font-semibold text-gray-800 mb-2">{{ __('Esperando Revisión Manual') }} </h2>
                    <p class="text-gray-600">{{ __('Este test contiene preguntas que requieren revisión por parte de un profesor.') }} </p>
                </div>

                <div class="border-t pt-6">
                    <h3 class="font-semibold mb-4">{{ __('Detalles del Test:') }} </h3>
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <span class="text-gray-600">{{ __('Test:') }} </span>
                            <p class="font-medium">{{ $test->title }} </p>
                        </div>
                        <div>
                            <span class="text-gray-600">{{ __('Fecha de envío:') }} </span>
                            <p class="font-medium">{{ $attempt->completed_at?->format('d/m/Y H:i') ?? 'N/A' }} </p>
                        </div>
                    </div>
                </div>
            </div>

            @if($submissions->count() > 0)
                <div class="bg-white rounded-lg shadow-md p-6 mb-8">
                    <h3 class="font-semibold mb-4">{{ __('Preguntas Pendientes:') }} </h3>

                    @foreach($submissions as $submission)
                        <div class="border-b last:border-0 py-4">
                            <p class="font-medium mb-2">{{ $submission->question->title ?? 'Pregunta' }} </p>
                            <div class="bg-gray-50 p-3 rounded">
                                <span class="text-sm text-gray-600">{{ __('Tu respuesta:') }} </span>
                                <p class="text-sm mt-1">{{ $submission->answer_text }} </p>
                            </div>
                            <div class="mt-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">{{ __('Pendiente de revisión') }} </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="text-center">
                <a href="{{ route('cursos.mis-cursos') }}" class="inline-flex items-center px-6 py-3 bg-corporate-primary text-white font-semibold rounded-lg hover:bg-orange-600 transition">{{ __('Volver a Mis Cursos') }} </a>
            </div>
        </div>
    </div>
</x-app-layout>
