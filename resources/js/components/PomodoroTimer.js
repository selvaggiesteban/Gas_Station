/**
 * Pomodoro Timer Component
 * Integrates with Academicus study session API
 */
class PomodoroTimer {
    constructor(options = {}) {
        this.container = document.querySelector(options.container || '#pomodoro-timer');
        this.onComplete = options.onComplete || (() => {});
        this.onStart = options.onStart || (() => {});
        this.onCancel = options.onCancel || (() => {});

        // Timer configuration
        this.durations = {
            pomodoro: 25 * 60,    // 25 minutes
            short_break: 5 * 60,  // 5 minutes
            long_break: 15 * 60,  // 15 minutes
        };

        this.currentType = 'pomodoro';
        this.timeLeft = this.durations.pomodoro;
        this.isRunning = false;
        this.interval = null;
        this.sessionId = null;

        // Audio context for notification
        this.audioContext = null;
        this.initAudio();

        // Initialize UI if container exists
        if (this.container) {
            this.render();
            this.attachEventListeners();
            this.checkActiveSession();
        }
    }

    initAudio() {
        try {
            window.AudioContext = window.AudioContext || window.webkitAudioContext;
            this.audioContext = new AudioContext();
        } catch (e) {
            console.warn('Web Audio API not supported');
        }
    }

    playNotificationSound() {
        if (!this.audioContext) return;

        const oscillator = this.audioContext.createOscillator();
        const gainNode = this.audioContext.createGain();

        oscillator.connect(gainNode);
        gainNode.connect(this.audioContext.destination);

        oscillator.frequency.value = 800;
        oscillator.type = 'sine';

        gainNode.gain.setValueAtTime(0.3, this.audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, this.audioContext.currentTime + 0.5);

        oscillator.start(this.audioContext.currentTime);
        oscillator.stop(this.audioContext.currentTime + 0.5);
    }

    render() {
        this.container.innerHTML = `
            <div class="pomodoro-container bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 max-w-md mx-auto">
                <div class="flex justify-center space-x-2 mb-6">
                    <button type="button" data-type="pomodoro" class="timer-type-btn px-4 py-2 rounded-lg text-sm font-medium transition-colors bg-blue-600 text-white">
                        Pomodoro
                    </button>
                    <button type="button" data-type="short_break" class="timer-type-btn px-4 py-2 rounded-lg text-sm font-medium transition-colors bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                        Descanso Corto
                    </button>
                    <button type="button" data-type="long_break" class="timer-type-btn px-4 py-2 rounded-lg text-sm font-medium transition-colors bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                        Descanso Largo
                    </button>
                </div>

                <div class="timer-display text-center mb-8">
                    <div class="text-7xl font-bold text-gray-800 dark:text-white font-mono tracking-wider">
                        ${this.formatTime(this.timeLeft)}
                    </div>
                    <div class="timer-status text-gray-500 dark:text-gray-400 mt-2 text-sm">
                        ${this.isRunning ? 'En progreso...' : 'Listo para comenzar'}
                    </div>
                </div>

                <div class="timer-progress mb-6">
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2.5">
                        <div class="timer-progress-bar bg-blue-600 h-2.5 rounded-full transition-all duration-1000" style="width: 0%"></div>
                    </div>
                </div>

                <div class="flex justify-center space-x-4">
                    <button type="button" class="timer-start-btn bg-green-600 hover:bg-green-700 text-white px-8 py-3 rounded-lg font-medium text-lg transition-colors flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Iniciar
                    </button>
                    <button type="button" class="timer-pause-btn bg-yellow-600 hover:bg-yellow-700 text-white px-6 py-3 rounded-lg font-medium text-lg transition-colors hidden">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </button>
                    <button type="button" class="timer-cancel-btn bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg font-medium text-lg transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="mt-6 text-center">
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        Pomodoros completados hoy: <span class="pomodoro-count font-bold text-blue-600">0</span>
                    </div>
                </div>
            </div>
        `;
    }

    attachEventListeners() {
        // Type selection
        this.container.querySelectorAll('.timer-type-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.setType(e.target.dataset.type));
        });

        // Start button
        this.container.querySelector('.timer-start-btn').addEventListener('click', () => this.start());

        // Pause button
        this.container.querySelector('.timer-pause-btn').addEventListener('click', () => this.pause());

        // Cancel button
        this.container.querySelector('.timer-cancel-btn').addEventListener('click', () => this.cancel());
    }

    setType(type) {
        if (this.isRunning) {
            if (!confirm('Cambiar el tipo reiniciará el temporizador. ¿Continuar?')) {
                return;
            }
            this.cancel();
        }

        this.currentType = type;
        this.timeLeft = this.durations[type];
        this.updateDisplay();

        // Update button styles
        this.container.querySelectorAll('.timer-type-btn').forEach(btn => {
            if (btn.dataset.type === type) {
                btn.classList.remove('bg-gray-200', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');
                btn.classList.add('bg-blue-600', 'text-white');
            } else {
                btn.classList.remove('bg-blue-600', 'text-white');
                btn.classList.add('bg-gray-200', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');
            }
        });
    }

    async start() {
        if (this.isRunning) return;

        try {
            // Start session via API
            const response = await fetch('/study/start', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                },
                body: JSON.stringify({
                    type: this.currentType,
                }),
            });

            const data = await response.json();

            if (data.success) {
                this.sessionId = data.session.id;
                this.isRunning = true;
                this.startTime = Date.now();
                this.totalTime = this.timeLeft;

                // Update UI
                this.container.querySelector('.timer-start-btn').classList.add('hidden');
                this.container.querySelector('.timer-pause-btn').classList.remove('hidden');
                this.container.querySelector('.timer-status').textContent = 'En progreso...';

                this.onStart({
                    type: this.currentType,
                    sessionId: this.sessionId,
                });

                // Start countdown
                this.interval = setInterval(() => this.tick(), 1000);
            }
        } catch (error) {
            console.error('Failed to start session:', error);
            alert('Error al iniciar la sesión. Por favor intenta de nuevo.');
        }
    }

    pause() {
        if (!this.isRunning) return;

        clearInterval(this.interval);
        this.isRunning = false;

        this.container.querySelector('.timer-start-btn').classList.remove('hidden');
        this.container.querySelector('.timer-start-btn').innerHTML = `
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Continuar
        `;
        this.container.querySelector('.timer-pause-btn').classList.add('hidden');
        this.container.querySelector('.timer-status').textContent = 'Pausado';
    }

    async cancel() {
        clearInterval(this.interval);

        try {
            await fetch('/study/cancel', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                },
            });
        } catch (error) {
            console.error('Failed to cancel session:', error);
        }

        this.reset();
        this.onCancel();
    }

    tick() {
        this.timeLeft--;
        this.updateDisplay();

        if (this.timeLeft <= 0) {
            this.complete();
        }
    }

    async complete() {
        clearInterval(this.interval);
        this.isRunning = false;
        this.playNotificationSound();

        try {
            if (this.sessionId) {
                await fetch(`/study/complete/${this.sessionId}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    },
                    body: JSON.stringify({
                        notes: `Completed ${this.currentType}`,
                    }),
                });
            }
        } catch (error) {
            console.error('Failed to complete session:', error);
        }

        // Update UI
        this.container.querySelector('.timer-status').textContent = '¡Completado!';
        this.updateStats();

        this.onComplete({
            type: this.currentType,
            sessionId: this.sessionId,
        });

        // Reset after delay
        setTimeout(() => this.reset(), 3000);
    }

    reset() {
        clearInterval(this.interval);
        this.isRunning = false;
        this.timeLeft = this.durations[this.currentType];
        this.sessionId = null;

        this.container.querySelector('.timer-start-btn').classList.remove('hidden');
        this.container.querySelector('.timer-start-btn').innerHTML = `
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Iniciar
        `;
        this.container.querySelector('.timer-pause-btn').classList.add('hidden');
        this.container.querySelector('.timer-status').textContent = 'Listo para comenzar';

        this.updateDisplay();
    }

    updateDisplay() {
        this.container.querySelector('.timer-display div').textContent = this.formatTime(this.timeLeft);

        // Update progress bar
        const totalTime = this.durations[this.currentType];
        const progress = ((totalTime - this.timeLeft) / totalTime) * 100;
        this.container.querySelector('.timer-progress-bar').style.width = `${progress}%`;
    }

    async updateStats() {
        try {
            const response = await fetch('/study/stats');
            const data = await response.json();

            if (data.success) {
                this.container.querySelector('.pomodoro-count').textContent = data.stats.today_pomodoros;
            }
        } catch (error) {
            console.error('Failed to load stats:', error);
        }
    }

    async checkActiveSession() {
        try {
            const response = await fetch('/study/current');
            const data = await response.json();

            if (data.success && data.active) {
                // Restore active session
                this.sessionId = data.session.id;
                this.currentType = data.session.type;
                this.timeLeft = data.remaining_seconds;
                this.isRunning = true;

                // Update UI
                this.container.querySelector('.timer-start-btn').classList.add('hidden');
                this.container.querySelector('.timer-pause-btn').classList.remove('hidden');
                this.container.querySelector('.timer-status').textContent = 'En progreso...';

                // Update type buttons
                this.container.querySelectorAll('.timer-type-btn').forEach(btn => {
                    if (btn.dataset.type === this.currentType) {
                        btn.classList.remove('bg-gray-200', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');
                        btn.classList.add('bg-blue-600', 'text-white');
                    } else {
                        btn.classList.remove('bg-blue-600', 'text-white');
                        btn.classList.add('bg-gray-200', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');
                    }
                });

                this.interval = setInterval(() => this.tick(), 1000);
            }

            // Load stats
            this.updateStats();
        } catch (error) {
            console.error('Failed to check active session:', error);
        }
    }

    formatTime(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PomodoroTimer;
}

// Auto-initialize if element exists
document.addEventListener('DOMContentLoaded', () => {
    const timerElement = document.querySelector('#pomodoro-timer');
    if (timerElement) {
        window.pomodoroTimer = new PomodoroTimer();
    }
});
