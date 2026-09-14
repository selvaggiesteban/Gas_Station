<x-app-layout>
    <!-- Alpine.js for Modals -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

    <div class="container mx-auto px-4 py-8" x-data="{
        blockModalOpen: false,
        topicModalOpen: false,
        testModalOpen: false,
        currentBlockId: null
    }">
        <div class="mb-8 flex items-center justify-between">
            <div>
                <a href="{{ route('admin.cursos.index') }}" class="text-sm text-gray-500 hover:text-gray-900 mb-2 inline-block">{{ __('← Volver a Cursos') }} </a>
                <h1 class="text-3xl font-bold text-gray-900">Estructura: {{ $course->title }} </h1>
            </div>
            <button @click="blockModalOpen = true" class="bg-corporate-primary text-white px-4 py-2 rounded-lg font-bold hover:bg-orange-600 transition">{{ __('+ Nuevo Bloque') }} </button>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                {{ session('success') }}
            </div>
        @endif
        
        @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="mb-6 border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <button class="border-corporate-primary text-corporate-primary whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">{{ __('Estructura de Temas') }} </button>
                <a href="#tests-section" class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">{{ __('Tests y Simulacros') }} </a>
            </nav>
        </div>

        <!-- Listado de Bloques (Sortable) -->
        <div id="blocks-container" class="space-y-6">
            @forelse($course->blocks as $block)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden block-item" data-id="{{ $block->id }}">
                    <!-- Block Header -->
                    <div class="bg-gray-50 px-6 py-4 flex justify-between items-center cursor-move border-b border-gray-200">
                        <div class="flex items-center">
                            <svg class="h-5 w-5 text-gray-400 mr-3 cursor-move" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                            <h3 class="text-lg font-bold text-gray-900">{{ $block->title }} </h3>
                        </div>
                        <div class="flex space-x-3">
                            <button @click="currentBlockId = {{ $block->id }}; topicModalOpen = true" class="text-sm text-corporate-primary font-medium hover:underline">{{ __('+ Añadir Tema') }} </button>
                            <form action="{{ route('admin.bloques.destroy', $block) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('¿Eliminar bloque y todos sus temas?') }}');">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-sm text-red-600 font-medium hover:underline">{{ __('Eliminar') }} </button>
                            </form>
                        </div>
                    </div>

                    <!-- Topics List (Sortable) -->
                    <ul class="topics-list min-h-[50px] p-4 bg-white" data-block-id="{{ $block->id }}">
                        @forelse($block->topics as $topic)
                            <li class="p-3 mb-2 bg-gray-50 border border-gray-200 rounded flex justify-between items-center topic-item" data-id="{{ $topic->id }}"> 
                                <div class="flex items-center">
                                    <svg class="h-4 w-4 text-gray-400 mr-2 cursor-move" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16" />
                                    </svg>
                                    <span class="font-medium text-gray-800">{{ $topic->title }} </span>
                                    @if($topic->available_at)
                                        <span class="ml-3 text-xs text-blue-600 bg-blue-100 px-2 py-0.5 rounded">Disponible: {{ \Carbon\Carbon::parse($topic->available_at)->format('d/m/Y') }} </span>
                                    @endif
                                </div>
                                <div class="flex items-center space-x-4">
                                    <a href="{{ route('admin.temas.edit', $topic) }}" class="text-blue-600 hover:text-blue-900 text-xs font-bold uppercase">{{ __('Editar Detalles / Archivos') }} </a>

                                    <form action="{{ route('admin.temas.destroy', $topic) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('¿Eliminar tema?') }}');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700" title="{{ __('Eliminar Tema') }}">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </form>
                                </div>
                            </li>
                        @empty
                            <li class="text-sm text-gray-500 italic p-2 text-center">{{ __('No hay temas en este bloque.') }} </li>
                        @endforelse
                    </ul>
                </div>
            @empty
                <div class="text-center py-12 bg-white rounded-lg shadow-sm border border-gray-200">
                    <p class="text-gray-500 mb-4">{{ __('Este curso aún no tiene bloques.') }} </p>
                    <button @click="blockModalOpen = true" class="text-corporate-primary font-bold hover:underline">{{ __('Crear el primer bloque') }} </button>
                </div>
            @endforelse
        </div>

        <!-- Create Block Modal -->
        <div x-show="blockModalOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true" @click="blockModalOpen = false">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form action="{{ route('admin.cursos.bloques.store', $course) }}" method="POST">
                        @csrf
                        <input type="hidden" name="course_id" value="{{ $course->id }}">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">{{ __('Nuevo Bloque') }} </h3>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">{{ __('Título') }} </label>
                                <input type="text" name="title" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-corporate-primary focus:ring-corporate-primary">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">{{ __('Descripción (Opcional)') }} </label>
                                <textarea name="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-corporate-primary focus:ring-corporate-primary"></textarea>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-corporate-primary text-base font-medium text-white hover:bg-orange-600 sm:ml-3 sm:w-auto sm:text-sm">{{ __('Guardar') }} </button>
                            <button type="button" @click="blockModalOpen = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">{{ __('Cancelar') }} </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Create Topic Modal -->
        <div x-show="topicModalOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true" @click="topicModalOpen = false">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form x-bind:action="`/admin/bloques/${currentBlockId}/temas`" method="POST">
                        @csrf
                        <input type="hidden" name="block_id" :value="currentBlockId">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">{{ __('Nuevo Tema') }} </h3>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">{{ __('Título') }} </label>
                                <input type="text" name="title" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-corporate-primary focus:ring-corporate-primary">
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">{{ __('Fecha de Disponibilidad (Opcional)') }} </label>
                                <input type="date" name="available_at" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-corporate-primary focus:ring-corporate-primary">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">{{ __('Contenido (HTML Opcional)') }} </label>
                                <textarea name="content" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-corporate-primary focus:ring-corporate-primary"></textarea>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-corporate-primary text-base font-medium text-white hover:bg-orange-600 sm:ml-3 sm:w-auto sm:text-sm">{{ __('Guardar') }} </button>
                            <button type="button" @click="topicModalOpen = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">{{ __('Cancelar') }} </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Tests Section -->
        <div id="tests-section" class="mt-12 pt-8 border-t border-gray-200">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900">{{ __('Tests y Simulacros') }} </h2>
                <button @click="testModalOpen = true" class="bg-gray-800 text-white px-4 py-2 rounded-lg font-bold hover:bg-gray-700 transition">{{ __('+ Nuevo Test') }} </button>
            </div>

            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Título') }} </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Tipo') }} </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Límite Tiempo') }} </th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Acciones') }} </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($course->tests ?? [] as $test)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">{{ $test->title }} </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ ucfirst(str_replace('_', ' ', $test->type)) }} </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $test->duration_minutes }} min</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('admin.examenes.show', $test) }}" class="text-corporate-primary hover:underline mr-3">{{ __('Gestionar Preguntas') }} </a>
                                    <form action="{{ route('admin.examenes.destroy', $test) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('¿Eliminar test?') }}');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:underline">{{ __('Eliminar') }} </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-gray-500 italic">{{ __('No hay tests creados para este curso.') }} </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Create Test Modal -->
        <div x-show="testModalOpen" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity" aria-hidden="true" @click="testModalOpen = false">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form action="{{ route('admin.cursos.examenes.store', $course) }}" method="POST">
                        @csrf
                        <input type="hidden" name="course_id" value="{{ $course->id }}">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">{{ __('Nuevo Test / Simulacro') }} </h3>
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700">{{ __('Título') }} </label>
                                <input type="text" name="title" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-corporate-primary focus:ring-corporate-primary">
                            </div>
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">{{ __('Tipo') }} </label>
                                    <select name="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-corporate-primary focus:ring-corporate-primary">
                                        <option value="simulator">{{ __('Simulacro Real') }} </option>
                                        <option value="topic_test">{{ __('Test de Tema') }} </option>
                                        <option value="level_test">{{ __('Test de Nivel') }} </option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">{{ __('Tiempo (Minutos)') }} </label>
                                    <input type="number" name="duration_minutes" value="60" min="0" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-corporate-primary focus:ring-corporate-primary">
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">{{ __('Límite Intentos') }} </label>
                                    <input type="number" name="attempt_limit" placeholder="{{ __('Vacío = Ilimitados') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-corporate-primary focus:ring-corporate-primary">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">{{ __('Nota de Corte (%)') }} </label>
                                    <input type="number" name="passing_score" value="50" min="0" max="100" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-corporate-primary focus:ring-corporate-primary">
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-corporate-primary text-base font-medium text-white hover:bg-orange-600 sm:ml-3 sm:w-auto sm:text-sm">{{ __('Crear Test') }} </button>
                            <button type="button" @click="testModalOpen = false" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">{{ __('Cancelar') }} </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Drag and Drop Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Sortable for Blocks
            const blocksContainer = document.getElementById('blocks-container');
            if (blocksContainer) {
                new Sortable(blocksContainer, {
                    animation: 150,
                    handle: '.cursor-move',
                    onEnd: function (evt) {
                        const items = evt.to.children;
                        const ids = Array.from(items).map(item => item.getAttribute('data-id')).filter(id => id);

                        fetch('{{ route("admin.bloques.reordenar") }}', {
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

            // Sortable for Topics within Blocks
            const topicLists = document.querySelectorAll('.topics-list');
            topicLists.forEach(list => {
                new Sortable(list, {
                    group: 'topics', // Allow moving between blocks
                    animation: 150,
                    handle: '.cursor-move',
                    onEnd: function (evt) {
                        const items = [];
                        document.querySelectorAll('.topics-list').forEach(blockContainer => {
                            const blockId = blockContainer.getAttribute('data-block-id');
                            Array.from(blockContainer.children).forEach((topicItem, index) => {
                                const topicId = topicItem.getAttribute('data-id');
                                if (topicId) {
                                    items.push({
                                        id: topicId,
                                        block_id: blockId,
                                        order: index + 1
                                    });
                                }
                            });
                        });

                        fetch('{{ route("admin.temas.reordenar") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ items: items })
                        });
                    }
                });
            });
        });
    </script>
</x-app-layout>
