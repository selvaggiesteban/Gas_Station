<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Videoclases y Sesiones en Vivo') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-10">

            <!-- Banner / Introducción -->
            <div class="bg-corporate-primary rounded-xl shadow-lg overflow-hidden flex flex-col md:flex-row items-center p-8 text-white">
                <div class="md:w-2/3 pr-8">
                    <h3 class="text-3xl font-bold mb-4">{{ __('Aulas Virtuales Interactivas') }}</h3>
                    <p class="text-orange-50 mb-6">{{ __('Únete a nuestras sesiones en vivo dirigidas por profesionales de Instituciones Penitenciarias. Participa, haz preguntas y repasa conceptos clave del temario oficial.') }}</p>
                </div>
                <div class="md:w-1/3 flex justify-center mt-6 md:mt-0">
                    <div class="bg-white/20 p-6 rounded-full backdrop-blur-sm">
                        <svg class="w-16 h-16 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            @php
                $upcoming = $meetings->filter(fn($m) => $m->status === 'scheduled' && $m->start_time->isFuture());
                $past = $meetings->filter(fn($m) => $m->status === 'finished' || $m->hasPassed());
            @endphp

            <!-- PRÓXIMAS CLASES EN VIVO -->
            <div>
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-2xl font-bold text-gray-900 flex items-center">
                        <span class="relative flex h-4 w-4 mr-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-4 w-4 bg-red-500"></span>
                        </span>{{ __('Próximas Sesiones Programadas') }}
                    </h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @forelse($upcoming as $meeting)
                        @php
                            $isLive = $meeting->isLive() || $meeting->isUpcoming();
                            $topicName = $meeting->topic->name ?? 'General';
                            $courseName = $meeting->topic->course->title ?? '';
                        @endphp
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md transition">
                            <div class="h-2 {{ $isLive ? 'bg-red-500' : 'bg-corporate-primary' }} w-full"></div>
                            <div class="p-6">
                                <div class="flex justify-between items-start mb-4">
                                    @if($isLive)
                                        <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full font-bold uppercase tracking-wide">{{ $topicName }}</span>
                                    @else
                                        <span class="bg-orange-100 text-corporate-primary text-xs px-2 py-1 rounded-full font-bold uppercase tracking-wide">{{ $topicName }}</span>
                                    @endif
                                    @if($courseName)
                                        <span class="text-sm font-medium text-gray-500">{{ $courseName }}</span>
                                    @endif
                                </div>
                                <h4 class="text-lg font-bold text-gray-900 mb-2">{{ $meeting->title }}</h4>

                                <div class="space-y-3 mt-4 text-sm text-gray-600">
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 mr-2 text-corporate-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        <span class="font-medium">{{ $meeting->formatted_start_time }}</span>
                                    </div>
                                    <div class="flex items-center">
                                        <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        {{ __('Duración est.:') }} {{ $meeting->duration_minutes }} min
                                    </div>
                                    @if($meeting->creator)
                                        <div class="flex items-center">
                                            <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                            {{ $meeting->creator->name }}
                                        </div>
                                    @endif
                                </div>

                                <div class="mt-6">
                                    @if($isLive)
                                        <a href="{{ $meeting->join_url }}" target="_blank" class="w-full inline-flex justify-center items-center px-4 py-3 bg-red-600 border border-transparent rounded-lg font-bold text-sm text-white hover:bg-red-700 transition-colors shadow-sm">{{ __('Entrar al Aula Virtual') }}</a>
                                        <p class="text-center text-xs text-gray-500 mt-2">{{ __('La sesión está activa.') }}</p>
                                    @else
                                        <a href="{{ $meeting->join_url }}" target="_blank" class="w-full inline-flex justify-center items-center px-4 py-3 bg-corporate-primary border border-transparent rounded-lg font-bold text-sm text-white hover:bg-orange-600 transition-colors shadow-sm">{{ __('Acceder a la clase') }}</a>
                                        <p class="text-center text-xs text-gray-500 mt-2">{{ __('La sala abrirá 10 minutos antes.') }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-full text-center py-16 bg-white rounded-lg border border-dashed border-gray-300 text-gray-500">
                            <svg class="mx-auto h-12 w-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                            <p class="text-xl font-medium">{{ __('No hay sesiones programadas próximamente.') }}</p>
                            <p class="mt-2 text-sm">{{ __('Vuelve pronto o revisa la videoteca más abajo.') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- CLASES ANTERIORES / GRABACIONES -->
            <div class="mt-12">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-2xl font-bold text-gray-900 flex items-center">
                        <svg class="w-6 h-6 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>{{ __('Videoteca (Clases Pasadas)') }}
                    </h3>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <ul class="divide-y divide-gray-100">
                        @forelse($past as $meeting)
                            <li class="p-6 hover:bg-gray-50 transition flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
                                <div class="flex items-start gap-4 flex-1">
                                    <div class="bg-gray-100 p-3 rounded-lg text-gray-500">
                                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                    </div>
                                    <div>
                                        <h4 class="text-lg font-bold text-gray-900">{{ $meeting->title }}</h4>
                                        <p class="text-sm text-gray-500 mt-1">
                                            {{ optional($meeting->topic)->name ?? 'General' }}
                                            &bull; {{ __('Emitido el') }} {{ $meeting->start_time->format('d/m/Y') }}
                                        </p>
                                        <div class="flex items-center mt-2 text-xs font-medium text-gray-400 gap-4">
                                            <span class="flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                {{ $meeting->duration_minutes }} min
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                @if($meeting->recording_url)
                                    <a href="{{ $meeting->recording_url }}" target="_blank" class="shrink-0 inline-flex items-center px-4 py-2 bg-white border-2 border-corporate-primary text-corporate-primary rounded-lg font-bold text-sm hover:bg-orange-50 transition">
                                        <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path></svg>{{ __('Ver Grabación') }}
                                    </a>
                                @else
                                    <span class="shrink-0 inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-200 rounded-lg text-sm text-gray-400">{{ __('Sin grabación disponible') }}</span>
                                @endif
                            </li>
                        @empty
                            <li class="p-12 text-center text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                <p class="text-lg font-medium">{{ __('No hay clases grabadas aún.') }}</p>
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <div class="mt-6">
                {{ $meetings->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
