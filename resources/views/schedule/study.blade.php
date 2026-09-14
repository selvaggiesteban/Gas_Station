<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                📚 Sesión de Estudio - {{ $schedule->title }}
            </h2>
            <a href="{{ route('cronograma.personal') }}" class="text-sm text-corporate-primary hover:text-orange-700 font-medium flex items-center">
                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>{{ __('Volver al Cronograma') }} </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <!-- Info del Evento -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6 border-l-4 border-corporate-primary">
                <div class="p-6">
                    <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">{{ $schedule->title }} </h3>
                            @if($schedule->description)<p class="text-sm text-gray-600 mt-2 bg-gray-50 p-3 rounded-lg">{{ $schedule->description }} </p>
                            @endif
                            <div class="flex items-center text-sm text-gray-500 mt-3">
                                <svg class="w-4 h-4 mr-1.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ $schedule->start_time->format('d/m/Y H:i') }} - {{ $schedule->end_time->format('H:i') }}
                            </div>
                        </div>

                        <form action="{{ route('cronograma.update', $schedule) }}" method="POST" class="w-full md:w-auto">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="is_completed" value="1">
                            <button type="submit" class="w-full md:w-auto inline-flex justify-center items-center px-6 py-3 bg-green-600 border border-transparent rounded-lg font-bold text-sm text-white uppercase tracking-widest hover:bg-green-700 transition-colors shadow-sm">
                                <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>{{ __('Marcar como Completado') }} </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Pomodoro Timer (Alpine.js) -->
            <div x-data="pomodoroTimer()" class="bg-gradient-to-br from-corporate-primary to-orange-600 overflow-hidden shadow-xl sm:rounded-2xl text-white relative">
                <!-- Decorative background elements -->
                <div class="absolute top-0 right-0 -mt-10 -mr-10 w-40 h-40 bg-white opacity-10 rounded-full blur-2xl"></div>
                <div class="absolute bottom-0 left-0 -mb-10 -ml-10 w-40 h-40 bg-black opacity-10 rounded-full blur-2xl"></div>

                <div class="p-8 md:p-12 text-center relative z-10">
                    <h3 class="text-2xl font-bold mb-2 flex items-center justify-center">
                        <svg class="w-6 h-6 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>{{ __('Pomodoro Timer') }} </h3>
                    <p x-text="statusText" class="text-orange-100 mb-8 font-medium h-6"></p>

                    <!-- Timer Display -->
                    <div class="mb-10">
                        <div class="text-8xl md:text-9xl font-mono font-bold tracking-tight drop-shadow-md" x-text="formattedTime"></div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="max-w-md mx-auto mb-10 bg-black/20 p-1 rounded-full backdrop-blur-sm">
                        <div class="rounded-full h-3 overflow-hidden relative">
                            <div class="bg-white h-full rounded-full transition-all duration-1000 ease-linear shadow-[0_0_10px_rgba(255,255,255,0.8)]" :style="`width: ${progressPercentage}%`"></div>
                        </div>
                    </div>

                    <!-- Main Controls -->
                    <div class="flex justify-center gap-4 mb-10">
                        <button x-show="!isRunning" @click="startTimer()" class="px-8 py-4 bg-white text-corporate-primary rounded-xl font-bold hover:bg-orange-50 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-1 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="solid" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>{{ __('Iniciar') }} </button>

                        <button x-show="isRunning" @click="pauseTimer()" class="px-8 py-4 bg-yellow-400 text-yellow-900 rounded-xl font-bold hover:bg-yellow-300 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-1 flex items-center" style="display: none;">
                            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>{{ __('Pausar') }} </button>

                        <button @click="resetTimer()" class="px-8 py-4 bg-black/20 text-white rounded-xl font-bold hover:bg-black/30 transition-all backdrop-blur-sm flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>{{ __('Reiniciar') }} </button>
                    </div>

                    <!-- Presets -->
                    <div class="flex flex-wrap justify-center gap-3">
                        <button @click="setMode('work')" :class="{'ring-2 ring-white ring-offset-2 ring-offset-corporate-primary': mode === 'work'}" class="px-5 py-2.5 bg-black/20 text-white rounded-lg text-sm font-medium hover:bg-black/30 transition-colors flex items-center backdrop-blur-sm">{{ __('💻 Trabajo (25m)') }} </button>

                        <button @click="setMode('shortBreak')" :class="{'ring-2 ring-white ring-offset-2 ring-offset-corporate-primary': mode === 'shortBreak'}" class="px-5 py-2.5 bg-green-500/30 text-white rounded-lg text-sm font-medium hover:bg-green-500/50 transition-colors flex items-center backdrop-blur-sm">{{ __('☕ Descanso Corto (5m)') }} </button>

                        <button @click="setMode('longBreak')" :class="{'ring-2 ring-white ring-offset-2 ring-offset-corporate-primary': mode === 'longBreak'}" class="px-5 py-2.5 bg-orange-500/30 text-white rounded-lg text-sm font-medium hover:bg-orange-500/50 transition-colors flex items-center backdrop-blur-sm">{{ __('🚶 Descanso Largo (15m)') }} </button>
                    </div>
                </div>
            </div>

            <!-- Estadísticas de la Sesión -->
            <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-xl p-6 text-center shadow-md border-t-4 border-red-500">
                    <div class="w-12 h-12 mx-auto bg-red-100 rounded-full flex items-center justify-center mb-3">
                        <svg class="w-6 h-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z" />
                        </svg>
                    </div>
                    <div id="session-count" class="text-3xl font-black text-gray-800">0</div>
                    <div class="text-sm font-semibold text-gray-500 uppercase tracking-wide mt-1">{{ __('Pomodoros') }} </div>
                </div>

                <div class="bg-white rounded-xl p-6 text-center shadow-md border-t-4 border-corporate-primary">
                    <div class="w-12 h-12 mx-auto bg-orange-100 rounded-full flex items-center justify-center mb-3">
                        <svg class="w-6 h-6 text-corporate-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                        </svg>
                    </div>
                    <div id="focus-time" class="text-3xl font-black text-gray-800">0</div>
                    <div class="text-sm font-semibold text-gray-500 uppercase tracking-wide mt-1">{{ __('Minutos Focus') }} </div>
                </div>

                <div class="bg-white rounded-xl p-6 text-center shadow-md border-t-4 border-green-500">
                    <div class="w-12 h-12 mx-auto bg-green-100 rounded-full flex items-center justify-center mb-3">
                        <svg class="w-6 h-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                        </svg>
                    </div>
                    <div id="break-time" class="text-3xl font-black text-gray-800">0</div>
                    <div class="text-sm font-semibold text-gray-500 uppercase tracking-wide mt-1">{{ __('Minutos Descanso') }} </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('pomodoroTimer', () => ({
                modes: {
                    work: { time: 25 * 60, name: 'Trabajo', status: '¡Concéntrate al máximo!' },
                    shortBreak: { time: 5 * 60, name: 'Descanso Corto', status: 'Relájate un momento.' },
                    longBreak: { time: 15 * 60, name: 'Descanso Largo', status: 'Tómate un buen respiro.' }
                },
                mode: 'work',
                timeRemaining: 25 * 60,
                totalTime: 25 * 60,
                isRunning: false,
                timerInterval: null,
                
                // Stats
                sessionsCompleted: 0,
                totalFocusMinutes: 0,
                totalBreakMinutes: 0,

                init() {
                    this.setMode('work');
                },

                get formattedTime() {
                    const minutes = Math.floor(this.timeRemaining / 60);
                    const seconds = this.timeRemaining % 60;
                    return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                },

                get progressPercentage() {
                    return (this.timeRemaining / this.totalTime) * 100;
                },

                get statusText() {
                    if (!this.isRunning && this.timeRemaining === this.totalTime) {
                        return `¿Listo para empezar? - ${this.modes[this.mode].name}`;
                    }
                    if (!this.isRunning && this.timeRemaining < this.totalTime) {
                        return '⏸️ Pausado';
                    }
                    return `⏳ ${this.modes[this.mode].status}`;
                },

                startTimer() {
                    if (this.isRunning) return;
                    this.isRunning = true;
                    
                    // Prevent multiple intervals
                    if (this.timerInterval) clearInterval(this.timerInterval);

                    this.timerInterval = setInterval(() => {
                        if (this.timeRemaining > 0) {
                            this.timeRemaining--;
                            
                            // Live update of focus minutes during work mode
                            if (this.mode === 'work' && (this.totalTime - this.timeRemaining) % 60 === 0) {
                                document.getElementById('focus-time').textContent = this.totalFocusMinutes + Math.floor((this.totalTime - this.timeRemaining) / 60);
                            }
                            
                        } else {
                            this.completeTimer();
                        }
                    }, 1000);
                },

                pauseTimer() {
                    clearInterval(this.timerInterval);
                    this.isRunning = false;
                },

                resetTimer() {
                    clearInterval(this.timerInterval);
                    this.isRunning = false;
                    this.timeRemaining = this.totalTime;
                    
                    // Reset live updates if reset mid-session
                    if (this.mode === 'work') {
                        document.getElementById('focus-time').textContent = this.totalFocusMinutes;
                    }
                },

                setMode(newMode) {
                    this.pauseTimer();
                    this.mode = newMode;
                    this.totalTime = this.modes[newMode].time;
                    this.timeRemaining = this.totalTime;
                },

                completeTimer() {
                    this.pauseTimer();
                    this.playBeep();

                    // Update stats
                    if (this.mode === 'work') {
                        this.sessionsCompleted++;
                        this.totalFocusMinutes += 25;
                        document.getElementById('session-count').textContent = this.sessionsCompleted;
                        document.getElementById('focus-time').textContent = this.totalFocusMinutes;
                        
                        // Switch to break automatically
                        if (this.sessionsCompleted > 0 && this.sessionsCompleted % 4 === 0) {
                            this.setMode('longBreak');
                        } else {
                            this.setMode('shortBreak');
                        }
                    } else {
                        // Was on break, add to break minutes
                        this.totalBreakMinutes += (this.mode === 'shortBreak' ? 5 : 15);
                        document.getElementById('break-time').textContent = this.totalBreakMinutes;
                        this.setMode('work');
                    }
                    
                    // Optional: show a browser notification
                    if (Notification && Notification.permission === 'granted') {
                        new Notification('¡Pomodoro!', {
                            body: this.mode === 'work' ? '¡Es hora de volver al trabajo!' : '¡Tiempo de descanso!'
                        });
                    } else if (Notification && Notification.permission !== 'denied') {
                        Notification.requestPermission();
                    }
                },

                playBeep() {
                    try {
                        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                        const oscillator = audioCtx.createOscillator();
                        const gainNode = audioCtx.createGain();

                        oscillator.connect(gainNode);
                        gainNode.connect(audioCtx.destination);

                        oscillator.frequency.value = 800;
                        oscillator.type = 'sine';

                        gainNode.gain.setValueAtTime(0.3, audioCtx.currentTime);
                        gainNode.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.5);

                        oscillator.start(audioCtx.currentTime);
                        oscillator.stop(audioCtx.currentTime + 0.5);
                    } catch(e) {
                        console.error('AudioContext no soportado:', e);
                    }
                }
            }));
        });
    </script>
    @endpush
</x-app-layout>
