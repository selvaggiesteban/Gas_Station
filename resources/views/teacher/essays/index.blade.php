<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">{{ __('Corrección de Exámenes') }} </h1>
            <p class="text-gray-600 mt-2">{{ __('Revisa y califica las respuestas abiertas de los alumnos.') }} </p>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                {{ session('success') }}
            </div>
        @endif

        <div class="space-y-6">
            @forelse($submissions as $submission)
                <div class="bg-white rounded-lg shadow-md overflow-hidden border border-gray-200">
                    <div class="bg-gray-50 px-6 py-3 border-b border-gray-200 flex justify-between items-center">
                        <div>
                            <span class="text-sm font-bold text-gray-700">{{ __('Alumno:') }} </span>
                            <span class="text-sm text-gray-600">{{ $submission->user->name }} </span>
                            <span class="mx-2 text-gray-300">|</span>
                            <span class="text-sm font-bold text-gray-700">{{ __('Test:') }} </span>
                            <span class="text-sm text-gray-600">{{ $submission->testAttempt->test->title }} </span>
                        </div>
                        <span class="text-xs text-gray-400 uppercase font-bold">{{ $submission->created_at->format('d/m/Y H:i') }} </span>
                    </div>
                    <div class="p-6">
                        <div class="mb-6">
                            <h4 class="text-sm font-bold text-corporate-primary uppercase mb-2">{{ __('Pregunta:') }} </h4>
                            <p class="text-lg text-gray-900 font-medium">{!! $submission->question->text !!}</p>
                        </div>

                        <div class="mb-6 bg-gray-50 p-4 rounded-lg border border-gray-200">
                            <h4 class="text-sm font-bold text-gray-700 uppercase mb-2">{{ __('Respuesta del Alumno:') }} </h4>
                            <div class="text-gray-800 whitespace-pre-wrap leading-relaxed">
                                {{ $submission->answer_text }}
                            </div>
                        </div>

                        <form action="{{ route('teacher.essays.grade', $submission) }}" method="POST" class="border-t border-gray-100 pt-6">
                            @csrf
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 items-end">
                                <div class="md:col-span-1">
                                    <label class="block text-sm font-bold text-gray-700 mb-2">Puntuación (Máx: {{ $submission->question->points }})</label>
                                    <input type="number" name="score" step="0.1" min="0" max="{{ $submission->question->points }}" required class="block w-full rounded-md border-gray-300 shadow-sm focus:border-corporate-primary focus:ring-corporate-primary">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-bold text-gray-700 mb-2">{{ __('Feedback para el Alumno (Opcional)') }} </label>
                                    <input type="text" name="feedback" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-corporate-primary focus:ring-corporate-primary" :placeholder="__('Ej: Muy buena respuesta, pero te faltó mencionar el artículo 5...')">
                                </div>
                                <div class="md:col-span-1">
                                    <button type="submit" class="w-full bg-corporate-primary text-white px-4 py-2 rounded-lg font-bold hover:bg-orange-600 transition">{{ __('Guardar Calificación') }} </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            @empty
                <div class="text-center py-20 bg-white rounded-lg border border-dashed border-gray-300 text-gray-500">
                    <svg class="mx-auto h-12 w-12 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" /></svg>
                    <p class="text-xl font-medium">{{ __('No hay respuestas pendientes de calificar.') }} </p>
                    <p class="mt-2 text-sm">{{ __('¡Buen trabajo! Has revisado todos los exámenes por ahora.') }} </p>
                </div>
            @endforelse
        </div>

        <div class="mt-8">
            {{ $submissions->links() }}
        </div>
    </div>
</x-app-layout>