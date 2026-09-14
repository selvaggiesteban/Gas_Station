<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Clasificación') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-xl font-bold text-gray-900">{{ __('Mejores Alumnos') }} </h3>
                        <p class="text-sm text-gray-500">{{ __('Top 10 Global') }} </p>
                    </div>
                    
                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200 bg-white">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="py-3 px-6 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Posición') }} </th>
                                    <th scope="col" class="py-3 px-6 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Alumno') }} </th>
                                    <th scope="col" class="py-3 px-6 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Nivel') }} </th>
                                    <th scope="col" class="py-3 px-6 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Puntos') }} </th>
                                    <th scope="col" class="py-3 px-6 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Batallas Ganadas') }} </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($leaders as $index => $user)<tr class="hover:bg-gray-50 transition-colors {{ Auth::id() === $user->id ? 'bg-corporate-primary/5' : '' }}">
                                        <td class="py-4 px-6 whitespace-nowrap">
                                            @if($index === 0)
                                                <div class="flex items-center justify-center w-8 h-8 rounded-full bg-yellow-100 text-yellow-600 font-bold">1</div>
                                            @elseif($index === 1)
                                                <div class="flex items-center justify-center w-8 h-8 rounded-full bg-gray-200 text-gray-600 font-bold">2</div>
                                            @elseif($index === 2)
                                                <div class="flex items-center justify-center w-8 h-8 rounded-full bg-orange-200 text-orange-700 font-bold">3</div>
                                            @else
                                                <div class="flex items-center justify-center w-8 h-8 text-gray-500 font-bold">{{ $index + 1 }} </div>
                                            @endif
                                        </td>
                                        <td class="py-4 px-6 whitespace-nowrap font-medium text-gray-900">
                                            <div class="flex items-center">
                                                {{ $user->name }}
                                                @if(Auth::id() === $user->id)<span class="ml-2 px-2 py-0.5 text-xs bg-corporate-primary text-white font-bold rounded-full">Tú</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="py-4 px-6 whitespace-nowrap">
                                            @php
                                                $rankName = $user->getRankName();
                                                $rankColorClass = match($rankName) {
                                                    'Diamante' => 'bg-orange-100 text-orange-800',
                                                    'Oro' => 'bg-yellow-100 text-yellow-800',
                                                    'Plata' => 'bg-gray-200 text-gray-800',
                                                    'Bronce' => 'bg-orange-200 text-orange-800',
                                                    default => 'bg-orange-100 text-orange-800' // Cobre
                                                };
                                            @endphp
                                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $rankColorClass }}">
                                                {{ $rankName }}
                                            </span>
                                        </td>
                                        <td class="py-4 px-6 whitespace-nowrap text-gray-700 font-bold">
                                            {{ number_format($user->points ?? 0, 0, ',', '.') }} pts
                                        </td>
                                        <td class="py-4 px-6 whitespace-nowrap text-gray-700">
                                            {{ $user->battles_won ?? 0 }}
                                        </td>
                                    </tr>
                                @endforeach
                                
                                @if($leaders->isEmpty())<tr>
                                        <td colspan="5" class="py-8 px-6 text-center text-gray-500">{{ __('Aún no hay alumnos en la clasificación.') }} </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
