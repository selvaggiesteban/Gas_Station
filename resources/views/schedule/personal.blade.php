<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-corporate-primary leading-tight">
                {{ __('Mi Cronograma Personal') }}
            </h2>
            
            <!-- Pomodoro Timer -->
            <div x-data="pomodoro()" class="bg-white rounded-full shadow-sm px-4 py-1.5 flex items-center space-x-4 border border-orange-100">
                <div class="flex items-center text-corporate-primary font-bold text-lg">
                    <svg class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span x-text="formatTime(time)">25:00</span>
                </div>
                <div class="flex space-x-2 border-l pl-4 border-gray-100">
                    <button @click="toggle()" class="text-gray-500 hover:text-corporate-primary focus:outline-none transition">
                        <template x-if="!isRunning">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd" /></svg>
                        </template>
                        <template x-if="isRunning">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zM7 8a1 1 0 012 0v4a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v4a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                        </template>
                    </button>
                    <button @click="reset()" class="text-gray-400 hover:text-red-500 focus:outline-none transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
                    </button>
                </div>
            </div>

            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-corporate-primary text-white">{{ __('Editable') }} </span>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                {{-- Formulario para agregar evento --}}
                <div class="lg:col-span-1">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-bold mb-4">{{ __('Agregar Evento') }} </h3>

                            @if(session('success'))
                                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-md">
                                    {{ session('success') }}
                                </div>
                            @endif

                            <form action="{{ route('cronograma.store') }}" method="POST">
                                @csrf
                                <div class="space-y-4">
                                    <div>
                                        <label for="title" class="block text-sm font-medium text-gray-700">{{ __('Título') }} </label>
                                        <input type="text" name="title" id="title" required
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-corporate-primary focus:ring-corporate-primary"
                                            placeholder="{{ __('Ej: Estudiar Tema 3') }}">
                                    </div>

                                    <div>
                                        <label for="topic_id" class="block text-sm font-medium text-gray-700">{{ __('Tema relacionado (opcional)') }} </label>
                                        <select name="topic_id" id="topic_id"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-corporate-primary focus:ring-corporate-primary">
                                            <option value="">{{ __('— Sin tema —') }}</option>
                                            @foreach($topics ?? [] as $topic)
                                                <option value="{{ $topic->id }}">{{ optional($topic->block?->course)->title ? $topic->block->course->title . ' › ' : '' }}{{ $topic->title }}</option>
                                            @endforeach
                                        </select>
                                        <x-input-error :messages="$errors->get('topic_id')" class="mt-2" />
                                    </div>

                                    <div>
                                        <label for="description" class="block text-sm font-medium text-gray-700">{{ __('Descripción (opcional)') }} </label>
                                        <textarea name="description" id="description" rows="3"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-corporate-primary focus:ring-corporate-primary"
                                            placeholder="{{ __('Notas adicionales...') }}"></textarea>
                                    </div>

                                    <div>
                                        <label for="start_time" class="block text-sm font-medium text-gray-700">{{ __('Fecha/Hora Inicio') }} </label>
                                        <input type="datetime-local" name="start_time" id="start_time" required
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-corporate-primary focus:ring-corporate-primary">
                                    </div>

                                    <div>
                                        <label for="end_time" class="block text-sm font-medium text-gray-700">{{ __('Fecha/Hora Fin') }} </label>
                                        <input type="datetime-local" name="end_time" id="end_time" required
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-corporate-primary focus:ring-corporate-primary">
                                    </div>

                                    <button type="submit"
                                        class="w-full inline-flex justify-center items-center px-4 py-2 bg-corporate-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-600 focus:bg-orange-600 active:bg-orange-700 focus:outline-none transition ease-in-out duration-150">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>{{ __('Agregar al Cronograma') }} </button>
                                </div>
                            </form>

                            <div class="mt-6 pt-6 border-t border-gray-200">
                                <h4 class="text-sm font-medium text-gray-700 mb-2">{{ __('Instrucciones:') }} </h4>
                                <ul class="text-sm text-gray-500 space-y-1 list-disc list-inside">
                                    <li>{{ __('Arrastra los eventos para reordenarlos') }} </li>
                                    <li>{{ __('Marca como completado cuando termines') }} </li>
                                    <li>{{ __('Haz clic en "Estudiar" para iniciar el Pomodoro') }} </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Lista de eventos con drag-drop --}}
                <div class="lg:col-span-2">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-corporate-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                </svg>{{ __('Mis Eventos (Arrastra para reordenar)') }} </h3>

                            @if($personalSchedules->isEmpty())<div class="text-center py-12 text-gray-500">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <p class="mt-4 text-sm">{{ __('No tienes eventos personales. ¡Agrega uno!') }} </p>
                                </div>
                            @else
                                <ul id="sortable-schedule" class="space-y-2">
                                    @foreach($personalSchedules as $schedule)
                                        <li class="schedule-item bg-white border rounded-lg p-4 cursor-move hover:shadow-md transition-shadow"
                                            data-id="{{ $schedule->id }}">
                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center gap-4">
                                                    <div class="cursor-move text-gray-400 hover:text-gray-600">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                                                        </svg>
                                                    </div>

                                                    <form action="{{ route('cronograma.update', $schedule) }}" method="POST" class="inline">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="is_completed" value="{{ $schedule->is_completed ? '0' : '1' }}">
                                                        <button type="submit" class="focus:outline-none">
                                                            <div class="w-6 h-6 rounded-full border-2 {{ $schedule->is_completed ? 'bg-green-500 border-green-500' : 'border-gray-300' }} flex items-center justify-center">
                                                                @if($schedule->is_completed)<svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                                    </svg>
                                                                @endif
                                                            </div>
                                                        </button>
                                                    </form>

                                                    <div>
                                                        <p class="font-medium {{ $schedule->is_completed ? 'line-through text-gray-400' : 'text-gray-900' }}">
                                                            {{ $schedule->title }}
                                                        </p>
                                                        <p class="text-sm text-gray-500">
                                                            {{ $schedule->start_time->format('d/m/Y H:i') }} - {{ $schedule->end_time->format('H:i') }}
                                                        </p>
                                                        @if($schedule->description)<p class="text-sm text-gray-400 mt-1">{{ $schedule->description }} </p>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="flex items-center gap-2">
                                                    <a href="{{ route('cronograma.estudiar', $schedule) }}"
                                                        class="inline-flex items-center px-3 py-1 bg-green-100 text-green-700 rounded-md text-sm hover:bg-green-200 transition">
                                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>{{ __('Estudiar') }} </a>

                                                    <form action="{{ route('cronograma.destroy', $schedule) }}" method="POST" class="inline"
                                                        onsubmit="return confirm('{{ __('¿Eliminar este evento?') }}')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-500 hover:text-red-700 p-1">
                                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>

                                <div id="reorder-feedback" class="mt-4 hidden">
                                    <div class="flex items-center text-green-600">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>{{ __('Orden guardado') }} </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const el = document.getElementById('sortable-schedule');
                if (el) {
                    new Sortable(el, {
                        animation: 150,
                        ghostClass: 'bg-orange-50',
                        dragClass: 'opacity-50',
                        handle: '.cursor-move',
                        onEnd: function(evt) {
                            saveOrder();
                        }
                    });
                }

                function saveOrder() {
                    const items = [];
                    document.querySelectorAll('.schedule-item').forEach(function(item) {
                        items.push(item.dataset.id);
                    });

                    fetch('{{ route('cronograma.reordenar') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            items: items
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const feedback = document.getElementById('reorder-feedback');
                            feedback.classList.remove('hidden');
                            setTimeout(() => {
                                feedback.classList.add('hidden');
                            }, 2000);
                        }
                    })
                    .catch(error => {
                        console.error('Error saving order:', error);
                    });
                }
            });
        </script>
    @endpush
</x-app-layout>
