<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Gestión de Retos Semanales') }} </h2>
    </x-slot>

    <div class="py-12" x-data="{ openModal: false, editing: false, challenge: {} }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-medium text-gray-900">{{ __('Retos Configurados') }} </h3>
                <button @click="openModal = true; editing = false; challenge = { goal_type: 'points', is_active: true }" class="inline-flex items-center px-4 py-2 bg-corporate-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-600">
                    {{ __('+ Nuevo Reto') }}
                </button>
            </div>

            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Título') }} </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Meta') }} </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Recompensa') }} </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Fechas') }} </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Estado') }} </th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">{{ __('Acciones') }} </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($challenges as $item)
                                    <tr>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-bold text-gray-900">{{ $item->title }} </div>
                                            <div class="text-xs text-gray-500">{{ Str::limit($item->description, 50) }} </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $item->goal_amount }} {{ $item->goal_type_label }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <span class="font-bold text-orange-600">+{{ $item->reward_points }} pts</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div class="text-xs">{{ $item->start_date->format('d/m/Y') }} </div>
                                            <div class="text-xs font-bold">{{ $item->end_date->format('d/m/Y') }} </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($item->is_active)
                                                <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">{{ __('Activo') }} </span>
                                            @else
                                                <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">{{ __('Inactivo') }} </span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex justify-end gap-3">
                                                <button @click="openModal = true; editing = true; challenge = {{ json_encode($item) }}" class="text-blue-600 hover:text-blue-900">{{ __('Editar') }} </button>
                                                <form action="{{ route('content-manager.desafios.destroy', $item) }}" method="POST" class="inline" onsubmit="return confirm('{{ __('¿Eliminar reto?') }}')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900">{{ __('Eliminar') }} </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div x-show="openModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="fixed inset-0 bg-gray-500 opacity-75" @click="openModal = false"></div>
                <div class="bg-white rounded-lg overflow-hidden shadow-xl transform transition-all max-w-2xl w-full mx-auto z-50 p-6">
                    <h3 class="text-lg font-bold mb-4" x-text="editing ? 'Editar Reto' : 'Nuevo Reto Semanal'"></h3>

                    <form :action="editing ? '{{ url('content-manager/desafios') }}/' + challenge.id : '{{ route('content-manager.desafios.store') }}'" method="POST">
                        @csrf
                        <template x-if="editing"><input type="hidden" name="_method" value="PUT"></template>

                        <div class="space-y-4">
                            <div>
                                <x-input-label for="title" value="Título" />
                                <input type="text" name="title" x-model="challenge.title" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-corporate-primary focus:ring-corporate-primary">
                            </div>
                            <div>
                                <x-input-label for="description" value="Descripción Estímulo" />
                                <textarea name="description" x-model="challenge.description" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-corporate-primary focus:ring-corporate-primary"></textarea>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="goal_type" value="Tipo de Meta" />
                                    <select name="goal_type" x-model="challenge.goal_type" class="w-full rounded-md border-gray-300 shadow-sm">
                                        <option value="points">Puntos</option>
                                        <option value="pomodoros">Pomodoros</option>
                                        <option value="tests_completed">Tests</option>
                                        <option value="battles_won">Batallas</option>
                                        <option value="topics_completed">Temas</option>
                                    </select>
                                </div>
                                <div>
                                    <x-input-label for="goal_amount" value="Cantidad" />
                                    <input type="number" name="goal_amount" x-model="challenge.goal_amount" required min="1" class="w-full rounded-md border-gray-300 shadow-sm">
                                </div>
                            </div>
                            <div>
                                <x-input-label for="reward_points" value="Puntos de Recompensa" />
                                <input type="number" name="reward_points" x-model="challenge.reward_points" required min="0" class="w-full rounded-md border-gray-300 shadow-sm">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="start_date" value="Fecha Inicio" />
                                    <input type="date" name="start_date" :value="challenge.start_date ? challenge.start_date.split('T')[0] : ''" required class="w-full rounded-md border-gray-300 shadow-sm">
                                </div>
                                <div>
                                    <x-input-label for="end_date" value="Fecha Fin" />
                                    <input type="date" name="end_date" :value="challenge.end_date ? challenge.end_date.split('T')[0] : ''" required class="w-full rounded-md border-gray-300 shadow-sm">
                                </div>
                            </div>
                            <label class="flex items-center">
                                <input type="checkbox" name="is_active" :checked="challenge.is_active" class="rounded border-gray-300 text-corporate-primary shadow-sm">
                                <span class="ml-2 text-sm text-gray-600">Reto Activo</span>
                            </label>
                        </div>

                        <div class="mt-6 flex justify-end gap-3">
                            <button type="button" @click="openModal = false" class="text-gray-600 hover:text-gray-800">Cancelar</button>
                            <x-primary-button x-text="editing ? 'Actualizar' : 'Crear Reto'"></x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
