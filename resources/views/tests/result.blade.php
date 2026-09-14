<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Resultado del Examen') }}: {{ $test->title }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <!-- Main Result Card -->
            <div class="bg-white overflow-hidden shadow-lg sm:rounded-xl mb-6">
                <div class="p-8 text-center">
                    <!-- Status Badge -->
                    <div class="mb-6">
                        @if($attempt->passed)<div class="inline-flex items-center justify-center w-28 h-28 bg-gradient-to-br from-green-400 to-green-600 rounded-full mb-4 shadow-lg">
                                <svg class="w-14 h-14 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                            </div>
                            <div class="inline-block px-4 py-1 bg-green-100 text-green-800 rounded-full text-sm font-semibold mb-2">{{ __('¡Enhorabuena!') }} </div>
                            <h3 class="text-3xl font-bold text-green-600">{{ __('¡Aprobado!') }} </h3>
                        @else
                            <div class="inline-flex items-center justify-center w-28 h-28 bg-gradient-to-br from-red-400 to-red-600 rounded-full mb-4 shadow-lg">
                                <svg class="w-14 h-14 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </div>
                            <div class="inline-block px-4 py-1 bg-red-100 text-red-800 rounded-full text-sm font-semibold mb-2">{{ __('Sigue practicando') }} </div>
                            <h3 class="text-3xl font-bold text-red-600">{{ __('No Aprobado') }} </h3>
                        @endif
                    </div>

                    <!-- Score Display with Progress Ring -->
                    <div class="relative inline-block mb-6">
                        <svg class="w-40 h-40 transform -rotate-90">
                            <circle cx="80" cy="80" r="70" stroke="#E5E7EB" stroke-width="12" fill="none"/>
                            <circle cx="80" cy="80" r="70"
                                    stroke="{{ $attempt->passed ? '#10B981' : '#EF4444' }}"
                                    stroke-width="12" fill="none"
                                    stroke-dasharray="440"
                                    stroke-dashoffset="{{ 440 - (440 * $attempt->score / 100) }}"
                                    class="transition-all duration-1000 ease-out"/>
                        </svg>
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-4xl font-black text-gray-900">{{ round($attempt->score, 1) }}%</span>
                            <span class="text-sm text-gray-500">{{ __('puntuación') }} </span>
                        </div>
                    </div>

                    <p class="text-gray-600 text-lg mb-8 max-w-md mx-auto">
                        {{ $attempt->passed
                            ? __('¡Excelente trabajo! Has superado el examen con una puntuación de :score%.', ['score' => round($attempt->score, 1)])
                            : __('Necesitas un :passing% para aprobar. ¡No te rindas, sigue practicando!', ['passing' => $test->passing_score])
                        }}
                    </p>

                    <!-- Stats Grid -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 max-w-2xl mx-auto mb-8">
                        <div class="p-4 bg-gray-50 rounded-xl text-center">
                            <div class="w-10 h-10 bg-corporate-primary/10 rounded-full flex items-center justify-center mx-auto mb-2">
                                <svg class="w-5 h-5 text-corporate-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <span class="block text-xl font-bold text-gray-800">{{ $test->passing_score }}%</span>
                            <span class="text-xs uppercase text-gray-500">{{ __('Nota de Corte') }} </span>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-xl text-center">
                            <div class="w-10 h-10 bg-corporate-primary/10 rounded-full flex items-center justify-center mx-auto mb-2">
                                <svg class="w-5 h-5 text-corporate-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <span class="block text-xl font-bold text-gray-800">{{ $attempt->completed_at->format('d/m/Y') }} </span>
                            <span class="text-xs uppercase text-gray-500">{{ __('Fecha') }} </span>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-xl text-center">
                            <div class="w-10 h-10 bg-corporate-primary/10 rounded-full flex items-center justify-center mx-auto mb-2">
                                <svg class="w-5 h-5 text-corporate-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <span class="block text-xl font-bold text-gray-800">
                                @if($attempt->time_spent_minutes)
                                    {{ $attempt->time_spent_minutes }} min
                                @else
                                    --
                                @endif
                            </span>
                            <span class="text-xs uppercase text-gray-500">{{ __('Tiempo') }} </span>
                        </div>
                        <div class="p-4 bg-gray-50 rounded-xl text-center">
                            <div class="w-10 h-10 bg-corporate-primary/10 rounded-full flex items-center justify-center mx-auto mb-2">
                                <svg class="w-5 h-5 text-corporate-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <span class="block text-xl font-bold text-gray-800">+{{ $attempt->score >= 80 ? 50 : ($attempt->score >= 60 ? 30 : 10) }} </span>
                            <span class="text-xs uppercase text-gray-500">{{ __('Puntos') }} </span>
                        </div>
                    </div>
<!-- Action Buttons -->
<div class="flex flex-col sm:flex-row justify-center gap-4">
    @if(!$attempt->passed)
        <a href="{{ route('examenes.presentar', $test) }}" class="inline-flex items-center justify-center px-6 py-3 bg-corporate-primary border border-transparent rounded-lg font-bold text-sm text-white uppercase tracking-wide hover:bg-[#D66A0F] transition shadow-md">
            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            {{ __('Reintentar Examen') }}
        </a>
        <a href="{{ route('examenes.presentar', ['test' => $test->id, 'only_failed' => 1]) }}" class="inline-flex items-center justify-center px-6 py-3 bg-amber-500 border border-transparent rounded-lg font-bold text-sm text-white uppercase tracking-wide hover:bg-amber-600 transition shadow-md">
            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            {{ __('Repetir solo falladas') }}
        </a>
    @endif
    <a href="{{ route('cursos.ver', $test->course_id) }}" class="inline-flex items-center justify-center px-6 py-3 bg-gray-800 border border-transparent rounded-lg font-bold text-sm text-white uppercase tracking-wide hover:bg-gray-700 transition">
                            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            {{ __('Volver al Curso') }}
                        </a>
                        <a href="{{ route('examenes.lista') }}" class="inline-flex items-center justify-center px-6 py-3 border-2 border-gray-300 rounded-lg font-bold text-sm text-gray-700 uppercase tracking-wide hover:bg-gray-50 transition">
                            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            {{ __('Ver Todos los Tests') }}
                        </a>
                    </div>
                </div>
            </div>

            <!-- Review Answers Section -->
            @if($attempt->answers && count($attempt->answers) > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-xl">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">{{ __('Revisión de Respuestas') }} </h3>
                        <p class="text-sm text-gray-500 mt-1">{{ __('Revisa tus respuestas para identificar áreas de mejora') }} </p>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            @foreach($test->questions as $index => $question)
                                @php
                                    $userAnswer = $attempt->answers[$question->id] ?? null;
                                    $correctOption = $question->options->firstWhere('is_correct', true);
                                    $isCorrect = $userAnswer && $userAnswer['answer'] == ($correctOption->id ?? null);
                                @endphp
                                <div class="flex items-start p-4 rounded-lg {{ $isCorrect ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' }}">
                                    <div class="flex-shrink-0 mr-3">
                                        @if($isCorrect)
                                            <svg class="w-6 h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        @else
                                            <svg class="w-6 h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        @endif
                                    </div>
                                    <div class="flex-1">
                                        <p class="font-medium text-gray-900">{{ $index + 1 }}. {{ $question->text }} </p>
                                        @if($userAnswer)
                                            <p class="text-sm mt-1 {{ $isCorrect ? 'text-green-700' : 'text-red-700' }}">
                                                Tu respuesta: {{ $question->options->firstWhere('id', $userAnswer['answer'])?->text ?? 'No respondida' }}
                                            </p>
                                        @else
                                            <p class="text-sm mt-1 text-gray-500">{{ __('No respondida') }} </p>
                                        @endif
                                        @if(!$isCorrect && $correctOption)
                                            <p class="text-sm mt-1 text-green-700 font-semibold">
                                                Respuesta correcta: {{ $correctOption->text }}
                                            </p>
                                        @endif
                                        
                                        @if($question->explanation)
                                            <div class="mt-3 p-3 bg-white/50 rounded border {{ $isCorrect ? 'border-green-100' : 'border-red-100' }} text-sm text-gray-700 italic">
                                                <span class="font-bold not-italic block mb-1 {{ $isCorrect ? 'text-green-800' : 'text-red-800' }}">{{ __('Explicación:') }} </span>
                                                {{ $question->explanation }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
