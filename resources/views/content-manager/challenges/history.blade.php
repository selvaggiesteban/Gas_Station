<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Historial de Impugnaciones') }} </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-medium">{{ __('Impugnaciones Revisadas') }} </h3>
                        <a href="{{ route('content-manager.challenges.index') }}" class="text-orange-600 hover:text-orange-800">{{ __('Ver pendientes �??') }} </a>
                    </div>

                    @if($challenges->isEmpty())<p class="text-gray-500 text-center py-8">{{ __('No hay impugnaciones revisadas.') }} </p>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Estudiante') }} </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Decisión') }} </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Revisado por') }} </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Fecha') }} </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Acciones') }} </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($challenges as $challenge)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">#{{ $challenge->id }} </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $challenge->user->name }} </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 py-1 text-xs rounded-full {{ $challenge->status === 'approved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                    {{ $challenge->status === 'approved' ? 'Aprobada' : 'Rechazada' }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $challenge->reviewer->name ?? 'N/A' }} </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $challenge->reviewed_at->format('d/m/Y') }} </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <a href="{{ route('content-manager.challenges.show', $challenge) }}" class="text-corporate-primary hover:text-orange-600">{{ __('Ver') }} </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            {{ $challenges->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
