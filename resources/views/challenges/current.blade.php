<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Header --}}
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-corporate-secondary">{{ __('Reto Semanal') }} </h1>
            <p class="text-gray-600 mt-2">{{ __('Compite con otros estudiantes y sube en el ranking') }} </p>
        </div>

        @if(isset($challenge) && $challenge)
            {{-- Current Challenge Card --}}
            <div class="bg-gradient-to-br from-corporate-primary to-orange-600 rounded-3xl p-8 text-white mb-8 shadow-xl">
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <span class="bg-white/20 px-3 py-1 rounded-full text-sm font-medium">Activo hasta {{ $challenge->ends_at?->format('d/m/Y') }} </span>
                            <span class="bg-white/20 px-3 py-1 rounded-full text-sm font-medium">{{ $challenge->participants_count ?? 0 }} participantes</span>
                        </div>
                        <h2 class="text-3xl font-bold mb-2">{{ $challenge->title }} </h2>
                        <p class="text-white/90 text-lg">{{ $challenge->description }} </p>
                    </div>
                    <div class="text-center bg-white/10 backdrop-blur-sm rounded-2xl p-6">
                        <p class="text-sm text-white/80 mb-1">{{ __('Tu posición') }} </p>
                        @if(isset($userRanking))
                            <p class="text-5xl font-bold">#{{ $userRanking->position ?? '-' }} </p>
                            <p class="text-sm text-white/80 mt-1">{{ $userRanking->score ?? 0 }} puntos</p>
                        @else
                            <p class="text-xl">{{ __('Aún no participas') }} </p>
                            <a href="{{ route('retos.actual') }}" class="inline-block mt-2 bg-white text-corporate-primary px-4 py-2 rounded-lg font-bold hover:bg-gray-100 transition">{{ __('¡Participar!') }} </a>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Rules --}}
            <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
                <h3 class="text-xl font-bold text-corporate-secondary mb-4">{{ __('📋 Reglas del Reto') }} </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 bg-corporate-primary/10 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-corporate-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium text-corporate-secondary">{{ __('Completa tests') }} </p>
                            <p class="text-sm text-gray-600">{{ __('+10 puntos por test completado') }} </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 bg-corporate-primary/10 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-corporate-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium text-corporate-secondary">{{ __('Racha de estudio') }} </p>
                            <p class="text-sm text-gray-600">{{ __('+5 puntos por día consecutivo') }} </p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 bg-corporate-primary/10 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-5 h-5 text-corporate-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-medium text-corporate-secondary">{{ __('Precisión') }} </p>
                            <p class="text-sm text-gray-600">{{ __('Bonus por +90% aciertos') }} </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Top Rankings --}}
            <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="text-xl font-bold text-corporate-secondary">{{ __('🏆 Clasificación') }} </h3>
                </div>

                @if(isset($rankings) && $rankings->count() > 0)
                    <div class="divide-y divide-gray-100">
                        @foreach($rankings as $index => $ranking)<div class="px-6 py-4 flex items-center gap-4 {{ $ranking->user_id == Auth::id() ? 'bg-corporate-primary/5' : '' }}">
                                <div class="w-8 text-center font-bold text-lg {{ $index < 3 ? 'text-corporate-primary' : 'text-gray-400' }}">
                                    {{ $index + 1 }}
                                </div>

                                <div class="w-10 h-10 bg-corporate-secondary rounded-full flex items-center justify-center text-white font-bold">
                                    {{ substr($ranking->user->name, 0, 1) }}
                                </div>

                                <div class="flex-grow">
                                    <p class="font-medium text-corporate-secondary">{{ $ranking->user->name }} </p>
                                    <p class="text-sm text-gray-500">{{ $ranking->completed_tests ?? 0 }} tests completados</p>
                                </div>

                                <div class="text-right">
                                    <p class="text-2xl font-bold text-corporate-primary">{{ $ranking->score }} </p>
                                    <p class="text-xs text-gray-500">{{ __('puntos') }} </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-8 text-center text-gray-500">
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p>{{ __('Aún no hay participantes en este reto.') }} </p>
                        <p class="text-sm mt-2">{{ __('¡Sé el primero en participar!') }} </p>
                    </div>
                @endif
            </div>
        @else
            {{-- No Active Challenge --}}
            <div class="bg-white rounded-2xl shadow-lg p-12 text-center">
                <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-corporate-secondary mb-2">{{ __('No hay reto activo') }} </h2>
                <p class="text-gray-600">{{ __('Los retos semanales se activan cada lunes. Vuelve pronto para participar.') }} </p>
            </div>
        @endif
    </div>
</x-app-layout>
