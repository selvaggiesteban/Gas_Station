<?php

use App\Models\CustomTest;
?>
<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $customTest->name }}
            </h2>
            <div class="flex items-center space-x-4">
                <div class="text-sm text-gray-600">{{ __('Pregunta') }} <span id="current-question">1</span> de {{ $questions->count() }}
                </div>
                <div class="text-lg font-mono font-bold text-corporate-primary" id="timer">
                    {{ sprintf('%02d:%02d', $customTest->time_limit_minutes, 0) }}
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('examenes-personalizados.enviar', $customTest) }}" id="test-form">
                @csrf

                <div class="space-y-6">
                    @foreach($questions as $index => $question)<div class="question-card bg-white overflow-hidden shadow-sm sm:rounded-lg {{ $index === 0 ? '' : 'hidden' }}" data-index="{{ $index }}">
                            <div class="p-6">
                                <div class="flex items-start mb-6">
                                    <span class="flex-shrink-0 w-8 h-8 bg-corporate-primary text-white rounded-full flex items-center justify-center text-sm font-medium">
                                        {{ $index + 1 }}
                                    </span>
                                    <div class="ml-4 flex-1">
                                        <p class="text-lg text-gray-900 font-medium">{{ $question->text }} </p>
                                        @if($question->explanation)<p class="mt-2 text-sm text-gray-500">{{ $question->explanation }} </p>
                                        @endif
                                    </div>
                                </div>

                                @if($question->isMultipleChoice() || $question->isFiveOptions())<div class="space-y-3 ml-12">
                                        @foreach($question->options as $option)<label class="flex items-start p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors">
                                                <input type="radio" name="answers[{{ $question->id }}]" value="{{ $option->id }}" class="mt-1 h-4 w-4 text-corporate-primary border-gray-300 focus:ring-corporate-primary">
                                                <span class="ml-3 text-gray-700">{{ $option->text }} </span>
                                            </label>
                                        @endforeach
                                    </div>
                                @elseif($question->isFillBlank())<div class="ml-12">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Tu respuesta:') }} </label>
                                        <input type="text" name="answers[{{ $question->id }}]" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-corporate-primary focus:ring-corporate-primary" :placeholder="__('Escribe tu respuesta...')">
                                    </div>
                                @elseif($question->isOpenEnded())<div class="ml-12">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Tu respuesta:') }} </label>
                                        <textarea name="answers[{{ $question->id }}]" rows="6" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-corporate-primary focus:ring-corporate-primary" :placeholder="__('Escribe tu respuesta aquí...')" maxlength="5000"></textarea>
                                        <p class="mt-1 text-xs text-gray-500">{{ __('Máximo 5000 caracteres') }} </p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Navigation -->
                <div class="mt-6 flex justify-between items-center bg-white p-4 rounded-lg shadow-sm">
                    <button type="button" id="prev-btn" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 disabled:opacity-50" disabled>{{ __('&larr; Anterior') }} </button>

                    <div class="flex space-x-2">
                        @foreach($questions as $index => $question)<button type="button" class="question-dot w-3 h-3 rounded-full bg-gray-300" data-index="{{ $index }}"></button>
                        @endforeach
                    </div>

                    <button type="button" id="next-btn" class="px-4 py-2 bg-corporate-primary text-white rounded-md hover:bg-orange-700">{{ __('Siguiente &rarr;') }} </button>
                </div>

                <!-- Submit -->
                <div class="mt-6 text-center">
                    <button type="submit" id="submit-btn" class="px-8 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 hidden">{{ __('Finalizar Test') }} </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const questions = document.querySelectorAll('.question-card');
            const prevBtn = document.getElementById('prev-btn');
            const nextBtn = document.getElementById('next-btn');
            const submitBtn = document.getElementById('submit-btn');
            const currentQuestionSpan = document.getElementById('current-question');
            const dots = document.querySelectorAll('.question-dot');
            const timerElement = document.getElementById('timer');

            let currentIndex = 0;
            const timeLimit = {{ $customTest->time_limit_minutes }};
            let timeRemaining = timeLimit * 60;

            function showQuestion(index) {
                questions.forEach((q, i) => {
                    q.classList.toggle('hidden', i !== index);
                });

                dots.forEach((d, i) => {
                    d.classList.toggle('bg-corporate-primary', i === index);
                    d.classList.toggle('bg-gray-300', i !== index);
                });

                currentQuestionSpan.textContent = index + 1;

                prevBtn.disabled = index === 0;

                if (index === questions.length - 1) {
                    nextBtn.classList.add('hidden');
                    submitBtn.classList.remove('hidden');
                } else {
                    nextBtn.classList.remove('hidden');
                    submitBtn.classList.add('hidden');
                }
            }

            prevBtn.addEventListener('click', () => {
                if (currentIndex > 0) {
                    currentIndex--;
                    showQuestion(currentIndex);
                }
            });

            nextBtn.addEventListener('click', () => {
                if (currentIndex < questions.length - 1) {
                    currentIndex++;
                    showQuestion(currentIndex);
                }
            });

            dots.forEach((dot, index) => {
                dot.addEventListener('click', () => {
                    currentIndex = index;
                    showQuestion(currentIndex);
                });
            });

            // Timer
            function updateTimer() {
                const minutes = Math.floor(timeRemaining / 60);
                const seconds = timeRemaining % 60;
                timerElement.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;

                if (timeRemaining <= 0) {
                    document.getElementById('test-form').submit();
                } else {
                    timeRemaining--;
                    setTimeout(updateTimer, 1000);
                }
            }

            updateTimer();
            showQuestion(0);
        });
    </script>
    @endpush
</x-app-layout>
