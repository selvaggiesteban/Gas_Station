<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Gestión de Anuncios') }} </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-medium">{{ __('Anuncios Publicados') }} </h3>
                <a href="{{ route('content-manager.announcements.create') }}" class="inline-flex items-center px-4 py-2 bg-corporate-primary border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-orange-600">{{ __('+ Crear Anuncio') }} </a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if($announcements->isEmpty())<p class="text-gray-500 text-center py-8">{{ __('No hay anuncios publicados.') }} </p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Título') }} </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Tipo') }} </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Estado') }} </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Publicado') }} </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Acciones') }} </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($announcements as $announcement)
                                        <tr>
                                            <td class="px-6 py-4">
                                                <div class="text-sm font-medium text-gray-900">{{ $announcement->title }} </div>
                                                <div class="text-xs text-gray-500">Por: {{ $announcement->publisher->name ?? 'Sistema' }} </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                @php
                                                    $typeClasses = [
                                                        'info' => 'bg-blue-100 text-blue-800',
                                                        'warning' => 'bg-yellow-100 text-yellow-800',
                                                        'urgent' => 'bg-red-100 text-red-800',
                                                    ];
                                                    $typeLabels = [
                                                        'info' => 'Info',
                                                        'warning' => 'Aviso',
                                                        'urgent' => 'Urgente'
                                                    ];
                                                @endphp<span class="px-2 py-1 text-xs rounded-full {{ $typeClasses[$announcement->type] ?? 'bg-gray-100 text-gray-800' }}">
                                                    {{ $typeLabels[$announcement->type] ?? $announcement->type }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 py-1 text-xs rounded-full {{ $announcement->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                                    {{ $announcement->is_active ? 'Activo' : 'Inactivo' }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $announcement->published_at?->format('d/m/Y') ?? 'No publicado' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <a href="{{ route('content-manager.announcements.edit', $announcement) }}" class="text-corporate-primary hover:text-orange-600 mr-3">{{ __('Editar') }} </a>
                                                <form action="{{ route('content-manager.announcements.destroy', $announcement) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-red-600 hover:text-red-900" onclick="return confirm('{{ __('¿Eliminar este anuncio?') }}')">{{ __('Eliminar') }} </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $announcements->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
