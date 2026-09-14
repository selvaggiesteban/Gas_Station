<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $test->title }}
            </h2>
            <!-- Timer Display -->
            <div id="timer-container" class="flex items-center bg-corporate-primary text-white px-4 py-2 rounded-lg shadow-md">
                <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span id="timer" class="font-mono text-lg font-bold" data-duration="{{ $test->duration_minutes ?? 60 }}">60:00</span>
            </div>
        </div>
    </x-slot>

    <!-- Progress Bar (Sticky) -->
    <div class="sticky top-0 z-50 bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-600">{{ __('Progreso') }} </span>
                <span id="progress-text" class="text-sm font-medium text-corporate-primary">0/{{ $test->questions->count() }} </span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2.5">
                <div id="progress-bar" class="bg-corporate-primary h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
            </div>
        </div>
    </div>

    <div class="py-8" x-data="{ 
        doubts: [], 
        favorites: [],
        toggleDoubt(id) {
            fetch('{{ route('questions.toggle-doubt') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ question_id: id })
            }).then(r => r.json()).then(data => {
                if(data.is_doubt) this.doubts.push(id);
                else this.doubts = this.doubts.filter(i => i !== id);
            });
        },
        toggleFavorite(id) {
            fetch('{{ route('questions.toggle-favorite') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                body: JSON.stringify({ question_id: id })
            }).then(r => r.json()).then(data => {
                if(data.is_favorite) this.favorites.push(id);
                else this.favorites = this.favorites.filter(i => i !== id);
            });
        }
    }">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <!-- Test Info Card -->
            <div class="bg-gradient-to-r from-corporate-primary/10 to-[#FF9933]/10 rounded-lg p-4 mb-6 border border-corporate-primary/20">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-corporate-primary mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span class="text-sm text-gray-700">
                            <strong>{{ $test->questions->count() }} </strong>{{ __('preguntas ·') }} <strong>{{ $test->duration_minutes ?? 60 }} </strong>{{ __('minutos') }} </span>
                    </div>
                    @if(($attemptStatus['remaining'] ?? null) !== null)
                        <span class="text-sm text-gray-600">{{ __('Intentos restantes:') }} <strong class="text-corporate-primary">{{ $attemptStatus['remaining'] }} </strong>
                        </span>
                    @endif
                </div>
            </div>

            <form action="{{ route('examenes.enviar', $test) }}" method="POST" id="test-form">
                @csrf

                @foreach($test->questions as $question)<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 question-card" data-question="{{ $loop->iteration }}">
                        <div class="p-6">
                            <div class="flex items-start mb-4">
                                <span class="flex-shrink-0 w-8 h-8 bg-corporate-primary text-white rounded-full flex items-center justify-center font-bold text-sm mr-3">
                                    {{ $loop->iteration }}
                                </span>
                                <div class="flex-1 flex justify-between items-start">
                                    <h3 class="text-lg font-bold text-gray-900">
                                        {{ $question->text }}
                                    </h3>
                                    <div class="flex items-center text-xs text-gray-400 font-mono ml-4 bg-gray-50 px-2 py-1 rounded border border-gray-100">
                                        <svg class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span class="question-timer" data-seconds="0">00:00</span>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-3 ml-11">
                                @if($question->type === 'multiple_choice' || $question->type === 'five_options')
                                    @foreach($question->options as $option)
                                        <label class="flex items-center p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition option-label">
                                            <input type="radio" name="answers[{{ $question->id }}]" value="{{ $option->id }}" class="w-4 h-4 text-corporate-primary border-gray-300 focus:ring-corporate-primary question-option" data-question="{{ $loop->parent->iteration }}">
                                            <span class="ml-3 text-gray-700">{{ $option->text }} </span>
                                        </label>
                                    @endforeach
                                @elseif($question->type === 'practical_case')
                                    <textarea name="practical_cases[{{ $question->id }}]" rows="5" class="w-full border-gray-300 focus:border-corporate-primary focus:ring-corporate-primary rounded-md shadow-sm question-option" data-question="{{ $loop->iteration }}" placeholder="{{ __('Escribe tu respuesta aquí...') }}"></textarea>
                                @elseif($question->type === 'open_ended')
                                    <textarea name="open_ended[{{ $question->id }}]" rows="5" class="w-full border-gray-300 focus:border-corporate-primary focus:ring-corporate-primary rounded-md shadow-sm question-option" data-question="{{ $loop->iteration }}" placeholder="{{ __('Escribe tu respuesta aquí...') }}"></textarea>
                                @elseif($question->type === 'fill_blank')
                                    <div class="bg-gray-50 p-4 rounded-lg border border-dashed border-gray-300">
                                        <input type="text" name="fill_blanks[{{ $question->id }}]" class="w-full border-gray-300 focus:border-corporate-primary focus:ring-corporate-primary rounded-md shadow-sm question-option" data-question="{{ $loop->iteration }}" placeholder="{{ __('Completa el texto faltante...') }}">
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="flex justify-between items-center mt-8">
                    <div class="text-sm text-gray-500">
                        <span id="answered-count">0</span> de {{ $test->questions->count() }} preguntas respondidas
                    </div>
                    <button type="submit" id="submit-btn" class="inline-flex items-center px-6 py-3 bg-corporate-primary border border-transparent rounded-md font-bold text-sm text-white uppercase tracking-widest hover:bg-[#D66A0F] active:bg-[#C25E0D] focus:outline-none focus:ring-2 focus:ring-corporate-primary focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition ease-in-out duration-150">
                        <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        {{ __('Finalizar y Entregar') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Timer functionality
            const timerElement = document.getElementById('timer');
            const timerContainer = document.getElementById('timer-container');
            const durationMinutes = parseInt(timerElement.dataset.duration);
            let timeRemaining = durationMinutes * 60; // in seconds

            function updateTimer() {
                const minutes = Math.floor(timeRemaining / 60);
                const seconds = timeRemaining % 60;
                timerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;

                // Add warning styles when time is running low
                if (timeRemaining <= 300) { // 5 minutes
                    timerContainer.classList.remove('bg-corporate-primary');
                    timerContainer.classList.add('bg-red-600', 'animate-pulse');
                } else if (timeRemaining <= 600) { // 10 minutes
                    timerContainer.classList.remove('bg-corporate-primary');
                    timerContainer.classList.add('bg-yellow-500');
                }

                if (timeRemaining <= 0) {
                    document.getElementById('test-form').submit();
                } else {
                    timeRemaining--;
                }
            }

            setInterval(updateTimer, 1000);
            updateTimer();

            // Progress tracking
            const totalQuestions = {{ $test->questions->count() }};
            const progressBar = document.getElementById('progress-bar');
            const progressText = document.getElementById('progress-text');
            const answeredCount = document.getElementById('answered-count');
            const options = document.querySelectorAll('.question-option');

            function updateProgress() {
                const answered = new Set();
                options.forEach(option => {
                    if (option.type === 'radio' || option.type === 'checkbox') {
                        if (option.checked) {
                            answered.add(option.dataset.question);
                        }
                    } else if (option.value && option.value.trim() !== '') {
                        answered.add(option.dataset.question);
                    }
                });

                const count = answered.size;
                const percentage = (count / totalQuestions) * 100;

                progressBar.style.width = percentage + '%';
                progressText.textContent = `${count}/${totalQuestions}`;
                answeredCount.textContent = count;

                // Highlight answered questions
                document.querySelectorAll('.question-card').forEach((card, index) => {
                    if (answered.has((index + 1).toString())) {
                        card.classList.add('ring-2', 'ring-corporate-primary/50');
                    } else {
                        card.classList.remove('ring-2', 'ring-corporate-primary/50');
                    }
                });
            }

            options.forEach(option => {
                option.addEventListener('change', updateProgress);
                option.addEventListener('input', updateProgress);
            });

            // Form submission confirmation
            document.getElementById('test-form').addEventListener('submit', function(e) {
                const answered = new Set();
                options.forEach(option => {
                    if (option.type === 'radio' || option.type === 'checkbox') {
                        if (option.checked) {
                            answered.add(option.dataset.question);
                        }
                    } else if (option.value && option.value.trim() !== '') {
                        answered.add(option.dataset.question);
                    }
                });

                if (answered.size < totalQuestions) {
                    const unanswered = totalQuestions - answered.size;
                    if (!confirm(`Te faltan ${unanswered} preguntas por responder. ¿Estás seguro de que quieres entregar el test?`)) {
                        e.preventDefault();
                    }
                }
            });

            // Per-question timer tracking
            const questionTimers = document.querySelectorAll('.question-timer');
            const questionCards = document.querySelectorAll('.question-card');
            let activeQuestionIndex = -1;

            const observerOptions = {
                root: null,
                rootMargin: '-20% 0px -70% 0px',
                threshold: 0
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        activeQuestionIndex = parseInt(entry.target.dataset.question) - 1;
                    }
                });
            }, observerOptions);

            questionCards.forEach(card => observer.observe(card));

            setInterval(() => {
                if (activeQuestionIndex !== -1 && activeQuestionIndex < questionTimers.length) {
                    const timer = questionTimers[activeQuestionIndex];
                    let seconds = parseInt(timer.dataset.seconds) + 1;
                    timer.dataset.seconds = seconds;
                    
                    const mins = Math.floor(seconds / 60);
                    const secs = seconds % 60;
                    timer.textContent = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
                }
            }, 1000);
        });
    </script>
    @endpush
</x-app-layout>
