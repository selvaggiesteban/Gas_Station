<div x-data="pomodoroTimer()" class="bg-gray-800 text-white p-6 rounded-lg shadow-lg text-center max-w-sm mx-auto">
    <h3 class="text-xl font-bold mb-4">{{ __('Concentración Pomodoro') }} </h3>

    <div class="text-5xl font-mono mb-6">
        <span x-text="formatTime(timeLeft)">25:00</span>
    </div>

    <div class="space-x-2">
        <button @click="startTimer" x-show="!isRunning" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded transition">{{ __('Iniciar') }} </button>
        <button @click="pauseTimer" x-show="isRunning" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-4 rounded transition">{{ __('Pausar') }} </button>
        <button @click="resetTimer" class="bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded transition">{{ __('Reiniciar') }} </button>
    </div>

    <div class="mt-4 text-sm text-gray-400">
        {{ __('Estado:') }} <span x-text="status" class="font-bold text-white"></span>
    </div>
</div>

<script>
    function pomodoroTimer() {
        return {
            timeLeft: 25 * 60,
            isRunning: false,
            timer: null,
            status: 'Tiempo de Estudio',
            sessionId: null,
            
            formatTime(seconds) {
                const m = Math.floor(seconds / 60);
                const s = seconds % 60;
                return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
            },
            
            async startTimer() {
                if (!this.timer) {
                    // Iniciar sesión en el backend si no existe una activa
                    if (!this.sessionId && this.status === 'Tiempo de Estudio') {
                        try {
                            const response = await fetch('{{ route('estudio.iniciar') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({
                                    type: 'pomodoro',
                                    duration_minutes: 25
                                })
                            });
                            const data = await response.json();
                            if (data.success) {
                                this.sessionId = data.session.id;
                            }
                        } catch (error) {
                            console.error('Error starting study session:', error);
                        }
                    }

                    this.isRunning = true;
                    this.timer = setInterval(() => {
                        if (this.timeLeft > 0) {
                            this.timeLeft--;
                        } else {
                            this.finishCycle();
                        }
                    }, 1000);
                }
            },
            
            pauseTimer() {
                this.isRunning = false;
                clearInterval(this.timer);
                this.timer = null;
            },
            
            resetTimer() {
                if (this.sessionId) {
                    fetch('{{ route('estudio.cancelar') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        }
                    });
                    this.sessionId = null;
                }
                
                this.pauseTimer();
                this.timeLeft = 25 * 60;
                this.status = 'Tiempo de Estudio';
            },

            async finishCycle() {
                this.pauseTimer();
                
                if (this.sessionId && this.status === 'Tiempo de Estudio') {
                    try {
                        await fetch(`/estudio/completar/${this.sessionId}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                notes: 'Pomodoro completado desde la interfaz web'
                            })
                        });
                        this.sessionId = null;
                    } catch (error) {
                        console.error('Error completing study session:', error);
                    }

                    alert('¡Buen trabajo! Has completado 25 minutos de estudio. Tómate un descanso de 5 minutos.');
                    this.timeLeft = 5 * 60; // 5 min descanso
                    this.status = 'Tiempo de Descanso';
                } else if (this.status === 'Tiempo de Descanso') {
                    alert('El descanso ha terminado. ¿Listo para otra sesión?');
                    this.timeLeft = 25 * 60;
                    this.status = 'Tiempo de Estudio';
                }
            }
        }
    }
</script>