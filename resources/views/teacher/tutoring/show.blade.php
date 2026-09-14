<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Responder Tutoría') }} </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <div class="mb-4">
                        <a href="{{ route('teacher.tutoring.pending') }}" class="text-corporate-primary hover:text-orange-800 flex items-center">
                            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>{{ __('Volver a la lista') }} </a>
                    </div>

                    <div class="border-b pb-4 mb-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <p class="text-sm text-gray-500">{{ __('Estudiante') }} </p>
                                <p class="font-semibold text-gray-900">{{ $question->user->name }} </p>
                                <p class="text-xs text-gray-600">{{ $question->user->email }} </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">{{ __('Tema / Curso') }} </p>
                                <p class="font-semibold text-gray-900">{{ $question->topic->title ?? 'General' }} </p>
                                <p class="text-xs text-gray-600">{{ $question->course->title ?? '' }} </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">{{ __('Fecha de consulta') }} </p>
                                <p class="font-semibold text-gray-900">{{ $question->created_at->format('d/m/Y H:i') }} </p>
                            </div>
                        </div>
                    </div>

                    <div class="mb-8">
                        <h3 class="text-lg font-bold text-gray-900 mb-2">{{ $question->title }} </h3>
                        <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded-r-lg">
                            <p class="text-gray-800 whitespace-pre-wrap">{{ $question->content }} </p>
                        </div>
                    </div>

                    @if($question->status === 'pending')
                        <div class="border-t pt-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Tu Respuesta') }} </h3>

                            <form action="{{ route('teacher.tutoring.answer', $question) }}" method="POST">
                                @csrf

                                <div class="mb-4">
                                    <x-input-label for="answer" value="Escribe tu respuesta detallada" />
                                    <textarea id="answer" name="answer_text" rows="8" minlength="10" maxlength="600"
                                        x-data="{ len: (document.getElementById('answer')?.value || '').length }"
                                        @input="len = $event.target.value.length"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-corporate-primary focus:ring focus:ring-orange-200 focus:ring-opacity-50"
                                        placeholder="{{ __('Resuelve la duda del alumno aquí...') }}"
                                        required>{{ old('answer_text') }}</textarea>
                                    <div class="flex justify-between items-start mt-1">
                                        <x-input-error :messages="$errors->get('answer_text')" class="mt-2" />
                                        <span class="text-xs text-gray-500">
                                            <span x-text="len"></span> / 600 caracteres &middot; mínimo 10
                                        </span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-4">
                                    <x-primary-button class="bg-corporate-primary hover:bg-orange-600">{{ __('Enviar Respuesta') }} </x-primary-button>
                                </div>
                            </form>
                        </div>
                    @else
                        <div class="border-t pt-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ __('Respuesta enviada') }} </h3>
                            <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded-r-lg">
                                <p class="text-gray-800 whitespace-pre-wrap">{{ $question->answer }} </p>
                                <div class="mt-4 pt-4 border-t border-green-100 flex justify-between items-center text-xs text-green-700">
                                    <span>Respondido por: {{ $question->answerer->name ?? 'Sistema' }} </span>
                                    <span>Fecha: {{ $question->answered_at?->format('d/m/Y H:i') }} </span>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
