<x-app-layout>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

    <div class="container mx-auto px-4 py-8" x-data="{
        questionModalOpen: false,
        questionType: 'multiple_choice',
        options: [
            {text: '', is_correct: true}, 
            {text: '', is_correct: false}, 
            {text: '', is_correct: false}, 
            {text: '', is_correct: false},
            {text: '', is_correct: false}
        ],
        setCorrect(index) {
            this.options.forEach((o, i) => {
                this.options[i].is_correct = (i === index);
            });
        }
    }">
        <div class="mb-8 flex items-center justify-between">
            <div>
                <a href="{{ route('admin.cursos.show', $test->course) }}" class="text-sm text-gray-500 hover:text-gray-900 mb-2 inline-block">{{ __('← Volver al Curso') }} </a>
                <h1 class="text-3xl font-bold text-gray-900">Gestionar Preguntas: {{ $test->title }} </h1>
                <p class="text-gray-600 mt-1">{{ ucfirst(str_replace('_', ' ', $test->type)) }} | {{ $test->time_limit_minutes }} min</p>
            </div>
            <button @click="questionModalOpen = true; questionType = 'multiple_choice'" class="bg-corporate-primary text-white px-4 py-2 rounded-lg font-bold hover:bg-orange-600 transition">{{ __('+ Nueva Pregunta') }} </button>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <p class="font-bold">Error de validación:</p>
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Question List (Sortable) -->
        <div id="questions-container" class="space-y-4">
            @forelse($test->questions()->orderBy('display_order', 'asc')->get() as $question)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden question-item" data-id="{{ $question->id }}">
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex items-center">
                                <svg class="h-5 w-5 text-gray-400 mr-3 cursor-move" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                                <span class="bg-gray-100 text-gray-600 text-xs font-bold px-2 py-1 rounded uppercase mr-3">{{ str_replace('_', ' ', $question->type) }} </span>
                                <div class="text-lg font-bold text-gray-900 leading-tight">
                                    {!! nl2br(e($question->text)) !!}
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                <form action="{{ route('admin.preguntas.destroy', $question) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('¿Eliminar pregunta?') }}');">      
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </form>
                            </div>
                        </div>

                        @if($question->type === 'multiple_choice' || $question->type === 'five_options')
                            <ul class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-4">
                                @foreach($question->options as $option)
                                    <li class="p-3 rounded border {{ $option->is_correct ? 'bg-green-50 border-green-200 text-green-800' : 'bg-gray-50 border-gray-200 text-gray-600' }}">        
                                        <span class="font-bold mr-2">{{ chr(65 + $loop->index) }})</span> {{ $option->text }}
                                        @if($option->is_correct)<span class="ml-2 text-xs font-bold uppercase">{{ __('(Correcta)') }} </span> @endif
                                    </li>
                                @endforeach
                            </ul>
                        @elseif($question->type === 'fill_blank')
                            <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded text-blue-800">
                                <p class="text-sm font-bold uppercase mb-1">{{ __('Respuesta Correcta (Hueco):') }} </p>
                                <p class="font-mono">{{ $question->fill_blank_text }} </p>
                            </div>
                        @elseif($question->type === 'open_ended' || $question->type === 'practical_case')
                            <div class="mt-4 p-4 bg-purple-50 border border-purple-200 rounded text-purple-800 italic">
                                <p class="text-sm font-bold uppercase not-italic mb-1">{{ __('Pregunta de Desarrollo:') }} </p>
                                <p>{{ __('Esta pregunta requiere corrección manual por un profesor.') }} </p>
                            </div>
                        @endif

                        @if($question->explanation)
                            <div class="mt-4 pt-4 border-t border-gray-100 text-sm text-gray-500 italic">
                                <strong>{{ __('Explicación:') }} </strong> {{ $question->explanation }}
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="text-center py-12 bg-white rounded-lg shadow-sm border border-gray-200 text-gray-500 italic">{{ __('No hay preguntas en este test todavía.') }} </div>
            @endforelse
        </div>

        <!-- Create Question Modal -->
        <div x-show="questionModalOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" x-cloak>
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true" @click="questionModalOpen = false">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                    <form action="{{ route('admin.examenes.preguntas.store', $test) }}" method="POST" novalidate>
                        @csrf
                        <input type="hidden" name="test_id" value="{{ $test->id }}">
                        
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-8">
                            <h3 class="text-2xl leading-6 font-bold text-gray-900 mb-6">{{ __('Añadir Nueva Pregunta') }} </h3>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-bold text-gray-700 mb-2">{{ __('Enunciado de la Pregunta') }} </label>
                                    <textarea name="text" rows="4" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-corporate-primary focus:ring-corporate-primary" placeholder="{{ __('Escribe aquí la pregunta...') }}">{{ old('text') }}</textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-gray-700 mb-2">{{ __('Tipo de Pregunta') }} </label>
                                    <select name="type" x-model="questionType" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-corporate-primary focus:ring-corporate-primary">
                                        <option value="multiple_choice">{{ __('Opción Múltiple (4 opciones)') }} </option>
                                        <option value="five_options">{{ __('Opción Múltiple (5 opciones)') }} </option>
                                        <option value="fill_blank">{{ __('Rellenar Hueco') }} </option>
                                        <option value="practical_case">{{ __('Supuesto Práctico (Desarrollo)') }} </option>
                                        <option value="open_ended">{{ __('Pregunta Abierta (Desarrollo)') }} </option>
                                    </select>
                                    <div class="mt-4">
                                        <label class="block text-sm font-bold text-gray-700 mb-2">{{ __('Puntos') }} </label>
                                        <input type="number" name="points" value="{{ old('points', 1) }}" step="0.1" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-corporate-primary focus:ring-corporate-primary">
                                    </div>
                                </div>
                            </div>

                            <!-- Options for Multiple Choice -->
                            <template x-if="questionType === 'multiple_choice' || questionType === 'five_options'">
                                <div class="mb-6 bg-gray-50 p-6 rounded-lg border border-gray-200">
                                    <h4 class="font-bold text-gray-700 mb-4 uppercase text-sm tracking-wider">{{ __('Opciones de Respuesta') }} </h4>
                                    <div class="space-y-3">
                                        <template x-for="(option, index) in (questionType === 'five_options' ? [0,1,2,3,4] : [0,1,2,3])" :key="index">
                                            <div class="flex items-center gap-4">
                                                <div class="flex-none flex items-center justify-center w-8 h-8 rounded-full bg-gray-200 font-bold text-gray-600" x-text="String.fromCharCode(65 + index)"></div>
                                                <input type="text" :name="'options['+index+'][text]'" class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-corporate-primary focus:ring-corporate-primary" placeholder="{{ __('Texto de la opción...') }}">
                                                <label class="flex items-center cursor-pointer">
                                                    <input type="radio" name="correct_radio" :value="index" @click="setCorrect(index)" :checked="options[index].is_correct" class="text-corporate-primary focus:ring-corporate-primary">
                                                    <input type="hidden" :name="'options['+index+'][is_correct]'" :value="options[index].is_correct ? 1 : 0">
                                                    <span class="ml-2 text-xs font-bold text-gray-500 uppercase">{{ __('Correcta') }} </span>
                                                </label>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>

                            <!-- Fill Blank Field -->
                            <template x-if="questionType === 'fill_blank'">
                                <div class="mb-6 bg-blue-50 p-6 rounded-lg border border-blue-200">
                                    <label class="block text-sm font-bold text-blue-700 mb-2 uppercase">{{ __('Palabra o frase exacta para el hueco') }} </label>
                                    <input type="text" name="fill_blank_text" class="mt-1 block w-full rounded-md border-blue-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="{{ __('Ej: Constitución') }}">
                                    <p class="mt-2 text-xs text-blue-600 italic">{{ __('Nota: El sistema ignorará mayúsculas/minúsculas y espacios extra al corregir.') }} </p>
                                </div>
                            </template>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">{{ __('Explicación de la Respuesta (Opcional)') }} </label>
                                <textarea name="explanation" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-corporate-primary focus:ring-corporate-primary" placeholder="{{ __('Por qué esta respuesta es la correcta...') }}">{{ old('explanation') }}</textarea>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-corporate-primary text-base font-medium text-white hover:bg-orange-600 sm:ml-3 sm:w-auto sm:text-sm">{{ __('Guardar Pregunta') }} </button>
                            <button type="button" @click="questionModalOpen = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">{{ __('Cancelar') }} </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const container = document.getElementById('questions-container');
            if (container) {
                new Sortable(container, {
                    animation: 150,
                    handle: '.cursor-move',
                    onEnd: function (evt) {
                        const items = evt.to.children;
                        const ids = Array.from(items).map(item => item.getAttribute('data-id')).filter(id => id);

                        fetch('{{ route("admin.preguntas.reordenar") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ ids: ids })
                        });
                    }
                });
            }
        });
    </script>
</x-app-layout>
