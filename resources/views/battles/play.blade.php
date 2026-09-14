<x-app-layout>
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-3xl mx-auto">
            <div class="bg-white rounded-xl shadow-lg overflow-hidden border border-orange-100">
                <div class="bg-corporate-primary p-6 text-white text-center">
                    <h2 class="text-2xl font-bold uppercase tracking-widest">{{ __('¡Batalla en Curso!') }} </h2>
                    <p class="text-orange-100 mt-1">{{ __('Responde a las 10 preguntas para vencer a tu oponente.') }} </p>
                </div>

                <form action="{{ route('battles.score', $battle) }}" method="POST" id="battle-form">
                    @csrf
                    <div class="p-8 space-y-12">
                        @foreach($questions as $index => $question)
                            <div class="question-container" id="question-{{ $index }}">
                                <div class="flex items-start mb-6">
                                    <span class="flex-none flex items-center justify-center w-8 h-8 rounded-full bg-orange-100 text-corporate-primary font-bold mr-4 text-sm">
                                        {{ $index + 1 }}
                                    </span>
                                    <h3 class="text-lg font-bold text-gray-900 leading-tight">
                                        {!! nl2br(e($question->text)) !!}
                                    </h3>
                                </div>

                                <div class="grid grid-cols-1 gap-4 ml-12">
                                    @foreach($question->options as $optionIndex => $option)
                                        <label class="relative flex items-center p-4 rounded-xl border-2 border-gray-100 cursor-pointer hover:border-orange-200 hover:bg-orange-50 transition-all group">
                                            <input type="radio" name="answers[{{ $question->id }}]" value="{{ $option->id }}" required class="w-5 h-5 text-corporate-primary border-gray-300 focus:ring-corporate-primary">
                                            <span class="ml-4 text-gray-700 group-hover:text-gray-900">
                                                <span class="font-bold mr-2 text-xs text-gray-400 uppercase">{{ chr(65 + $optionIndex) }})</span>
                                                {{ $option->text }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="bg-gray-50 p-6 border-t border-gray-100 flex justify-between items-center">
                        <p class="text-xs text-gray-500 italic">{{ __('Nota: Una vez enviado no podrás cambiar tus respuestas.') }} </p>
                        <button type="submit" class="bg-corporate-primary text-white px-8 py-3 rounded-lg font-bold uppercase tracking-widest hover:bg-orange-600 transition shadow-lg shadow-orange-200">
                            {{ __('Finalizar Batalla') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
