<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Gestión de Cronograma Oficial') }} </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-medium">{{ __('Eventos del Cronograma') }} </h3>
                <a href="{{ route('content-manager.schedules.create') }}" class="inline-flex items-center px-4 py-2 bg-corporate-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-600">{{ __('+ Crear Evento') }} </a>
            </div>

            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if($officialSchedules->isEmpty())<p class="text-gray-500 text-center py-8">{{ __('No hay eventos oficiales programados.') }} </p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Evento') }} </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Inicio') }} </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Fin') }} </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Sesiones de Estudio') }} </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Acciones') }} </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($officialSchedules as $schedule)
                                        <tr>
                                            <td class="px-6 py-4">
                                                <div class="text-sm font-medium text-gray-900">{{ $schedule->title }} </div>
                                                <div class="text-xs text-gray-500 line-clamp-1">{{ $schedule->description }} </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $schedule->start_time->format('d/m/Y H:i') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $schedule->end_time->format('d/m/Y H:i') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $schedule->study_sessions_count }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex gap-3">
                                                    <a href="{{ route('content-manager.schedules.edit', $schedule) }}" class="text-corporate-primary hover:text-orange-600">{{ __('Editar') }} </a>
                                                    
                                                    <form action="{{ route('content-manager.cronogramas.sync', $schedule) }}" method="POST" class="inline">
                                                        @csrf
                                                        <button type="submit" class="text-blue-600 hover:text-blue-900" :title="__('Sincronizar a todos los alumnos')">{{ __('Sincronizar') }} </button>
                                                    </form>

                                                    <form action="{{ route('content-manager.schedules.destroy', $schedule) }}" method="POST" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('{{ __('¿Eliminar este evento?') }}')">{{ __('Eliminar') }} </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $officialSchedules->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>