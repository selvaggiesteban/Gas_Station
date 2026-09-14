<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Cronogramas') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Cronograma Oficial de la Academia --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-red-700">{{ __('Cronograma Oficial de la Academia') }} </h3>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">{{ __('Solo lectura') }} </span>
                        </div>
                        <p class="text-sm text-gray-500 mb-4">{{ __('Calendario establecido por la academia. No editable.') }} </p>

                        <ul class="divide-y divide-gray-200">
                            @forelse($officialSchedules as $schedule)
                                <li class="py-3 bg-red-50 p-2 rounded mb-2">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                            </svg>
                                            <span class="text-sm font-medium">{{ $schedule->title }} </span>
                                        </div>
                                        <span class="text-xs text-gray-500">{{ $schedule->start_time->format('d/m H:i') }} </span>
                                    </div>
                                </li>
                            @empty
                                <li class="py-4 text-center text-gray-500 text-sm">{{ __('No hay eventos oficiales programados') }} </li>
                            @endforelse
                        </ul>

                        <a href="{{ route('cronograma.oficial') }}" class="mt-4 inline-flex items-center text-sm text-red-600 hover:text-red-800">{{ __('Ver cronograma completo') }} </a>
                    </div>
                </div>

                {{-- Mi Cronograma Personal --}}
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-corporate-primary">{{ __('Mi Cronograma Personal') }} </h3>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-corporate-primary text-corporate-primary">{{ __('Editable') }} </span>
                        </div>
                        <p class="text-sm text-gray-500 mb-4">{{ __('Organiza tu estudio personal. Muestra tus próximos eventos.') }} </p>

                        <ul class="divide-y divide-gray-200" id="personal-schedule-preview">
                            @forelse($personalSchedules->take(5) as $schedule)<li class="py-3 flex items-center justify-between hover:bg-gray-50 p-2 rounded">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-4 h-4 text-corporate-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        <div>
                                            <span class="text-sm font-medium block">{{ $schedule->title }} </span>
                                            <span class="text-xs text-gray-500">{{ $schedule->start_time->format('d/m H:i') }} </span>
                                        </div>
                                    </div>
                                    @if($schedule->is_completed)<span class="text-green-500 text-xs">{{ __('✓ Completado') }} </span>
                                    @endif
                                </li>
                            @empty
                                <li class="py-4 text-center text-gray-500 text-sm">{{ __('No tienes eventos personales') }} </li>
                            @endforelse
                        </ul>

                        <div class="mt-4 flex gap-2">
                            <a href="{{ route('cronograma.personal') }}" class="inline-flex items-center text-sm text-corporate-primary hover:text-orange-600">{{ __('Gestionar mi cronograma') }} </a>
                        </div>

                        @if($personalSchedules->isNotEmpty())<div class="mt-4 pt-4 border-t">
                                <a href="{{ route('cronograma.estudiar', $personalSchedules->first()) }}" class="w-full inline-flex justify-center items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-500 focus:bg-green-500 active:bg-green-700 focus:outline-none transition ease-in-out duration-150">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>{{ __('Iniciar Sesión de Estudio (Pomodoro)') }} </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
