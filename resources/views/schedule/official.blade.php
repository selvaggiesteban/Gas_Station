<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-red-800 leading-tight">
                {{ __('Cronograma Oficial de la Academia') }}
            </h2>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">{{ __('Solo lectura') }} </span>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-6">
                        <p class="text-gray-600">{{ __('Este es el calendario oficial establecido por la academia. Los eventos son de carácter obligatorio y no pueden ser modificados.') }} </p>
                    </div>

                    @if($officialSchedules->isEmpty())<div class="text-center py-12 text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            <p class="mt-4 text-sm">{{ __('No hay eventos oficiales programados actualmente.') }} </p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-red-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-red-800 uppercase tracking-wider">{{ __('Evento') }} </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-red-800 uppercase tracking-wider">{{ __('Fecha inicio') }} </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-red-800 uppercase tracking-wider">{{ __('Fecha fin') }} </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-red-800 uppercase tracking-wider">{{ __('Descripción') }} </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($officialSchedules as $schedule)
                                        <tr class="hover:bg-red-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-8 w-8 flex items-center justify-center bg-red-100 rounded-full">
                                                        <svg class="h-4 w-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                                        </svg>
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900">{{ $schedule->title }} </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $schedule->start_time->format('d/m/Y H:i') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                {{ $schedule->end_time->format('d/m/Y H:i') }}
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-500">
                                                {{ $schedule->description ?? 'Sin descripción' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    <div class="mt-6 flex gap-4">
                        <a href="{{ route('cronograma.personal') }}" class="inline-flex items-center text-gray-600 hover:text-gray-800">{{ __('Volver a Cronogramas') }} </a>
                        <a href="{{ route('cronograma.personal') }}" class="inline-flex items-center text-corporate-primary hover:text-orange-600">{{ __('Ver mi cronograma personal') }} </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
